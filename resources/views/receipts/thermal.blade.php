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

    <!-- Header Identitas Toko / Cabang -->
    @php
        $receiptStoreName = $sale->outlet ? $sale->outlet->name : $setting->name;
        $receiptAddress = ($sale->outlet && !empty($sale->outlet->address)) ? $sale->outlet->address : $setting->address;
        $receiptPhone = ($sale->outlet && !empty($sale->outlet->phone)) ? $sale->outlet->phone : $setting->phone;
    @endphp
    <div class="text-center">
        @if($setting && $setting->logo_url)
            <img src="{{ $setting->logo_url }}" alt="Logo" style="max-height: 48px; max-width: 120px; object-fit: contain; margin-bottom: 4px; display: inline-block;">
        @endif
        <div style="font-weight: 900; font-size: 13px;">{{ $receiptStoreName }}</div>
        @if($receiptAddress)
            <div style="font-size: 10px; color: #6b7280; margin-top: 2px;">{{ $receiptAddress }}</div>
        @endif
        @if($receiptPhone)
            <div style="font-size: 10px; color: #6b7280;">Telp: {{ $receiptPhone }}</div>
        @endif
    </div>

    <div class="divider"></div>

    <!-- Info Nota -->
    <div class="flex">
        <span style="color: #4b5563;">No. Nota</span>
        <span class="bold">{{ $sale->invoice_number }}</span>
    </div>
    <div class="flex">
        <span style="color: #4b5563;">Tanggal</span>
        <span>{{ $sale->date->format('d/m/Y  H:i') }}</span>
    </div>
    <div class="flex">
        <span style="color: #4b5563;">Kasir</span>
        <span>Kasir 1</span>
    </div>
    @if($sale->customer && strtoupper($sale->customer->name) !== 'UMUM')
    <div class="flex">
        <span style="color: #4b5563;">Pelanggan</span>
        <span>{{ $sale->customer->name }}</span>
    </div>
    @endif

    <div class="divider"></div>

    <!-- Rincian Barang Belanja -->
    <table class="table-item">
        @foreach($sale->items as $item)
            <tr>
                <td colspan="2" class="text-left" style="font-weight: 600; font-size: 11px;">{{ $item->product->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="text-left" style="width: 50%; font-size: 9.5px; color: #6b7280;">
                    &nbsp;&nbsp;{{ (float)$item->qty }} x Rp {{ number_format($item->selling_price, 0, ',', '.') }}
                </td>
                <td class="text-right" style="width: 50%; font-size: 10.5px;">
                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                </td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <!-- Total Perhitungan -->
    <div class="flex">
        <span>Subtotal</span>
        <span>Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span>
    </div>
    @if($sale->discount > 0)
        <div class="flex">
            <span>Diskon</span>
            <span>-Rp {{ number_format($sale->discount, 0, ',', '.') }}</span>
        </div>
    @endif
    <div class="flex bold" style="font-size: 11.5px; margin-top: 1px;">
        <span>Total</span>
        <span>Rp {{ number_format($sale->total, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Bayar ({{ strtoupper($sale->payment_method ?? 'Tunai') }})</span>
        <span>Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</span>
    </div>
    @if($sale->remaining_receivable > 0)
        <div class="flex bold" style="color: #b91c1c;">
            <span>Sisa Piutang</span>
            <span>Rp {{ number_format($sale->remaining_receivable, 0, ',', '.') }}</span>
        </div>
    @else
        <div class="flex">
            <span>Kembali</span>
            <span>Rp {{ number_format(max(0, $sale->paid_amount - $sale->total), 0, ',', '.') }}</span>
        </div>
    @endif

    <div class="divider"></div>

    <!-- Footer Struk -->
    <div class="text-center" style="font-size: 9.5px; color: #6b7280; margin-top: 6px; font-style: italic;">
        @if(!empty($setting->receipt_footer))
            <div>{!! nl2br(e($setting->receipt_footer)) !!}</div>
        @else
            <div>Terima kasih telah berbelanja!</div>
            <div>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</div>
        @endif
        <div style="margin-top: 6px; font-style: normal; color: #9ca3af;">--- * ---</div>
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
