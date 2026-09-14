<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\DebtPayment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\AccountingService;
use App\Services\PosTransactionService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(
        protected PosTransactionService $posService,
        protected AccountingService $accountingService
    ) {}

    /**
     * Daftar Pembelian (Dual Pane: Input on left, History on right)
     */
    public function index(Request $request)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();
        $accounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();

        $perPage = $request->input('per_page', 15);
        $query = Purchase::with(['supplier', 'items.product']);
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%$search%"));
            });
        }

        $history = ($perPage === 'all')
            ? $query->orderByDesc('created_at')->paginate(10000)->withQueryString()
            : $query->orderByDesc('created_at')->paginate((int)$perPage)->withQueryString();

        return view('purchase.index', compact('suppliers', 'products', 'accounts', 'history'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'payment_method' => 'required|string',
            'account_id' => 'nullable|exists:accounts,id',
            'paid_amount' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.buy_price' => 'required|numeric|min:0',
        ]);

        $this->posService->processPurchase($data);

        return redirect()->route('purchase.index')->with('success', 'Pembelian berhasil diproses & HPP otomatis dihitung!');
    }

    /**
     * Pembayaran Hutang
     */
    public function debtPayments(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $suppliers = Supplier::orderBy('name')->get();
        $accounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();

        // Outstanding purchases
        $unpaidPurchases = Purchase::where('status', 'BELUM LUNAS')
            ->with('supplier')
            ->orderByDesc('date')
            ->get();

        $perPage = $request->input('per_page', 20);
        $paymentsQuery = DebtPayment::whereBetween('date', [$startDate, $endDate])
            ->with(['supplier', 'account']);

        if ($search = $request->input('search')) {
            $paymentsQuery->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%$search%")
                  ->orWhere('notes', 'like', "%$search%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%$search%"));
            });
        }

        $payments = ($perPage === 'all')
            ? $paymentsQuery->orderByDesc('date')->paginate(10000)->withQueryString()
            : $paymentsQuery->orderByDesc('date')->paginate((int)$perPage)->withQueryString();

        return view('purchase.debt_payments', compact('suppliers', 'accounts', 'unpaidPurchases', 'payments', 'startDate', 'endDate'));
    }

    public function storeDebtPayment(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'account_id' => 'required|exists:accounts,id',
            'amount_paid' => 'required|numeric|min:1',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $paymentNumber = $this->posService->generateTransactionNumber('PBH');

        $payment = DebtPayment::create([
            'payment_number' => $paymentNumber,
            'date' => $data['date'],
            'supplier_id' => $data['supplier_id'],
            'purchase_id' => $data['purchase_id'] ?? null,
            'account_id' => $data['account_id'],
            'discount' => (float) ($data['discount'] ?? 0),
            'amount_paid' => (float) $data['amount_paid'],
            'notes' => $data['notes'] ?? 'Pembayaran Hutang Supplier',
        ]);

        // If specific purchase is linked, reduce remaining debt
        if (!empty($data['purchase_id'])) {
            $purchase = Purchase::find($data['purchase_id']);
            if ($purchase) {
                $totalPayment = (float)$data['amount_paid'] + (float)($data['discount'] ?? 0);
                $purchase->remaining_debt = max(0, $purchase->remaining_debt - $totalPayment);
                $purchase->paid_amount += (float)$data['amount_paid'];
                if ($purchase->remaining_debt <= 0) {
                    $purchase->status = 'LUNAS';
                }
                $purchase->save();
            }
        }

        $this->accountingService->recordDebtPayment($payment);

        return redirect()->route('purchase.debt_payments')->with('success', 'Pembayaran hutang berhasil dicatat!');
    }

    public function updateDebtPayment(Request $request, DebtPayment $payment)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'amount_paid' => 'required|numeric|min:1',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $data) {
            // Revert previous debt reduction on purchase
            if ($payment->purchase_id) {
                $oldPurchase = Purchase::find($payment->purchase_id);
                if ($oldPurchase) {
                    $oldTotal = (float)$payment->amount_paid + (float)$payment->discount;
                    $oldPurchase->remaining_debt += $oldTotal;
                    $oldPurchase->paid_amount = max(0, $oldPurchase->paid_amount - (float)$payment->amount_paid);
                    $oldPurchase->status = $oldPurchase->remaining_debt > 0 ? 'BELUM LUNAS' : 'LUNAS';
                    $oldPurchase->save();
                }
            }

            $newAmount = (float) $data['amount_paid'];
            $newDiscount = (float) ($data['discount'] ?? 0);

            // Apply new debt reduction
            if ($payment->purchase_id) {
                $purchase = Purchase::find($payment->purchase_id);
                if ($purchase) {
                    $newTotal = $newAmount + $newDiscount;
                    $purchase->remaining_debt = max(0, $purchase->remaining_debt - $newTotal);
                    $purchase->paid_amount += $newAmount;
                    $purchase->status = $purchase->remaining_debt <= 0 ? 'LUNAS' : 'BELUM LUNAS';
                    $purchase->save();
                }
            }

            $payment->update([
                'date' => $data['date'],
                'account_id' => $data['account_id'],
                'amount_paid' => $newAmount,
                'discount' => $newDiscount,
                'notes' => $data['notes'] ?? 'Pembayaran Hutang (Koreksi)',
            ]);

            \App\Models\JournalEntry::where('source_type', 'debt_payment')
                ->where('source_id', $payment->id)
                ->each(fn($j) => $j->delete());

            $this->accountingService->recordDebtPayment($payment);
        });

        return redirect()->route('purchase.debt_payments')->with('success', "Pembayaran hutang [{$payment->payment_number}] berhasil diperbarui!");
    }

    public function destroyDebtPayment(DebtPayment $payment)
    {
        $num = $payment->payment_number;
        \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
            if ($payment->purchase_id) {
                $purchase = Purchase::find($payment->purchase_id);
                if ($purchase) {
                    $total = (float)$payment->amount_paid + (float)$payment->discount;
                    $purchase->remaining_debt += $total;
                    $purchase->paid_amount = max(0, $purchase->paid_amount - (float)$payment->amount_paid);
                    $purchase->status = $purchase->remaining_debt > 0 ? 'BELUM LUNAS' : 'LUNAS';
                    $purchase->save();
                }
            }

            \App\Models\JournalEntry::where('source_type', 'debt_payment')
                ->where('source_id', $payment->id)
                ->each(fn($j) => $j->delete());

            $payment->delete();
        });

        return redirect()->route('purchase.debt_payments')->with('success', "Pembayaran hutang [{$num}] berhasil dibatalkan dan saldo hutang dikembalikan!");
    }
}
