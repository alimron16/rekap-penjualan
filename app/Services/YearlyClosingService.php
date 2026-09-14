<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\YearlyClosing;
use Exception;
use Illuminate\Support\Facades\DB;

class YearlyClosingService
{
    public function __construct(
        protected AccountingService $accountingService,
        protected FinancialReportService $reportService
    ) {}

    /**
     * Preview / Prepare closing for a specific year.
     */
    public function prepareClosing(int $year): array
    {
        $startDate = "$year-01-01";
        $endDate = "$year-12-31";

        $pl = $this->reportService->getProfitAndLoss($startDate, $endDate);
        $netProfit = $pl['net_profit'];

        $accounts = Account::orderBy('code')->get();
        $preview = [];

        foreach ($accounts as $acc) {
            $isClosed = in_array($acc->group, ['PENDAPATAN', 'HPP', 'BIAYA', 'PENDAPATAN LAIN', 'BIAYA LAIN']);
            $actionNote = 'TIDAK DITUTUP (Akun Riil)';

            if ($acc->type === 'H') {
                $actionNote = 'SKIP: Header';
            } elseif ($isClosed) {
                $actionNote = "DITUTUP $year (Reset Saldo ke 0)";
            } elseif ($acc->code === '3-2000') {
                $actionNote = "MENERIMA LABA TAHUNAN (+$netProfit)";
            }

            $preview[] = [
                'account' => $acc,
                'is_closed' => $isClosed,
                'current_balance' => (float) $acc->current_balance,
                'balance_after' => $isClosed ? 0.0 : (float) $acc->current_balance + ($acc->code === '3-2000' ? $netProfit : 0),
                'note' => $actionNote,
            ];
        }

        return [
            'year' => $year,
            'net_profit' => $netProfit,
            'accounts' => $preview,
        ];
    }

    /**
     * Execute closing process for a year.
     */
    public function executeClosing(int $year, string $closingDate): YearlyClosing
    {
        return DB::transaction(function () use ($year, $closingDate) {
            $existing = YearlyClosing::where('year', $year)->first();
            if ($existing && $existing->status === 'DIPROSES') {
                throw new Exception("Tahun buku {$year} sudah pernah ditutup!");
            }

            $prep = $this->prepareClosing($year);
            $netProfit = $prep['net_profit'];

            $retainedEarningsAcc = Account::where('code', '3-2000')->first();
            if ($retainedEarningsAcc) {
                $retainedEarningsAcc->current_balance += $netProfit;
                $retainedEarningsAcc->save();
            }

            // Reset nominal accounts
            Account::whereIn('group', ['PENDAPATAN', 'HPP', 'BIAYA', 'PENDAPATAN LAIN', 'BIAYA LAIN'])
                ->update(['current_balance' => 0]);

            $closing = YearlyClosing::updateOrCreate(
                ['year' => $year],
                [
                    'closing_date' => $closingDate,
                    'status' => 'DIPROSES',
                    'net_profit' => $netProfit,
                    'retained_earnings_account_id' => $retainedEarningsAcc ? $retainedEarningsAcc->id : null,
                    'notes' => "Tutup buku tahun {$year} berhasil. Laba dialihkan ke Laba Ditahan.",
                ]
            );

            return $closing;
        });
    }
}
