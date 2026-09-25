<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\DigitalSale;
use App\Models\InventoryAdjustment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    /**
     * Laporan Pembelian
     */
    public function purchases(Request $request)
    {
        $startDate = $request->filled('start_date') ? $request->input('start_date') : date('Y-m-01');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : date('Y-m-d');
        $perPage = $request->input('per_page', 25);

        $query = Purchase::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%$search%"));
            });
        }

        $purchases = ($perPage === 'all')
            ? $query->with(['supplier', 'items'])->orderByDesc('date')->paginate(10000)->withQueryString()
            : $query->with(['supplier', 'items'])->orderByDesc('date')->paginate((int)$perPage)->withQueryString();

        $totalQty = 0;
        $totalSubtotal = 0;
        $totalDiscount = 0;
        $totalPaid = 0;
        $totalDebt = 0;

        foreach ($purchases as $p) {
            $totalQty += $p->items->sum('qty');
            $totalSubtotal += $p->subtotal;
            $totalDiscount += $p->discount;
            $totalPaid += $p->paid_amount;
            $totalDebt += $p->remaining_debt;
        }

        return view('reports.purchases', compact(
            'purchases', 'startDate', 'endDate',
            'totalQty', 'totalSubtotal', 'totalDiscount', 'totalPaid', 'totalDebt'
        ));
    }

    /**
     * Laporan Penjualan (Mendukung Retail, Grosir, & Produk Multi / Pulsa)
     */
    public function sales(Request $request)
    {
        $startDate = $request->filled('start_date') ? $request->input('start_date') : date('Y-m-01');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : date('Y-m-d');
        $saleType = $request->input('sale_type', 'all');
        $perPage = $request->input('per_page', 25);
        $search = $request->input('search');

        $unifiedSales = collect();

        // 1. Ambil Penjualan Fisik (Retail / Grosir)
        if ($saleType !== 'digital') {
            $query = Sale::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"]);
            if ($saleType !== 'all') {
                $query->where('sale_type', $saleType);
            }
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%$search%")
                      ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%$search%"));
                });
            }

            $salesList = $query->with(['customer', 'items', 'outlet'])->get();
            foreach ($salesList as $s) {
                $unifiedSales->push((object)[
                    'id' => $s->id,
                    'is_digital' => false,
                    'date' => $s->date,
                    'invoice_number' => $s->invoice_number,
                    'sale_type' => $s->sale_type,
                    'customer_name' => $s->customer->name ?? 'UMUM',
                    'items_qty' => (float) $s->items->sum('qty'),
                    'subtotal' => (float) $s->subtotal,
                    'discount' => (float) $s->discount,
                    'total' => (float) $s->total,
                    'paid_amount' => (float) $s->paid_amount,
                    'remaining_receivable' => (float) $s->remaining_receivable,
                    'status' => $s->status,
                    'receipt_url' => route('receipt.thermal', $s->id),
                ]);
            }
        }

        // 2. Ambil Penjualan Elektrik / Multi Pulsa (Digital)
        if ($saleType === 'all' || $saleType === 'digital') {
            $digitalQuery = DigitalSale::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
                ->with(['digitalProduct']);

            if ($search) {
                $digitalQuery->where(function ($q) use ($search) {
                    $q->where('transaction_number', 'like', "%$search%")
                      ->orWhere('customer_number', 'like', "%$search%")
                      ->orWhereHas('digitalProduct', fn($dp) => $dp->where('name', 'like', "%$search%"));
                });
            }

            $digitalList = $digitalQuery->get();
            foreach ($digitalList as $ds) {
                $unifiedSales->push((object)[
                    'id' => $ds->id,
                    'is_digital' => true,
                    'date' => $ds->date,
                    'invoice_number' => $ds->transaction_number,
                    'sale_type' => 'digital',
                    'customer_name' => ($ds->digitalProduct->name ?? 'Pulsa') . ' (' . $ds->customer_number . ')',
                    'items_qty' => 1,
                    'subtotal' => (float) $ds->selling_price,
                    'discount' => 0,
                    'total' => (float) $ds->selling_price,
                    'paid_amount' => (float) $ds->selling_price,
                    'remaining_receivable' => 0,
                    'status' => $ds->status,
                    'receipt_url' => route('receipt.thermal_digital', $ds->id),
                ]);
            }
        }

        // Urutkan berdasarkan tanggal terbaru
        $sortedSales = $unifiedSales->sortByDesc(fn($item) => $item->date ? $item->date->timestamp : 0)->values();

        // Hitung Total Summary Keseluruhan
        $totalQty = $sortedSales->sum('items_qty');
        $totalSubtotal = $sortedSales->sum('subtotal');
        $totalDiscount = $sortedSales->sum('discount');
        $totalFinal = $sortedSales->sum('total');
        $totalPaid = $sortedSales->sum('paid_amount');
        $totalReceivable = $sortedSales->sum('remaining_receivable');

        // Manual Pagination untuk Unified Collection
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $itemsPerPage = ($perPage === 'all') ? 10000 : (int)$perPage;
        $currentItems = $sortedSales->slice(($currentPage - 1) * $itemsPerPage, $itemsPerPage)->values();

        $sales = new LengthAwarePaginator(
            $currentItems,
            $sortedSales->count(),
            $itemsPerPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('reports.sales', compact(
            'sales', 'startDate', 'endDate', 'saleType',
            'totalQty', 'totalSubtotal', 'totalDiscount', 'totalFinal', 'totalPaid', 'totalReceivable'
        ));
    }

    /**
     * Laporan Hutang
     */
    public function debts(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        $query = Purchase::where('status', 'BELUM LUNAS')
            ->where('remaining_debt', '>', 0)
            ->with('supplier');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%$search%"));
            });
        }

        $debts = ($perPage === 'all')
            ? $query->orderByDesc('date')->paginate(10000)->withQueryString()
            : $query->orderByDesc('date')->paginate((int)$perPage)->withQueryString();

        $totalHutang = $debts->sum('remaining_debt');

        return view('reports.debts', compact('debts', 'totalHutang'));
    }

    /**
     * Laporan Piutang
     */
    public function receivables(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        $query = Sale::where('status', 'BELUM LUNAS')
            ->where('remaining_receivable', '>', 0)
            ->with('customer');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%$search%"));
            });
        }

        $receivables = ($perPage === 'all')
            ? $query->orderByDesc('date')->paginate(10000)->withQueryString()
            : $query->orderByDesc('date')->paginate((int)$perPage)->withQueryString();

        $totalPiutang = $receivables->sum('remaining_receivable');

        return view('reports.receivables', compact('receivables', 'totalPiutang'));
    }

    /**
     * Laporan Persediaan
     */
    public function inventory(Request $request)
    {
        $startDate = $request->filled('start_date') ? $request->input('start_date') : date('Y-m-01');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : date('Y-m-d');

        $itemsIn = InventoryAdjustment::where('type', 'IN')
            ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with('product')->get();

        $itemsOut = InventoryAdjustment::where('type', 'OUT')
            ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with('product')->get();

        $stockOpname = InventoryAdjustment::where('type', 'OPNAME')
            ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with('product')->get();

        return view('reports.inventory', compact('itemsIn', 'itemsOut', 'stockOpname', 'startDate', 'endDate'));
    }

    /**
     * Laporan Kas
     */
    public function cash(Request $request)
    {
        $startDate = $request->filled('start_date') ? $request->input('start_date') : date('Y-m-01');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : date('Y-m-d');

        $kasMasuk = CashTransaction::where('type', 'IN')
            ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with(['debitAccount', 'creditAccount'])->get();

        $kasKeluar = CashTransaction::where('type', 'OUT')
            ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with(['debitAccount', 'creditAccount'])->get();

        $kasTransfer = CashTransaction::where('type', 'TRANSFER')
            ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with(['debitAccount', 'creditAccount'])->get();

        return view('reports.cash', compact('kasMasuk', 'kasKeluar', 'kasTransfer', 'startDate', 'endDate'));
    }

    /**
     * Laporan Laba Jual
     */
    public function profitSales(Request $request)
    {
        $startDate = $request->filled('start_date') ? $request->input('start_date') : date('Y-m-01');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : date('Y-m-d');
        $perPage = $request->input('per_page', 25);

        $productSummary = $this->reportService->getProductProfitSummary($startDate, $endDate);

        $query = Sale::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with(['customer', 'items.product']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%$search%"));
            });
        }

        $salesTransactions = ($perPage === 'all')
            ? $query->orderByDesc('date')->paginate(10000)->withQueryString()
            : $query->orderByDesc('date')->paginate((int)$perPage)->withQueryString();

        return view('reports.profit_sales', compact('productSummary', 'salesTransactions', 'startDate', 'endDate'));
    }

    /**
     * Laporan Laba Rugi
     */
    public function profitLoss(Request $request)
    {
        $startDate = $request->filled('start_date') ? $request->input('start_date') : date('Y-m-01');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : date('Y-m-d');

        $pl = $this->reportService->getProfitAndLoss($startDate, $endDate);

        return view('reports.profit_loss', compact('pl', 'startDate', 'endDate'));
    }

    /**
     * Neraca (Format Skontro)
     */
    public function balanceSheet(Request $request)
    {
        $asOfDate = $request->filled('as_of_date') ? $request->input('as_of_date') : date('Y-m-d');
        $bs = $this->reportService->getBalanceSheet($asOfDate);

        return view('reports.balance_sheet', compact('bs', 'asOfDate'));
    }
}
