<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AgentTransfer;
use App\Models\CashTransaction;
use App\Models\DigitalSale;
use App\Models\Outlet;
use App\Models\Sale;
use App\Models\ShiftLog;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    public function __construct(
        protected AccountingService $accountingService,
        protected PosTransactionService $posService
    ) {}

    /**
     * Get real-time shift summary and metrics.
     * Shift starts from the end of the last closed shift for the outlet, or start of today.
     */
    public function getShiftSummary(?int $outletId = null, ?int $userId = null): array
    {
        $now = Carbon::now();

        // 1. Determine shift start time
        // Shift berjalan berkesinambungan dan TIDAK PERNAH ter-reset otomatis oleh pergantian tanggal / tengah malam.
        // Shift HANYA berganti jika kasir/admin secara manual menekan tombol "Tutup Shift & Setor".
        $lastShift = ShiftLog::when($outletId, fn($q) => $q->where('outlet_id', $outletId))
            ->latest('end_time')
            ->first();

        if ($lastShift && $lastShift->end_time) {
            $startTime = Carbon::parse($lastShift->end_time);
        } else {
            // Jika belum ada riwayat tutup shift sama sekali, ambil sejak transaksi paling awal
            $firstSaleDate = Sale::when($outletId, fn($q) => $q->where('outlet_id', $outletId))->min('date');
            $startTime = $firstSaleDate ? Carbon::parse($firstSaleDate) : Carbon::create(2020, 1, 1);
        }

        // Urutan nomor shift berkesinambungan untuk outlet ini (tidak ter-reset ke 1 saat tengah malam)
        $totalShiftsCount = ShiftLog::when($outletId, fn($q) => $q->where('outlet_id', $outletId))->count();
        $shiftNumber = $totalShiftsCount + 1;

        // 2. Fetch Retail POS Sales during this shift
        $salesQuery = Sale::where('date', '>=', $startTime)
            ->where('date', '<=', $now);
        if ($outletId) {
            $salesQuery->where('outlet_id', $outletId);
        }
        $sales = $salesQuery->with('customer')->get();

        $cashSales = (float) $sales->filter(function ($s) {
            return in_array(strtolower(trim($s->payment_method ?? '')), ['tunai', 'cash']);
        })->sum('paid_amount');

        $nonCashSales = (float) $sales->filter(function ($s) {
            return !in_array(strtolower(trim($s->payment_method ?? '')), ['tunai', 'cash']);
        })->sum('paid_amount');

        $receivableSales = (float) $sales->sum('remaining_receivable');
        $totalSales = (float) $sales->sum('total');
        $salesCount = $sales->count();

        // 3. Fetch Digital Sales (Pulsa / PLN / PPOB) during this shift
        $digitalQuery = DigitalSale::where('date', '>=', $startTime)
            ->where('date', '<=', $now)
            ->where('status', 'SUKSES')
            ->with(['digitalProduct', 'cashAccount']);
        if ($outletId) {
            $digitalQuery->where('outlet_id', $outletId);
        }
        $digitalSales = $digitalQuery->get();

        $totalDigitalSales = (float) $digitalSales->sum('selling_price');
        $totalDigitalHpp = (float) $digitalSales->sum('hpp');
        $totalDigitalProfit = (float) $digitalSales->sum('profit_margin');
        $digitalSalesCount = $digitalSales->count();

        // 4. Fetch Agent Transfers (Uang masuk kasir dari transfer nasabah)
        $transferQuery = AgentTransfer::where('created_at', '>=', $startTime)
            ->where('created_at', '<=', $now)
            ->where('status', 'approved');
        if ($outletId) {
            $transferQuery->where('outlet_id', $outletId);
        }
        $transfers = $transferQuery->get();
        $totalTransferCash = (float) $transfers->sum('total_amount');
        $totalTransferFee = (float) $transfers->sum('admin_fee');
        $transferCount = $transfers->count();

        // 5. Fetch Tarik Tunai (Uang keluar dari laci ke nasabah)
        $withdrawQuery = CashTransaction::where('type', 'TRANSFER')
            ->where(function ($q) {
                $q->where('notes', 'like', 'Tarik Tunai%')
                  ->orWhere('transaction_number', 'like', 'TT%');
            })
            ->where('date', '>=', $startTime)
            ->where('date', '<=', $now);
        if ($outletId) {
            $withdrawQuery->where('outlet_id', $outletId);
        }
        $withdraws = $withdrawQuery->get();
        $totalWithdrawCash = (float) $withdraws->sum('amount');
        $totalWithdrawFee = (float) $withdraws->sum('admin_fee');
        $withdrawCount = $withdraws->count();

        // 6. Fetch Kas Keluar / Beban Operasional (Makan, Sampah, dll)
        $expenseQuery = CashTransaction::where('type', 'OUT')
            ->where('date', '>=', $startTime)
            ->where('date', '<=', $now);
        if ($outletId) {
            $expenseQuery->where('outlet_id', $outletId);
        }
        $expenses = $expenseQuery->get();
        $totalExpense = (float) $expenses->sum('amount');

        // 6b. Fetch Kas Masuk / Penambahan Modal Kas Retail (Koreksi + Kas Retail, Modal Masuk)
        $cashInQuery = CashTransaction::where('type', 'IN')
            ->where('date', '>=', $startTime)
            ->where('date', '<=', $now);
        if ($outletId) {
            $cashInQuery->where('outlet_id', $outletId);
        }
        $cashIns = $cashInQuery->get();
        $totalCashIn = (float) $cashIns->sum('amount');

        // 7. Balances of the 3 Cash categories (Per-outlet dedicated accounts)
        $cashRetailAcc = Account::getOutletCashRetailAccount($outletId);
        $saldoMultiAcc = Account::getOutletMultiAccount($outletId);
        $cashTransferAcc = Account::where('code', '1-1111')->first();
        $saldoBcaAcc = Account::where('code', '1-1113')->first();

        $saldoMultiBalance = (float) ($saldoMultiAcc?->current_balance ?? 0);
        $saldoBcaBalance = (float) ($saldoBcaAcc?->current_balance ?? 0);

        // Required drawer reserve (modal awal retail)
        $requiredReserve = 400000.0;

        if ($outletId) {
            // Per-outlet cash drawer calculation:
            // Drawer physical cash = starting retained modal + cash sales + cash in - operational expenses
            $modalAwalRetail = ($lastShift && $lastShift->cash_retail_retained !== null)
                ? (float) $lastShift->cash_retail_retained
                : $requiredReserve;

            $cashRetailBalance = $modalAwalRetail + $cashSales + $totalCashIn - $totalExpense;
            $recommendedDeposit = max(0.0, $cashRetailBalance - $requiredReserve);

            // Transfer cash on hand = starting transfer modal + incoming cash - withdrawals paid out
            // Saldo ini BISA bernilai minus jika uang keluar tarik tunai melebihi saldo kas transfer yang ada
            $modalAwalTransfer = ($lastShift && $lastShift->cash_transfer_retained !== null)
                ? (float) $lastShift->cash_transfer_retained
                : 0.0;

            $cashTransferBalance = $modalAwalTransfer + $totalTransferCash - $totalWithdrawCash;
        } else {
            // Global view across all stores from general ledger
            $cashRetailBalance = (float) ($cashRetailAcc?->current_balance ?? 0);
            $cashTransferBalance = (float) ($cashTransferAcc?->current_balance ?? 0);
            $recommendedDeposit = max(0.0, $cashRetailBalance - $requiredReserve);
        }

        // Duration string
        $diffMinutes = $startTime->diffInMinutes($now);
        $hours = floor($diffMinutes / 60);
        $mins = $diffMinutes % 60;
        $durationFormatted = ($hours > 0 ? "{$hours} jam " : "") . "{$mins} menit";

        // Active user and outlet
        $outlet = $outletId ? Outlet::find($outletId) : null;
        $activeUser = $userId ? User::find($userId) : null;

        return [
            'shift_number' => $shiftNumber,
            'start_time' => $startTime->toDateTimeString(),
            'start_time_formatted' => $startTime->translatedFormat('d M Y, H:i'),
            'end_time' => $now->toDateTimeString(),
            'end_time_formatted' => $now->translatedFormat('d M Y, H:i'),
            'duration' => $durationFormatted,
            'outlet_id' => $outletId,
            'outlet_name' => $outlet?->name ?? 'Semua Outlet / Pusat',
            'user_name' => $activeUser?->name ?? 'Kasir Operasional',
            
            // 3 Cash Categories Details
            'cash_retail' => [
                'name' => 'Cash Retail (Kas Laci Toko)',
                'code' => $cashRetailAcc->code,
                'balance' => $cashRetailBalance,
                'ledger_balance' => (float) $cashRetailAcc->current_balance,
                'required_reserve' => $requiredReserve,
                'recommended_deposit' => $recommendedDeposit,
                'shift_cash_sales' => $cashSales,
                'shift_cash_in' => $totalCashIn,
                'shift_cash_out' => $totalExpense,
                'starting_modal' => $modalAwalRetail ?? $requiredReserve,
            ],
            'cash_multi' => [
                'name' => 'Cash Multi (Produk Digital / Pulsa)',
                'code' => $saldoMultiAcc->code,
                'balance' => $saldoMultiBalance,
                'shift_sales' => $totalDigitalSales,
                'shift_profit' => $totalDigitalProfit,
                'shift_count' => $digitalSalesCount,
            ],
            'cash_transfer' => [
                'name' => 'Cash Transfer (Transfer Agen & Tarik Tunai)',
                'code' => '1-1111',
                'balance' => $cashTransferBalance,
                'shift_transfers_in' => $totalTransferCash,
                'shift_transfers_fee' => $totalTransferFee,
                'shift_transfers_count' => $transferCount,
                'shift_withdrawals_out' => $totalWithdrawCash,
                'shift_withdrawals_fee' => $totalWithdrawFee,
            ],

            // Shift operational summary
            'summary' => [
                'total_sales' => $totalSales,
                'cash_sales' => $cashSales,
                'non_cash_sales' => $nonCashSales,
                'receivable_sales' => $receivableSales,
                'sales_count' => $salesCount,
                'total_digital_sales' => $totalDigitalSales,
                'total_digital_profit' => $totalDigitalProfit,
                'digital_sales_count' => $digitalSalesCount,
                'total_transfer_cash' => $totalTransferCash,
                'total_transfer_fee' => $totalTransferFee,
                'transfer_count' => $transferCount,
                'total_withdraw_cash' => $totalWithdrawCash,
                'total_withdraw_fee' => $totalWithdrawFee,
                'total_expense' => $totalExpense,
                'total_transactions' => $salesCount + $digitalSalesCount + $transferCount,
            ],

            // Digital sales list snippet
            'digital_sales' => $digitalSales->take(15)->map(function ($ds) {
                return [
                    'id' => $ds->id,
                    'transaction_number' => $ds->transaction_number,
                    'product_name' => $ds->digitalProduct?->name ?? 'Produk Multi',
                    'customer_number' => $ds->customer_number,
                    'selling_price' => (float) $ds->selling_price,
                    'profit_margin' => (float) $ds->profit_margin,
                    'status' => $ds->status,
                    'time' => $ds->date ? Carbon::parse($ds->date)->format('H:i') : null,
                ];
            }),

            // Last closed shift info
            'last_shift' => $lastShift ? [
                'id' => $lastShift->id,
                'user_name' => $lastShift->user?->name ?? 'Kasir',
                'closed_at' => Carbon::parse($lastShift->end_time)->translatedFormat('d M Y, H:i'),
                'total_deposited' => (float) $lastShift->total_deposited,
                'cash_retail_retained' => (float) $lastShift->cash_retail_retained,
            ] : null,
        ];
    }

    /**
     * Close the current shift, record deposits across the 3 cash types,
     * post accounting entries, and create a ShiftLog.
     */
    public function closeShift(array $data, ?int $outletId, ?int $userId): ShiftLog
    {
        return DB::transaction(function () use ($data, $outletId, $userId) {
            $user = $userId ? User::find($userId) : auth()->user();
            $now = Carbon::now();

            // 1. Gather current shift summary snapshot
            $summary = $this->getShiftSummary($outletId, $userId);

            // 2. Parse deposits & retained modals for the 3 categories
            $cashRetailDeposit = (float) ($data['cash_retail_deposit'] ?? 0);
            $cashRetailRetained = (float) ($data['cash_retail_retained'] ?? 400000);

            $cashMultiDeposit = (float) ($data['cash_multi_deposit'] ?? 0);
            $cashMultiRetained = (float) ($data['cash_multi_retained'] ?? 0);

            $cashTransferDeposit = (float) ($data['cash_transfer_deposit'] ?? 0);
            $cashTransferRetained = (float) ($data['cash_transfer_retained'] ?? 0);

            $totalDeposited = $cashRetailDeposit + $cashMultiDeposit + $cashTransferDeposit;

            // Find Brankas / Pusat destination account
            $brangkasAcc = Account::where('name', 'like', '%BRANGKAS%')->first()
                ?: Account::where('code', '1-1113')->first()
                ?: Account::where('type', 'D')->first();

            $cashRetailAcc = Account::where('code', '1-1110')->first();
            $cashTransferAcc = Account::where('code', '1-1111')->first();

            // 3. Create accounting cash transactions for each deposit
            // Retail Cash Deposit
            if ($cashRetailDeposit > 0 && $cashRetailAcc && $brangkasAcc) {
                $trxNumber = $this->posService->generateTransactionNumber('ST-RET');
                $trx = CashTransaction::create([
                    'transaction_number' => $trxNumber,
                    'type' => 'TRANSFER',
                    'date' => $now,
                    'outlet_id' => $outletId,
                    'user_id' => $userId,
                    'debit_account_id' => $brangkasAcc->id,
                    'credit_account_id' => $cashRetailAcc->id,
                    'amount' => $cashRetailDeposit,
                    'admin_fee' => 0,
                    'notes' => "Setor Cash Retail Shift #{$summary['shift_number']} [{$user?->name}] - Sisa Modal Rp " . number_format($cashRetailRetained, 0, ',', '.'),
                ]);
                $this->accountingService->recordCashTransaction($trx);
            }

            // Transfer Cash Deposit
            if ($cashTransferDeposit > 0 && $cashTransferAcc && $brangkasAcc) {
                $trxNumber = $this->posService->generateTransactionNumber('ST-TRF');
                $trx = CashTransaction::create([
                    'transaction_number' => $trxNumber,
                    'type' => 'TRANSFER',
                    'date' => $now,
                    'outlet_id' => $outletId,
                    'user_id' => $userId,
                    'debit_account_id' => $brangkasAcc->id,
                    'credit_account_id' => $cashTransferAcc->id,
                    'amount' => $cashTransferDeposit,
                    'admin_fee' => 0,
                    'notes' => "Setor Cash Transfer Agen Shift #{$summary['shift_number']} [{$user?->name}] - Sisa Modal Rp " . number_format($cashTransferRetained, 0, ',', '.'),
                ]);
                $this->accountingService->recordCashTransaction($trx);
            }

            // Multi Cash Deposit
            if ($cashMultiDeposit > 0 && $cashRetailAcc && $brangkasAcc) {
                $trxNumber = $this->posService->generateTransactionNumber('ST-MLT');
                $trx = CashTransaction::create([
                    'transaction_number' => $trxNumber,
                    'type' => 'TRANSFER',
                    'date' => $now,
                    'outlet_id' => $outletId,
                    'user_id' => $userId,
                    'debit_account_id' => $brangkasAcc->id,
                    'credit_account_id' => $cashRetailAcc->id,
                    'amount' => $cashMultiDeposit,
                    'admin_fee' => 0,
                    'notes' => "Setor Cash Multi (Pulsa/PLN) Shift #{$summary['shift_number']} [{$user?->name}] - Sisa Modal Rp " . number_format($cashMultiRetained, 0, ',', '.'),
                ]);
                $this->accountingService->recordCashTransaction($trx);
            }

            // 4. Save ShiftLog Snapshot
            $shiftLog = ShiftLog::create([
                'outlet_id' => $outletId,
                'user_id' => $userId,
                'start_time' => $summary['start_time'],
                'end_time' => $now,
                'cash_retail_deposited' => $cashRetailDeposit,
                'cash_retail_retained' => $cashRetailRetained,
                'cash_multi_deposited' => $cashMultiDeposit,
                'cash_multi_retained' => $cashMultiRetained,
                'cash_transfer_deposited' => $cashTransferDeposit,
                'cash_transfer_retained' => $cashTransferRetained,
                'total_deposited' => $totalDeposited,
                'cash_sales' => $summary['summary']['cash_sales'],
                'non_cash_sales' => $summary['summary']['non_cash_sales'],
                'receivable_sales' => $summary['summary']['receivable_sales'],
                'digital_sales' => $summary['summary']['total_digital_sales'],
                'digital_profit' => $summary['summary']['total_digital_profit'],
                'transfer_cash' => $summary['summary']['total_transfer_cash'],
                'transfer_fee' => $summary['summary']['total_transfer_fee'],
                'withdraw_cash' => $summary['summary']['total_withdraw_cash'],
                'withdraw_fee' => $summary['summary']['total_withdraw_fee'],
                'expenses' => $summary['summary']['total_expense'],
                'transaction_count' => $summary['summary']['total_transactions'],
                'notes' => $data['notes'] ?? 'Ganti Shift / Tutup Kasir',
            ]);

            return $shiftLog;
        });
    }

    /**
     * Get shift history for an outlet or all outlets.
     */
    public function getShiftHistory(?int $outletId = null, int $limit = 30)
    {
        return ShiftLog::with(['outlet', 'user'])
            ->when($outletId, fn($q) => $q->where('outlet_id', $outletId))
            ->latest('end_time')
            ->paginate($limit);
    }
}
