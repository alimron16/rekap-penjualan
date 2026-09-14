@extends('layouts.app')

@section('title', 'Laporan Laba Jual')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                <span>LAPORAN LABA JUAL (PER PRODUK & PER TRANSAKSI)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Analisis margin profit, omzet, modal HPP dan kontribusi laba setiap barang dagangan.</p>
        </div>

        <form action="{{ route('reports.profit_sales') }}" method="GET" class="flex flex-wrap items-center gap-2 text-xs">
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-2.5 py-1.5 border border-slate-300 rounded font-medium">
            <span class="text-slate-400 font-bold">-</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-2.5 py-1.5 border border-slate-300 rounded font-medium">
            <button type="submit" class="btn-retro btn-refresh">
                <span>REFRESH</span>
            </button>
        </form>
    </div>

    <!-- Total Laba Kotor Banner -->
    <div class="bg-[#14421b] text-white p-3.5 rounded-lg border-2 border-slate-200 shadow-sm flex justify-between items-center text-xs font-bold">
        <span class="text-green-100 uppercase tracking-wider">TOTAL LABA KOTOR PENJUALAN PERIODE INI:</span>
        <span class="font-mono text-xl text-white/80 font-extrabold">Rp {{ number_format($productSummary['total_laba'], 0, ',', '.') }}</span>
    </div>

    <!-- 1. LAPORAN LABA JUAL PER PRODUK -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-2.5 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">1. Laporan Laba Jual Per Produk (Margin & Kontribusi)</h3>
            <span class="text-[10px] text-green-200">{{ count($productSummary['products']) }} Produk Terjual</span>
        </div>

        <x-table-toolbar tableId="profitProductsTable" excelName="Laba_Jual_Per_Produk" placeholder="Cari barang atau kode..." />

        <div class="overflow-x-auto max-h-72">
            <table id="profitProductsTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">NO</th>
                        <th>KODE BARANG</th>
                        <th>NAMA BARANG</th>
                        <th class="text-center">QTY TERJUAL</th>
                        <th class="text-right">OMZET RETAIL</th>
                        <th class="text-right">OMZET GROSIR</th>
                        <th class="text-right">TOTAL OMZET</th>
                        <th class="text-right">TOTAL MODAL (HPP)</th>
                        <th class="text-right">LABA KOTOR</th>
                        <th class="text-center">MARGIN %</th>
                        <th class="text-center">KONTRIBUSI %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productSummary['products'] as $idx => $p)
                        <tr>
                            <td class="text-center text-slate-500 font-semibold">{{ $idx + 1 }}</td>
                            <td class="font-mono font-bold text-emerald-900">{{ $p['code'] }}</td>
                            <td class="font-bold text-slate-800">{{ $p['name'] }}</td>
                            <td class="text-center font-mono font-bold">{{ (float)$p['qty'] }}</td>
                            <td class="text-right font-mono">Rp {{ number_format($p['omzet_retail'], 0, ',', '.') }}</td>
                            <td class="text-right font-mono">Rp {{ number_format($p['omzet_grosir'], 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-slate-900">Rp {{ number_format($p['total_omzet'], 0, ',', '.') }}</td>
                            <td class="text-right font-mono text-slate-600">Rp {{ number_format($p['total_modal'], 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-extrabold text-emerald-700">Rp {{ number_format($p['laba_kotor'], 0, ',', '.') }}</td>
                            <td class="text-center font-mono font-bold {{ $p['margin_pct'] >= 15 ? 'text-emerald-700' : 'text-slate-500' }}">
                                {{ $p['margin_pct'] }}%
                            </td>
                            <td class="text-center font-mono font-bold text-slate-700">
                                {{ $p['kontribusi_pct'] }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-6 text-slate-400">Belum ada barang fisik terjual pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. LAPORAN LABA JUAL PER TRANSAKSI -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#0b3c1a] text-white px-4 py-2.5 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">2. Laporan Laba Jual Per Transaksi Nota</h3>
            <span class="text-[10px] text-white/80">{{ $salesTransactions->total() }} Nota</span>
        </div>

        <x-table-toolbar tableId="profitTransactionsTable" excelName="Laba_Jual_Per_Transaksi" placeholder="Cari invoice, pelanggan..." />

        <div class="overflow-x-auto max-h-72">
            <table id="profitTransactionsTable" class="excel-table">
                <thead>
                    <tr>
                        <th>NO TRANSAKSI</th>
                        <th>TANGGAL</th>
                        <th class="text-center">JENIS</th>
                        <th>PELANGGAN</th>
                        <th class="text-center">QTY</th>
                        <th class="text-right">OMZET</th>
                        <th class="text-right">MODAL (HPP)</th>
                        <th class="text-right">LABA NOTA</th>
                        <th class="text-center">MARGIN %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesTransactions as $s)
                        @php
                            $sTotalHpp = $s->total_hpp;
                            $sLaba = $s->total - $sTotalHpp;
                            $sMargin = $s->total > 0 ? ($sLaba / $s->total) * 100 : 0;
                        @endphp
                        <tr>
                            <td class="font-mono font-bold text-emerald-900">{{ $s->invoice_number }}</td>
                            <td class="whitespace-nowrap">{{ $s->date->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $s->sale_type === 'grosir' ? 'bg-indigo-100 text-indigo-900' : 'bg-emerald-100 text-emerald-900' }}">
                                    {{ $s->sale_type }}
                                </span>
                            </td>
                            <td class="font-bold text-slate-800">{{ $s->customer->name ?? 'UMUM' }}</td>
                            <td class="text-center font-mono font-bold">{{ $s->items->sum('qty') }}</td>
                            <td class="text-right font-mono font-bold text-slate-900">Rp {{ number_format($s->total, 0, ',', '.') }}</td>
                            <td class="text-right font-mono text-slate-600">Rp {{ number_format($sTotalHpp, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-extrabold text-emerald-700">Rp {{ number_format($sLaba, 0, ',', '.') }}</td>
                            <td class="text-center font-mono font-bold text-slate-800">{{ round($sMargin, 1) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-6 text-slate-400">Tidak ada transaksi penjualan pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-2 border-t border-slate-200 bg-slate-50">
            {{ $salesTransactions->links() }}
        </div>
    </div>
</div>
@endsection
