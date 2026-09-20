<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\DigitalProduct;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterDataController extends Controller
{
    /**
     * Daftar Item (Physical Products)
     */
    public function items(Request $request)
    {
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
        $items = ($perPage === 'all')
            ? $query->orderBy('name')->paginate(10000)->withQueryString()
            : $query->orderBy('name')->paginate((int)$perPage)->withQueryString();

        $totalStockValue = Product::all()->sum(fn($p) => (float)$p->stock * (float)$p->hpp);

        $types = Category::where('type', 'physical_type')->pluck('name');
        $brands = Category::where('type', 'physical_brand')->pluck('name');
        $allPhysicalCategories = Category::whereIn('type', ['physical_type', 'physical_brand'])->orderBy('type')->orderBy('name')->get();

        return view('master.items', compact('items', 'totalStockValue', 'types', 'brands', 'allPhysicalCategories'));
    }

    public function storeItem(Request $request)
    {
        $data = $request->validate([
            'item_code' => 'required|string|unique:products,item_code',
            'name' => 'required|string',
            'type' => 'required|string',
            'new_type' => 'nullable|string',
            'brand' => 'nullable|string',
            'new_brand' => 'nullable|string',
            'stock' => 'required|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'hpp' => 'required|numeric|min:0',
            'retail_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'status' => 'required|string',
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

        // Server-side: Toko/FL tidak bisa mengubah harga — abaikan nilai harga dari form
        $user = auth()->user();
        if ($user && $user->isToko()) {
            // Toko tidak bisa buat item baru — set harga ke 0, perlu diisi oleh Admin
            $data['hpp'] = 0;
            $data['retail_price'] = 0;
            $data['wholesale_price'] = 0;
        }

        Product::create($data);

        return redirect()->route('master.items')->with('success', 'Item berhasil ditambahkan!');
    }

    public function updateItem(Request $request, Product $product)
    {
        $data = $request->validate([
            'item_code' => ['required', 'string', Rule::unique('products', 'item_code')->ignore($product->id)],
            'name' => 'required|string',
            'type' => 'required|string',
            'new_type' => 'nullable|string',
            'brand' => 'nullable|string',
            'new_brand' => 'nullable|string',
            'stock' => 'required|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'hpp' => 'required|numeric|min:0',
            'retail_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'status' => 'required|string',
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

        // Hak akses edit stok: Jika role toko atau tidak memiliki permission edit_stock, abaikan perubahan angka stok fisik
        $user = auth()->user();
        if ($user && (!$user->isSuperAdmin() && (!$user->hasPermission('edit_stock') || $user->isToko()))) {
            $data['stock'] = $product->stock;
        }

        // Hak akses edit harga: Toko/FL tidak bisa mengubah HPP dan harga jual
        if ($user && $user->isToko()) {
            $data['hpp'] = $product->hpp;
            $data['retail_price'] = $product->retail_price;
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

        $product->delete();
        return redirect()->route('master.items')->with('success', "Item [{$name}] berhasil dihapus!");
    }

    /**
     * Produk Multi (Digital Products)
     */
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

        $perPage = $request->input('per_page', 25);
        $products = ($perPage === 'all')
            ? $query->orderBy('trx_type')->orderBy('name')->paginate(10000)->withQueryString()
            : $query->orderBy('trx_type')->orderBy('name')->paginate((int)$perPage)->withQueryString();

        $trxTypes = Category::where('type', 'digital_type')->pluck('name');
        $categories = Category::where('type', 'digital_category')->pluck('name');
        $allDigitalCategories = Category::whereIn('type', ['digital_type', 'digital_category'])->orderBy('type')->orderBy('name')->get();

        return view('master.multi', compact('products', 'trxTypes', 'categories', 'allDigitalCategories'));
    }

    public function storeMultiProduct(Request $request)
    {
        $data = $request->validate([
            'product_code' => 'required|string|unique:digital_products,product_code',
            'name' => 'required|string',
            'trx_type' => 'required|string',
            'new_trx_type' => 'nullable|string',
            'category' => 'required|string',
            'new_category' => 'nullable|string',
            'hpp' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'status' => 'required|string',
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
            'product_code' => ['required', 'string', Rule::unique('digital_products', 'product_code')->ignore($digitalProduct->id)],
            'name' => 'required|string',
            'trx_type' => 'required|string',
            'new_trx_type' => 'nullable|string',
            'category' => 'required|string',
            'new_category' => 'nullable|string',
            'hpp' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'status' => 'required|string',
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

    /**
     * Store new category/type
     */
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

    /**
     * Update category/type
     */
    public function updateCategory(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $oldName = $category->name;
        $newName = strtoupper(trim($data['name']));

        // Update referencing products
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

    /**
     * Delete category/type
     */
    public function destroyCategory(Category $category)
    {
        $name = $category->name;
        $category->delete();
        return back()->with('success', "Kategori / Jenis [{$name}] berhasil dihapus!");
    }

    /**
     * Suppliers
     */
    public function suppliers(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        $query = Supplier::withCount('purchases');
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%")
                  ->orWhere('bank_name', 'like', "%$search%");
            });
        }
        $suppliers = ($perPage === 'all')
            ? $query->orderBy('name')->paginate(10000)->withQueryString()
            : $query->orderBy('name')->paginate((int)$perPage)->withQueryString();

        return view('master.suppliers', compact('suppliers'));
    }

    public function storeSupplier(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'account_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        Supplier::create($data);
        return redirect()->route('master.suppliers')->with('success', 'Supplier berhasil ditambahkan!');
    }

    public function updateSupplier(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'account_name' => 'nullable|string',
            'notes' => 'nullable|string',
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

    /**
     * Customers
     */
    public function customers(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        $query = Customer::withCount('sales');
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%")
                  ->orWhere('address', 'like', "%$search%");
            });
        }
        $customers = ($perPage === 'all')
            ? $query->orderBy('name')->paginate(10000)->withQueryString()
            : $query->orderBy('name')->paginate((int)$perPage)->withQueryString();

        return view('master.customers', compact('customers'));
    }

    public function storeCustomer(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|string',
        ]);

        Customer::create($data);
        return redirect()->route('master.customers')->with('success', 'Pelanggan berhasil ditambahkan!');
    }

    public function updateCustomer(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $customer->update($data);
        return redirect()->route('master.customers')->with('success', "Data pelanggan [{$customer->name}] berhasil diperbarui!");
    }

    public function destroyCustomer(Customer $customer)
    {
        $name = $customer->name;
        if (strtoupper($name) === 'UMUM') {
            return back()->with('error', "Pelanggan default sistem [UMUM] tidak dapat dihapus!");
        }
        if ($customer->sales()->exists()) {
            return back()->with('error', "Pelanggan [{$name}] tidak dapat dihapus karena memiliki riwayat transaksi penjualan!");
        }

        $customer->delete();
        return redirect()->route('master.customers')->with('success', "Pelanggan [{$name}] berhasil dihapus!");
    }
}
