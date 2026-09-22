<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\DigitalProduct;
use App\Models\DigitalSale;
use App\Services\PosTransactionService;
use Exception;
use Illuminate\Http\Request;

class DigitalSaleController extends Controller
{
    public function __construct(
        protected PosTransactionService $posService
    ) {}

    public function index(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $outlets = \App\Models\Outlet::where('status', 'active')->orderBy('name')->get();

        $selectedOutletId = $user->outlet_id;
        if ($isAdmin && $request->filled('outlet_id')) {
            $selectedOutletId = (int) $request->outlet_id;
        } elseif (!$selectedOutletId && $outlets->isNotEmpty()) {
            $selectedOutletId = $outlets->first()->id;
        }

        $products = DigitalProduct::where('status', 'OPEN')
            ->when($selectedOutletId, fn($q) => $q->where(fn($s) => $s->where('outlet_id', $selectedOutletId)->orWhereNull('outlet_id')))
            ->orderBy('name')
            ->get();

        // Dedicated per-outlet multi & cash accounts
        $multiAccount = Account::getOutletMultiAccount($selectedOutletId);
        $cashRetailAccount = Account::getOutletCashRetailAccount($selectedOutletId);

        $depositAccounts = Account::where('group', 'AKTIVA')
            ->where('type', 'D')
            ->where(function ($q) use ($selectedOutletId) {
                $q->where('outlet_id', $selectedOutletId)
                  ->orWhere(fn($s) => $s->whereNull('outlet_id')->whereIn('code', ['1-1113', '1-1120']));
            })
            ->where(function ($s) {
                $s->where('code', 'like', '1-1131%')
                  ->orWhere('code', 'like', '1-1113%')
                  ->orWhere('code', 'like', '1-1120%');
            })
            ->orderByRaw("CASE WHEN code LIKE '1-1131%' THEN 0 ELSE 1 END")
            ->get();

        $cashAccounts = Account::where(function ($q) use ($selectedOutletId) {
                $q->where('outlet_id', $selectedOutletId)
                  ->orWhereNull('outlet_id');
            })
            ->where(function ($s) {
                $s->where('code', 'like', '1-1110%')
                  ->orWhereIn('code', ['1-1113', '1-1111', '1-1112', '1-1120', '1-1121']);
            })
            ->get();

        $saldoMulti = (float) $multiAccount->current_balance;
        $cashRetail = (float) $cashRetailAccount->current_balance;

        $history = DigitalSale::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->when($selectedOutletId, fn($q) => $q->where('outlet_id', $selectedOutletId))
            ->with(['digitalProduct', 'depositAccount', 'cashAccount'])
            ->orderByDesc('date')
            ->paginate(20);

        return view('digital.index', compact(
            'products',
            'depositAccounts',
            'cashAccounts',
            'saldoMulti',
            'cashRetail',
            'history',
            'startDate',
            'endDate',
            'outlets',
            'selectedOutletId',
            'isAdmin'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'digital_product_id' => 'required|exists:digital_products,id',
            'customer_number' => 'required|string',
            'deposit_account_id' => 'required|exists:accounts,id',
            'cash_account_id' => 'required|exists:accounts,id',
            'selling_price' => 'nullable|numeric|min:0',
            'hpp' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'outlet_id' => 'nullable|exists:outlets,id',
        ]);

        try {
            $user = auth()->user();
            $data['outlet_id'] = ($user->isAdmin() && $request->filled('outlet_id'))
                ? (int) $request->outlet_id
                : ($user->outlet_id ?? \App\Models\Outlet::where('status', 'active')->value('id'));
            $data['user_id'] = $user->id;
            $this->posService->processDigitalSale($data);
            return redirect()->route('digital.index', ['outlet_id' => $data['outlet_id']])->with('success', 'Transaksi elektrik berhasil diproses!');
        } catch (Exception $e) {
            return redirect()->route('digital.index')->with('error', $e->getMessage());
        }
    }

    public function reverse(DigitalSale $digitalSale)
    {
        try {
            $this->posService->reverseDigitalSale($digitalSale);
            return redirect()->route('digital.index', ['outlet_id' => $digitalSale->outlet_id])->with('success', 'Transaksi berhasil diubah menjadi GAGAL dan saldo telah dikembalikan!');
        } catch (Exception $e) {
            return redirect()->route('digital.index')->with('error', $e->getMessage());
        }
    }

    public function topupMulti(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        $data = $request->validate([
            'source_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1000',
            'notes' => 'nullable|string',
            'outlet_id' => 'nullable|exists:outlets,id',
        ]);

        $outletId = ($isAdmin && $request->filled('outlet_id'))
            ? (int) $request->outlet_id
            : ($user->outlet_id ?? $request->input('outlet_id') ?? \App\Models\Outlet::where('status', 'active')->orderBy('id')->value('id'));

        $multiAccount = Account::getOutletMultiAccount($outletId);
        $outlet = \App\Models\Outlet::find($outletId);

        try {
            $trxNumber = $this->posService->generateTransactionNumber('TP');

            $trx = \App\Models\CashTransaction::create([
                'transaction_number' => $trxNumber,
                'type' => 'OUT',
                'date' => now(),
                'outlet_id' => $outletId,
                'user_id' => $user->id,
                'debit_account_id' => $multiAccount->id, // Saldo Multi toko bertambah
                'credit_account_id' => $data['source_account_id'], // Kas / Bank berkurang
                'amount' => $data['amount'],
                'admin_fee' => 0,
                'notes' => $data['notes'] ?? ('Top Up Saldo Multi [' . ($outlet?->name ?? 'Cabang ' . $outletId) . ']'),
            ]);

            app(\App\Services\AccountingService::class)->recordCashTransaction($trx);

            return redirect()->route('digital.index', ['outlet_id' => $outletId])
                ->with('success', 'Top Up Saldo Multi [' . $multiAccount->name . '] sebesar Rp ' . number_format($data['amount'], 0, ',', '.') . ' berhasil!');
        } catch (Exception $e) {
            return redirect()->route('digital.index', ['outlet_id' => $outletId ?? null])
                ->with('error', 'Gagal top up saldo multi: ' . $e->getMessage());
        }
    }
}
