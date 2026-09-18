<?php

use App\Http\Controllers\Api\MobileApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [MobileApiController::class, 'login']);
Route::get('/dashboard', [MobileApiController::class, 'dashboard']);

// 1. Master Data
Route::get('/products', [MobileApiController::class, 'products']);
Route::post('/products', [MobileApiController::class, 'storeProduct']);
Route::put('/products/{id}', [MobileApiController::class, 'updateProduct']);
Route::delete('/products/{id}', [MobileApiController::class, 'destroyProduct']);

Route::get('/multi-products', [MobileApiController::class, 'multiProducts']);
Route::post('/multi-products', [MobileApiController::class, 'storeMultiProduct']);
Route::put('/multi-products/{id}', [MobileApiController::class, 'updateMultiProduct']);
Route::delete('/multi-products/{id}', [MobileApiController::class, 'destroyMultiProduct']);

Route::get('/customers', [MobileApiController::class, 'customers']);
Route::post('/customers', [MobileApiController::class, 'storeCustomer']);
Route::put('/customers/{id}', [MobileApiController::class, 'updateCustomer']);
Route::delete('/customers/{id}', [MobileApiController::class, 'destroyCustomer']);

Route::get('/suppliers', [MobileApiController::class, 'suppliers']);
Route::post('/suppliers', [MobileApiController::class, 'storeSupplier']);
Route::put('/suppliers/{id}', [MobileApiController::class, 'updateSupplier']);
Route::delete('/suppliers/{id}', [MobileApiController::class, 'destroySupplier']);

Route::get('/accounts', [MobileApiController::class, 'accounts']);
Route::post('/accounts', [MobileApiController::class, 'storeAccount']);
Route::put('/accounts/{id}', [MobileApiController::class, 'updateAccount']);
Route::delete('/accounts/{id}', [MobileApiController::class, 'destroyAccount']);

Route::get('/outlets', [MobileApiController::class, 'outlets']);
Route::post('/outlets', [MobileApiController::class, 'storeOutlet']);
Route::put('/outlets/{id}', [MobileApiController::class, 'updateOutlet']);
Route::delete('/outlets/{id}', [MobileApiController::class, 'destroyOutlet']);
Route::post('/outlets/{id}/toggle-status', [MobileApiController::class, 'toggleOutletStatus']);


// 2. POS Kasir
Route::get('/pos/data', [MobileApiController::class, 'posData']);
Route::post('/pos/checkout', [MobileApiController::class, 'posCheckout']);
Route::post('/pos/withdraw', [MobileApiController::class, 'posWithdraw']);

// 3. Digital / Pulsa
Route::get('/digital/data', [MobileApiController::class, 'digitalData']);
Route::post('/digital/checkout', [MobileApiController::class, 'digitalCheckout']);
Route::post('/digital/topup', [MobileApiController::class, 'topupMulti']);

// 4. Piutang & Retur
Route::get('/receivables', [MobileApiController::class, 'receivables']);
Route::post('/receivables/pay', [MobileApiController::class, 'storeReceivablePayment']);
Route::get('/returns', [MobileApiController::class, 'returns']);
Route::post('/returns', [MobileApiController::class, 'storeReturn']);

// 5. Pembelian & Hutang
Route::get('/purchases', [MobileApiController::class, 'purchases']);
Route::post('/purchases', [MobileApiController::class, 'storePurchase']);
Route::post('/purchases/pay-debt', [MobileApiController::class, 'storeDebtPayment']);

// 6. Persediaan
Route::get('/inventory/adjustments', [MobileApiController::class, 'inventoryAdjustments']);
Route::post('/inventory/adjustments', [MobileApiController::class, 'storeInventoryAdjustment']);

// 7. Transfer Agen & Notifikasi
Route::get('/transfers', [MobileApiController::class, 'transfers']);
Route::get('/transfers/pending-check', [MobileApiController::class, 'checkPendingTransfers']);
Route::get('/notifications/poll', [MobileApiController::class, 'pollNotifications']);
Route::post('/transfers', [MobileApiController::class, 'storeTransfer']);
Route::post('/transfers/{id}/approve', [MobileApiController::class, 'approveTransfer']);
Route::post('/transfers/{id}/reject', [MobileApiController::class, 'rejectTransfer']);

// 8. Kas & Akuntansi
Route::get('/cash-transactions', [MobileApiController::class, 'cashTransactions']);
Route::post('/cash-transactions', [MobileApiController::class, 'storeCashTransaction']);
Route::get('/pos/shift-summary', [MobileApiController::class, 'shiftSummary']);
Route::post('/pos/close-shift', [MobileApiController::class, 'closeShift']);
Route::get('/pos/unified-logs', [MobileApiController::class, 'unifiedLogs']);

// 9. Laporan Keuangan
Route::get('/reports', [MobileApiController::class, 'financialReports']);
Route::get('/reports/sales', [MobileApiController::class, 'reportSales']);
Route::get('/reports/purchases', [MobileApiController::class, 'reportPurchases']);
Route::get('/reports/cash', [MobileApiController::class, 'reportCash']);
Route::get('/reports/profit-loss', [MobileApiController::class, 'reportProfitLoss']);
Route::get('/reports/balance-sheet', [MobileApiController::class, 'reportBalanceSheet']);
Route::get('/reports/debts-receivables', [MobileApiController::class, 'reportDebtsReceivables']);

// 10. Pengaturan & User
Route::get('/users', [MobileApiController::class, 'users']);
Route::post('/users', [MobileApiController::class, 'storeUser']);
Route::put('/users/{id}', [MobileApiController::class, 'updateUser']);
Route::delete('/users/{id}', [MobileApiController::class, 'destroyUser']);
Route::post('/users/{id}/toggle-status', [MobileApiController::class, 'toggleUserStatus']);
Route::get('/settings', [MobileApiController::class, 'storeSettings']);
Route::put('/settings', [MobileApiController::class, 'updateSettings']);
Route::post('/settings/logo', [MobileApiController::class, 'uploadSettingsLogo']);

