@extends('layouts.app')

@section('title', 'Laporan Laba Rugi')

@section('content')
<div class="space-y-4">

    <!-- Header & Filter -->
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span>LAPORAN LABA RUGI (INCOME STATEMENT)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Laporan kinerja operasional toko berbasis double-entry bookkeeping berstandar akuntansi.</p>
        </div>

        <form action="{{ route('reports.profit_loss') }}" method="GET" class="flex flex-wrap items-center gap-2 text-xs">
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-2.5 py-1.5 border border-slate-300 rounded font-medium">
            <span class="text-slate-400 font-bold">-</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-2.5 py-1.5 border border-slate-300 rounded font-medium">
            <button type="submit" class="btn-retro btn-refresh">
                <span>REFRESH</span>
            </button>
        </form>
    </div>

    <!-- Excel-like Statement Layout -->
    <div class="max-w-4xl mx-auto bg-white rounded-lg border-2 border-[#14421b] shadow-lg overflow-hidden">
        
        <div class="bg-[#14421b] text-white p-4 text-center border-b border-green-900">
            <h3 class="font-extrabold text-base tracking-wider uppercase">ELEPHANT CELL GROUP</h3>
            <p class="text-xs text-white/80 font-bold uppercase tracking-widest mt-0.5">LAPORAN LABA RUGI KOMPREHENSIF</p>
            <p class="text-[11px] text-green-200 mt-1">Periode: {{ date('d F Y', strtotime($startDate)) }} s/d {{ date('d F Y', strtotime($endDate)) }}</p>
        </div>

        <div class="p-6 space-y-6 text-xs">
            
            <!-- 1. PENDAPATAN USAHA -->
            <div class="space-y-2">
                <div class="bg-emerald-50 px-3 py-1.5 rounded font-extrabold text-emerald-950 text-sm border-l-4 border-emerald-600 flex justify-between">
                    <span>1. PENDAPATAN OPERASIONAL</span>
                    <span></span>
                </div>
                
                <div class="space-y-1.5 pl-4 pr-2">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-700">Pendapatan Penjualan Retail Fisik</span>
                        <span class="font-mono font-semibold">Rp {{ number_format($pl['revenues']['retail'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-700">Pendapatan Penjualan Grosir Mitra</span>
                        <span class="font-mono font-semibold">Rp {{ number_format($pl['revenues']['grosir'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-700">Pendapatan Penjualan Multi Elektrik (Pulsa/Token)</span>
                        <span class="font-mono font-semibold">Rp {{ number_format($pl['revenues']['multi'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-700">Pendapatan Jasa Agen Transfer Fee</span>
                        <span class="font-mono font-semibold text-emerald-700">Rp {{ number_format($pl['revenues']['jasa_transfer'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="flex justify-between font-bold text-slate-900 bg-slate-50 px-4 py-2 rounded">
                    <span>TOTAL PENDAPATAN KOTOR:</span>
                    <span class="font-mono text-sm text-emerald-800">Rp {{ number_format($pl['revenues']['total'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- 2. HARGA POKOK PENJUALAN (HPP) -->
            <div class="space-y-2">
                <div class="bg-slate-50 px-3 py-1.5 rounded font-extrabold text-slate-800 text-sm border-l-4 border-slate-300 flex justify-between">
                    <span>2. BEBAN HARGA POKOK PENJUALAN (HPP)</span>
                    <span></span>
                </div>

                <div class="space-y-1.5 pl-4 pr-2">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-700">HPP Penjualan Retail Fisik (Moving Average)</span>
                        <span class="font-mono font-semibold text-slate-700">Rp {{ number_format($pl['hpp']['retail'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-700">HPP Penjualan Grosir Mitra</span>
                        <span class="font-mono font-semibold text-slate-700">Rp {{ number_format($pl['hpp']['grosir'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-700">HPP Modal Server Multi Elektrik</span>
                        <span class="font-mono font-semibold text-slate-700">Rp {{ number_format($pl['hpp']['multi'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="flex justify-between font-bold text-slate-900 bg-slate-50 px-4 py-2 rounded">
                    <span>TOTAL BEBAN HPP:</span>
                    <span class="font-mono text-sm text-rose-700">Rp {{ number_format($pl['hpp']['total'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- LABA KOTOR BANNER -->
            <div class="bg-slate-50 p-3.5 rounded-lg border-2 border-slate-200 flex justify-between items-center text-sm font-extrabold">
                <span class="text-slate-900">LABA KOTOR USAHA (GROSS PROFIT):</span>
                <span class="font-mono text-base text-emerald-900">Rp {{ number_format($pl['gross_profit'], 0, ',', '.') }}</span>
            </div>

            <!-- 3. BEBAN OPERASIONAL (BIAYA) -->
            <div class="space-y-2">
                <div class="bg-rose-50 px-3 py-1.5 rounded font-extrabold text-rose-950 text-sm border-l-4 border-rose-600 flex justify-between">
                    <span>3. BEBAN BIAYA OPERASIONAL & UMUM</span>
                    <span></span>
                </div>

                <div class="space-y-1.5 pl-4 pr-2">
                    @forelse($pl['expenses']['breakdown'] as $accName => $val)
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span class="text-slate-700">{{ $accName }}</span>
                            <span class="font-mono font-semibold text-rose-700">Rp {{ number_format($val, 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <div class="py-2 text-slate-400 italic text-center">Belum ada catatan biaya operasional pada periode ini.</div>
                    @endforelse
                </div>

                <div class="flex justify-between font-bold text-slate-900 bg-slate-50 px-4 py-2 rounded">
                    <span>TOTAL BIAYA OPERASIONAL:</span>
                    <span class="font-mono text-sm text-rose-700">Rp {{ number_format($pl['expenses']['total'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- LABA BERSIH AKHIR -->
            <div class="bg-[#14421b] text-white p-4 rounded-xl shadow flex justify-between items-center text-base font-extrabold">
                <div>
                    <span class="block tracking-wide">LABA BERSIH PERIODE BERJALAN (NET PROFIT):</span>
                    <span class="text-[11px] text-green-200 font-normal">Formula: Total Laba Kotor - Total Biaya Operasional</span>
                </div>
                <span class="font-mono text-2xl {{ $pl['net_profit'] >= 0 ? 'text-white/80' : 'text-rose-400' }}">
                    Rp {{ number_format($pl['net_profit'], 0, ',', '.') }}
                </span>
            </div>

        </div>

    </div>

</div>
@endsection
