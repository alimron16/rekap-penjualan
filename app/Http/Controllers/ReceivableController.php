<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\AccountingService;
use App\Services\PosTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivableController extends Controller
{
    public function __construct(
        protected PosTransactionService $posService,
        protected AccountingService $accountingService
    ) {}

    /**
     * Pembayaran Piutang
     */
    public function payments(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $customers = Customer::orderBy('name')->get();
        $accounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();

        $unpaidSales = Sale::where('status', 'BELUM LUNAS')
            ->with('customer')
            ->orderByDesc('date')
            ->get();

        $perPage = $request->input('per_page', 20);
        $paymentsQuery = ReceivablePayment::whereBetween('date', [$startDate, $endDate])
            ->with(['customer', 'account']);

        if ($search = $request->input('search')) {
            $paymentsQuery->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%$search%")
                  ->orWhere('notes', 'like', "%$search%")
                  ->orWhereHas('customer', fn($sq) => $sq->where('name', 'like', "%$search%"));
            });
        }

        $payments = ($perPage === 'all')
            ? $paymentsQuery->orderByDesc('date')->paginate(10000)->withQueryString()
            : $paymentsQuery->orderByDesc('date')->paginate((int)$perPage)->withQueryString();

        return view('receivable.payments', compact('customers', 'accounts', 'unpaidSales', 'payments', 'startDate', 'endDate'));
    }

    public function storePayment(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'sale_id' => 'nullable|exists:sales,id',
            'account_id' => 'required|exists:accounts,id',
            'amount_paid' => 'required|numeric|min:1',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $paymentNumber = $this->posService->generateTransactionNumber('PR-BAYAR');

        $payment = ReceivablePayment::create([
            'payment_number' => $paymentNumber,
            'date' => $data['date'],
            'customer_id' => $data['customer_id'],
            'sale_id' => $data['sale_id'] ?? null,
            'account_id' => $data['account_id'],
            'discount' => (float) ($data['discount'] ?? 0),
            'amount_paid' => (float) $data['amount_paid'],
            'notes' => $data['notes'] ?? 'Pembayaran Piutang Pelanggan',
        ]);

        if (!empty($data['sale_id'])) {
            $sale = Sale::find($data['sale_id']);
            if ($sale) {
                $totalPaid = (float)$data['amount_paid'] + (float)($data['discount'] ?? 0);
                $sale->remaining_receivable = max(0, $sale->remaining_receivable - $totalPaid);
                $sale->paid_amount += (float)$data['amount_paid'];
                if ($sale->remaining_receivable <= 0) {
                    $sale->status = 'LUNAS';
                }
                $sale->save();
            }
        }

        $this->accountingService->recordReceivablePayment($payment);

        return redirect()->route('receivable.payments')->with('success', 'Pembayaran piutang berhasil dicatat!');
    }

    /**
     * Reture Penjualan
     */
    public function returns(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $query = SaleReturn::with(['originalSale', 'customer', 'product', 'refundAccount']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%$search%")
                  ->orWhere('reason', 'like', "%$search%")
                  ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%$search%"))
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%$search%"));
            });
        }

        $returns = ($perPage === 'all')
            ? $query->orderByDesc('created_at')->paginate(10000)->withQueryString()
            : $query->orderByDesc('created_at')->paginate((int)$perPage)->withQueryString();

        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $accounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();
        $outlets = \App\Models\Outlet::where('status', 'active')->orderBy('name')->get();

        return view('receivable.returns', compact('returns', 'products', 'customers', 'accounts', 'outlets'));
    }

    public function storeReturn(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'original_sale_id' => 'nullable|exists:sales,id',
            'customer_id' => 'nullable|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:1',
            'amount' => 'required|numeric|min:0',
            'refund_account_id' => 'nullable|exists:accounts,id',
            'outlet_id' => 'nullable|exists:outlets,id',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $data['account_id'] = $data['refund_account_id'] ?? null;
        $data['refund_amount'] = $data['amount'];
        $data['outlet_id'] = $data['outlet_id'] ?? auth()->user()?->outlet_id;

        $this->posService->processSaleReturn($data);

        return redirect()->route('receivable.returns')->with('success', 'Retur penjualan berhasil disimpan, stok barang toko bertambah dan kas telah disesuaikan!');
    }

    public function updatePayment(Request $request, ReceivablePayment $payment)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'amount_paid' => 'required|numeric|min:1',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($payment, $data) {
            if ($payment->sale_id) {
                $oldSale = Sale::find($payment->sale_id);
                if ($oldSale) {
                    $oldTotal = (float)$payment->amount_paid + (float)$payment->discount;
                    $oldSale->remaining_receivable += $oldTotal;
                    $oldSale->paid_amount = max(0, $oldSale->paid_amount - (float)$payment->amount_paid);
                    $oldSale->status = $oldSale->remaining_receivable > 0 ? 'BELUM LUNAS' : 'LUNAS';
                    $oldSale->save();
                }
            }

            $newAmount = (float) $data['amount_paid'];
            $newDiscount = (float) ($data['discount'] ?? 0);

            if ($payment->sale_id) {
                $sale = Sale::find($payment->sale_id);
                if ($sale) {
                    $newTotal = $newAmount + $newDiscount;
                    $sale->remaining_receivable = max(0, $sale->remaining_receivable - $newTotal);
                    $sale->paid_amount += $newAmount;
                    $sale->status = $sale->remaining_receivable <= 0 ? 'LUNAS' : 'BELUM LUNAS';
                    $sale->save();
                }
            }

            $payment->update([
                'date' => $data['date'],
                'account_id' => $data['account_id'],
                'amount_paid' => $newAmount,
                'discount' => $newDiscount,
                'notes' => $data['notes'] ?? 'Pembayaran Piutang (Koreksi)',
            ]);

            \App\Models\JournalEntry::where('source_type', 'receivable_payment')
                ->where('source_id', $payment->id)
                ->each(fn($j) => $j->delete());

            $this->accountingService->recordReceivablePayment($payment);
        });

        return redirect()->route('receivable.payments')->with('success', "Pembayaran piutang [{$payment->payment_number}] berhasil diperbarui!");
    }

    public function destroyPayment(ReceivablePayment $payment)
    {
        $num = $payment->payment_number;
        DB::transaction(function () use ($payment) {
            if ($payment->sale_id) {
                $sale = Sale::find($payment->sale_id);
                if ($sale) {
                    $total = (float)$payment->amount_paid + (float)$payment->discount;
                    $sale->remaining_receivable += $total;
                    $sale->paid_amount = max(0, $sale->paid_amount - (float)$payment->amount_paid);
                    $sale->status = $sale->remaining_receivable > 0 ? 'BELUM LUNAS' : 'LUNAS';
                    $sale->save();
                }
            }

            \App\Models\JournalEntry::where('source_type', 'receivable_payment')
                ->where('source_id', $payment->id)
                ->each(fn($j) => $j->delete());

            $payment->delete();
        });

        return redirect()->route('receivable.payments')->with('success', "Pembayaran piutang [{$num}] berhasil dibatalkan dan saldo piutang dikembalikan!");
    }

    public function destroyReturn(SaleReturn $itemReturn)
    {
        $num = $itemReturn->return_number;
        DB::transaction(function () use ($itemReturn) {
            $product = $itemReturn->product;
            if ($product) {
                $product->decrement('stock', (float)$itemReturn->qty);
            }
            $itemReturn->delete();
        });

        return redirect()->route('receivable.returns')->with('success', "Retur [{$num}] berhasil dibatalkan dan stok dikembalikan!");
    }
}
