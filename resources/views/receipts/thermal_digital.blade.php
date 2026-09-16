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
            font-family: 'Courier New', Courier, monospace;
            width: 76mm;
            margin: 0 auto;
            padding: 8px 4px;
            font-size: 11px;
            color: #000;
            line-height: 1.25;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .dashed {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .double-dashed {
            border-top: 2px dashed #000;
            margin: 6px 0;
        }
        .flex {
            display: flex;
            justify-content: space-between;
        }
        .btn-print {
            background: #14421b;
            color: white;
            border: none;
            padding: 8px 16px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 10px;
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
        <div class="bold" style="font-size: 13px; text-transform: uppercase;">{{ $setting->name ?? 'ELEPHANT CELL GROUP' }}</div>
        @if(!empty($setting->address))
            <div style="font-size: 10px;">{{ $setting->address }}</div>
        @endif
        @if(!empty($setting->phone))
            <div style="font-size: 10px;">Telp/WA: {{ $setting->phone }}</div>
        @endif
    </div>

    <div class="dashed"></div>

    <!-- Info Transaksi Pulsa -->
    <div class="text-center bold" style="font-size: 11px; margin-bottom: 4px;">
        STRUK PEMBELIAN PULSA / LISTRIK / PPOB
    </div>
    <div class="flex">
        <span>No Trx:</span>
        <span class="bold">{{ $digitalSale->transaction_number }}</span>
    </div>
    <div class="flex">
        <span>Tanggal:</span>
        <span>{{ $digitalSale->date ? $digitalSale->date->format('d/m/Y H:i') : date('d/m/Y H:i') }}</span>
    </div>
    <div class="flex">
        <span>Kasir:</span>
        <span>Kasir 1</span>
    </div>

    <div class="dashed"></div>

    <!-- Detail Produk & Nomor Tujuan -->
    <div style="margin-bottom: 4px;">
        <div class="bold" style="font-size: 12px;">{{ $digitalSale->digitalProduct->name ?? 'PRODUK ELEKTRIK' }}</div>
        <div class="flex" style="font-size: 11px; margin-top: 2px;">
            <span>No Tujuan/Meter:</span>
            <span class="bold" style="font-size: 12px;">{{ $digitalSale->customer_number }}</span>
        </div>
        @if(!empty($digitalSale->notes))
            <div class="flex" style="font-size: 10px; margin-top: 1px;">
                <span>Catatan / SN:</span>
                <span>{{ $digitalSale->notes }}</span>
            </div>
        @endif
    </div>

    <div class="dashed"></div>

    <!-- Total Tagihan -->
    <div class="flex bold" style="font-size: 12px; margin-top: 2px;">
        <span>TOTAL TAGIHAN:</span>
        <span>Rp {{ number_format($digitalSale->selling_price, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Metode Bayar:</span>
        <span>TUNAI / CASH</span>
    </div>
    <div class="flex">
        <span>Status Transaksi:</span>
        <span class="bold" style="color: {{ $digitalSale->status === 'SUKSES' ? 'green' : 'red' }};">{{ $digitalSale->status }}</span>
    </div>

    <div class="double-dashed"></div>

    <!-- Footer -->
    <div class="text-center" style="font-size: 10px; margin-top: 6px;">
        @if(!empty($setting->receipt_footer))
            <div>{!! nl2br(e($setting->receipt_footer)) !!}</div>
        @else
            <div>Terima Kasih Telah Bertransaksi</div>
            <div>Simpan struk ini sebagai bukti pembayaran yang sah</div>
        @endif
        <div style="margin-top: 4px; font-weight: bold;">-- {{ $setting->name ?? 'ELEPHANT CELL' }} --</div>
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
