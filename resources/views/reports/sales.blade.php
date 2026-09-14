@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>LAPORAN PENJUALAN (RETAIL & GROSIR)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Rekapitulasi penjualan fisik seluruh transaksi kasir eceran dan grosir partai.</p>
        </div>

        <form action="{{ route('reports.sales') }}" method="GET" class="flex flex-wrap items-center gap-2 text-xs">
            <select name="sale_type" class="px-2.5 py-1.5 border border-slate-300 rounded font-semibold">
                <option value="all" {{ $saleType === 'all' ? 'selected' : '' }}>-- Semua Penjualan --</option>
                <option value="retail" {{ $saleType === 'retail' ? 'selected' : '' }}>Retail (Eceran)</option>
                <option value="grosir" {{ $saleType === 'grosir' ? 'selected' : '' }}>Grosir (Partai)</option>
            </select>
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-2 py-1.5 border border-slate-300 rounded font-medium">
            <span class="text-slate-400 font-bold">-</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-2 py-1.5 border border-slate-300 rounded font-medium">
            <button type="submit" class="btn-retro btn-refresh">
                <span>REFRESH</span>
            </button>
        </form>
    </div>

    <!-- Summary Box -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm">
            <span class="text-[10px] text-slate-500 block font-semibold uppercase">TOTAL BARANG TERJUAL</span>
            <span class="text-lg font-mono font-bold text-slate-900">{{ (float)$totalQty }} Pcs</span>
        </div>
        <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm">
            <span class="text-[10px] text-slate-500 block font-semibold uppercase">TOTAL OMZET BRUTO</span>
            <span class="text-lg font-mono font-bold text-slate-900">Rp {{ number_format($totalSubtotal, 0, ',', '.') }}</span>
        </div>
        <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm">
            <span class="text-[10px] text-slate-500 block font-semibold uppercase">TOTAL UANG KAS MASUK</span>
            <span class="text-lg font-mono font-bold text-emerald-800">Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>
        </div>
        <div class="bg-white p-3 rounded-lg border border-sky-300 shadow-sm">
            <span class="text-[10px] text-sky-600 block font-semibold uppercase">SISA PIUTANG PELANGGAN</span>
            <span class="text-lg font-mono font-bold text-sky-700">Rp {{ number_format($totalReceivable, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-2.5 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">Tabel Data Laporan Penjualan</h3>
            <span class="text-[10px] text-green-200">Total: {{ $sales->total() }} Nota</span>
        </div>

        <x-table-toolbar tableId="reportSalesTable" excelName="Laporan_Penjualan" placeholder="Cari invoice, pelanggan..." />

        <div class="overflow-x-auto">
            <table id="reportSalesTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th>TANGGAL</th>
                        <th>NO TRANSAKSI</th>
                        <th class="text-center">TIPE</th>
                        <th>PELANGGAN</th>
                        <th class="text-center">QTY</th>
                        <th class="text-right">SUBTOTAL</th>
                        <th class="text-right">POTONGAN</th>
                        <th class="text-right">TOTAL AKHIR</th>
                        <th class="text-right">DIBAYAR</th>
                        <th class="text-right">PIUTANG</th>
                        <th class="text-center">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $idx => $s)
                        <tr>
                            <td class="text-center text-slate-500 font-semibold">{{ $sales->firstItem() + $idx }}</td>
                            <td class="whitespace-nowrap">{{ $s->date->format('d/m/Y H:i') }}</td>
                            <td class="font-mono font-bold text-emerald-900">{{ $s->invoice_number }}</td>
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $s->sale_type === 'grosir' ? 'bg-indigo-100 text-indigo-900' : 'bg-emerald-100 text-emerald-900' }}">
                                    {{ $s->sale_type }}
                                </span>
                            </td>
                            <td class="font-bold text-slate-800">{{ $s->customer->name ?? 'UMUM' }}</td>
                            <td class="text-center font-mono font-bold">{{ $s->items->sum('qty') }}</td>
                            <td class="text-right font-mono">Rp {{ number_format($s->subtotal, 0, ',', '.') }}</td>
                            <td class="text-right font-mono text-slate-500">{{ $s->discount > 0 ? 'Rp ' . number_format($s->discount, 0, ',', '.') : '-' }}</td>
                            <td class="text-right font-mono font-bold text-slate-900">Rp {{ number_format($s->total, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-emerald-800">Rp {{ number_format($s->paid_amount, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold {{ $s->remaining_receivable > 0 ? 'text-sky-700' : 'text-slate-400' }}">
                                {{ $s->remaining_receivable > 0 ? 'Rp ' . number_format($s->remaining_receivable, 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $s->status === 'LUNAS' ? 'bg-emerald-100 text-emerald-800' : 'bg-sky-100 text-sky-800' }}">
                                    {{ $s->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-6 text-slate-400">Tidak ada data penjualan pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-2 border-t border-slate-200 bg-slate-50">
            {{ $sales->links() }}
        </div>
    </div>
</div>
@endsection
