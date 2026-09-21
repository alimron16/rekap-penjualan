<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AgentTransfer;
use App\Models\Customer;
use App\Models\DigitalProduct;
use App\Models\DigitalSale;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ShiftLog;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Outlets exist
        $this->call(OutletSeeder::class);

        $outlets = Outlet::where('status', 'active')->orderBy('id')->get();
        if ($outlets->isEmpty()) {
            return;
        }

        $outlet1 = $outlets->firstWhere('id', 1) ?? $outlets->first();
        $outlet2 = $outlets->firstWhere('id', 2) ?? ($outlets->count() > 1 ? $outlets[1] : $outlet1);
        $outlet3 = $outlets->firstWhere('id', 3) ?? ($outlets->count() > 2 ? $outlets[2] : $outlet2);

        // 2. Ensure Users (including Demo Accounts) exist
        $this->call(UserSeeder::class);

        $adminUser = User::where('email', 'demo@elephantcell.com')->first()
            ?? User::where('email', 'admin@elephantcell.com')->first();
        $cashierUser1 = User::where('email', 'demo.kasir@elephantcell.com')->first()
            ?? User::where('email', 'toko@elephantcell.com')->first();
        $cashierUser2 = User::where('email', 'kasir@elephantcell.com')->first()
            ?? $cashierUser1;

        // 3. Ensure Master Customers & Suppliers exist with outlets
        $this->seedDemoCustomersAndSuppliers($outlet1, $outlet2, $outlet3);

        // 4. Ensure Products Stock per Outlet
        $this->seedProductStocksAcrossOutlets($outlet1, $outlet2, $outlet3);

        // 5. Ensure Digital Products distributed
        $this->distributeDigitalProducts($outlet1, $outlet2, $outlet3);

        // 6. Seed Demo POS Transactions
        $this->seedDemoPosSales($outlet1, $outlet2, $adminUser, $cashierUser1, $cashierUser2);

        // 7. Seed Demo Digital Sales
        $this->seedDemoDigitalSales($outlet1, $outlet2, $cashierUser1, $cashierUser2);

        // 8. Seed Demo Shift Logs
        $this->seedDemoShiftLogs($outlet1, $outlet2, $cashierUser1, $cashierUser2);

        // 9. Seed Demo Agent Transfers
        $this->seedDemoAgentTransfers($outlet1, $outlet2, $adminUser, $cashierUser1);
    }

    protected function seedDemoCustomersAndSuppliers($outlet1, $outlet2, $outlet3): void
    {
        Customer::firstOrCreate(
            ['name' => 'UMUM'],
            [
                'phone' => '081200000000',
                'address' => 'Pelanggan Walk-in Retail',
                'status' => 'Aktif',
                'outlet_id' => null,
            ]
        );

        $demoCustomers = [
            ['name' => 'Budi Tambun Cell (Demo)', 'phone' => '081234567810', 'address' => 'Jl. Sultan Hasanudin No. 12, Tambun', 'status' => 'Aktif', 'outlet_id' => $outlet1->id],
            ['name' => 'Konter Tambun Berkah (Demo)', 'phone' => '081234567811', 'address' => 'Pasar Tambun Blok B', 'status' => 'Aktif', 'outlet_id' => $outlet1->id],
            ['name' => 'Mitra Cibitung Cell (Demo)', 'phone' => '085712345610', 'address' => 'Jl. Raya Teuku Umar No. 88, Cibitung', 'status' => 'Aktif', 'outlet_id' => $outlet2->id],
            ['name' => 'Pak Hendra Grosir (Demo)', 'phone' => '085712345611', 'address' => 'Pasar Induk Cibitung', 'status' => 'Aktif', 'outlet_id' => $outlet2->id],
            ['name' => 'Sentosa Phone Cikarang (Demo)', 'phone' => '087812345610', 'address' => 'Jababeka 2 Blok A, Cikarang', 'status' => 'Aktif', 'outlet_id' => $outlet3->id],
        ];

        foreach ($demoCustomers as $c) {
            Customer::updateOrCreate(['name' => $c['name']], $c);
        }

        $demoSuppliers = [
            ['name' => 'DISTRIBUTOR UTAMA INDONESIA (GLOBAL)', 'phone' => '02188880001', 'address' => 'Roxy Mas Jakarta Pusat', 'outlet_id' => null],
            ['name' => 'GROSIR AKSESORIS TAMBUN (DEMO)', 'phone' => '02188880002', 'address' => 'Komplek Pertokoan Tambun', 'outlet_id' => $outlet1->id],
            ['name' => 'SUPPLIER SPAREPART CIBITUNG (DEMO)', 'phone' => '02188880003', 'address' => 'Ruko Cibitung Square', 'outlet_id' => $outlet2->id],
        ];

        foreach ($demoSuppliers as $s) {
            Supplier::updateOrCreate(['name' => $s['name']], $s);
        }
    }

    protected function seedProductStocksAcrossOutlets($outlet1, $outlet2, $outlet3): void
    {
        $products = Product::all();
        if ($products->isEmpty()) {
            return;
        }

        foreach ($products as $i => $p) {
            // Outlet 1: stock 20 - 45
            $stock1 = 20 + (($i * 3) % 25);
            // Outlet 2: stock 8 - 22
            $stock2 = 8 + (($i * 2) % 15);
            // Outlet 3: stock 0 - 10 (leave a few 0 to demonstrate out-of-stock)
            $stock3 = ($i % 4 === 0) ? 0 : (2 + ($i % 8));

            ProductStock::updateOrCreate(
                ['product_id' => $p->id, 'outlet_id' => $outlet1->id],
                ['stock' => $stock1, 'min_stock' => 5]
            );

            ProductStock::updateOrCreate(
                ['product_id' => $p->id, 'outlet_id' => $outlet2->id],
                ['stock' => $stock2, 'min_stock' => 5]
            );

            ProductStock::updateOrCreate(
                ['product_id' => $p->id, 'outlet_id' => $outlet3->id],
                ['stock' => $stock3, 'min_stock' => 5]
            );

            $p->syncTotalStock();
        }
    }

    protected function distributeDigitalProducts($outlet1, $outlet2, $outlet3): void
    {
        $digitals = DigitalProduct::orderBy('id')->get();
        if ($digitals->isEmpty()) {
            return;
        }

        $idx = 0;
        foreach ($digitals as $dp) {
            if ($idx < 10) {
                $dp->update(['outlet_id' => null]); // Global
            } elseif ($idx < 30) {
                $dp->update(['outlet_id' => $outlet1->id]);
            } elseif ($idx < 55) {
                $dp->update(['outlet_id' => $outlet2->id]);
            } else {
                $dp->update(['outlet_id' => $outlet3->id]);
            }
            $idx++;
        }
    }

    protected function seedDemoPosSales($outlet1, $outlet2, $adminUser, $cashier1, $cashier2): void
    {
        // Don't recreate if demo sales already exist
        if (Sale::where('invoice_number', 'like', 'INV-DEMO-%')->exists()) {
            return;
        }

        $cashAccount = Account::where('code', '1-1110')->first() // CASH RETAIL
            ?? Account::where('type', 'D')->where('group', 'AKTIVA')->first();
        $bcaAccount = Account::where('code', '1-1113')->first() // SALDO BCA
            ?? $cashAccount;

        $customerUmum = Customer::where('name', 'UMUM')->first();
        $customerBudi = Customer::where('name', 'like', '%Budi%')->first() ?? $customerUmum;

        $sampleProducts = Product::where('status', 'Masih Dijual')->take(8)->get();
        if ($sampleProducts->count() < 2) {
            return;
        }

        $p1 = $sampleProducts[0];
        $p2 = $sampleProducts[1];
        $p3 = $sampleProducts[2] ?? $p1;
        $p4 = $sampleProducts[3] ?? $p2;

        // Sale 1: Hari Ini - Outlet Tambun - Tunai (Cash Retail)
        $subtotal1 = ($p1->retail_price * 2) + ($p2->retail_price * 1);
        $sale1 = Sale::create([
            'invoice_number' => 'INV-DEMO-' . Carbon::today()->format('Ymd') . '-0001',
            'sale_type' => 'retail',
            'date' => Carbon::now()->subHours(2),
            'outlet_id' => $outlet1->id,
            'user_id' => $cashier1?->id,
            'customer_id' => $customerUmum?->id,
            'subtotal' => $subtotal1,
            'discount' => 0,
            'total' => $subtotal1,
            'paid_amount' => ceil($subtotal1 / 10000) * 10000, // Uang pas/lebih
            'remaining_receivable' => 0,
            'payment_method' => 'Tunai',
            'account_id' => $cashAccount?->id,
            'status' => 'LUNAS',
            'notes' => 'Transaksi Demo Kasir POS 1',
        ]);

        SaleItem::create([
            'sale_id' => $sale1->id,
            'product_id' => $p1->id,
            'qty' => 2,
            'selling_price' => $p1->retail_price,
            'hpp' => $p1->hpp,
            'subtotal' => $p1->retail_price * 2,
            'stock_before' => 30,
            'stock_after' => 28,
        ]);

        SaleItem::create([
            'sale_id' => $sale1->id,
            'product_id' => $p2->id,
            'qty' => 1,
            'selling_price' => $p2->retail_price,
            'hpp' => $p2->hpp,
            'subtotal' => $p2->retail_price * 1,
            'stock_before' => 25,
            'stock_after' => 24,
        ]);

        // Sale 2: Hari Ini - Outlet Cibitung - Transfer (BCA)
        $subtotal2 = ($p3->retail_price * 1) + ($p4->retail_price * 2);
        $sale2 = Sale::create([
            'invoice_number' => 'INV-DEMO-' . Carbon::today()->format('Ymd') . '-0002',
            'sale_type' => 'retail',
            'date' => Carbon::now()->subMinutes(45),
            'outlet_id' => $outlet2->id,
            'user_id' => $cashier2?->id,
            'customer_id' => $customerBudi?->id,
            'subtotal' => $subtotal2,
            'discount' => 0,
            'total' => $subtotal2,
            'paid_amount' => $subtotal2,
            'remaining_receivable' => 0,
            'payment_method' => 'Transfer',
            'account_id' => $bcaAccount?->id,
            'status' => 'LUNAS',
            'notes' => 'Transaksi Demo Transfer BCA',
        ]);

        SaleItem::create([
            'sale_id' => $sale2->id,
            'product_id' => $p3->id,
            'qty' => 1,
            'selling_price' => $p3->retail_price,
            'hpp' => $p3->hpp,
            'subtotal' => $p3->retail_price,
            'stock_before' => 15,
            'stock_after' => 14,
        ]);

        // Sale 3: Kemarin - Outlet Tambun - Tunai
        $subtotal3 = ($p1->retail_price * 3);
        $sale3 = Sale::create([
            'invoice_number' => 'INV-DEMO-' . Carbon::yesterday()->format('Ymd') . '-0001',
            'sale_type' => 'retail',
            'date' => Carbon::yesterday()->setHour(14)->setMinute(30),
            'outlet_id' => $outlet1->id,
            'user_id' => $cashier1?->id,
            'customer_id' => $customerUmum?->id,
            'subtotal' => $subtotal3,
            'discount' => 0,
            'total' => $subtotal3,
            'paid_amount' => $subtotal3,
            'remaining_receivable' => 0,
            'payment_method' => 'Tunai',
            'account_id' => $cashAccount?->id,
            'status' => 'LUNAS',
            'notes' => 'Transaksi Demo Kemarin',
        ]);

        SaleItem::create([
            'sale_id' => $sale3->id,
            'product_id' => $p1->id,
            'qty' => 3,
            'selling_price' => $p1->retail_price,
            'hpp' => $p1->hpp,
            'subtotal' => $subtotal3,
            'stock_before' => 33,
            'stock_after' => 30,
        ]);
    }

    protected function seedDemoDigitalSales($outlet1, $outlet2, $cashier1, $cashier2): void
    {
        if (DigitalSale::where('transaction_number', 'like', 'DIG-DEMO-%')->exists()) {
            return;
        }

        $digitalProduct = DigitalProduct::first();
        if (!$digitalProduct) {
            return;
        }

        $depositAccount = Account::where('code', '1-1131')->first() // SALDO MULTI
            ?? Account::where('type', 'D')->where('group', 'AKTIVA')->first();
        $cashAccount = Account::where('code', '1-1110')->first()
            ?? $depositAccount;

        DigitalSale::create([
            'outlet_id' => $outlet1->id,
            'user_id' => $cashier1?->id,
            'transaction_number' => 'DIG-DEMO-' . Carbon::today()->format('Ymd') . '-0001',
            'date' => Carbon::now()->subHours(1),
            'digital_product_id' => $digitalProduct->id,
            'customer_number' => '081298765432',
            'selling_price' => $digitalProduct->retail_price ?: 28000,
            'hpp' => $digitalProduct->hpp ?: 25500,
            'profit_margin' => ($digitalProduct->retail_price ?: 28000) - ($digitalProduct->hpp ?: 25500),
            'deposit_account_id' => $depositAccount?->id,
            'cash_account_id' => $cashAccount?->id,
            'status' => 'Sukses',
            'notes' => 'Transaksi Pulsa Demo',
        ]);
    }

    protected function seedDemoShiftLogs($outlet1, $outlet2, $cashier1, $cashier2): void
    {
        if (ShiftLog::where('notes', 'like', '%Demo%')->exists()) {
            return;
        }

        // Active shift today for cashier 1
        ShiftLog::create([
            'outlet_id' => $outlet1->id,
            'user_id' => $cashier1?->id,
            'start_time' => Carbon::today()->setHour(8)->setMinute(0),
            'end_time' => null, // active
            'cash_retail_deposited' => 0,
            'cash_retail_retained' => 200000, // modal kasir
            'cash_multi_deposited' => 0,
            'cash_multi_retained' => 100000,
            'cash_transfer_deposited' => 0,
            'cash_transfer_retained' => 0,
            'total_deposited' => 0,
            'cash_sales' => 35000,
            'non_cash_sales' => 0,
            'receivable_sales' => 0,
            'digital_sales' => 28000,
            'digital_profit' => 2500,
            'transfer_cash' => 0,
            'transfer_fee' => 0,
            'withdraw_cash' => 0,
            'withdraw_fee' => 0,
            'expenses' => 0,
            'transaction_count' => 2,
            'notes' => 'Shift Aktif Kasir Demo Hari Ini',
        ]);

        // Closed shift yesterday
        ShiftLog::create([
            'outlet_id' => $outlet1->id,
            'user_id' => $cashier1?->id,
            'start_time' => Carbon::yesterday()->setHour(8)->setMinute(0),
            'end_time' => Carbon::yesterday()->setHour(21)->setMinute(30),
            'cash_retail_deposited' => 350000,
            'cash_retail_retained' => 200000,
            'cash_multi_deposited' => 0,
            'cash_multi_retained' => 100000,
            'cash_transfer_deposited' => 0,
            'cash_transfer_retained' => 0,
            'total_deposited' => 350000,
            'cash_sales' => 350000,
            'non_cash_sales' => 120000,
            'receivable_sales' => 0,
            'digital_sales' => 150000,
            'digital_profit' => 12000,
            'transfer_cash' => 0,
            'transfer_fee' => 0,
            'withdraw_cash' => 0,
            'withdraw_fee' => 0,
            'expenses' => 15000,
            'transaction_count' => 12,
            'notes' => 'Shift Kemarin Kasir Demo Selesai',
        ]);
    }

    protected function seedDemoAgentTransfers($outlet1, $outlet2, $adminUser, $cashier1): void
    {
        if (AgentTransfer::where('reference_no', 'like', 'TRF-DEMO-%')->exists()) {
            return;
        }

        $bcaAccount = Account::where('code', '1-1113')->first();

        // 1. Approved transfer
        AgentTransfer::create([
            'reference_no' => 'TRF-DEMO-001',
            'user_id' => $cashier1?->id,
            'outlet_id' => $outlet1->id,
            'store_name' => $outlet1->name,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Kantor Pusat Elephant Cell',
            'amount' => 250000,
            'admin_fee' => 2500,
            'total_amount' => 252500,
            'status' => 'approved',
            'processed_by' => $cashier1?->id,
            'approved_by' => $adminUser?->id,
            'source_account_id' => $bcaAccount?->id,
            'notes' => 'Setoran Kas Toko ke Rekening BCA Pusat (Demo)',
            'processed_at' => Carbon::now()->subHours(5),
            'approved_at' => Carbon::now()->subHours(4),
        ]);

        // 2. Pending transfer (to test approve flow)
        AgentTransfer::create([
            'reference_no' => 'TRF-DEMO-002',
            'user_id' => $cashier1?->id,
            'outlet_id' => $outlet2->id,
            'store_name' => $outlet2->name,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Kantor Pusat Elephant Cell',
            'amount' => 150000,
            'admin_fee' => 2500,
            'total_amount' => 152500,
            'status' => 'pending',
            'processed_by' => $cashier1?->id,
            'approved_by' => null,
            'source_account_id' => $bcaAccount?->id,
            'notes' => 'Menunggu persetujuan admin operasional (Demo)',
            'processed_at' => Carbon::now()->subMinutes(30),
            'approved_at' => null,
        ]);
    }
}
