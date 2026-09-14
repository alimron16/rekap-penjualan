<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AgentTransferController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DigitalSaleController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Authentication Routes (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Mobile App Seamless Auto-Login Bridge
Route::get('/mobile/auth-bridge', function (\Illuminate\Http\Request $request) {
    $token = $request->query('token');
    $target = $request->query('target', '/');

    if ($token) {
        $user = \App\Models\User::where('remember_token', $token)->first();
        if ($user && $user->is_active) {
            \Illuminate\Support\Facades\Auth::login($user, true);
            $request->session()->regenerate();
            return redirect($target);
        }
    }

    return redirect('/login');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');


// Protected Routes (Must be logged in)
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Master Data
    Route::prefix('master')->name('master.')->group(function () {
        Route::get('/items', [MasterDataController::class, 'items'])->name('items');
        Route::post('/items', [MasterDataController::class, 'storeItem'])->name('items.store');
        Route::put('/items/{product}', [MasterDataController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{product}', [MasterDataController::class, 'destroyItem'])->name('items.destroy');

        Route::get('/multi', [MasterDataController::class, 'multiProducts'])->name('multi');
        Route::post('/multi', [MasterDataController::class, 'storeMultiProduct'])->name('multi.store');
        Route::put('/multi/{digitalProduct}', [MasterDataController::class, 'updateMultiProduct'])->name('multi.update');
        Route::delete('/multi/{digitalProduct}', [MasterDataController::class, 'destroyMultiProduct'])->name('multi.destroy');

        Route::get('/suppliers', [MasterDataController::class, 'suppliers'])->name('suppliers');
        Route::post('/suppliers', [MasterDataController::class, 'storeSupplier'])->name('suppliers.store');
        Route::put('/suppliers/{supplier}', [MasterDataController::class, 'updateSupplier'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [MasterDataController::class, 'destroySupplier'])->name('suppliers.destroy');

        Route::get('/customers', [MasterDataController::class, 'customers'])->name('customers');
        Route::post('/customers', [MasterDataController::class, 'storeCustomer'])->name('customers.store');
        Route::put('/customers/{customer}', [MasterDataController::class, 'updateCustomer'])->name('customers.update');
        Route::delete('/customers/{customer}', [MasterDataController::class, 'destroyCustomer'])->name('customers.destroy');

        Route::post('/categories', [MasterDataController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [MasterDataController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [MasterDataController::class, 'destroyCategory'])->name('categories.destroy');
    });

    // Pembelian
    Route::prefix('purchase')->name('purchase.')->group(function () {
        Route::get('/', [PurchaseController::class, 'index'])->name('index');
        Route::post('/', [PurchaseController::class, 'store'])->name('store');
        Route::get('/debt-payments', [PurchaseController::class, 'debtPayments'])->name('debt_payments');
        Route::post('/debt-payments', [PurchaseController::class, 'storeDebtPayment'])->name('debt_payments.store');
        Route::put('/debt-payments/{payment}', [PurchaseController::class, 'updateDebtPayment'])->name('debt_payments.update');
        Route::delete('/debt-payments/{payment}', [PurchaseController::class, 'destroyDebtPayment'])->name('debt_payments.destroy');
    });

    // Penjualan POS
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/retail', [PosController::class, 'retail'])->name('retail');
        Route::get('/wholesale', [PosController::class, 'wholesale'])->name('wholesale');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
    });

    // Penjualan Pulsa & PPOB (Digital)
    Route::prefix('digital')->name('digital.')->group(function () {
        Route::get('/', [DigitalSaleController::class, 'index'])->name('index');
        Route::post('/', [DigitalSaleController::class, 'store'])->name('store');
    });

    // Transfer Agen & Bank (Approval & Bukti Transfer)
    Route::prefix('transfer')->name('transfer.')->group(function () {
        Route::get('/', [AgentTransferController::class, 'index'])->name('index');
        Route::post('/', [AgentTransferController::class, 'store'])->name('store');
        Route::post('/{transfer}/approve', [AgentTransferController::class, 'approve'])->name('approve');
        Route::post('/{transfer}/reject', [AgentTransferController::class, 'reject'])->name('reject');
        Route::get('/check-pending', [AgentTransferController::class, 'checkPending'])->name('check_pending');
    });

    // Pembayaran Piutang & Retur Penjualan
    Route::prefix('receivable')->name('receivable.')->group(function () {
        Route::get('/payments', [ReceivableController::class, 'payments'])->name('payments');
        Route::post('/payments', [ReceivableController::class, 'storePayment'])->name('payments.store');
        Route::put('/payments/{payment}', [ReceivableController::class, 'updatePayment'])->name('payments.update');
        Route::delete('/payments/{payment}', [ReceivableController::class, 'destroyPayment'])->name('payments.destroy');

        Route::get('/returns', [ReceivableController::class, 'returns'])->name('returns');
        Route::post('/returns', [ReceivableController::class, 'storeReturn'])->name('returns.store');
        Route::put('/returns/{returnSale}', [ReceivableController::class, 'updateReturn'])->name('returns.update');
        Route::delete('/returns/{returnSale}', [ReceivableController::class, 'destroyReturn'])->name('returns.destroy');
    });

    // Persediaan (Penyesuaian & Opname)
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/adjustments', [InventoryController::class, 'adjustments'])->name('adjustments');
        Route::post('/adjustments', [InventoryController::class, 'storeAdjustment'])->name('adjustments.store');
        Route::get('/opname', [InventoryController::class, 'opname'])->name('opname');
    });

    // Akuntansi (Bagan Akun COA, Kas Masuk, Kas Keluar, Kas Transfer)
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/accounts', [AccountingController::class, 'accounts'])->name('accounts');
        Route::post('/accounts', [AccountingController::class, 'storeAccount'])->name('accounts.store');
        Route::put('/accounts/{account}', [AccountingController::class, 'updateAccount'])->name('accounts.update');
        Route::delete('/accounts/{account}', [AccountingController::class, 'destroyAccount'])->name('accounts.destroy');

        Route::get('/cash-in', [AccountingController::class, 'cashIn'])->name('cash_in');
        Route::post('/cash-in', [AccountingController::class, 'storeCashIn'])->name('cash_in.store');
        Route::put('/cash-in/{transaction}', [AccountingController::class, 'updateCashIn'])->name('cash_in.update');
        Route::delete('/cash-in/{transaction}', [AccountingController::class, 'destroyCashIn'])->name('cash_in.destroy');

        Route::get('/cash-out', [AccountingController::class, 'cashOut'])->name('cash_out');
        Route::post('/cash-out', [AccountingController::class, 'storeCashOut'])->name('cash_out.store');
        Route::put('/cash-out/{transaction}', [AccountingController::class, 'updateCashOut'])->name('cash_out.update');
        Route::delete('/cash-out/{transaction}', [AccountingController::class, 'destroyCashOut'])->name('cash_out.destroy');

        Route::get('/cash-transfer', [AccountingController::class, 'cashTransfer'])->name('cash_transfer');
        Route::post('/cash-transfer', [AccountingController::class, 'storeCashTransfer'])->name('cash_transfer.store');
        Route::put('/cash-transfer/{transaction}', [AccountingController::class, 'updateCashTransfer'])->name('cash_transfer.update');
        Route::delete('/cash-transfer/{transaction}', [AccountingController::class, 'destroyCashTransfer'])->name('cash_transfer.destroy');
    });

    // Laporan (9 Reports)
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/purchases', [ReportController::class, 'purchases'])->name('purchases');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/debts', [ReportController::class, 'debts'])->name('debts');
        Route::get('/receivables', [ReportController::class, 'receivables'])->name('receivables');
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('/cash', [ReportController::class, 'cash'])->name('cash');
        Route::get('/profit-sales', [ReportController::class, 'profitSales'])->name('profit_sales');
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit_loss');
        Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->name('balance_sheet');
    });

    // Pengaturan, Tutup Buku & Manajemen Pengguna
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/', [SettingsController::class, 'update'])->name('update');

        Route::get('/templates', [SettingsController::class, 'templates'])->name('templates');
        Route::post('/templates/{target}', [SettingsController::class, 'updateTarget'])->name('templates.update');

        Route::get('/yearly-closing', [SettingsController::class, 'yearlyClosing'])->name('yearly_closing');
        Route::post('/yearly-closing', [SettingsController::class, 'processClosing'])->name('yearly_closing.process');

        // User Management (Kelola Pengguna)
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle_status');
        });
    });

    // Cetak Struk & Faktur
    Route::prefix('receipt')->name('receipt.')->group(function () {
        Route::get('/thermal/{sale}', [ReceiptController::class, 'thermal'])->name('thermal');
        Route::get('/invoice/{sale}', [ReceiptController::class, 'invoice'])->name('invoice');
    });

});

