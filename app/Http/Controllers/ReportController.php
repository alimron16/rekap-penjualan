<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\InventoryAdjustment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;

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
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $perPage = $request->input('per_page', 25);

        $query = Purchase::whereBetween('date', [$startDate, $endDate])
            ->with(['supplier', 'items']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%$search%"));
            });
        }

        $purchases = ($perPage === 'all')
            ? $query->orderByDesc('date')->paginate(10000)->withQueryString()
            : $query->orderByDesc('date')->paginate((int)$perPage)->withQueryString();

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
     * Laporan Penjualan
     */
    public function sales(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $saleType = $request->input('sale_type', 'all');
        $perPage = $request->input('per_page', 25);

        $query = Sale::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"]);
        if ($saleType !== 'all') {
            $query->where('sale_type', $saleType);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%$search%"));
            });
        }

        $sales = ($perPage === 'all')
            ? $query->with(['customer', 'items'])->orderByDesc('date')->paginate(10000)->withQueryString()
            : $query->with(['customer', 'items'])->orderByDesc('date')->paginate((int)$perPage)->withQueryString();

        $totalQty = 0;
        $totalSubtotal = 0;
        $totalDiscount = 0;
        $totalFinal = 0;
        $totalPaid = 0;
        $totalReceivable = 0;

        foreach ($sales as $s) {
            $totalQty += $s->items->sum('qty');
            $totalSubtotal += $s->subtotal;
            $totalDiscount += $s->discount;
            $totalFinal += $s->total;
            $totalPaid += $s->paid_amount;
            $totalReceivable += $s->remaining_receivable;
        }

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
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

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
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

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
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
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
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $pl = $this->reportService->getProfitAndLoss($startDate, $endDate);

        return view('reports.profit_loss', compact('pl', 'startDate', 'endDate'));
    }

    /**
     * Neraca (Format Skontro)
     */
    public function balanceSheet(Request $request)
    {
        $asOfDate = $request->input('as_of_date', date('Y-m-d'));
        $bs = $this->reportService->getBalanceSheet($asOfDate);

        return view('reports.balance_sheet', compact('bs', 'asOfDate'));
    }
}
