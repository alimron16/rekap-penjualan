<?php

namespace App\Services;
namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\MonthlyTarget;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreSetting;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    public function index(Request $request)
    {
        $setting = StoreSetting::first();
        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-d'));

        // Financial report for period
        $pl = $this->reportService->getProfitAndLoss($startDate, $endDate);

        // Core KPI Cards (matching the 8 Excel green boxes)
        $totalPersediaan = Product::all()->sum(fn($p) => (float)$p->stock * (float)$p->hpp);
        $totalHutang = (float) Purchase::where('status', 'BELUM LUNAS')->sum('remaining_debt');
        $totalPiutang = (float) Sale::where('status', 'BELUM LUNAS')->sum('remaining_receivable');
        
        $totalKasBank = (float) Account::where('group', 'AKTIVA')
            ->whereIn('code', ['1-1110', '1-1111', '1-1112', '1-1113', '1-1120', '1-1121', '1-1122', '1-1123', '1-1130', '1-1131', '1-1190'])
            ->sum('current_balance');

        $salesCount = Sale::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])->count();
        $retailSalesCount = Sale::where('sale_type', 'retail')->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])->count();
        $grosirSalesCount = Sale::where('sale_type', 'grosir')->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])->count();

        // Top 10 Best-selling items (URUTAN PRODUK TERLARIS)
        $topProducts = SaleItem::select('product_id', DB::raw('SUM(qty) as total_sold'))
            ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"]);
            })
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->with('product')
            ->get();

        // Target Profit for current month
        $currentYear = (int) date('Y', strtotime($startDate));
        $currentMonth = (int) date('m', strtotime($startDate));
        $target = MonthlyTarget::where('year', $currentYear)->where('month', $currentMonth)->first();
        $targetProfit = $target ? (float)$target->target_profit : 15000000.0;
        $realizedProfit = $pl['net_profit'];
        $remainingTarget = max(0, $targetProfit - $realizedProfit);
        $progressPct = $targetProfit > 0 ? min(100, round(($realizedProfit / $targetProfit) * 100, 1)) : 0;

        // Daily trend data for Chart.js
        $dailySales = Sale::select(DB::raw("DATE(date) as day"), DB::raw("SUM(total) as revenue"))
            ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('revenue', 'day')
            ->toArray();

        // Accounts list for cash & bank breakdown
        $cashAccounts = Account::whereIn('code', ['1-1110', '1-1111', '1-1112', '1-1113', '1-1120', '1-1131'])->get();

        return view('dashboard.index', compact(
            'setting',
            'startDate',
            'endDate',
            'pl',
            'totalPersediaan',
            'totalHutang',
            'totalPiutang',
            'totalKasBank',
            'salesCount',
            'retailSalesCount',
            'grosirSalesCount',
            'topProducts',
            'targetProfit',
            'realizedProfit',
            'remainingTarget',
            'progressPct',
            'dailySales',
            'cashAccounts'
        ));
    }
}
