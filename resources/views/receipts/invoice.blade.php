<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INVOICE #{{ $sale->invoice_number }} - {{ $store->store_name }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .invoice-box { box-shadow: none !important; border: none !important; padding: 0 !important; }
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f5f7;
            color: #2b2b2b;
            margin: 0;
            padding: 20px;
        }
        .invoice-box {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px 40px;
            border: 1px solid #dcdcdc;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-radius: 4px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px double #14421b;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .brand-title {
            font-size: 22px;
            font-weight: 900;
            font-style: italic;
            text-decoration: underline;
            color: #14421b;
            letter-spacing: 0.5px;
        }
        .store-sub {
            font-size: 11px;
            color: #555;
            margin-top: 3px;
            line-height: 1.4;
        }
        .invoice-badge {
            text-align: right;
            vertical-align: top;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: 800;
            color: #14421b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        .invoice-meta {
            font-size: 12px;
            color: #555;
            margin-top: 5px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 25px;
            font-size: 12px;
        }
        .meta-table td {
            vertical-align: top;
            padding: 4px 0;
        }
        .bill-to {
            width: 55%;
        }
        .bill-to strong {
            color: #14421b;
            font-size: 13px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 12px;
        }
        .items-table th {
            background-color: #14421b;
            color: #ffffff;
            font-weight: 700;
            text-align: left;
            padding: 10px 12px;
            border: 1px solid #14421b;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        .items-table td {
            padding: 9px 12px;
            border: 1px solid #e0e0e0;
        }
        .items-table tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-wrap {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        .summary-table {
            width: 320px;
            border-collapse: collapse;
            font-size: 12px;
        }
        .summary-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #eee;
        }
        .summary-table .grand-total {
            background-color: #eaf3eb;
            font-size: 14px;
            font-weight: 800;
            color: #14421b;
            border-top: 2px solid #14421b;
            border-bottom: 2px solid #14421b;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding: 0 30px;
            text-align: center;
            font-size: 12px;
        }
        .sig-box {
            width: 180px;
        }
        .sig-line {
            margin-top: 65px;
            border-top: 1px solid #333;
            font-weight: 600;
            padding-top: 4px;
        }
        .no-print-bar {
            max-width: 800px;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background-color: #14421b;
            color: #fff;
            padding: 8px 18px;
            border: none;
            font-weight: bold;
            cursor: pointer;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn-print:hover { background-color: #0d2d12; }
        .btn-back {
            background-color: #6c757d;
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
        }
    </style>
</head>
<body>

    <div class="no-print-bar no-print">
        <a href="javascript:window.history.back()" class="btn-back">&larr; Kembali</a>
        <div>
            <a href="{{ route('receipt.thermal', $sale->id) }}" class="btn-back" style="background:#1b3f75; margin-right:8px;" target="_blank">Cetak Struk Kasir (Thermal)</a>
            <button onclick="window.print()" class="btn-print">Cetak Invoice (A4 / A5)</button>
        </div>
    </div>

    <div class="invoice-box">
        <table class="header-table">
            <tr>
                <td style="vertical-align: top;">
                    <div class="brand-title">{{ $store->store_name ?? 'ELEPHANT CELL GROUP' }}</div>
                    <div class="store-sub">
                        {{ $store->address ?? 'Pusat Aksesoris & Pulsa All Operator - Tambun Selatan, Bekasi' }}<br>
                        Telp / WA: {{ $store->phone ?? '0812-XXXX-XXXX' }}
                    </div>
                </td>
                <td class="invoice-badge">
                    <div class="invoice-title">FAKTUR PENJUALAN</div>
                    <div class="invoice-meta">
                        <strong>No. Faktur:</strong> {{ $sale->invoice_number }}<br>
                        <strong>Tanggal:</strong> {{ date('d F Y H:i', strtotime($sale->sale_date)) }}<br>
                        <strong>Tipe:</strong> <span style="text-transform:uppercase; font-weight:bold; color:{{ $sale->sale_type == 'grosir' ? '#d9534f' : '#14421b' }};">{{ $sale->sale_type }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <table class="meta-table">
            <tr>
                <td class="bill-to">
                    <span style="color:#777; font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">Kepada Yth:</span><br>
                    <strong>{{ $sale->customer->name ?? 'Pelanggan Umum (Umum)' }}</strong><br>
                    {{ $sale->customer->address ?? '-' }}<br>
                    Telp: {{ $sale->customer->phone ?? '-' }}
                </td>
                <td style="width: 45%; text-align: right;">
                    <span style="color:#777; font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">Status Pembayaran:</span><br>
                    @if($sale->payment_status == 'lunas')
                        <span style="background:#d4edda; color:#155724; padding:3px 10px; border-radius:3px; font-weight:bold; display:inline-block; margin-top:3px;">LUNAS</span>
                    @else
                        <span style="background:#fff3cd; color:#856404; padding:3px 10px; border-radius:3px; font-weight:bold; display:inline-block; margin-top:3px;">PIUTANG (BELUM LUNAS)</span><br>
                        <small style="color:#666;">Jatuh Tempo: {{ $sale->due_date ? date('d/m/Y', strtotime($sale->due_date)) : '-' }}</small>
                    @endif
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 35px;">No</th>
                    <th style="width: 100px;">Kode Item</th>
                    <th>Nama Barang / Deskripsi</th>
                    <th class="text-center" style="width: 65px;">Qty</th>
                    <th class="text-right" style="width: 110px;">Harga Satuan</th>
                    <th class="text-right" style="width: 120px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td style="font-family: monospace; font-weight: 600;">{{ $item->product->code ?? '-' }}</td>
                    <td>
                        <strong>{{ $item->product->name ?? '-' }}</strong>
                        @if($item->product && $item->product->category)
                            <br><small style="color:#777;">Kategori: {{ $item->product->category->name }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary-wrap">
            <table class="summary-table">
                <tr>
                    <td>Total Transaksi</td>
                    <td class="text-right"><strong>Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</strong></td>
                </tr>
                <tr>
                    <td>Diskon / Potongan</td>
                    <td class="text-right">Rp {{ number_format($sale->discount_amount ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr class="grand-total">
                    <td>GRAND TOTAL</td>
                    <td class="text-right">Rp {{ number_format($sale->final_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Jumlah Dibayar</td>
                    <td class="text-right">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>
                        @if($sale->payment_status == 'lunas')
                            Kembalian
                        @else
                            Sisa Piutang
                        @endif
                    </td>
                    <td class="text-right" style="font-weight:bold; color:{{ $sale->payment_status == 'lunas' ? '#14421b' : '#c82333' }};">
                        @if($sale->payment_status == 'lunas')
                            Rp {{ number_format($sale->change_amount, 0, ',', '.') }}
                        @else
                            Rp {{ number_format($sale->remaining_debt, 0, ',', '.') }}
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div style="font-size: 11px; color: #555; background: #fdf6e2; border-left: 3px solid #14421b; padding: 8px 12px; margin-bottom: 25px;">
            <strong>Catatan:</strong>
            <ol style="margin: 3px 0 0 15px; padding: 0;">
                <li>Barang yang sudah dibeli dapat ditukar maksimal 2 hari kerja dengan menyertakan bukti faktur ini.</li>
                <li>Garansi tidak berlaku jika stiker segel rusak atau kerusakan akibat kelalaian pemakaian.</li>
                <li>Terima kasih telah berbelanja di {{ $store->store_name ?? 'ELEPHANT CELL GROUP' }}.</li>
            </ol>
        </div>

        <div class="signatures">
            <div class="sig-box">
                <div>Penerima / Pelanggan,</div>
                <div class="sig-line">{{ $sale->customer->name ?? '( ................................... )' }}</div>
            </div>
            <div class="sig-box">
                <div>Hormat Kami,</div>
                <div class="sig-line">Kasir: {{ $sale->user->name ?? 'Admin' }}</div>
            </div>
        </div>
    </div>

</body>
</html>
