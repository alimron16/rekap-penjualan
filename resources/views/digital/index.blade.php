@extends('layouts.app')

@section('title', 'Penjualan Elektrik & Multi')

@section('content')
<div class="space-y-4">

    <!-- Top Live Balances & Controls -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Saldo Multi Server -->
        <div class="bg-[#14421b] text-white p-3 rounded-lg border-2 border-slate-200 shadow">
            <span class="text-[10px] text-green-200 block font-semibold uppercase">SALDO MULTI (MODAL SERVER)</span>
            <span class="text-xl font-mono font-extrabold text-white/80">Rp {{ number_format($saldoMulti, 0, ',', '.') }}</span>
            <span class="text-[10px] text-green-300 block">Akun: 1-1131 SALDO MULTI</span>
        </div>

        <!-- Cash Laci Kasir -->
        <div class="bg-[#133e1c] text-white p-3 rounded-lg border border-emerald-700 shadow">
            <span class="text-[10px] text-emerald-200 block font-semibold uppercase">CASH RETAIL (KAS LACI)</span>
            <span class="text-xl font-mono font-extrabold text-white">Rp {{ number_format($cashRetail, 0, ',', '.') }}</span>
            <span class="text-[10px] text-emerald-300 block">Akun: 1-1110 CASH RETAIL</span>
        </div>

        <!-- Total Transaksi Sukses Periode Ini -->
        <div class="bg-white p-3 rounded-lg border border-slate-200 shadow">
            <span class="text-[10px] text-slate-500 block font-semibold uppercase">TRANSAKSI SUKSES</span>
            <span class="text-xl font-mono font-extrabold text-emerald-700">{{ $history->where('status', 'SUKSES')->count() }} Trx</span>
            <span class="text-[10px] text-slate-500 block">Laba Margin: Rp {{ number_format($history->where('status', 'SUKSES')->sum('profit_margin'), 0, ',', '.') }}</span>
        </div>

        <!-- Total Transaksi Gagal (Reversed) -->
        <div class="bg-white p-3 rounded-lg border border-rose-300 shadow">
            <span class="text-[10px] text-slate-500 block font-semibold uppercase">TRANSAKSI GAGAL</span>
            <span class="text-xl font-mono font-extrabold text-rose-600">{{ $history->where('status', 'GAGAL')->count() }} Trx</span>
            <span class="text-[10px] text-rose-500 block">Saldo deposit dikembalikan</span>
        </div>
    </div>

    <!-- Excel Warning Box -->
    <div class="bg-slate-50 border border-slate-200 p-3 rounded-lg text-xs text-slate-800 flex items-start gap-2.5">
        <svg class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="leading-relaxed">
            <strong>PETUNJUK EXCEL PENJUALAN ELEKTRIK:</strong> Pada Penjualan Elektrik isi Kode Produk dan No Pelanggan. Jika transaksi pada aplikasi multi server ternyata GAGAL, klik tombol <strong>"GAGALKAN"</strong> pada tabel riwayat agar saldo modal server otomatis kembali dan kas laci berkurang.
        </div>
    </div>

    <!-- DUAL PANE: Form Input Elektrik vs Riwayat Live -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- FORM INPUT ELEKTRIK (Cols 4) -->
        <div class="lg:col-span-4 bg-white rounded-lg border-2 border-[#14421b] shadow-md overflow-hidden flex flex-col">
            <div class="bg-[#14421b] text-white px-4 py-2.5 flex items-center justify-between">
                <h3 class="font-bold text-xs uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span>Input Transaksi Elektrik</span>
                </h3>
                <span class="text-[10px] text-white/80 font-mono font-bold">PE-{{ date('His') }}</span>
            </div>

            <form action="{{ route('digital.store') }}" method="POST" class="p-4 space-y-3.5 text-xs flex-1 flex flex-col justify-between">
                @csrf
                
                <div class="space-y-3">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Pilih Produk Digital *</label>
                        <select name="digital_product_id" id="selectDigitalProduct" required onchange="handleDigitalSelect()" class="w-full px-2.5 py-2 border border-slate-300 rounded font-semibold text-xs bg-white">
                            <option value="">-- Pilih Pulsa / Token Listrik --</option>
                            @foreach($products as $dp)
                                <option value="{{ $dp->id }}" data-hpp="{{ $dp->hpp }}" data-price="{{ $dp->selling_price }}" data-type="{{ $dp->trx_type }}">
                                    [{{ $dp->product_code }}] {{ $dp->name }} - Rp {{ number_format($dp->selling_price, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">No Pelanggan (Meter PLN / No HP / ID Game) *</label>
                        <input type="text" name="customer_number" required placeholder="Contoh: 32018882991 atau 0812XXXXXXXX" class="w-full px-3 py-2 border border-slate-300 rounded font-mono font-bold text-sm text-slate-900 bg-slate-50">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Potong Dari Akun Modal *</label>
                            <select name="deposit_account_id" required class="w-full px-2 py-1.5 border border-slate-300 rounded text-[11px]">
                                @foreach($depositAccounts as $da)
                                    <option value="{{ $da->id }}" {{ $da->code === '1-1131' ? 'selected' : '' }}>{{ $da->name }} (Rp {{ number_format($da->current_balance, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Terima Uang Ke Akun *</label>
                            <select name="cash_account_id" required class="w-full px-2 py-1.5 border border-slate-300 rounded text-[11px]">
                                @foreach($cashAccounts as $ca)
                                    <option value="{{ $ca->id }}" {{ $ca->code === '1-1110' ? 'selected' : '' }}>{{ $ca->name }} (Rp {{ number_format($ca->current_balance, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Price & Margin Summary -->
                    <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 space-y-1.5">
                        <div class="flex justify-between">
                            <span class="text-slate-600">HPP Modal Server:</span>
                            <span id="labelHppVal" class="font-mono font-bold text-slate-800">Rp 0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Harga Jual Kasir:</span>
                            <span id="labelPriceVal" class="font-mono font-bold text-slate-900">Rp 0</span>
                        </div>
                        <div class="flex justify-between pt-1 border-t border-slate-200 font-bold">
                            <span class="text-emerald-900">Estimasi Margin Laba:</span>
                            <span id="labelMarginVal" class="font-mono text-emerald-800 text-sm font-extrabold">+Rp 0</span>
                        </div>
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="notes" placeholder="Contoh: Token rumah Ibu Siti" class="w-full px-2.5 py-1.5 border border-slate-300 rounded">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-200 mt-2">
                    <button type="submit" class="w-full btn-retro btn-save py-2.5 text-sm justify-center flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span>PROSES TRANSAKSI ELEKTRIK</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- RIWAYAT PENJUALAN ELEKTRIK (Cols 8) -->
        <div class="lg:col-span-8 bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="bg-[#133e1c] text-white px-4 py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="font-bold text-xs uppercase tracking-wider">Tabel Log Penjualan Elektrik</h3>
                </div>
                
                <!-- Filter Tanggal Periode -->
                <form action="{{ route('digital.index') }}" method="GET" class="flex flex-wrap items-center gap-1.5 text-xs text-slate-900">
                    <input type="date" name="start_date" value="{{ $startDate }}" class="px-2 py-0.5 rounded text-[11px] bg-white">
                    <span class="text-white">-</span>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="px-2 py-0.5 rounded text-[11px] bg-white">
                    <button type="submit" class="px-2.5 py-0.5 bg-amber-400 text-slate-900 font-bold rounded text-[10px] hover:bg-amber-300">CARI</button>
                </form>
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="excel-table">
                    <thead>
                        <tr>
                            <th>NO TRANSAKSI</th>
                            <th>TIMESTAMP</th>
                            <th>NAMA PRODUK</th>
                            <th>NO TUJUAN / PELANGGAN</th>
                            <th class="text-right">HARGA JUAL</th>
                            <th class="text-right">HPP</th>
                            <th class="text-right">LABA</th>
                            <th class="text-center">STATUS</th>
                            <th class="text-center">AKSI REVERSAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $item)
                            <tr>
                                <td class="font-mono font-bold text-xs text-emerald-900">{{ $item->transaction_number }}</td>
                                <td class="whitespace-nowrap text-[11px] text-slate-500">{{ $item->date->format('d/m/Y H:i') }}</td>
                                <td class="font-semibold text-slate-800">{{ $item->digitalProduct->name ?? '-' }}</td>
                                <td class="font-mono font-bold text-slate-900">{{ $item->customer_number }}</td>
                                <td class="text-right font-mono font-bold">Rp {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                                <td class="text-right font-mono text-slate-600">Rp {{ number_format($item->hpp, 0, ',', '.') }}</td>
                                <td class="text-right font-mono font-bold text-emerald-700">+Rp {{ number_format($item->profit_margin, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $item->status === 'SUKSES' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }}">
                                        {{ $item->status }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($item->status === 'SUKSES')
                                        <form action="{{ route('digital.reverse', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin transaksi multi ini GAGAL? Saldo deposit modal akan dikembalikan dan kas laci berkurang.')">
                                            @csrf
                                            <button type="submit" class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-300 text-[10px] font-bold inline-flex items-center gap-1">
                                                <svg class="w-3 h-3 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                                <span>GAGALKAN</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[10px] text-slate-400 font-semibold italic">Reversed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-8 text-slate-400">Tidak ada riwayat transaksi elektrik pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-2 border-t border-slate-200 bg-slate-50">
                {{ $history->links() }}
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    function handleDigitalSelect() {
        const select = document.getElementById('selectDigitalProduct');
        const opt = select.options[select.selectedIndex];
        if (opt && opt.value) {
            const hpp = parseFloat(opt.dataset.hpp || 0);
            const price = parseFloat(opt.dataset.price || 0);
            const margin = price - hpp;

            document.getElementById('labelHppVal').innerText = 'Rp ' + hpp.toLocaleString('id-ID');
            document.getElementById('labelPriceVal').innerText = 'Rp ' + price.toLocaleString('id-ID');
            document.getElementById('labelMarginVal').innerText = '+Rp ' + margin.toLocaleString('id-ID');
        } else {
            document.getElementById('labelHppVal').innerText = 'Rp 0';
            document.getElementById('labelPriceVal').innerText = 'Rp 0';
            document.getElementById('labelMarginVal').innerText = '+Rp 0';
        }
    }
</script>
@endpush
