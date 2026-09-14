<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\StoreSetting;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function thermal(Sale $sale)
    {
        $setting = StoreSetting::first();
        $sale->load(['items.product', 'customer']);

        return view('receipts.thermal', compact('sale', 'setting'));
    }

    public function invoice(Sale $sale)
    {
        $setting = StoreSetting::first();
        $sale->load(['items.product', 'customer']);

        return view('receipts.invoice', compact('sale', 'setting'));
    }
}
