<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Pulsa #{{ $digitalSale->transaction_number }} - {{ $setting->name ?? 'ELEPHANT CELL' }}</title>
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
            border-top: 1px solid #e5e7eb;
            margin: 8px 0;
        }
        .flex {
            display: flex;
            justify-content: space-between;
            padding: 1.5px 0;
        }
        .btn-print {
            background: #14421b;
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
        <button onclick="window.print()" class="btn-print">CETAK STRUK THERMAL</button>
    </div>

    <!-- Header Toko -->
    <div class="text-center">
        @if($setting && $setting->logo_url)
            <img src="{{ $setting->logo_url }}" alt="Logo" style="max-height: 48px; max-width: 120px; object-fit: contain; margin-bottom: 4px; display: inline-block;">
        @endif
        <div style="font-weight: 900; font-size: 13px;">{{ $setting->name ?? 'ELEPHANT CELL GROUP' }}</div>
        @if(!empty($setting->address))
            <div style="font-size: 10px; color: #6b7280; margin-top: 2px;">{{ $setting->address }}</div>
        @endif
        @if(!empty($setting->phone))
            <div style="font-size: 10px; color: #6b7280;">Telp: {{ $setting->phone }}</div>
        @endif
    </div>

    <div class="divider"></div>

    <!-- Info Transaksi Pulsa -->
    <div class="flex">
        <span style="color: #4b5563;">No. Trx</span>
        <span class="bold">{{ $digitalSale->transaction_number }}</span>
    </div>
    <div class="flex">
        <span style="color: #4b5563;">Tanggal</span>
        <span>{{ $digitalSale->date ? $digitalSale->date->format('d/m/Y  H:i') : date('d/m/Y  H:i') }}</span>
    </div>
    <div class="flex">
        <span style="color: #4b5563;">Kategori</span>
        <span>Pulsa / PPOB</span>
    </div>

    <div class="divider"></div>

    <!-- Detail Produk & Nomor Tujuan -->
    <div class="flex">
        <span style="color: #4b5563;">Produk</span>
        <span class="bold">{{ $digitalSale->digitalProduct->name ?? 'PRODUK ELEKTRIK' }}</span>
    </div>
    <div class="flex">
        <span style="color: #4b5563;">No. Tujuan</span>
        <span>{{ $digitalSale->customer_number }}</span>
    </div>
    @if(!empty($digitalSale->notes))
    <div class="flex">
        <span style="color: #4b5563;">SN / Ket</span>
        <span style="font-size: 10px;">{{ $digitalSale->notes }}</span>
    </div>
    @endif

    <div class="divider"></div>

    <!-- Total Tagihan -->
    <div class="flex bold" style="font-size: 11.5px; margin-top: 1px;">
        <span>Total</span>
        <span>Rp {{ number_format($digitalSale->selling_price, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Bayar</span>
        <span>Tunai</span>
    </div>
    <div class="flex">
        <span>Status</span>
        <span class="bold" style="color: {{ $digitalSale->status === 'SUKSES' ? '#15803d' : '#b91c1c' }};">{{ $digitalSale->status }}</span>
    </div>

    <div class="divider"></div>

    <!-- Footer -->
    <div class="text-center" style="font-size: 9.5px; color: #6b7280; margin-top: 6px; font-style: italic;">
        @if(!empty($setting->receipt_footer))
            <div>{!! nl2br(e($setting->receipt_footer)) !!}</div>
        @else
            <div>Terima kasih telah berbelanja!</div>
            <div>Simpan struk ini sebagai bukti transaksi yang sah.</div>
        @endif
        <div style="margin-top: 6px; font-style: normal; color: #9ca3af;">--- * ---</div>
    </div>

    <script>
        window.addEventListener('load', () => {
            if (window.location.search.includes('autoprint=1')) {
                window.print();
            }
        });
    </script>
</body>
</html>
