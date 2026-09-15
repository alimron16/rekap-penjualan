<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk #{{ $sale->invoice_number }} - {{ $setting->name }}</title>
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
        .table-item {
            width: 100%;
            border-collapse: collapse;
        }
        .table-item td {
            padding: 2px 0;
            vertical-align: top;
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

    <!-- Header Identitas Toko / Cabang -->
    @php
        $receiptStoreName = $sale->outlet ? $sale->outlet->name : $setting->name;
        $receiptAddress = ($sale->outlet && !empty($sale->outlet->address)) ? $sale->outlet->address : $setting->address;
        $receiptPhone = ($sale->outlet && !empty($sale->outlet->phone)) ? $sale->outlet->phone : $setting->phone;
    @endphp
    <div class="text-center">
        <div class="bold" style="font-size: 13px; text-transform: uppercase;">{{ $receiptStoreName }}</div>
        @if($receiptAddress)
            <div style="font-size: 10px;">{{ $receiptAddress }}</div>
        @endif
        @if($receiptPhone)
            <div style="font-size: 10px;">Telp/WA: {{ $receiptPhone }}</div>
        @endif
    </div>

    <div class="dashed"></div>

    <!-- Info Nota -->
    <div class="flex">
        <span>No Nota:</span>
        <span class="bold">{{ $sale->invoice_number }}</span>
    </div>
    <div class="flex">
        <span>Tanggal:</span>
        <span>{{ $sale->date->format('d/m/Y H:i') }}</span>
    </div>
    <div class="flex">
        <span>Kasir:</span>
        <span>Kasir 1</span>
    </div>
    <div class="flex">
        <span>Pelanggan:</span>
        <span class="bold">{{ $sale->customer->name ?? 'UMUM' }}</span>
    </div>
    <div class="flex">
        <span>Jenis Trx:</span>
        <span class="bold uppercase">{{ $sale->sale_type }}</span>
    </div>

    <div class="dashed"></div>

    <!-- Rincian Barang Belanja -->
    <table class="table-item">
        @foreach($sale->items as $item)
            <tr>
                <td colspan="3" class="bold text-left">{{ $item->product->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="text-left" style="width: 35%;">{{ (float)$item->qty }} x {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                <td class="text-right" style="width: 65%;">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="dashed"></div>

    <!-- Total Perhitungan -->
    <div class="flex">
        <span>Subtotal:</span>
        <span>Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span>
    </div>
    @if($sale->discount > 0)
        <div class="flex">
            <span>Diskon:</span>
            <span>-Rp {{ number_format($sale->discount, 0, ',', '.') }}</span>
        </div>
    @endif
    <div class="flex bold" style="font-size: 12px; margin-top: 2px;">
        <span>TOTAL AKHIR:</span>
        <span>Rp {{ number_format($sale->total, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Bayar (Tunai):</span>
        <span>Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</span>
    </div>
    @if($sale->remaining_receivable > 0)
        <div class="flex bold" style="color: #900;">
            <span>Sisa Piutang:</span>
            <span>Rp {{ number_format($sale->remaining_receivable, 0, ',', '.') }}</span>
        </div>
    @else
        <div class="flex">
            <span>Kembali:</span>
            <span>Rp {{ number_format(max(0, $sale->paid_amount - $sale->total), 0, ',', '.') }}</span>
        </div>
    @endif

    <div class="double-dashed"></div>

    <!-- Footer Struk -->
    <div class="text-center" style="font-size: 10px; margin-top: 6px;">
        @if(!empty($setting->receipt_footer))
            <div>{!! nl2br(e($setting->receipt_footer)) !!}</div>
        @else
            <div>Terima Kasih Atas Kunjungan Anda</div>
            <div>Barang yang dibeli tidak dapat ditukar</div>
            <div>kecuali dengan perjanjian nota</div>
        @endif
        <div style="margin-top: 4px; font-weight: bold;">-- {{ $receiptStoreName }} --</div>
    </div>

    <script>
        window.addEventListener('load', () => {
            // Auto print if opened via POS
            if (window.location.search.includes('autoprint=1')) {
                window.print();
            }
        });
    </script>
</body>
</html>
