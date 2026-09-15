<?php

use App\Http\Controllers\Api\MobileApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [MobileApiController::class, 'login']);
Route::get('/dashboard', [MobileApiController::class, 'dashboard']);

// 1. Master Data
Route::get('/products', [MobileApiController::class, 'products']);
Route::post('/products', [MobileApiController::class, 'storeProduct']);
Route::get('/multi-products', [MobileApiController::class, 'multiProducts']);
Route::get('/customers', [MobileApiController::class, 'customers']);
Route::post('/customers', [MobileApiController::class, 'storeCustomer']);
Route::get('/suppliers', [MobileApiController::class, 'suppliers']);
Route::post('/suppliers', [MobileApiController::class, 'storeSupplier']);
Route::get('/accounts', [MobileApiController::class, 'accounts']);
Route::post('/accounts', [MobileApiController::class, 'storeAccount']);

// 2. POS Kasir
Route::get('/pos/data', [MobileApiController::class, 'posData']);
Route::post('/pos/checkout', [MobileApiController::class, 'posCheckout']);

// 3. Digital / Pulsa
Route::get('/digital/data', [MobileApiController::class, 'digitalData']);
Route::post('/digital/checkout', [MobileApiController::class, 'digitalCheckout']);

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

// 7. Transfer Agen
Route::get('/transfers', [MobileApiController::class, 'transfers']);
Route::get('/transfers/pending-check', [MobileApiController::class, 'checkPendingTransfers']);
Route::post('/transfers', [MobileApiController::class, 'storeTransfer']);
Route::post('/transfers/{id}/approve', [MobileApiController::class, 'approveTransfer']);
Route::post('/transfers/{id}/reject', [MobileApiController::class, 'rejectTransfer']);

// 8. Kas & Akuntansi
Route::get('/cash-transactions', [MobileApiController::class, 'cashTransactions']);
Route::post('/cash-transactions', [MobileApiController::class, 'storeCashTransaction']);

// 9. Laporan Keuangan
Route::get('/reports', [MobileApiController::class, 'financialReports']);

// 10. Pengaturan & User
Route::get('/users', [MobileApiController::class, 'users']);
Route::post('/users', [MobileApiController::class, 'storeUser']);
Route::get('/settings', [MobileApiController::class, 'storeSettings']);
