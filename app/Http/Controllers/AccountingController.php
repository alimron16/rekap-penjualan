<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CashTransaction;
use App\Services\AccountingService;
use App\Services\PosTransactionService;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function __construct(
        protected PosTransactionService $posService,
        protected AccountingService $accountingService
    ) {}

    /**
     * Daftar Perkiraan (COA)
     */
    public function accounts(Request $request)
    {
        $perPage = $request->input('per_page', 'all');
        $query = Account::query();
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                  ->orWhere('name', 'like', "%$search%")
                  ->orWhere('group', 'like', "%$search%");
            });
        }
        $accounts = ($perPage === 'all' || empty($perPage))
            ? $query->orderBy('code')->paginate(10000)->withQueryString()
            : $query->orderBy('code')->paginate((int)$perPage)->withQueryString();

        return view('accounting.accounts', compact('accounts'));
    }

    public function storeAccount(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|unique:accounts,code|max:20',
            'name' => 'required|string|max:100',
            'group' => 'required|string|in:AKTIVA,KEWAJIBAN,MODAL,PENDAPATAN,HPP,BIAYA,PENDAPATAN LAIN,BIAYA LAIN',
            'type' => 'required|string|in:H,D,K',
            'initial_balance' => 'nullable|numeric|min:0',
        ]);

        $initial = (float) ($data['initial_balance'] ?? 0);

        Account::create([
            'code' => strtoupper($data['code']),
            'name' => strtoupper($data['name']),
            'group' => $data['group'],
            'type' => $data['type'],
            'initial_balance' => $initial,
            'current_balance' => $initial,
            'is_system_locked' => false,
        ]);

        return redirect()->route('accounting.accounts')->with('success', "Akun [{$data['code']} - {$data['name']}] berhasil ditambahkan!");
    }

    public function updateAccount(Request $request, Account $account)
    {
        if ($account->is_system_locked) {
            $data = $request->validate([
                'name' => 'required|string|max:100',
                'initial_balance' => 'nullable|numeric|min:0',
            ]);

            $oldInitial = (float) $account->initial_balance;
            $newInitial = (float) ($data['initial_balance'] ?? 0);
            $diff = $newInitial - $oldInitial;

            $account->update([
                'name' => strtoupper($data['name']),
                'initial_balance' => $newInitial,
                'current_balance' => $account->current_balance + $diff,
            ]);
        } else {
            $data = $request->validate([
                'code' => 'required|string|max:20|unique:accounts,code,' . $account->id,
                'name' => 'required|string|max:100',
                'group' => 'required|string|in:AKTIVA,KEWAJIBAN,MODAL,PENDAPATAN,HPP,BIAYA,PENDAPATAN LAIN,BIAYA LAIN',
                'type' => 'required|string|in:H,D,K',
                'initial_balance' => 'nullable|numeric|min:0',
            ]);

            $oldInitial = (float) $account->initial_balance;
            $newInitial = (float) ($data['initial_balance'] ?? 0);
            $diff = $newInitial - $oldInitial;

            $account->update([
                'code' => strtoupper($data['code']),
                'name' => strtoupper($data['name']),
                'group' => $data['group'],
                'type' => $data['type'],
                'initial_balance' => $newInitial,
                'current_balance' => $account->current_balance + $diff,
            ]);
        }

        return redirect()->route('accounting.accounts')->with('success', "Akun [{$account->code}] berhasil diperbarui!");
    }

    public function destroyAccount(Account $account)
    {
        if ($account->is_system_locked) {
            return redirect()->route('accounting.accounts')->with('error', "Akun sistem [{$account->code}] terkunci dan tidak boleh dihapus!");
        }

        $hasJournals = $account->journalLines()->exists();
        if ($hasJournals) {
            return redirect()->route('accounting.accounts')->with('error', "Akun [{$account->code}] tidak dapat dihapus karena sudah memiliki riwayat jurnal transaksi!");
        }

        $code = $account->code;
        $account->delete();

        return redirect()->route('accounting.accounts')->with('success', "Akun [{$code}] berhasil dihapus!");
    }

    /**
     * Kas Masuk
     */
    public function cashIn(Request $request)
    {
        $destAccounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();
        $sourceAccounts = Account::whereIn('group', ['PENDAPATAN', 'MODAL', 'KEWAJIBAN', 'PENDAPATAN LAIN'])->get();

        $perPage = $request->input('per_page', 15);
        $query = CashTransaction::where('type', 'IN')
            ->with(['debitAccount', 'creditAccount']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%$search%")
                  ->orWhere('notes', 'like', "%$search%")
                  ->orWhereHas('debitAccount', fn($aq) => $aq->where('name', 'like', "%$search%"))
                  ->orWhereHas('creditAccount', fn($aq) => $aq->where('name', 'like', "%$search%"));
            });
        }

        $history = ($perPage === 'all')
            ? $query->orderByDesc('date')->paginate(10000)->withQueryString()
            : $query->orderByDesc('date')->paginate((int)$perPage)->withQueryString();

        return view('accounting.cash_in', compact('destAccounts', 'sourceAccounts', 'history'));
    }

    public function storeCashIn(Request $request)
    {
        $data = $request->validate([
            'debit_account_id' => 'required|exists:accounts,id', // Penerima dana (Kas/Bank)
            'credit_account_id' => 'required|exists:accounts,id', // Sumber dana / Kategori
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
        ]);

        $trxNumber = $this->posService->generateTransactionNumber('KM');

        $trx = CashTransaction::create([
            'transaction_number' => $trxNumber,
            'type' => 'IN',
            'date' => now(),
            'debit_account_id' => $data['debit_account_id'],
            'credit_account_id' => $data['credit_account_id'],
            'amount' => $data['amount'],
            'admin_fee' => 0,
            'notes' => $data['notes'] ?? 'Kas Masuk',
        ]);

        $this->accountingService->recordCashTransaction($trx);

        return redirect()->route('accounting.cash_in')->with('success', 'Kas Masuk berhasil dicatat!');
    }

    /**
     * Kas Keluar
     */
    public function cashOut(Request $request)
    {
        $sourceAccounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();
        $expenseAccounts = Account::whereIn('group', ['BIAYA', 'KEWAJIBAN', 'BIAYA LAIN'])->whereIn('type', ['D'])->get();

        $perPage = $request->input('per_page', 15);
        $query = CashTransaction::where('type', 'OUT')
            ->with(['debitAccount', 'creditAccount']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%$search%")
                  ->orWhere('notes', 'like', "%$search%")
                  ->orWhereHas('debitAccount', fn($aq) => $aq->where('name', 'like', "%$search%"))
                  ->orWhereHas('creditAccount', fn($aq) => $aq->where('name', 'like', "%$search%"));
            });
        }

        $history = ($perPage === 'all')
            ? $query->orderByDesc('date')->paginate(10000)->withQueryString()
            : $query->orderByDesc('date')->paginate((int)$perPage)->withQueryString();

        return view('accounting.cash_out', compact('sourceAccounts', 'expenseAccounts', 'history'));
    }

    public function storeCashOut(Request $request)
    {
        $data = $request->validate([
            'credit_account_id' => 'required|exists:accounts,id', // Sumber dana (Kas/Bank berkurang)
            'debit_account_id' => 'required|exists:accounts,id', // Kategori Biaya/Pengeluaran (Beban bertambah)
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
        ]);

        $trxNumber = $this->posService->generateTransactionNumber('KK');

        $trx = CashTransaction::create([
            'transaction_number' => $trxNumber,
            'type' => 'OUT',
            'date' => now(),
            'debit_account_id' => $data['debit_account_id'],
            'credit_account_id' => $data['credit_account_id'],
            'amount' => $data['amount'],
            'admin_fee' => 0,
            'notes' => $data['notes'] ?? 'Kas Keluar',
        ]);

        $this->accountingService->recordCashTransaction($trx);

        return redirect()->route('accounting.cash_out')->with('success', 'Kas Keluar berhasil dicatat!');
    }

    /**
     * Kas Transfer (Transfer Tunai & Tarik Tunai Agen)
     */
    public function cashTransfer(Request $request)
    {
        $sourceAccounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();
        $destAccounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();

        $perPage = $request->input('per_page', 15);
        $query = CashTransaction::where('type', 'TRANSFER')
            ->with(['debitAccount', 'creditAccount']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%$search%")
                  ->orWhere('notes', 'like', "%$search%")
                  ->orWhereHas('debitAccount', fn($aq) => $aq->where('name', 'like', "%$search%"))
                  ->orWhereHas('creditAccount', fn($aq) => $aq->where('name', 'like', "%$search%"));
            });
        }

        $history = ($perPage === 'all')
            ? $query->orderByDesc('date')->paginate(10000)->withQueryString()
            : $query->orderByDesc('date')->paginate((int)$perPage)->withQueryString();

        return view('accounting.cash_transfer', compact('sourceAccounts', 'destAccounts', 'history'));
    }

    public function storeCashTransfer(Request $request)
    {
        $data = $request->validate([
            'credit_account_id' => 'required|exists:accounts,id', // Dari Akun
            'debit_account_id' => 'required|exists:accounts,id', // Transfer Ke (biasanya CASH TRANSFER)
            'amount' => 'required|numeric|min:1',
            'admin_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $this->posService->processKasTransfer($data);

        return redirect()->route('accounting.cash_transfer')->with('success', 'Kas Transfer berhasil diproses & Fee Admin diakui!');
    }

    public function updateCashIn(Request $request, CashTransaction $transaction)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'debit_account_id' => 'required|exists:accounts,id',
            'credit_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
        ]);

        $transaction->update([
            'date' => $data['date'],
            'debit_account_id' => $data['debit_account_id'],
            'credit_account_id' => $data['credit_account_id'],
            'amount' => $data['amount'],
            'notes' => $data['notes'] ?? 'Kas Masuk (Koreksi)',
        ]);

        \App\Models\JournalEntry::where('source_type', 'cash_transaction')
            ->where('source_id', $transaction->id)
            ->each(fn($j) => $j->delete());

        $this->accountingService->recordCashTransaction($transaction);

        return redirect()->route('accounting.cash_in')->with('success', "Transaksi [{$transaction->transaction_number}] berhasil diperbarui!");
    }

    public function destroyCashIn(CashTransaction $transaction)
    {
        $num = $transaction->transaction_number;
        \App\Models\JournalEntry::where('source_type', 'cash_transaction')
            ->where('source_id', $transaction->id)
            ->each(fn($j) => $j->delete());

        $transaction->delete();
        return redirect()->route('accounting.cash_in')->with('success', "Transaksi [{$num}] berhasil dibatalkan/dihapus!");
    }

    public function updateCashOut(Request $request, CashTransaction $transaction)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'credit_account_id' => 'required|exists:accounts,id',
            'debit_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
        ]);

        $transaction->update([
            'date' => $data['date'],
            'credit_account_id' => $data['credit_account_id'],
            'debit_account_id' => $data['debit_account_id'],
            'amount' => $data['amount'],
            'notes' => $data['notes'] ?? 'Kas Keluar (Koreksi)',
        ]);

        \App\Models\JournalEntry::where('source_type', 'cash_transaction')
            ->where('source_id', $transaction->id)
            ->each(fn($j) => $j->delete());

        $this->accountingService->recordCashTransaction($transaction);

        return redirect()->route('accounting.cash_out')->with('success', "Transaksi [{$transaction->transaction_number}] berhasil diperbarui!");
    }

    public function destroyCashOut(CashTransaction $transaction)
    {
        $num = $transaction->transaction_number;
        \App\Models\JournalEntry::where('source_type', 'cash_transaction')
            ->where('source_id', $transaction->id)
            ->each(fn($j) => $j->delete());

        $transaction->delete();
        return redirect()->route('accounting.cash_out')->with('success', "Transaksi [{$num}] berhasil dibatalkan/dihapus!");
    }

    public function updateCashTransfer(Request $request, CashTransaction $transaction)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'credit_account_id' => 'required|exists:accounts,id',
            'debit_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1',
            'admin_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $transaction->update([
            'date' => $data['date'],
            'credit_account_id' => $data['credit_account_id'],
            'debit_account_id' => $data['debit_account_id'],
            'amount' => $data['amount'],
            'admin_fee' => $data['admin_fee'] ?? 0,
            'notes' => $data['notes'] ?? 'Kas Transfer (Koreksi)',
        ]);

        \App\Models\JournalEntry::where('source_type', 'cash_transaction')
            ->where('source_id', $transaction->id)
            ->each(fn($j) => $j->delete());

        $this->accountingService->recordCashTransaction($transaction);

        return redirect()->route('accounting.cash_transfer')->with('success', "Kas Transfer [{$transaction->transaction_number}] berhasil diperbarui!");
    }

    public function destroyCashTransfer(CashTransaction $transaction)
    {
        $num = $transaction->transaction_number;
        \App\Models\JournalEntry::where('source_type', 'cash_transaction')
            ->where('source_id', $transaction->id)
            ->each(fn($j) => $j->delete());

        $transaction->delete();
        return redirect()->route('accounting.cash_transfer')->with('success', "Kas Transfer [{$num}] berhasil dibatalkan/dihapus!");
    }
}
