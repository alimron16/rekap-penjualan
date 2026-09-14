<?php

namespace App\Http\Controllers;

use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Services\PosTransactionService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        protected PosTransactionService $posService
    ) {}

    /**
     * Item Masuk
     */
    public function itemIn(Request $request)
    {
        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();
        $perPage = $request->input('per_page', 15);
        $query = InventoryAdjustment::where('type', 'IN')->with('product');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_number', 'like', "%$search%")
                  ->orWhere('notes', 'like', "%$search%")
                  ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%$search%"));
            });
        }

        $history = ($perPage === 'all')
            ? $query->orderByDesc('created_at')->paginate(10000)->withQueryString()
            : $query->orderByDesc('created_at')->paginate((int)$perPage)->withQueryString();

        return view('inventory.item_in', compact('products', 'history'));
    }

    public function storeItemIn(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'cost_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $data['type'] = 'IN';
        $this->posService->processInventoryAdjustment($data);

        return redirect()->route('inventory.item_in')->with('success', 'Item Masuk berhasil dicatat!');
    }

    /**
     * Item Keluar
     */
    public function itemOut(Request $request)
    {
        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();
        $perPage = $request->input('per_page', 15);
        $query = InventoryAdjustment::where('type', 'OUT')->with('product');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_number', 'like', "%$search%")
                  ->orWhere('notes', 'like', "%$search%")
                  ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%$search%"));
            });
        }

        $history = ($perPage === 'all')
            ? $query->orderByDesc('created_at')->paginate(10000)->withQueryString()
            : $query->orderByDesc('created_at')->paginate((int)$perPage)->withQueryString();

        return view('inventory.item_out', compact('products', 'history'));
    }

    public function storeItemOut(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $data['type'] = 'OUT';
        $this->posService->processInventoryAdjustment($data);

        return redirect()->route('inventory.item_out')->with('success', 'Item Keluar berhasil dicatat!');
    }

    /**
     * Stok Opname
     */
    public function stockOpname(Request $request)
    {
        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();
        $perPage = $request->input('per_page', 15);
        $query = InventoryAdjustment::where('type', 'OPNAME')->with('product');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_number', 'like', "%$search%")
                  ->orWhere('notes', 'like', "%$search%")
                  ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%$search%"));
            });
        }

        $history = ($perPage === 'all')
            ? $query->orderByDesc('created_at')->paginate(10000)->withQueryString()
            : $query->orderByDesc('created_at')->paginate((int)$perPage)->withQueryString();

        return view('inventory.stock_opname', compact('products', 'history'));
    }

    public function storeStockOpname(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'actual_stock' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $data['type'] = 'OPNAME';
        $this->posService->processInventoryAdjustment($data);

        return redirect()->route('inventory.stock_opname')->with('success', 'Stok Opname berhasil diproses & saldo sistem disesuaikan!');
    }

    public function updateItemIn(Request $request, InventoryAdjustment $adjustment)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'qty' => 'required|numeric|min:0.01',
            'cost_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($adjustment, $data) {
            $product = $adjustment->product;
            $oldQty = (float) $adjustment->qty;
            $newQty = (float) $data['qty'];
            $diff = $newQty - $oldQty;

            $product->increment('stock', $diff);

            $costPrice = (float) ($data['cost_price'] ?? $adjustment->cost_price);
            $totalVal = $newQty * $costPrice;

            $adjustment->update([
                'date' => $data['date'],
                'qty' => $newQty,
                'cost_price' => $costPrice,
                'total_value' => $totalVal,
                'notes' => $data['notes'] ?? 'Item Masuk (Koreksi)',
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
            $product = $adjustment->product;
            $product->decrement('stock', (float)$adjustment->qty);

            \App\Models\JournalEntry::where('source_type', 'inventory_adjustment')
                ->where('source_id', $adjustment->id)
                ->each(fn($j) => $j->delete());

            $adjustment->delete();
        });

        return redirect()->route('inventory.item_in')->with('success', "Item Masuk [{$num}] berhasil dibatalkan dan stok dikembalikan!");
    }

    public function updateItemOut(Request $request, InventoryAdjustment $adjustment)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'qty' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($adjustment, $data) {
            $product = $adjustment->product;
            $oldQty = (float) $adjustment->qty;
            $newQty = (float) $data['qty'];
            $diff = $newQty - $oldQty;

            $product->decrement('stock', $diff);

            $costPrice = (float) $adjustment->cost_price;
            $totalVal = $newQty * $costPrice;

            $adjustment->update([
                'date' => $data['date'],
                'qty' => $newQty,
                'total_value' => $totalVal,
                'notes' => $data['notes'] ?? 'Item Keluar (Koreksi)',
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
            $product = $adjustment->product;
            $product->increment('stock', (float)$adjustment->qty);

            \App\Models\JournalEntry::where('source_type', 'inventory_adjustment')
                ->where('source_id', $adjustment->id)
                ->each(fn($j) => $j->delete());

            $adjustment->delete();
        });

        return redirect()->route('inventory.item_out')->with('success', "Item Keluar [{$num}] berhasil dibatalkan dan stok dikembalikan!");
    }

    public function updateStockOpname(Request $request, InventoryAdjustment $adjustment)
    {
        $data = $request->validate([
            'actual_stock' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($adjustment, $data) {
            $product = $adjustment->product;
            $newActual = (float) $data['actual_stock'];
            $systemStock = (float) $adjustment->system_stock;
            $diffQty = $newActual - $systemStock;
            $totalVal = abs($diffQty) * (float) $adjustment->cost_price;

            $product->update(['stock' => $newActual]);

            $adjustment->update([
                'actual_stock' => $newActual,
                'qty' => $diffQty,
                'total_value' => $totalVal,
                'notes' => $data['notes'] ?? 'Stok Opname (Koreksi)',
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
            $product = $adjustment->product;
            $product->update(['stock' => (float)$adjustment->system_stock]);

            \App\Models\JournalEntry::where('source_type', 'inventory_adjustment')
                ->where('source_id', $adjustment->id)
                ->each(fn($j) => $j->delete());

            $adjustment->delete();
        });

        return redirect()->route('inventory.stock_opname')->with('success', "Stok Opname [{$num}] berhasil dibatalkan dan stok dikembalikan ke stok sistem!");
    }
}
