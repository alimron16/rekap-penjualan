<?php

namespace App\Http\Controllers;

use App\Models\InventoryAdjustment;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\PosTransactionService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        protected PosTransactionService $posService
    ) {}

    // ----------------------------------------------------------------
    // Helper
    // ----------------------------------------------------------------

    private function outletId(): ?int
    {
        $user = auth()->user();
        return $user?->isAdmin() ? null : $user?->outlet_id;
    }

    // ================================================================
    // Item Masuk
    // ================================================================

    public function itemIn(Request $request)
    {
        $outletId = $this->outletId();

        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();

        // Attach outlet stock to each product
        if ($outletId) {
            $outletStocks = ProductStock::where('outlet_id', $outletId)
                ->whereIn('product_id', $products->pluck('id'))
                ->pluck('stock', 'product_id');

            $products->each(fn($p) => $p->outlet_stock = (float) ($outletStocks[$p->id] ?? 0));
        } else {
            $products->each(fn($p) => $p->outlet_stock = (float) $p->stock);
        }

        $perPage = $request->input('per_page', 15);
        $query   = InventoryAdjustment::where('type', 'IN')
            ->with(['product', 'outlet', 'user'])
            ->when($outletId, fn($q) => $q->where('outlet_id', $outletId));

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_number', 'like', "%$search%")
                  ->orWhere('notes', 'like', "%$search%")
                  ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%$search%"));
            });
        }

        $history = ($perPage === 'all')
            ? $query->orderByDesc('created_at')->paginate(10000)->withQueryString()
            : $query->orderByDesc('created_at')->paginate((int) $perPage)->withQueryString();

        $outlets = auth()->user()?->isAdmin()
            ? Outlet::where('status', 'active')->orderBy('name')->get()
            : collect();

        return view('inventory.item_in', compact('products', 'history', 'outlets', 'outletId'));
    }

    public function storeItemIn(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty'        => 'required|numeric|min:0.01',
            'cost_price' => 'nullable|numeric|min:0',
            'notes'      => 'nullable|string',
        ]);

        $data['type']      = 'IN';
        $data['outlet_id'] = $request->input('outlet_id') ?? auth()->user()?->outlet_id;

        $this->posService->processInventoryAdjustment($data);

        return redirect()->route('inventory.item_in')->with('success', 'Item Masuk berhasil dicatat!');
    }

    // ================================================================
    // Item Keluar
    // ================================================================

    public function itemOut(Request $request)
    {
        $outletId = $this->outletId();

        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();
        if ($outletId) {
            $outletStocks = ProductStock::where('outlet_id', $outletId)
                ->whereIn('product_id', $products->pluck('id'))
                ->pluck('stock', 'product_id');
            $products->each(fn($p) => $p->outlet_stock = (float) ($outletStocks[$p->id] ?? 0));
        } else {
            $products->each(fn($p) => $p->outlet_stock = (float) $p->stock);
        }

        $perPage = $request->input('per_page', 15);
        $query   = InventoryAdjustment::where('type', 'OUT')
            ->with(['product', 'outlet', 'user'])
            ->when($outletId, fn($q) => $q->where('outlet_id', $outletId));

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_number', 'like', "%$search%")
                  ->orWhere('notes', 'like', "%$search%")
                  ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%$search%"));
            });
        }

        $history = ($perPage === 'all')
            ? $query->orderByDesc('created_at')->paginate(10000)->withQueryString()
            : $query->orderByDesc('created_at')->paginate((int) $perPage)->withQueryString();

        $outlets = auth()->user()?->isAdmin()
            ? Outlet::where('status', 'active')->orderBy('name')->get()
            : collect();

        return view('inventory.item_out', compact('products', 'history', 'outlets', 'outletId'));
    }

    public function storeItemOut(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty'        => 'required|numeric|min:0.01',
            'notes'      => 'nullable|string',
        ]);

        $data['type']      = 'OUT';
        $data['outlet_id'] = $request->input('outlet_id') ?? auth()->user()?->outlet_id;

        $this->posService->processInventoryAdjustment($data);

        return redirect()->route('inventory.item_out')->with('success', 'Item Keluar berhasil dicatat!');
    }

    // ================================================================
    // Stok Opname
    // ================================================================

    public function stockOpname(Request $request)
    {
        $outletId = $this->outletId();

        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();
        if ($outletId) {
            $outletStocks = ProductStock::where('outlet_id', $outletId)
                ->whereIn('product_id', $products->pluck('id'))
                ->pluck('stock', 'product_id');
            $products->each(fn($p) => $p->outlet_stock = (float) ($outletStocks[$p->id] ?? 0));
        } else {
            $products->each(fn($p) => $p->outlet_stock = (float) $p->stock);
        }

        $perPage = $request->input('per_page', 15);
        $query   = InventoryAdjustment::where('type', 'OPNAME')
            ->with(['product', 'outlet', 'user'])
            ->when($outletId, fn($q) => $q->where('outlet_id', $outletId));

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_number', 'like', "%$search%")
                  ->orWhere('notes', 'like', "%$search%")
                  ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%$search%"));
            });
        }

        $history = ($perPage === 'all')
            ? $query->orderByDesc('created_at')->paginate(10000)->withQueryString()
            : $query->orderByDesc('created_at')->paginate((int) $perPage)->withQueryString();

        $outlets = auth()->user()?->isAdmin()
            ? Outlet::where('status', 'active')->orderBy('name')->get()
            : collect();

        return view('inventory.stock_opname', compact('products', 'history', 'outlets', 'outletId'));
    }

    public function storeStockOpname(Request $request)
    {
        $data = $request->validate([
            'product_id'   => 'required|exists:products,id',
            'actual_stock' => 'required|numeric|min:0',
            'notes'        => 'nullable|string',
        ]);

        $data['type']      = 'OPNAME';
        $data['outlet_id'] = $request->input('outlet_id') ?? auth()->user()?->outlet_id;

        $this->posService->processInventoryAdjustment($data);

        return redirect()->route('inventory.stock_opname')->with('success', 'Stok Opname berhasil diproses & saldo sistem disesuaikan!');
    }

    // ================================================================
    // Update & Destroy — Item Masuk
    // ================================================================

    public function updateItemIn(Request $request, InventoryAdjustment $adjustment)
    {
        $data = $request->validate([
            'date'       => 'required|date',
            'qty'        => 'required|numeric|min:0.01',
            'cost_price' => 'nullable|numeric|min:0',
            'notes'      => 'nullable|string',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($adjustment, $data) {
            $product   = $adjustment->product;
            $outletId  = $adjustment->outlet_id;
            $oldQty    = (float) $adjustment->qty;
            $newQty    = (float) $data['qty'];
            $diff      = $newQty - $oldQty;

            // Adjust per-outlet stock
            if ($outletId) {
                $outletStock = ProductStock::forOutlet($product->id, $outletId);
                $outletStock->increment('stock', $diff);
            }

            // Sync global
            $product->increment('stock', $diff);

            $costPrice = (float) ($data['cost_price'] ?? $adjustment->cost_price);
            $totalVal  = $newQty * $costPrice;

            $adjustment->update([
                'date'       => $data['date'],
                'qty'        => $newQty,
                'cost_price' => $costPrice,
                'total_value'=> $totalVal,
                'notes'      => $data['notes'] ?? 'Item Masuk (Koreksi)',
            ]);

            \App\Models\JournalEntry::where('source_type', 'inventory_adjustment')
                ->where('source_id', $adjustment->id)
                ->each(fn($j) => $j->delete());

            app(\App\Services\AccountingService::class)->recordInventoryAdjustment($adjustment);
        });

        return redirect()->route('inventory.item_in')->with('success', "Item Masuk [{$adjustment->adjustment_number}] berhasil diperbarui!");
    }

    public function destroyItemIn(InventoryAdjustment $adjustment)
    {
        $num = $adjustment->adjustment_number;
        \Illuminate\Support\Facades\DB::transaction(function () use ($adjustment) {
            $product  = $adjustment->product;
            $outletId = $adjustment->outlet_id;
            $qty      = (float) $adjustment->qty;

            // Revert per-outlet stock
            if ($outletId) {
                ProductStock::forOutlet($product->id, $outletId)->decrement('stock', $qty);
            }
            // Sync global
            $product->decrement('stock', $qty);

            \App\Models\JournalEntry::where('source_type', 'inventory_adjustment')
                ->where('source_id', $adjustment->id)
                ->each(fn($j) => $j->delete());

            $adjustment->delete();
        });

        return redirect()->route('inventory.item_in')->with('success', "Item Masuk [{$num}] berhasil dibatalkan dan stok dikembalikan!");
    }

    // ================================================================
    // Update & Destroy — Item Keluar
    // ================================================================

    public function updateItemOut(Request $request, InventoryAdjustment $adjustment)
    {
        $data = $request->validate([
            'date'  => 'required|date',
            'qty'   => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($adjustment, $data) {
            $product  = $adjustment->product;
            $outletId = $adjustment->outlet_id;
            $oldQty   = (float) $adjustment->qty;
            $newQty   = (float) $data['qty'];
            $diff     = $newQty - $oldQty; // positive = more taken out

            if ($outletId) {
                ProductStock::forOutlet($product->id, $outletId)->decrement('stock', $diff);
            }
            $product->decrement('stock', $diff);

            $costPrice = (float) $adjustment->cost_price;
            $totalVal  = $newQty * $costPrice;

            $adjustment->update([
                'date'        => $data['date'],
                'qty'         => $newQty,
                'total_value' => $totalVal,
                'notes'       => $data['notes'] ?? 'Item Keluar (Koreksi)',
            ]);

            \App\Models\JournalEntry::where('source_type', 'inventory_adjustment')
                ->where('source_id', $adjustment->id)
                ->each(fn($j) => $j->delete());

            app(\App\Services\AccountingService::class)->recordInventoryAdjustment($adjustment);
        });

        return redirect()->route('inventory.item_out')->with('success', "Item Keluar [{$adjustment->adjustment_number}] berhasil diperbarui!");
    }

    public function destroyItemOut(InventoryAdjustment $adjustment)
    {
        $num = $adjustment->adjustment_number;
        \Illuminate\Support\Facades\DB::transaction(function () use ($adjustment) {
            $product  = $adjustment->product;
            $outletId = $adjustment->outlet_id;
            $qty      = (float) $adjustment->qty;

            // Revert — add back the stock that was taken out
            if ($outletId) {
                ProductStock::forOutlet($product->id, $outletId)->increment('stock', $qty);
            }
            $product->increment('stock', $qty);

            \App\Models\JournalEntry::where('source_type', 'inventory_adjustment')
                ->where('source_id', $adjustment->id)
                ->each(fn($j) => $j->delete());

            $adjustment->delete();
        });

        return redirect()->route('inventory.item_out')->with('success', "Item Keluar [{$num}] berhasil dibatalkan dan stok dikembalikan!");
    }

    // ================================================================
    // Update & Destroy — Stok Opname
    // ================================================================

    public function updateStockOpname(Request $request, InventoryAdjustment $adjustment)
    {
        $data = $request->validate([
            'actual_stock' => 'required|numeric|min:0',
            'notes'        => 'nullable|string',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($adjustment, $data) {
            $product     = $adjustment->product;
            $outletId    = $adjustment->outlet_id;
            $newActual   = (float) $data['actual_stock'];
            $systemStock = (float) $adjustment->system_stock;
            $diffQty     = $newActual - $systemStock;
            $totalVal    = abs($diffQty) * (float) $adjustment->cost_price;

            // Update per-outlet stock
            if ($outletId) {
                ProductStock::updateOrCreate(
                    ['product_id' => $product->id, 'outlet_id' => $outletId],
                    ['stock' => $newActual]
                );
                // Sync global
                $product->syncTotalStock();
            } else {
                $product->update(['stock' => $newActual]);
            }

            $adjustment->update([
                'actual_stock' => $newActual,
                'qty'          => $diffQty,
                'total_value'  => $totalVal,
                'notes'        => $data['notes'] ?? 'Stok Opname (Koreksi)',
            ]);

            \App\Models\JournalEntry::where('source_type', 'inventory_adjustment')
                ->where('source_id', $adjustment->id)
                ->each(fn($j) => $j->delete());

            app(\App\Services\AccountingService::class)->recordInventoryAdjustment($adjustment);
        });

        return redirect()->route('inventory.stock_opname')->with('success', "Stok Opname [{$adjustment->adjustment_number}] berhasil diperbarui!");
    }

    public function destroyStockOpname(InventoryAdjustment $adjustment)
    {
        $num = $adjustment->adjustment_number;
        \Illuminate\Support\Facades\DB::transaction(function () use ($adjustment) {
            $product     = $adjustment->product;
            $outletId    = $adjustment->outlet_id;
            $systemStock = (float) $adjustment->system_stock;

            if ($outletId) {
                ProductStock::updateOrCreate(
                    ['product_id' => $product->id, 'outlet_id' => $outletId],
                    ['stock' => $systemStock]
                );
                $product->syncTotalStock();
            } else {
                $product->update(['stock' => $systemStock]);
            }

            \App\Models\JournalEntry::where('source_type', 'inventory_adjustment')
                ->where('source_id', $adjustment->id)
                ->each(fn($j) => $j->delete());

            $adjustment->delete();
        });

        return redirect()->route('inventory.stock_opname')->with('success', "Stok Opname [{$num}] berhasil dibatalkan dan stok dikembalikan ke stok sistem!");
    }
}
