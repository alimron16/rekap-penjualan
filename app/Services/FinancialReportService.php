<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\DigitalSale;
use App\Models\InventoryAdjustment;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * Generate Profit and Loss statement (Laporan Laba Rugi).
     */
    public function getProfitAndLoss(string $startDate, string $endDate): array
    {
        $sales = Sale::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])->get();
        $digitalSales = DigitalSale::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->where('status', 'SUKSES')
            ->get();
        $transfers = CashTransaction::where('type', 'TRANSFER')
            ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->get();

        // 1. Revenue components
        $retailRevenue = (float) $sales->where('sale_type', 'retail')->sum('total');
        $wholesaleRevenue = (float) $sales->where('sale_type', 'grosir')->sum('total');
        $transferFeeRevenue = (float) $transfers->sum('admin_fee');
        $multiRevenue = (float) $digitalSales->sum('selling_price');
        $salesDiscount = (float) $sales->sum('discount');

        $totalRevenue = $retailRevenue + $wholesaleRevenue + $transferFeeRevenue + $multiRevenue;

        // 2. COGS (HPP)
        $retailHpp = (float) $sales->where('sale_type', 'retail')->sum(fn($s) => $s->total_hpp);
        $wholesaleHpp = (float) $sales->where('sale_type', 'grosir')->sum(fn($s) => $s->total_hpp);
        $multiHpp = (float) $digitalSales->sum('hpp');
        $totalHpp = $retailHpp + $wholesaleHpp + $multiHpp;

        // 3. Gross Profit
        $grossProfit = $totalRevenue - $totalHpp;

        // 4. Operating Expenses from cash out transactions and journals
        $expenseLines = JournalEntryLine::whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('entry_date', [$startDate, $endDate]);
        })->whereHas('account', function ($q) {
            $q->where('group', 'BIAYA');
        })->with('account')->get();

        $expensesBreakdown = [];
        $totalExpense = 0;

        foreach ($expenseLines->groupBy('account.name') as $accName => $lines) {
            $sum = (float) $lines->sum('debit') - (float) $lines->sum('credit');
            if ($sum > 0) {
                $expensesBreakdown[$accName] = $sum;
                $totalExpense += $sum;
            }
        }

        // If no journal expense yet, check cash out transactions
        if ($totalExpense === 0) {
            $cashOuts = CashTransaction::where('type', 'OUT')
                ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
                ->with('debitAccount')
                ->get();
            foreach ($cashOuts as $co) {
                $name = $co->debitAccount ? $co->debitAccount->name : 'BIAYA UMUM';
                $expensesBreakdown[$name] = ($expensesBreakdown[$name] ?? 0) + (float) $co->amount;
                $totalExpense += (float) $co->amount;
            }
        }

        // 5. Net Profit
        $netProfit = $grossProfit - $totalExpense;

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'revenues' => [
                'retail' => $retailRevenue,
                'grosir' => $wholesaleRevenue,
                'jasa_transfer' => $transferFeeRevenue,
                'multi' => $multiRevenue,
                'total' => $totalRevenue,
            ],
            'hpp' => [
                'retail' => $retailHpp,
                'grosir' => $wholesaleHpp,
                'multi' => $multiHpp,
                'total' => $totalHpp,
            ],
            'gross_profit' => $grossProfit,
            'expenses' => [
                'breakdown' => $expensesBreakdown,
                'total' => $totalExpense,
            ],
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Generate Balance Sheet (Neraca Skontro format).
     *
     * Aktiva = Kewajiban + Modal
     */
    public function getBalanceSheet(string $asOfDate): array
    {
        // 1. Current Asset Accounts (Kas, Bank, Piutang, Persediaan)
        $aktivaAccounts = Account::where('group', 'AKTIVA')
            ->where('type', '!=', 'H')
            ->get();

        // Calculate actual inventory valuation from physical stock & HPP
        $actualInventoryValuation = Product::all()->sum(fn($p) => (float)$p->stock * (float)$p->hpp);

        $aktivaList = [];
        $totalAktiva = 0;

        foreach ($aktivaAccounts as $acc) {
            $balance = (float) $acc->current_balance;
            // For PERSEDIAAN BARANG, show either the ledger balance or the physical inventory valuation
            if ($acc->code === '1-2010' && $actualInventoryValuation > 0) {
                $balance = $actualInventoryValuation;
            }
            $aktivaList[] = [
                'code' => $acc->code,
                'name' => $acc->name,
                'balance' => $balance,
            ];
            $totalAktiva += $balance;
        }

        // 2. Liabilities Accounts (Hutang)
        $kewajibanAccounts = Account::where('group', 'KEWAJIBAN')
            ->where('type', '!=', 'H')
            ->get();

        $kewajibanList = [];
        $totalKewajiban = 0;
        foreach ($kewajibanAccounts as $acc) {
            $balance = (float) $acc->current_balance;
            $kewajibanList[] = [
                'code' => $acc->code,
                'name' => $acc->name,
                'balance' => $balance,
            ];
            $totalKewajiban += $balance;
        }

        // 3. Equity Accounts (Modal, Laba Ditahan, Laba Berjalan)
        $modalAccounts = Account::where('group', 'MODAL')
            ->where('type', '!=', 'H')
            ->get();

        $yearStart = date('Y-01-01', strtotime($asOfDate));
        $pl = $this->getProfitAndLoss($yearStart, $asOfDate);
        $currentYearProfit = $pl['net_profit'];

        $modalList = [];
        $totalModal = 0;
        foreach ($modalAccounts as $acc) {
            $balance = (float) $acc->current_balance;
            if ($acc->code === '3-3000') {
                // LABA TAHUN BERJALAN
                $balance = $currentYearProfit;
            }
            $modalList[] = [
                'code' => $acc->code,
                'name' => $acc->name,
                'balance' => $balance,
            ];
            $totalModal += $balance;
        }

        $totalKewajibanDanModal = $totalKewajiban + $totalModal;
        $isBalanced = abs($totalAktiva - $totalKewajibanDanModal) < 1.0;

        return [
            'as_of_date' => $asOfDate,
            'aktiva' => [
                'accounts' => $aktivaList,
                'total' => $totalAktiva,
            ],
            'kewajiban' => [
                'accounts' => $kewajibanList,
                'total' => $totalKewajiban,
            ],
            'modal' => [
                'accounts' => $modalList,
                'total' => $totalModal,
            ],
            'total_kewajiban_modal' => $totalKewajibanDanModal,
            'difference' => $totalAktiva - $totalKewajibanDanModal,
            'is_balanced' => $isBalanced,
        ];
    }

    /**
     * Profit summary per product (sheet Laporan Laba Jual).
     */
    public function getProductProfitSummary(string $startDate, string $endDate): array
    {
        $items = SaleItem::whereHas('sale', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"]);
        })->with(['product', 'sale'])->get();

        $grouped = $items->groupBy('product_id');
        $results = [];
        $totalProfitAll = 0;

        foreach ($grouped as $productId => $productItems) {
            $product = $productItems->first()->product;
            $qty = $productItems->sum('qty');
            $omzetRetail = $productItems->where('sale.sale_type', 'retail')->sum('subtotal');
            $omzetGrosir = $productItems->where('sale.sale_type', 'grosir')->sum('subtotal');
            $totalOmzet = $omzetRetail + $omzetGrosir;
            $totalModal = $productItems->sum(fn ($i) => $i->qty * $i->hpp);
            $labaKotor = $totalOmzet - $totalModal;
            $marginPct = $totalOmzet > 0 ? ($labaKotor / $totalOmzet) * 100 : 0;

            $totalProfitAll += $labaKotor;

            $results[] = [
                'code' => $product ? $product->item_code : '-',
                'name' => $product ? $product->name : '-',
                'qty' => $qty,
                'omzet_retail' => $omzetRetail,
                'omzet_grosir' => $omzetGrosir,
                'total_omzet' => $totalOmzet,
                'total_modal' => $totalModal,
                'laba_kotor' => $labaKotor,
                'margin_pct' => round($marginPct, 1),
            ];
        }

        // Calculate contribution %
        foreach ($results as &$r) {
            $r['kontribusi_pct'] = $totalProfitAll > 0 ? round(($r['laba_kotor'] / $totalProfitAll) * 100, 1) : 0;
        }

        return [
            'total_laba' => $totalProfitAll,
            'products' => $results,
        ];
    }
}
