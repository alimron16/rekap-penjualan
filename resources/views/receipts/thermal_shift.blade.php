<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Rekap Shift #{{ $shiftLog->id }} - {{ $setting->name ?? 'POS' }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            width: 74mm;
            margin: 0 auto;
            padding: 10px 6px;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.35;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: 700; }
        .divider {
            border-top: 1px dashed #9ca3af;
            margin: 8px 0;
        }
        .flex {
            display: flex;
            justify-content: space-between;
            padding: 1.5px 0;
        }
        .btn-print {
            background: #047857;
            color: white;
            border: none;
            padding: 8px 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            margin-bottom: 12px;
            width: 100%;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 8px;">
        <button onclick="window.print()" class="btn-print">CETAK STRUK TUTUP SHIFT</button>
    </div>

    <div class="text-center">
        @if(!empty($setting->logo))
            <img src="{{ asset('storage/' . $setting->logo) }}" alt="Logo" style="max-height: 48px; max-width: 150px; object-fit: contain; margin-bottom: 4px;">
        @endif
        <div class="bold" style="font-size: 14px;">{{ $setting->name ?? 'ELEPHANT POS' }}</div>
        @if(!empty($setting->address))
            <div style="font-size: 10px; color: #4b5563;">{{ $setting->address }}</div>
        @endif
        @if(!empty($setting->phone))
            <div style="font-size: 10px; color: #4b5563;">Telp: {{ $setting->phone }}</div>
        @endif
        <div class="divider"></div>
        <div class="bold" style="font-size: 12px; letter-spacing: 0.5px;">REKAP & TUTUP SHIFT KASIR</div>
        <div style="font-size: 10px; color: #6b7280;">ID Shift: #{{ str_pad($shiftLog->id, 5, '0', STR_PAD_LEFT) }}</div>
    </div>

    <div class="divider"></div>

    <div class="flex">
        <span>Cabang / Outlet:</span>
        <span class="bold">{{ $shiftLog->outlet->name ?? 'Pusat / Semua' }}</span>
    </div>
    <div class="flex">
        <span>Kasir / Petugas:</span>
        <span class="bold">{{ $shiftLog->user->name ?? 'Kasir' }}</span>
    </div>
    <div class="flex">
        <span>Waktu Mulai:</span>
        <span>{{ $shiftLog->start_time ? $shiftLog->start_time->format('d/m/y H:i') : '-' }}</span>
    </div>
    <div class="flex">
        <span>Waktu Selesai:</span>
        <span>{{ $shiftLog->end_time ? $shiftLog->end_time->format('d/m/y H:i') : '-' }}</span>
    </div>

    <div class="divider"></div>
    <div class="bold" style="margin-bottom: 4px;">RINGKASAN OPERASIONAL SHIFT</div>

    <div class="flex">
        <span>Penjualan Tunai Retail:</span>
        <span class="bold">Rp {{ number_format($shiftLog->cash_sales, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Penjualan Non-Tunai (TF/QRIS):</span>
        <span>Rp {{ number_format($shiftLog->non_cash_sales, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Penjualan Tempo (Piutang):</span>
        <span>Rp {{ number_format($shiftLog->receivable_sales, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Penjualan Pulsa / Digital:</span>
        <span>Rp {{ number_format($shiftLog->digital_sales, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Margin Laba Pulsa:</span>
        <span style="color: #047857;">+Rp {{ number_format($shiftLog->digital_profit, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Transfer Agen (Uang Masuk):</span>
        <span>Rp {{ number_format($shiftLog->transfer_cash, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Fee Transfer Agen:</span>
        <span style="color: #047857;">+Rp {{ number_format($shiftLog->transfer_fee, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Tarik Tunai (Uang Keluar):</span>
        <span>-Rp {{ number_format($shiftLog->withdraw_cash, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Kas Keluar (Beban Toko):</span>
        <span style="color: #dc2626;">-Rp {{ number_format($shiftLog->expenses, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Total Nota / Transaksi:</span>
        <span class="bold">{{ $shiftLog->transaction_count }} Trx</span>
    </div>

    <div class="divider"></div>
    <div class="bold" style="margin-bottom: 4px;">SETORAN & SISA MODAL (3 KAS)</div>

    <div style="background: #f3f4f6; padding: 4px 6px; border-radius: 4px; margin-bottom: 4px;">
        <div class="flex bold">
            <span>1. Cash Retail (Laci Toko)</span>
        </div>
        <div class="flex" style="font-size: 10px;">
            <span>Disetor ke Brankas:</span>
            <span class="bold">Rp {{ number_format($shiftLog->cash_retail_deposited, 0, ',', '.') }}</span>
        </div>
        <div class="flex" style="font-size: 10px;">
            <span>Disisakan sbg Modal:</span>
            <span>Rp {{ number_format($shiftLog->cash_retail_retained, 0, ',', '.') }}</span>
        </div>
    </div>

    <div style="background: #f3f4f6; padding: 4px 6px; border-radius: 4px; margin-bottom: 4px;">
        <div class="flex bold">
            <span>2. Cash Multi (Pulsa/PPOB)</span>
        </div>
        <div class="flex" style="font-size: 10px;">
            <span>Disetor ke Brankas:</span>
            <span class="bold">Rp {{ number_format($shiftLog->cash_multi_deposited, 0, ',', '.') }}</span>
        </div>
        <div class="flex" style="font-size: 10px;">
            <span>Disisakan sbg Modal:</span>
            <span>Rp {{ number_format($shiftLog->cash_multi_retained, 0, ',', '.') }}</span>
        </div>
    </div>

    <div style="background: #f3f4f6; padding: 4px 6px; border-radius: 4px; margin-bottom: 4px;">
        <div class="flex bold">
            <span>3. Cash Transfer (Agen)</span>
        </div>
        <div class="flex" style="font-size: 10px;">
            <span>Disetor ke Brankas:</span>
            <span class="bold">Rp {{ number_format($shiftLog->cash_transfer_deposited, 0, ',', '.') }}</span>
        </div>
        <div class="flex" style="font-size: 10px;">
            <span>Disisakan sbg Modal:</span>
            <span>Rp {{ number_format($shiftLog->cash_transfer_retained, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="divider"></div>

    <div class="flex bold" style="font-size: 13px;">
        <span>TOTAL UANG DISETOR:</span>
        <span style="color: #047857;">Rp {{ number_format($shiftLog->total_deposited, 0, ',', '.') }}</span>
    </div>

    @if(!empty($shiftLog->notes))
    <div style="margin-top: 6px; font-size: 10px; color: #4b5563;">
        <span class="bold">Catatan:</span> {{ $shiftLog->notes }}
    </div>
    @endif

    <div class="divider"></div>

    <div style="display: flex; justify-content: space-between; margin-top: 18px; text-align: center; font-size: 10px;">
        <div style="width: 45%;">
            <div>Diserahkan oleh:</div>
            <div style="height: 35px;"></div>
            <div class="bold">({{ $shiftLog->user->name ?? 'Kasir' }})</div>
            <div>Kasir Shift</div>
        </div>
        <div style="width: 45%;">
            <div>Diterima oleh:</div>
            <div style="height: 35px;"></div>
            <div class="bold">(......................)</div>
            <div>Supervisor / Pusat</div>
        </div>
    </div>

    <div class="text-center" style="margin-top: 14px; font-size: 9px; color: #9ca3af;">
        Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
