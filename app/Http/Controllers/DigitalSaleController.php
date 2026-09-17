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

        $products = DigitalProduct::where('status', 'OPEN')->orderBy('name')->get();
        $depositAccounts = Account::whereIn('code', ['1-1131', '1-1113', '1-1120'])->get(); // SALDO MULTI, SALDO BCA, SALDO BRI
        $cashAccounts = Account::whereIn('code', ['1-1110', '1-1112'])->get(); // CASH RETAIL, CASH MULTI

        $saldoMulti = Account::where('code', '1-1131')->value('current_balance') ?? 0;
        $cashRetail = Account::where('code', '1-1110')->value('current_balance') ?? 0;

        $history = DigitalSale::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
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
            'endDate'
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
        ]);

        try {
            $this->posService->processDigitalSale($data);
            return redirect()->route('digital.index')->with('success', 'Transaksi elektrik berhasil diproses!');
        } catch (Exception $e) {
            return redirect()->route('digital.index')->with('error', $e->getMessage());
        }
    }

    public function reverse(DigitalSale $digitalSale)
    {
        try {
            $this->posService->reverseDigitalSale($digitalSale);
            return redirect()->route('digital.index')->with('success', 'Transaksi berhasil diubah menjadi GAGAL dan saldo telah dikembalikan!');
        } catch (Exception $e) {
            return redirect()->route('digital.index')->with('error', $e->getMessage());
        }
    }

    public function topupMulti(Request $request)
    {
        $data = $request->validate([
            'source_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1000',
            'notes' => 'nullable|string',
        ]);

        $multiAccount = Account::where('code', '1-1131')->firstOrFail();

        try {
            $trxNumber = $this->posService->generateTransactionNumber('TP');

            $trx = \App\Models\CashTransaction::create([
                'transaction_number' => $trxNumber,
                'type' => 'OUT',
                'date' => now(),
                'debit_account_id' => $multiAccount->id, // Saldo Multi bertambah
                'credit_account_id' => $data['source_account_id'], // Kas / Bank berkurang
                'amount' => $data['amount'],
                'admin_fee' => 0,
                'notes' => $data['notes'] ?? 'Top Up Saldo Multi Server',
            ]);

            app(\App\Services\AccountingService::class)->recordCashTransaction($trx);

            return redirect()->route('digital.index')->with('success', 'Top Up Saldo Multi sebesar Rp ' . number_format($data['amount'], 0, ',', '.') . ' berhasil!');
        } catch (Exception $e) {
            return redirect()->route('digital.index')->with('error', 'Gagal top up saldo multi: ' . $e->getMessage());
        }
    }
}