// Mobile App REST API Endpoints
Route::prefix('api')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\MobileApiController::class, 'login']);
    Route::get('/dashboard', [\App\Http\Controllers\Api\MobileApiController::class, 'dashboard']);
    Route::get('/products', [\App\Http\Controllers\Api\MobileApiController::class, 'products']);
    Route::get('/customers', [\App\Http\Controllers\Api\MobileApiController::class, 'customers']);
    Route::get('/suppliers', [\App\Http\Controllers\Api\MobileApiController::class, 'suppliers']);
    Route::get('/accounts', [\App\Http\Controllers\Api\MobileApiController::class, 'accounts']);
    Route::get('/pos/data', [\App\Http\Controllers\Api\MobileApiController::class, 'posData']);
    Route::post('/pos/checkout', [\App\Http\Controllers\Api\MobileApiController::class, 'posCheckout']);
    Route::get('/digital/data', [\App\Http\Controllers\Api\MobileApiController::class, 'digitalData']);
    Route::post('/digital/checkout', [\App\Http\Controllers\Api\MobileApiController::class, 'digitalCheckout']);
    Route::get('/transfers', [\App\Http\Controllers\Api\MobileApiController::class, 'transfers']);
    Route::post('/transfers', [\App\Http\Controllers\Api\MobileApiController::class, 'storeTransfer']);
    Route::get('/cash-transactions', [\App\Http\Controllers\Api\MobileApiController::class, 'cashTransactions']);
    Route::post('/cash-transactions', [\App\Http\Controllers\Api\MobileApiController::class, 'storeCashTransaction']);
    Route::get('/reports', [\App\Http\Controllers\Api\MobileApiController::class, 'financialReports']);
});


