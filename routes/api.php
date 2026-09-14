<?php

use App\Http\Controllers\Api\MobileApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [MobileApiController::class, 'login']);
Route::get('/dashboard', [MobileApiController::class, 'dashboard']);
Route::get('/products', [MobileApiController::class, 'products']);
Route::get('/transfers', [MobileApiController::class, 'transfers']);
Route::post('/transfers', [MobileApiController::class, 'storeTransfer']);
Route::post('/transfers/{id}/approve', [MobileApiController::class, 'approveTransfer']);
