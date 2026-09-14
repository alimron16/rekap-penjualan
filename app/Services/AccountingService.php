<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\DebtPayment;
use App\Models\DigitalSale;
use App\Models\InventoryAdjustment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Purchase;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\SaleReturn;
use Exception;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Create a balanced journal entry with lines and update account balances.
     *
     * @param array $lines Array of ['account_id' => int, 'debit' => float, 'credit' => float, 'memo' => string]
     */
    public function createJournalEntry(
        string $entryNumber,
        string $entryDate,
        ?string $refType = null,
        ?int $refId = null,
        ?string $description = null,
        array $lines = []
    ): JournalEntry {
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        // Allow max rounding tolerance of 0.01
        if (abs($totalDebit - $totalCredit) > 0.05) {
            throw new Exception("Entri jurnal tidak seimbang! Total Debit: {$totalDebit}, Total Kredit: {$totalCredit}");
        }

        $year = (int) date('Y', strtotime($entryDate));

        return DB::transaction(function () use ($entryNumber, $entryDate, $refType, $refId, $description, $year, $lines) {
            $journal = JournalEntry::create([
                'entry_number' => $entryNumber,
                'entry_date' => $entryDate,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'description' => $description,
                'fiscal_year' => $year,
            ]);

            foreach ($lines as $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                if ($debit == 0 && $credit == 0) {
                    continue;
                }

                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $line['account_id'],
                    'debit' => $debit,
                    'credit' => $credit,
                    'memo' => $line['memo'] ?? null,
                ]);

                // Update account balance
                $account = Account::lockForUpdate()->find($line['account_id']);
                if ($account) {
                    // Normal balance: D (Debet), K (Kredit)
                    if ($account->type === 'D') {
                        $account->current_balance += ($debit - $credit);
                    } elseif ($account->type === 'K') {
                        $account->current_balance += ($credit - $debit);
                    } else {
                        // Header or general
                        $account->current_balance += ($debit - $credit);
                    }
                    $account->save();
                }
            }

            return $journal;
        });
    }

    /**
     * Record accounting entries for a Sale (Retail or Wholesale).
     *
     * 1. Cash/Bank/Piutang (D) vs Pendapatan Retail/Grosir (K)
     * 2. HPP Penjualan (D) vs Persediaan Barang (K)
     */
    public function recordSale(Sale $sale): JournalEntry
    {
        $lines = [];

        // Determine destination cash account or receivable
        $cashAcc = $sale->account_id 
            ? Account::find($sale->account_id) 
            : Account::where('code', '1-1110')->first(); // default CASH RETAIL

        $piutangAcc = Account::where('code', '1-1210')->first(); // PIUTANG PENJUALAN
        $salesAcc = $sale->sale_type === 'grosir'
            ? Account::where('code', '4-1100')->first() // PENDAPATAN GROSIR
            : Account::where('code', '4-1000')->first(); // PENDAPATAN RETAIL

        $hppAcc = Account::where('code', '5-1000')->first(); // HPP PENJUALAN
        $inventoryAcc = Account::where('code', '1-2010')->first(); // PERSEDIAAN BARANG

        // 1. Cash received
        if ($sale->paid_amount > 0 && $cashAcc) {
            $lines[] = [
                'account_id' => $cashAcc->id,
                'debit' => $sale->paid_amount,
                'credit' => 0,
                'memo' => "Penerimaan kas penjualan {$sale->invoice_number}",
            ];
        }

        // 2. Remaining receivable
        if ($sale->remaining_receivable > 0 && $piutangAcc) {
            $lines[] = [
                'account_id' => $piutangAcc->id,
                'debit' => $sale->remaining_receivable,
                'credit' => 0,
                'memo' => "Piutang penjualan {$sale->invoice_number}",
            ];
        }

        // 3. Sales revenue
        if ($salesAcc) {
            $lines[] = [
                'account_id' => $salesAcc->id,
                'debit' => 0,
                'credit' => $sale->total,
                'memo' => "Pendapatan penjualan {$sale->invoice_number}",
            ];
        }

        // 4. COGS (HPP) entry
        $totalHpp = $sale->total_hpp;
        if ($totalHpp > 0 && $hppAcc && $inventoryAcc) {
            $lines[] = [
                'account_id' => $hppAcc->id,
                'debit' => $totalHpp,
                'credit' => 0,
                'memo' => "Beban HPP penjualan {$sale->invoice_number}",
            ];
            $lines[] = [
                'account_id' => $inventoryAcc->id,
                'debit' => 0,
                'credit' => $totalHpp,
                'memo' => "Pengurangan persediaan penjualan {$sale->invoice_number}",
            ];
        }

        return $this->createJournalEntry(
            "JRN-{$sale->invoice_number}",
            $sale->date->format('Y-m-d'),
            'sale',
            $sale->id,
            "Penjualan {$sale->sale_type} {$sale->invoice_number}",
            $lines
        );
    }

    /**
     * Record accounting entries for a Digital Sale (Pulsa / Token Multi).
     *
     * 1. Cash (D) = Selling Price
     * 2. Saldo Multi / Modal Server (K) = HPP
     * 3. Pendapatan Multi (K) = Profit Margin (or full revenue vs full HPP)
     */
    public function recordDigitalSale(DigitalSale $sale): JournalEntry
    {
        $cashAcc = Account::find($sale->cash_account_id) ?: Account::where('code', '1-1110')->first();
        $depositAcc = Account::find($sale->deposit_account_id) ?: Account::where('code', '1-1131')->first(); // SALDO MULTI
        $multiRevenueAcc = Account::where('code', '4-1300')->first(); // PENDAPATAN MULTI
        $multiHppAcc = Account::where('code', '5-1100')->first(); // HPP MULTI

        $lines = [];

        // Cash received from customer
        $lines[] = [
            'account_id' => $cashAcc->id,
            'debit' => $sale->selling_price,
            'credit' => 0,
            'memo' => "Penerimaan penjualan elektrik {$sale->transaction_number}",
        ];

        // Revenue multi
        $lines[] = [
            'account_id' => $multiRevenueAcc->id,
            'debit' => 0,
            'credit' => $sale->selling_price,
            'memo' => "Pendapatan elektrik {$sale->transaction_number}",
        ];

        // COGS Multi
        $lines[] = [
            'account_id' => $multiHppAcc->id,
            'debit' => $sale->hpp,
            'credit' => 0,
            'memo' => "HPP elektrik {$sale->transaction_number}",
        ];

        // Reduction from Deposit modal server
        $lines[] = [
            'account_id' => $depositAcc->id,
            'debit' => 0,
            'credit' => $sale->hpp,
            'memo' => "Potong saldo deposit {$sale->transaction_number}",
        ];

        return $this->createJournalEntry(
            "JRN-{$sale->transaction_number}",
            $sale->date->format('Y-m-d'),
            'digital_sale',
            $sale->id,
            "Penjualan Elektrik {$sale->transaction_number} - {$sale->digitalProduct->name}",
            $lines
        );
    }

    /**
     * Record reversal for a failed Digital Sale.
     */
    public function recordDigitalSaleReversal(DigitalSale $sale): JournalEntry
    {
        $cashAcc = Account::find($sale->cash_account_id) ?: Account::where('code', '1-1110')->first();
        $depositAcc = Account::find($sale->deposit_account_id) ?: Account::where('code', '1-1131')->first();
        $multiRevenueAcc = Account::where('code', '4-1300')->first();
        $multiHppAcc = Account::where('code', '5-1100')->first();

        $lines = [
            [
                'account_id' => $multiRevenueAcc->id,
                'debit' => $sale->selling_price,
                'credit' => 0,
                'memo' => "Reversal pendapatan elektrik gagal {$sale->transaction_number}",
            ],
            [
                'account_id' => $cashAcc->id,
                'debit' => 0,
                'credit' => $sale->selling_price,
                'memo' => "Reversal kas kasir elektrik gagal {$sale->transaction_number}",
            ],
            [
                'account_id' => $depositAcc->id,
                'debit' => $sale->hpp,
                'credit' => 0,
                'memo' => "Pengembalian saldo deposit elektrik gagal {$sale->transaction_number}",
            ],
            [
                'account_id' => $multiHppAcc->id,
                'debit' => 0,
                'credit' => $sale->hpp,
                'memo' => "Reversal HPP elektrik gagal {$sale->transaction_number}",
            ],
        ];

        return $this->createJournalEntry(
            "REV-{$sale->transaction_number}",
            now()->format('Y-m-d'),
            'digital_sale_reversal',
            $sale->id,
            "Reversal GAGAL Transaksi Elektrik {$sale->transaction_number}",
            $lines
        );
    }

    /**
     * Record accounting entries for Purchase.
     */
    public function recordPurchase(Purchase $purchase): JournalEntry
    {
        $inventoryAcc = Account::where('code', '1-2010')->first(); // PERSEDIAAN BARANG
        $cashAcc = $purchase->account_id 
            ? Account::find($purchase->account_id) 
            : Account::where('code', '1-1110')->first();
        $payableAcc = Account::where('code', '2-1101')->first(); // HUTANG PEMBELIAN

        $lines = [];

        // Inventory addition
        $lines[] = [
            'account_id' => $inventoryAcc->id,
            'debit' => $purchase->total,
            'credit' => 0,
            'memo' => "Persediaan masuk pembelian {$purchase->invoice_number}",
        ];

        // Cash paid
        if ($purchase->paid_amount > 0 && $cashAcc) {
            $lines[] = [
                'account_id' => $cashAcc->id,
                'debit' => 0,
                'credit' => $purchase->paid_amount,
                'memo' => "Pembayaran tunai pembelian {$purchase->invoice_number}",
            ];
        }

        // Remaining payable / debt
        if ($purchase->remaining_debt > 0 && $payableAcc) {
            $lines[] = [
                'account_id' => $payableAcc->id,
                'debit' => 0,
                'credit' => $purchase->remaining_debt,
                'memo' => "Hutang supplier pembelian {$purchase->invoice_number}",
            ];
        }

        return $this->createJournalEntry(
            "JRN-{$purchase->invoice_number}",
            $purchase->date->format('Y-m-d'),
            'purchase',
            $purchase->id,
            "Pembelian barang {$purchase->invoice_number}",
            $lines
        );
    }

    /**
     * Record debt payment to supplier.
     */
    public function recordDebtPayment(DebtPayment $payment): JournalEntry
    {
        $payableAcc = Account::where('code', '2-1101')->first(); // HUTANG PEMBELIAN
        $sourceAcc = Account::find($payment->account_id);
        $discountAcc = Account::where('code', '5-1300')->first(); // POTONGAN PEMBELIAN

        $lines = [
            [
                'account_id' => $payableAcc->id,
                'debit' => $payment->amount_paid + $payment->discount,
                'credit' => 0,
                'memo' => "Pelunasan hutang supplier {$payment->supplier->name}",
            ],
            [
                'account_id' => $sourceAcc->id,
                'debit' => 0,
                'credit' => $payment->amount_paid,
                'memo' => "Kas keluar pembayaran hutang {$payment->payment_number}",
            ],
        ];

        if ($payment->discount > 0 && $discountAcc) {
            $lines[] = [
                'account_id' => $discountAcc->id,
                'debit' => 0,
                'credit' => $payment->discount,
                'memo' => "Potongan pembelian {$payment->payment_number}",
            ];
        }

        return $this->createJournalEntry(
            "JRN-{$payment->payment_number}",
            $payment->date->format('Y-m-d'),
            'debt_payment',
            $payment->id,
            "Pembayaran Hutang {$payment->payment_number} kepada {$payment->supplier->name}",
            $lines
        );
    }

    /**
     * Record receivable payment from customer.
     */
    public function recordReceivablePayment(ReceivablePayment $payment): JournalEntry
    {
        $destAcc = Account::find($payment->account_id);
        $receivableAcc = Account::where('code', '1-1210')->first(); // PIUTANG PENJUALAN
        $discountAcc = Account::where('code', '4-1500')->first(); // POTONGAN PENJUALAN

        $lines = [
            [
                'account_id' => $destAcc->id,
                'debit' => $payment->amount_paid,
                'credit' => 0,
                'memo' => "Penerimaan kas pembayaran piutang {$payment->payment_number}",
            ],
            [
                'account_id' => $receivableAcc->id,
                'debit' => 0,
                'credit' => $payment->amount_paid + $payment->discount,
                'memo' => "Pelunasan piutang pelanggan {$payment->customer->name}",
            ],
        ];

        if ($payment->discount > 0 && $discountAcc) {
            $lines[] = [
                'account_id' => $discountAcc->id,
                'debit' => $payment->discount,
                'credit' => 0,
                'memo' => "Potongan piutang penjualan {$payment->payment_number}",
            ];
        }

        return $this->createJournalEntry(
            "JRN-{$payment->payment_number}",
            $payment->date->format('Y-m-d'),
            'receivable_payment',
            $payment->id,
            "Pembayaran Piutang {$payment->payment_number} dari {$payment->customer->name}",
            $lines
        );
    }

    /**
     * Record Cash Transactions (Kas Masuk, Kas Keluar, Kas Transfer with Admin fee).
     */
    public function recordCashTransaction(CashTransaction $trx): JournalEntry
    {
        $debitAcc = Account::find($trx->debit_account_id);
        $creditAcc = Account::find($trx->credit_account_id);

        $lines = [];

        if ($trx->type === 'TRANSFER') {
            // Kas Transfer (Agen Transfer Tunai / Tarik Tunai)
            // Kas diterima (CASH TRANSFER) = Nominal + Admin fee
            // Rekening bank / kas berkurang = Nominal
            // Pendapatan Jasa = Admin fee
            $serviceIncomeAcc = Account::where('code', '4-1200')->first(); // PENDAPATAN JASA

            $lines[] = [
                'account_id' => $debitAcc->id,
                'debit' => $trx->amount + $trx->admin_fee,
                'credit' => 0,
                'memo' => "Kas masuk transfer tunai {$trx->transaction_number}",
            ];
            $lines[] = [
                'account_id' => $creditAcc->id,
                'debit' => 0,
                'credit' => $trx->amount,
                'memo' => "Pengurangan saldo transfer {$trx->transaction_number}",
            ];

            if ($trx->admin_fee > 0 && $serviceIncomeAcc) {
                $lines[] = [
                    'account_id' => $serviceIncomeAcc->id,
                    'debit' => 0,
                    'credit' => $trx->admin_fee,
                    'memo' => "Pendapatan jasa transfer fee {$trx->transaction_number}",
                ];
            }
        } else {
            // Normal IN or OUT
            $lines[] = [
                'account_id' => $debitAcc->id,
                'debit' => $trx->amount,
                'credit' => 0,
                'memo' => "Transaksi {$trx->type} {$trx->transaction_number}",
            ];
            $lines[] = [
                'account_id' => $creditAcc->id,
                'debit' => 0,
                'credit' => $trx->amount,
                'memo' => "Transaksi {$trx->type} {$trx->transaction_number}",
            ];
        }

        return $this->createJournalEntry(
            "JRN-{$trx->transaction_number}",
            $trx->date->format('Y-m-d'),
            'cash_transaction',
            $trx->id,
            "Kas {$trx->type} {$trx->transaction_number} - {$trx->notes}",
            $lines
        );
    }

    /**
     * Record Inventory Adjustments (Item Masuk, Item Keluar, Stok Opname).
     */
    public function recordInventoryAdjustment(InventoryAdjustment $adj): JournalEntry
    {
        $inventoryAcc = Account::where('code', '1-2010')->first(); // PERSEDIAAN BARANG

        if ($adj->type === 'IN') {
            $costAcc = Account::where('code', '6-2201')->first(); // ITEM MASUK
            $lines = [
                [
                    'account_id' => $inventoryAcc->id,
                    'debit' => $adj->total_value,
                    'credit' => 0,
                    'memo' => "Penambahan persediaan item masuk {$adj->adjustment_number}",
                ],
                [
                    'account_id' => $costAcc->id,
                    'debit' => 0,
                    'credit' => $adj->total_value,
                    'memo' => "Kontra akun item masuk {$adj->adjustment_number}",
                ],
            ];
        } elseif ($adj->type === 'OUT') {
            $costAcc = Account::where('code', '6-2202')->first(); // ITEM KELUAR
            $lines = [
                [
                    'account_id' => $costAcc->id,
                    'debit' => $adj->total_value,
                    'credit' => 0,
                    'memo' => "Beban persediaan item keluar {$adj->adjustment_number}",
                ],
                [
                    'account_id' => $inventoryAcc->id,
                    'debit' => 0,
                    'credit' => $adj->total_value,
                    'memo' => "Pengurangan persediaan item keluar {$adj->adjustment_number}",
                ],
            ];
        } else {
            // OPNAME
            $costAcc = Account::where('code', '6-2203')->first(); // STOK OPNAME
            if ($adj->diff_qty < 0) {
                // Deficit: inventory decreases
                $val = abs($adj->total_value);
                $lines = [
                    ['account_id' => $costAcc->id, 'debit' => $val, 'credit' => 0, 'memo' => "Selisih kurang opname {$adj->adjustment_number}"],
                    ['account_id' => $inventoryAcc->id, 'debit' => 0, 'credit' => $val, 'memo' => "Penyesuaian stok opname {$adj->adjustment_number}"],
                ];
            } else {
                // Surplus: inventory increases
                $val = abs($adj->total_value);
                $lines = [
                    ['account_id' => $inventoryAcc->id, 'debit' => $val, 'credit' => 0, 'memo' => "Selisih lebih opname {$adj->adjustment_number}"],
                    ['account_id' => $costAcc->id, 'debit' => 0, 'credit' => $val, 'memo' => "Kontra akun stok opname {$adj->adjustment_number}"],
                ];
            }
        }

        return $this->createJournalEntry(
            "JRN-{$adj->adjustment_number}",
            $adj->date->format('Y-m-d'),
            'inventory_adjustment',
            $adj->id,
            "Penyesuaian persediaan {$adj->type} {$adj->adjustment_number}",
            $lines
        );
    }
}
