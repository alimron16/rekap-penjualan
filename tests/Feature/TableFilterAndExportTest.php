<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class TableFilterAndExportTest extends TestCase
{
    public function test_master_items_toolbar_and_per_page(): void
    {
        $response = $this->get(route('master.items', ['per_page' => 50]));
        $response->assertStatus(200);
        $response->assertSee('Tampilkan:');
        $response->assertSee('itemsTable');
        $response->assertSee('exportTableToExcel');
        $response->assertSee('DOWNLOAD EXCEL');

        $allResponse = $this->get(route('master.items', ['per_page' => 'all']));
        $allResponse->assertStatus(200);
    }

    public function test_accounting_accounts_toolbar_and_search(): void
    {
        $response = $this->get(route('accounting.accounts', ['per_page' => 'all']));
        $response->assertStatus(200);
        $response->assertSee('accountsTable');
        $response->assertSee('Bagan_Akun_COA');

        $searchResponse = $this->get(route('accounting.accounts', ['search' => 'KAS']));
        $searchResponse->assertStatus(200);
    }

    public function test_purchase_index_toolbar(): void
    {
        $response = $this->get(route('purchase.index', ['per_page' => 100]));
        $response->assertStatus(200);
        $response->assertSee('purchasesTable');
        $response->assertSee('Rekap_Pembelian');
    }

    public function test_inventory_and_receivable_toolbars(): void
    {
        $invIn = $this->get(route('inventory.item_in', ['per_page' => 50]));
        $invIn->assertStatus(200);
        $invIn->assertSee('itemInTable');

        $recPay = $this->get(route('receivable.payments', ['per_page' => 25]));
        $recPay->assertStatus(200);
        $recPay->assertSee('receivablePaymentsTable');
    }

    public function test_reports_toolbars(): void
    {
        $salesRep = $this->get(route('reports.sales', ['per_page' => 50]));
        $salesRep->assertStatus(200);
        $salesRep->assertSee('reportSalesTable');

        $purchasesRep = $this->get(route('reports.purchases', ['per_page' => 'all']));
        $purchasesRep->assertStatus(200);
        $purchasesRep->assertSee('reportPurchasesTable');
    }
}
