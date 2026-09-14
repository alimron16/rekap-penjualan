<?php

use App\Http\Controllers\Api\MobileApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [MobileApiController::class, 'login']);
Route::get('/dashboard', [MobileApiController::class, 'dashboard']);

// Master Data
Route::get('/products', [MobileApiController::class, 'products']);
Route::get('/customers', [MobileApiController::class, 'customers']);
Route::get('/suppliers', [MobileApiController::class, 'suppliers']);
Route::get('/accounts', [MobileApiController::class, 'accounts']);

// POS Kasir
Route::get('/pos/data', [MobileApiController::class, 'posData']);
Route::post('/pos/checkout', [MobileApiController::class, 'posCheckout']);

// Digital / Pulsa
Route::get('/digital/data', [MobileApiController::class, 'digitalData']);
Route::post('/digital/checkout', [MobileApiController::class, 'digitalCheckout']);

// Transfer Agen
Route::get('/transfers', [MobileApiController::class, 'transfers']);
Route::post('/transfers', [MobileApiController::class, 'storeTransfer']);

// Kas & Akuntansi
Route::get('/cash-transactions', [MobileApiController::class, 'cashTransactions']);
Route::post('/cash-transactions', [MobileApiController::class, 'storeCashTransaction']);

// Laporan Keuangan
Route::get('/reports', [MobileApiController::class, 'financialReports']);

