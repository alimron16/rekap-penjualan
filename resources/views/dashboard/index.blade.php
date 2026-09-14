@extends('layouts.app')

@section('title', 'Dashboard Operasional')

@section('content')
<div class="space-y-4 sm:space-y-5">

    <!-- Top Filter & Warning Panel -->
    <div class="bg-white rounded-lg p-3 sm:p-4 border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4">
        
        <!-- Filter Form -->
        <form action="{{ route('dashboard') }}" method="GET" class="flex flex-wrap items-center gap-2 sm:gap-3">
            <div class="flex items-center gap-1.5 sm:gap-2">
                <span class="text-xs font-bold text-slate-700">Periode:</span>
                <input type="date" name="start_date" value="{{ $startDate }}" class="text-xs px-2.5 py-1.5 border border-slate-300 rounded font-medium focus:ring-1 focus:ring-emerald-500">
            </div>

            <span class="text-slate-400 font-bold">s/d</span>

            <div class="flex items-center gap-1.5 sm:gap-2">
                <input type="date" name="end_date" value="{{ $endDate }}" class="text-xs px-2.5 py-1.5 border border-slate-300 rounded font-medium focus:ring-1 focus:ring-emerald-500">
            </div>

            <button type="submit" class="btn-retro btn-refresh">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>REFRESH</span>
            </button>
        </form>

        <!-- Instruction Box -->
        <div class="bg-slate-50 border border-slate-200 px-3 py-1.5 rounded text-[11px] text-slate-800 flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span><strong>PETUNJUK:</strong> Data periode terakumulasi otomatis secara real-time dari database ACID.</span>
        </div>
    </div>

    <!-- 8 KOTAK KPI UTAMA (Responsive: 1 col HP, 2 col Tablet, 4 col Desktop) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- 1. Total Persediaan Barang -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Persediaan Barang</p>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>
            <p class="text-xl font-extrabold font-mono mt-2 text-slate-900">Rp {{ number_format($totalPersediaan, 0, ',', '.') }}</p>
            <span class="text-[11px] text-slate-400 mt-1">Nilai fisik stok moving HPP</span>
        </div>

        <!-- 2. Total Hutang -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Hutang Supplier</p>
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <p class="text-xl font-extrabold font-mono mt-2 text-slate-900">Rp {{ number_format($totalHutang, 0, ',', '.') }}</p>
            <span class="text-[11px] text-slate-400 mt-1">Sisa tagihan kulakan belum lunas</span>
        </div>

        <!-- 3. Total Piutang -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Piutang Pelanggan</p>
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-xl font-extrabold font-mono mt-2 text-slate-900">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</p>
            <span class="text-[11px] text-slate-400 mt-1">Penjualan tempo belum tertagih</span>
        </div>

        <!-- 4. Total Kas & Bank -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Kas & Bank</p>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                </div>
            </div>
            <p class="text-xl font-extrabold font-mono mt-2 text-emerald-800">Rp {{ number_format($totalKasBank, 0, ',', '.') }}</p>
            <span class="text-[11px] text-slate-400 mt-1">Cash laci, BCA, BRI & saldo multi</span>
        </div>

        <!-- 5. Total Pendapatan -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Pendapatan</p>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <p class="text-xl font-extrabold font-mono mt-2 text-slate-900">Rp {{ number_format($pl['revenues']['total'], 0, ',', '.') }}</p>
            <span class="text-[11px] text-slate-400 mt-1">Retail + Grosir + Multi + Jasa TF</span>
        </div>

        <!-- 6. Total Biaya -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Biaya Operasional</p>
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                </div>
            </div>
            <p class="text-xl font-extrabold font-mono mt-2 text-slate-900">Rp {{ number_format($pl['expenses']['total'], 0, ',', '.') }}</p>
            <span class="text-[11px] text-slate-400 mt-1">Kas keluar operasional & listrik/sewa</span>
        </div>

        <!-- 7. Total Transaksi -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Transaksi</p>
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
            </div>
            <p class="text-xl font-extrabold font-mono mt-2 text-slate-900">{{ $salesCount }} <span class="text-xs font-normal text-slate-500">Nota</span></p>
            <span class="text-[11px] text-slate-400 mt-1">Retail: {{ $retailSalesCount }} | Grosir: {{ $grosirSalesCount }}</span>
        </div>

        <!-- 8. Laba Bersih Real-Time -->
        <div class="bg-[#133e1c] text-white p-4 rounded-xl border border-emerald-800 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-bold text-emerald-200 uppercase tracking-wider">Laba Bersih Real-Time</p>
                <div class="w-8 h-8 rounded-lg bg-white/10 text-emerald-200 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-xl font-extrabold font-mono mt-2 {{ $pl['net_profit'] >= 0 ? 'text-white' : 'text-white/80' }}">
                Rp {{ number_format($pl['net_profit'], 0, ',', '.') }}
            </p>
            <span class="text-[11px] text-emerald-300/80 mt-1">Laba kotor - Biaya operasional</span>
        </div>
    </div>

    <!-- 3 RINCIAN BREAKDOWN & TARGET PROFIT GAUGE -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        
        <!-- Kolom Kiri: Rincian Akun & Arus Kas -->
        <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-xs space-y-3">
            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-200 flex items-center justify-between">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    <span>Rincian Saldo Kas & Bank</span>
                </span>
                <span class="text-[10px] text-slate-500 font-normal">Akun Aktiva</span>
            </h2>
            <div class="space-y-1.5 text-xs">
                @foreach($cashAccounts as $ca)
                    <div class="flex items-center justify-between py-1 border-b border-dashed border-slate-100">
                        <span class="text-slate-600 font-medium">{{ $ca->name }}</span>
                        <span class="font-mono font-bold text-slate-800">Rp {{ number_format($ca->current_balance, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>

            <div class="pt-2">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-1 border-b border-slate-200 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Rincian Margin & Laba Kotor</span>
                </h3>
                <div class="space-y-1.5 text-xs mt-2">
                    <div class="flex justify-between text-slate-600">
                        <span>Laba Retail Fisik:</span>
                        <span class="font-mono font-semibold">Rp {{ number_format($pl['revenues']['retail'] - $pl['hpp']['retail'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Laba Grosir:</span>
                        <span class="font-mono font-semibold">Rp {{ number_format($pl['revenues']['grosir'] - $pl['hpp']['grosir'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Laba Multi Elektrik:</span>
                        <span class="font-mono font-semibold">Rp {{ number_format($pl['revenues']['multi'] - $pl['hpp']['multi'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Pendapatan Jasa Transfer:</span>
                        <span class="font-mono font-semibold text-emerald-700">Rp {{ number_format($pl['revenues']['jasa_transfer'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-slate-900 pt-1.5 border-t border-slate-200">
                        <span>Total Laba Kotor:</span>
                        <span class="font-mono text-emerald-800">Rp {{ number_format($pl['gross_profit'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Tengah: Realisasi Target Profit -->
        <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-200 flex items-center justify-between">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>Target Profit Bulan Ini</span>
                    </span>
                    <a href="{{ route('settings.templates') }}" class="text-[10px] text-emerald-700 hover:underline">Ubah Target</a>
                </h2>
                
                <div class="grid grid-cols-2 gap-2 my-3 text-xs">
                    <div class="bg-slate-50 p-2.5 rounded border border-slate-200">
                        <span class="text-[10px] text-slate-600 block">Target Bulanan:</span>
                        <span class="font-mono font-bold text-sm text-slate-900">Rp {{ number_format($targetProfit, 0, ',', '.') }}</span>
                    </div>
                    <div class="bg-emerald-50 p-2.5 rounded border border-emerald-300">
                        <span class="text-[10px] text-emerald-700 block">Realisasi Saat Ini:</span>
                        <span class="font-mono font-bold text-sm text-emerald-800">Rp {{ number_format($realizedProfit, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Visual Progress Bar -->
            <div class="py-2 space-y-1.5">
                <div class="flex justify-between text-xs font-bold">
                    <span>Pencapaian: {{ $progressPct }}%</span>
                    <span class="text-slate-500 font-normal font-mono text-[11px]">Sisa: Rp {{ number_format($remainingTarget, 0, ',', '.') }}</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-3.5 overflow-hidden p-0.5 border border-slate-300">
                    <div class="bg-gradient-to-r from-emerald-600 to-amber-400 h-full rounded-full transition-all duration-500" style="width: {{ min(100, $progressPct) }}%"></div>
                </div>
            </div>

            <!-- Target Status Badge -->
            <div class="mt-2 text-center">
                @if($progressPct >= 100)
                    <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full border border-emerald-400">
                        <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>TARGET BULANAN TERCAPAI</span>
                    </span>
                @else
                    <span class="inline-block bg-slate-100 text-slate-800 text-[11px] font-semibold px-3 py-0.5 rounded-full border border-slate-200">
                        Menuju target profit {{ date('F Y', strtotime($startDate)) }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Kolom Kanan: Chart Trend Penjualan -->
        <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-xs flex flex-col">
            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-200 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                <span>Tren Penjualan Harian</span>
            </h2>
            <div class="flex-1 min-h-[180px] pt-2">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>

    </div>

    <!-- TABEL PREVIEW: URUTAN PRODUK TERLARIS -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3 class="text-xs font-bold uppercase tracking-wider text-white">Urutan Produk Terlaris Periode Ini</h3>
            </div>
            <a href="{{ route('master.items') }}" class="text-[11px] text-emerald-200 hover:text-white hover:underline font-semibold flex items-center gap-1">
                <span>Lihat Semua Item</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th class="w-28 text-left">KODE BARANG</th>
                        <th class="text-left">NAMA PRODUK</th>
                        <th class="text-left">JENIS</th>
                        <th class="text-left">MEREK</th>
                        <th class="text-right">HARGA JUAL</th>
                        <th class="text-center">JUMLAH TERJUAL</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProducts as $idx => $tp)
                        <tr>
                            <td class="text-center font-bold text-slate-500">{{ $idx + 1 }}</td>
                            <td class="font-mono font-semibold text-emerald-800">{{ $tp->product->item_code ?? '-' }}</td>
                            <td class="font-bold text-slate-800">{{ $tp->product->name ?? '-' }}</td>
                            <td><span class="px-2 py-0.5 rounded bg-slate-100 text-[10px] font-semibold text-slate-700">{{ $tp->product->type ?? '-' }}</span></td>
                            <td>{{ $tp->product->brand ?? '-' }}</td>
                            <td class="text-right font-mono font-semibold">Rp {{ number_format($tp->product->retail_price ?? 0, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <span class="bg-emerald-50 text-emerald-800 font-bold px-2.5 py-0.5 rounded-full border border-emerald-200 font-mono text-xs">
                                    {{ (int)$tp->total_sold }} pcs
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-6 text-slate-400 font-medium italic">
                                Belum ada riwayat transaksi penjualan fisik pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('salesTrendChart');
        if (ctx) {
            const rawData = @json($dailySales);
            const labels = Object.keys(rawData);
            const values = Object.values(rawData);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels.length ? labels : ['Hari ini'],
                    datasets: [{
                        label: 'Omzet Penjualan (Rp)',
                        data: values.length ? values : [0],
                        backgroundColor: '#15803d',
                        borderColor: '#0b3c1a',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(val) {
                                    return 'Rp ' + (val >= 1000 ? (val/1000) + 'k' : val);
                                },
                                font: { size: 10, family: 'JetBrains Mono' }
                            }
                        },
                        x: {
                            ticks: { font: { size: 10, family: 'Plus Jakarta Sans' } }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
