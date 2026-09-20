<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\DigitalProduct;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterDataController extends Controller
{
    // ----------------------------------------------------------------
    // Helper: resolve outlet scope for the current user
    // ----------------------------------------------------------------

    /** Returns the outlet_id to scope data by (null = admin sees all). */
    private function scopedOutletId(): ?int
    {
        $user = auth()->user();
        // Admin & super-admin see all outlets unless they filter
        if ($user && $user->isAdmin()) {
            return null;
        }
        return $user?->outlet_id;
    }

    // ================================================================
    // Items (Physical Products)
    // ================================================================

    public function items(Request $request)
    {
        $user     = auth()->user();
        $outletId = $request->input('outlet_id', $user?->outlet_id);

        // Non-admin always uses their own outlet
        if ($user && !$user->isAdmin()) {
            $outletId = $user->outlet_id;
        }

        $query = Product::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('item_code', 'like', "%$search%")
                  ->orWhere('name', 'like', "%$search%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($brand = $request->input('brand')) {
            $query->where('brand', $brand);
        }

        $perPage = $request->input('per_page', 25);
        $items   = ($perPage === 'all')
            ? $query->orderBy('name')->paginate(10000)->withQueryString()
            : $query->orderBy('name')->paginate((int) $perPage)->withQueryString();

        // Attach per-outlet stock to each product in this page
        if ($outletId) {
            $productIds   = $items->pluck('id');
            $outletStocks = ProductStock::where('outlet_id', $outletId)
                ->whereIn('product_id', $productIds)
                ->pluck('stock', 'product_id');

            $items->each(function ($product) use ($outletStocks) {
                $product->outlet_stock = (float) ($outletStocks[$product->id] ?? 0);
            });

            // Total stock value for this outlet
            $totalStockValue = ProductStock::where('outlet_id', $outletId)
                ->join('products', 'products.id', '=', 'product_stocks.product_id')
                ->selectRaw('SUM(product_stocks.stock * products.hpp) as val')
                ->value('val') ?? 0;
        } else {
            // Admin without outlet filter: use global products.stock
            $items->each(fn($p) => $p->outlet_stock = (float) $p->stock);
            $totalStockValue = Product::all()->sum(fn($p) => (float) $p->stock * (float) $p->hpp);
        }

        $types                 = Category::where('type', 'physical_type')->pluck('name');
        $brands                = Category::where('type', 'physical_brand')->pluck('name');
        $allPhysicalCategories = Category::whereIn('type', ['physical_type', 'physical_brand'])
            ->orderBy('type')->orderBy('name')->get();

        return view('master.items', compact('items', 'totalStockValue', 'types', 'brands', 'allPhysicalCategories', 'outletId'));
    }

    public function storeItem(Request $request)
    {
        $data = $request->validate([
            'item_code'       => 'required|string|unique:products,item_code',
            'name'            => 'required|string',
            'type'            => 'required|string',
            'new_type'        => 'nullable|string',
            'brand'           => 'nullable|string',
            'new_brand'       => 'nullable|string',
            'stock'           => 'required|numeric|min:0',
            'min_stock'       => 'nullable|integer|min:0',
            'hpp'             => 'required|numeric|min:0',
            'retail_price'    => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'status'          => 'required|string',
        ]);

        if ($data['type'] === '__NEW__' && !empty($data['new_type'])) {
            $data['type'] = strtoupper(trim($data['new_type']));
            Category::firstOrCreate(['type' => 'physical_type', 'name' => $data['type']]);
        }

        if (($data['brand'] ?? '') === '__NEW__' && !empty($data['new_brand'])) {
            $data['brand'] = strtoupper(trim($data['new_brand']));
            Category::firstOrCreate(['type' => 'physical_brand', 'name' => $data['brand']]);
        }

        unset($data['new_type'], $data['new_brand']);

        if (empty($data['wholesale_price'])) {
            $data['wholesale_price'] = $data['retail_price'];
        }

        $user = auth()->user();
        if ($user && $user->isToko()) {
            $data['hpp']             = 0;
            $data['retail_price']    = 0;
            $data['wholesale_price'] = 0;
        }

        $initialStock = (float) $data['stock'];
        // products.stock will be updated via per-outlet sync; start at 0 globally
        // and immediately seed the per-outlet record below.
        $data['stock'] = 0;

        $product = Product::create($data);

        // Seed per-outlet stock for the user's outlet (or default outlet)
        $outletId = $user?->outlet_id
            ?? \App\Models\Outlet::where('status', 'active')->value('id');

        if ($outletId) {
            ProductStock::updateOrCreate(
                ['product_id' => $product->id, 'outlet_id' => $outletId],
                ['stock' => $initialStock, 'min_stock' => $data['min_stock'] ?? 0]
            );
            // Sync global total
            $product->syncTotalStock();
        } else {
            // No outlet configured; fall back to global stock
            $product->update(['stock' => $initialStock]);
        }

        return redirect()->route('master.items')->with('success', 'Item berhasil ditambahkan!');
    }

    public function updateItem(Request $request, Product $product)
    {
        $data = $request->validate([
            'item_code'       => ['required', 'string', Rule::unique('products', 'item_code')->ignore($product->id)],
            'name'            => 'required|string',
            'type'            => 'required|string',
            'new_type'        => 'nullable|string',
            'brand'           => 'nullable|string',
            'new_brand'       => 'nullable|string',
            'stock'           => 'required|numeric|min:0',
            'min_stock'       => 'nullable|integer|min:0',
            'hpp'             => 'required|numeric|min:0',
            'retail_price'    => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'status'          => 'required|string',
        ]);

        if ($data['type'] === '__NEW__' && !empty($data['new_type'])) {
            $data['type'] = strtoupper(trim($data['new_type']));
            Category::firstOrCreate(['type' => 'physical_type', 'name' => $data['type']]);
        }

        if (($data['brand'] ?? '') === '__NEW__' && !empty($data['new_brand'])) {
            $data['brand'] = strtoupper(trim($data['new_brand']));
            Category::firstOrCreate(['type' => 'physical_brand', 'name' => $data['brand']]);
        }

        unset($data['new_type'], $data['new_brand']);

        if (empty($data['wholesale_price'])) {
            $data['wholesale_price'] = $data['retail_price'];
        }

        $user     = auth()->user();
        $outletId = $user?->outlet_id;

        // ------------ Stock editing ------------
        // Super-admin / users with edit_stock can directly change outlet stock.
        // Others: stock field is ignored.
        if ($user && ($user->isSuperAdmin() || ($user->hasPermission('edit_stock') && !$user->isToko()))) {
            if ($outletId) {
                // Update per-outlet stock for this user's outlet
                ProductStock::updateOrCreate(
                    ['product_id' => $product->id, 'outlet_id' => $outletId],
                    ['stock' => (float) $data['stock'], 'min_stock' => (int) ($data['min_stock'] ?? 0)]
                );
                // Sync global total
                $product->syncTotalStock();
            } else {
                // Admin without outlet: legacy update global stock directly
                // (edge case; shouldn't normally happen)
            }
        }
        // Remove stock from update payload (managed via product_stocks)
        unset($data['stock'], $data['min_stock']);

        // ------------ Price editing ------------
        if ($user && $user->isToko()) {
            $data['hpp']             = $product->hpp;
            $data['retail_price']    = $product->retail_price;
            $data['wholesale_price'] = $product->wholesale_price;
        }

        $product->update($data);

        return redirect()->route('master.items')->with('success', "Item [{$product->name}] berhasil diperbarui!");
    }

    public function destroyItem(Product $product)
    {
        $name = $product->name;
        if ($product->saleItems()->exists() || $product->purchaseItems()->exists()) {
            return back()->with('error', "Item [{$name}] tidak dapat dihapus karena sudah memiliki riwayat transaksi! Ubah status menjadi 'Tidak Dijual' jika ingin menonaktifkan.");
        }

        $product->delete(); // product_stocks will cascade-delete
        return redirect()->route('master.items')->with('success', "Item [{$name}] berhasil dihapus!");
    }

    // ================================================================
    // Multi Products (Digital)
    // ================================================================

    public function multiProducts(Request $request)
    {
        $query = DigitalProduct::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('product_code', 'like', "%$search%")
                  ->orWhere('name', 'like', "%$search%");
            });
        }

        if ($trxType = $request->input('trx_type')) {
            $query->where('trx_type', $trxType);
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $perPage  = $request->input('per_page', 25);
        $products = ($perPage === 'all')
            ? $query->orderBy('trx_type')->orderBy('name')->paginate(10000)->withQueryString()
            : $query->orderBy('trx_type')->orderBy('name')->paginate((int) $perPage)->withQueryString();

        $trxTypes            = Category::where('type', 'digital_type')->pluck('name');
        $categories          = Category::where('type', 'digital_category')->pluck('name');
        $allDigitalCategories = Category::whereIn('type', ['digital_type', 'digital_category'])
            ->orderBy('type')->orderBy('name')->get();

        return view('master.multi', compact('products', 'trxTypes', 'categories', 'allDigitalCategories'));
    }

    public function storeMultiProduct(Request $request)
    {
        $data = $request->validate([
            'product_code'   => 'required|string|unique:digital_products,product_code',
            'name'           => 'required|string',
            'trx_type'       => 'required|string',
            'new_trx_type'   => 'nullable|string',
            'category'       => 'required|string',
            'new_category'   => 'nullable|string',
            'hpp'            => 'required|numeric|min:0',
            'selling_price'  => 'required|numeric|min:0',
            'status'         => 'required|string',
        ]);

        if ($data['trx_type'] === '__NEW__' && !empty($data['new_trx_type'])) {
            $data['trx_type'] = strtoupper(trim($data['new_trx_type']));
            Category::firstOrCreate(['type' => 'digital_type', 'name' => $data['trx_type']]);
        }

        if ($data['category'] === '__NEW__' && !empty($data['new_category'])) {
            $data['category'] = strtoupper(trim($data['new_category']));
            Category::firstOrCreate(['type' => 'digital_category', 'name' => $data['category']]);
        }

        unset($data['new_trx_type'], $data['new_category']);

        DigitalProduct::create($data);

        return redirect()->route('master.multi')->with('success', 'Produk Multi berhasil ditambahkan!');
    }

    public function updateMultiProduct(Request $request, DigitalProduct $digitalProduct)
    {
        $data = $request->validate([
            'product_code'  => ['required', 'string', Rule::unique('digital_products', 'product_code')->ignore($digitalProduct->id)],
            'name'          => 'required|string',
            'trx_type'      => 'required|string',
            'new_trx_type'  => 'nullable|string',
            'category'      => 'required|string',
            'new_category'  => 'nullable|string',
            'hpp'           => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'status'        => 'required|string',
        ]);

        if ($data['trx_type'] === '__NEW__' && !empty($data['new_trx_type'])) {
            $data['trx_type'] = strtoupper(trim($data['new_trx_type']));
            Category::firstOrCreate(['type' => 'digital_type', 'name' => $data['trx_type']]);
        }

        if ($data['category'] === '__NEW__' && !empty($data['new_category'])) {
            $data['category'] = strtoupper(trim($data['new_category']));
            Category::firstOrCreate(['type' => 'digital_category', 'name' => $data['category']]);
        }

        unset($data['new_trx_type'], $data['new_category']);

        $digitalProduct->update($data);

        return redirect()->route('master.multi')->with('success', "Produk Multi [{$digitalProduct->name}] berhasil diperbarui!");
    }

    public function destroyMultiProduct(DigitalProduct $digitalProduct)
    {
        $name = $digitalProduct->name;
        if ($digitalProduct->sales()->exists()) {
            return back()->with('error', "Produk [{$name}] tidak dapat dihapus karena sudah memiliki riwayat penjualan elektrik! Ubah status menjadi 'CLOSE' jika ingin menonaktifkan.");
        }

        $digitalProduct->delete();
        return redirect()->route('master.multi')->with('success', "Produk Multi [{$name}] berhasil dihapus!");
    }

    // ================================================================
    // Categories
    // ================================================================

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|in:digital_type,digital_category,physical_type,physical_brand',
            'name' => 'required|string|max:100',
        ]);

        $name = strtoupper(trim($data['name']));
        Category::firstOrCreate(['type' => $data['type'], 'name' => $name]);

        return back()->with('success', "Kategori / Jenis [{$name}] berhasil ditambahkan!");
    }

    public function updateCategory(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $oldName = $category->name;
        $newName = strtoupper(trim($data['name']));

        if ($category->type === 'physical_type') {
            Product::where('type', $oldName)->update(['type' => $newName]);
        } elseif ($category->type === 'physical_brand') {
            Product::where('brand', $oldName)->update(['brand' => $newName]);
        } elseif ($category->type === 'digital_type') {
            DigitalProduct::where('trx_type', $oldName)->update(['trx_type' => $newName]);
        } elseif ($category->type === 'digital_category') {
            DigitalProduct::where('category', $oldName)->update(['category' => $newName]);
        }

        $category->update(['name' => $newName]);

        return back()->with('success', "Kategori / Jenis [{$oldName}] berhasil diperbarui menjadi [{$newName}]!");
    }

    public function destroyCategory(Category $category)
    {
        $name = $category->name;
        $category->delete();
        return back()->with('success', "Kategori / Jenis [{$name}] berhasil dihapus!");
    }

    // ================================================================
    // Suppliers (global list; purchases are per-outlet)
    // ================================================================

    public function suppliers(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        $query   = Supplier::withCount('purchases');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%")
                  ->orWhere('bank_name', 'like', "%$search%");
            });
        }

        $suppliers = ($perPage === 'all')
            ? $query->orderBy('name')->paginate(10000)->withQueryString()
            : $query->orderBy('name')->paginate((int) $perPage)->withQueryString();

        return view('master.suppliers', compact('suppliers'));
    }

    public function storeSupplier(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string',
            'phone'          => 'nullable|string',
            'address'        => 'nullable|string',
            'bank_name'      => 'nullable|string',
            'account_number' => 'nullable|string',
            'account_name'   => 'nullable|string',
            'notes'          => 'nullable|string',
        ]);

        Supplier::create($data);
        return redirect()->route('master.suppliers')->with('success', 'Supplier berhasil ditambahkan!');
    }

    public function updateSupplier(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name'           => 'required|string',
            'phone'          => 'nullable|string',
            'address'        => 'nullable|string',
            'bank_name'      => 'nullable|string',
            'account_number' => 'nullable|string',
            'account_name'   => 'nullable|string',
            'notes'          => 'nullable|string',
        ]);

        $supplier->update($data);
        return redirect()->route('master.suppliers')->with('success', "Data supplier [{$supplier->name}] berhasil diperbarui!");
    }

    public function destroySupplier(Supplier $supplier)
    {
        $name = $supplier->name;
        if ($supplier->purchases()->exists()) {
            return back()->with('error', "Supplier [{$name}] tidak dapat dihapus karena memiliki riwayat pembelian/kulakan!");
        }

        $supplier->delete();
        return redirect()->route('master.suppliers')->with('success', "Supplier [{$name}] berhasil dihapus!");
    }

    // ================================================================
    // Customers (per-outlet)
    // ================================================================

    public function customers(Request $request)
    {
        $user     = auth()->user();
        $outletId = $this->scopedOutletId();

        // Admin can filter by outlet
        if ($user?->isAdmin() && $request->has('outlet_id')) {
            $outletId = $request->input('outlet_id') ?: null;
        }

        $perPage = $request->input('per_page', 25);
        $query   = Customer::withCount('sales');

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%")
                  ->orWhere('address', 'like', "%$search%");
            });
        }

        $customers = ($perPage === 'all')
            ? $query->orderBy('name')->paginate(10000)->withQueryString()
            : $query->orderBy('name')->paginate((int) $perPage)->withQueryString();

        $outlets = $user?->isAdmin()
            ? \App\Models\Outlet::where('status', 'active')->orderBy('name')->get()
            : collect();

        return view('master.customers', compact('customers', 'outlets', 'outletId'));
    }

    public function storeCustomer(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string',
            'phone'   => 'nullable|string',
            'address' => 'nullable|string',
            'notes'   => 'nullable|string',
            'status'  => 'required|string',
        ]);

        $user = auth()->user();
        $data['outlet_id'] = $request->input('outlet_id') ?? $user?->outlet_id;

        Customer::create($data);
        return redirect()->route('master.customers')->with('success', 'Pelanggan berhasil ditambahkan!');
    }

    public function updateCustomer(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'    => 'required|string',
            'phone'   => 'nullable|string',
            'address' => 'nullable|string',
            'notes'   => 'nullable|string',
            'status'  => 'required|string',
        ]);

        $customer->update($data);
        return redirect()->route('master.customers')->with('success', "Data pelanggan [{$customer->name}] berhasil diperbarui!");
    }

    public function destroyCustomer(Customer $customer)
    {
        $name = $customer->name;
        if (strtoupper($name) === 'UMUM') {
            return back()->with('error', 'Pelanggan default sistem [UMUM] tidak dapat dihapus!');
        }
        if ($customer->sales()->exists()) {
            return back()->with('error', "Pelanggan [{$name}] tidak dapat dihapus karena memiliki riwayat transaksi penjualan!");
        }

        $customer->delete();
        return redirect()->route('master.customers')->with('success', "Pelanggan [{$name}] berhasil dihapus!");
    }
}
