<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\DigitalProduct;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\JournalEntry;
use App\Services\HppCalculationService;
use App\Services\PosTransactionService;
use App\Services\AccountingService;
use App\Services\FinancialReportService;
use App\Services\YearlyClosingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PosAccountingTest extends TestCase
{
    use DatabaseTransactions;

    protected HppCalculationService $hppService;
    protected PosTransactionService $posService;
    protected AccountingService $accountingService;
    protected FinancialReportService $reportService;
    protected YearlyClosingService $closingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hppService = app(HppCalculationService::class);
        $this->posService = app(PosTransactionService::class);
        $this->accountingService = app(AccountingService::class);
        $this->reportService = app(FinancialReportService::class);
        $this->closingService = app(YearlyClosingService::class);
    }

    /**
     * Test 1: Moving Average HPP Calculation
     * Formula: ((old_stock * old_hpp) + (buy_qty * buy_price)) / (old_stock + buy_qty)
     */
    public function test_moving_average_hpp_calculation()
    {
        $product = Product::first();
        $this->assertNotNull($product, "Product should exist from seeder");

        // Initial setup: 10 units at 10,000 HPP
        $product->stock = 10;
        $product->hpp = 10000;
        $product->save();

        // Buy 10 items at 20,000. New total = (100,000 + 200,000) / 20 = 15,000
        $audit = $this->hppService->applyPurchase($product, 10, 20000);

        $this->assertEquals(15000, $audit['hpp_after']);
        $product->refresh();
        $this->assertEquals(15000, $product->hpp);
        $this->assertEquals(20, $product->stock);
    }

    /**
     * Test 2: Dual-Pane POS Checkout (Cash) & Balanced Double-Entry Journal
     */
    public function test_pos_cash_sale_creates_balanced_journal_and_reduces_stock()
    {
        $product = Product::where('stock', '>=', 5)->first();
        if (!$product) {
            $product = Product::first();
            $product->update(['stock' => 20, 'retail_price' => 50000, 'hpp' => 35000]);
        }
        $this->assertNotNull($product);

        $initialStock = (float) $product->stock;
        $customer = Customer::first();
        $cashAcc = Account::where('code', '1-1110')->first(); // CASH RETAIL

        $saleData = [
            'sale_type' => 'retail',
            'customer_id' => $customer ? $customer->id : null,
            'discount' => 0,
            'paid_amount' => $product->retail_price * 2,
            'payment_method' => 'Tunai',
            'account_id' => $cashAcc ? $cashAcc->id : null,
            'notes' => 'Test POS Cash Sale',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'price' => $product->retail_price,
                ]
            ]
        ];

        $sale = $this->posService->checkoutPos($saleData);

        $this->assertNotNull($sale);
        $this->assertEquals('LUNAS', $sale->status);

        // Verify stock reduced
        $product->refresh();
        $this->assertEquals($initialStock - 2, (float) $product->stock);

        // Verify balanced double-entry journal was created
        $journal = JournalEntry::where('ref_type', 'sale')
            ->where('ref_id', $sale->id)
            ->first();

        $this->assertNotNull($journal, "Journal entry must be automatically created");
        $totalDebit = $journal->lines->sum('debit');
        $totalCredit = $journal->lines->sum('credit');
        $this->assertGreaterThan(0, $totalDebit);
        $this->assertEquals($totalDebit, $totalCredit, "Debit must equal Credit in double-entry");
    }

    /**
     * Test 3: Digital Service Transaction and Reversal
     */
    public function test_digital_sale_and_reversal_restores_deposit()
    {
        $digProduct = DigitalProduct::first();
        $this->assertNotNull($digProduct);

        $depositAcc = Account::where('code', '1-1131')->first(); // SALDO MULTI
        $cashAcc = Account::where('code', '1-1110')->first(); // CASH RETAIL
        $this->assertNotNull($depositAcc);
        $this->assertNotNull($cashAcc);

        $initialDeposit = (float) $depositAcc->current_balance;

        // Perform digital sale
        $digSale = $this->posService->processDigitalSale([
            'digital_product_id' => $digProduct->id,
            'customer_number' => '081234567890',
            'selling_price' => 52000,
            'hpp' => 50000,
            'deposit_account_id' => $depositAcc->id,
            'cash_account_id' => $cashAcc->id,
            'notes' => 'Test Pulsa Elektrik Sukses'
        ]);

        $this->assertNotNull($digSale);
        $this->assertEquals(2000, $digSale->profit_margin);

        $depositAcc->refresh();
        $this->assertEquals($initialDeposit - 50000, (float) $depositAcc->current_balance, "Deposit balance should decrease by HPP");

        // Verify double-entry journal
        $journal = JournalEntry::where('ref_type', 'digital_sale')
            ->where('ref_id', $digSale->id)
            ->first();
        $this->assertNotNull($journal);
        $this->assertEquals($journal->lines->sum('debit'), $journal->lines->sum('credit'));

        // Now reverse due to failure
        $reversed = $this->posService->reverseDigitalSale($digSale);
        $this->assertEquals('GAGAL', $reversed->status);

        $depositAcc->refresh();
        $this->assertEquals($initialDeposit, (float) $depositAcc->current_balance, "Deposit must be fully restored upon reversal");
    }

    /**
     * Test 4: Kas Transfer with Admin Fee posting to 4-1200 PENDAPATAN JASA
     */
    public function test_kas_transfer_with_admin_fee()
    {
        $cashTransferAcc = Account::where('code', '1-1111')->first(); // CASH TRANSFER (Debit)
        $bcaAcc = Account::where('code', '1-1113')->first(); // SALDO BCA (Credit)
        $pendapatanJasa = Account::where('code', '4-1200')->first(); // PENDAPATAN JASA

        $this->assertNotNull($cashTransferAcc);
        $this->assertNotNull($bcaAcc);
        $this->assertNotNull($pendapatanJasa);

        $trx = $this->posService->processKasTransfer([
            'debit_account_id' => $cashTransferAcc->id,
            'credit_account_id' => $bcaAcc->id,
            'amount' => 100000,
            'admin_fee' => 2500,
            'notes' => 'Transfer Tarik Tunai Pelanggan'
        ]);

        $this->assertNotNull($trx);

        // Verify balanced journal
        $journal = JournalEntry::where('ref_type', 'cash_transaction')
            ->where('ref_id', $trx->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals($journal->lines->sum('debit'), $journal->lines->sum('credit'), "Debit must equal Credit");

        // Check line items: Cash transfer debited 102500, BCA credited 100000, Pendapatan Jasa credited 2500
        $lines = $journal->lines;
        $debitToTransfer = $lines->where('account_id', $cashTransferAcc->id)->sum('debit');
        $creditToBca = $lines->where('account_id', $bcaAcc->id)->sum('credit');
        $creditToJasa = $lines->where('account_id', $pendapatanJasa->id)->sum('credit');

        $this->assertEquals(102500, $debitToTransfer);
        $this->assertEquals(100000, $creditToBca);
        $this->assertEquals(2500, $creditToJasa);
    }

    /**
     * Test 5: Balance Sheet Formula Integrity
     */
    public function test_balance_sheet_integrity()
    {
        $balanceSheet = $this->reportService->getBalanceSheet(now()->toDateString());

        $this->assertArrayHasKey('aktiva', $balanceSheet);
        $this->assertArrayHasKey('kewajiban', $balanceSheet);
        $this->assertArrayHasKey('modal', $balanceSheet);
        $this->assertArrayHasKey('is_balanced', $balanceSheet);

        $this->assertTrue(is_numeric($balanceSheet['aktiva']['total']));
        $this->assertTrue(is_numeric($balanceSheet['total_kewajiban_modal']));
    }

    /**
     * Test 6: Dynamic Category & Trx Type Addition
     */
    public function test_dynamic_category_and_trx_type_addition()
    {
        // 1. Direct Category Creation via POST
        $response = $this->post(route('master.categories.store'), [
            'type' => 'digital_type',
            'name' => 'E-WALLET BARU'
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('categories', [
            'type' => 'digital_type',
            'name' => 'E-WALLET BARU'
        ]);

        // 2. On-the-fly Multi Product creation with __NEW__
        $postData = [
            'product_code' => 'TEST_EW100',
            'name' => 'SALDO TEST 100K',
            'trx_type' => '__NEW__',
            'new_trx_type' => 'FINTECH SPESIAL',
            'category' => '__NEW__',
            'new_category' => 'LINKAJA BARU',
            'hpp' => 99000,
            'selling_price' => 102000,
            'status' => 'OPEN',
        ];

        $resMulti = $this->post(route('master.multi.store'), $postData);
        $resMulti->assertRedirect(route('master.multi'));

        $this->assertDatabaseHas('categories', [
            'type' => 'digital_type',
            'name' => 'FINTECH SPESIAL'
        ]);
        $this->assertDatabaseHas('categories', [
            'type' => 'digital_category',
            'name' => 'LINKAJA BARU'
        ]);
        $this->assertDatabaseHas('digital_products', [
            'product_code' => 'TEST_EW100',
            'trx_type' => 'FINTECH SPESIAL',
            'category' => 'LINKAJA BARU'
        ]);
    }
}
