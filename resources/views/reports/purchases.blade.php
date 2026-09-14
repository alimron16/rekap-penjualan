@extends('layouts.app')

@section('title', 'Laporan Pembelian')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span>LAPORAN PEMBELIAN BARANG (KULAKAN)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Rekapitulasi seluruh faktur kulakan barang dagangan dari supplier.</p>
        </div>

        <form action="{{ route('reports.purchases') }}" method="GET" class="flex flex-wrap items-center gap-2 text-xs">
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-2.5 py-1.5 border border-slate-300 rounded font-medium">
            <span class="text-slate-400 font-bold">-</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-2.5 py-1.5 border border-slate-300 rounded font-medium">
            <button type="submit" class="btn-retro btn-refresh">
                <span>REFRESH</span>
            </button>
        </form>
    </div>

    <!-- Summary Box -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm">
            <span class="text-[10px] text-slate-500 block font-semibold uppercase">TOTAL BARANG DIBELI</span>
            <span class="text-lg font-mono font-bold text-slate-900">{{ (float)$totalQty }} Pcs</span>
        </div>
        <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm">
            <span class="text-[10px] text-slate-500 block font-semibold uppercase">TOTAL PEMBELIAN KOTOR</span>
            <span class="text-lg font-mono font-bold text-slate-900">Rp {{ number_format($totalSubtotal, 0, ',', '.') }}</span>
        </div>
        <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm">
            <span class="text-[10px] text-slate-500 block font-semibold uppercase">TOTAL SUDAH DIBAYAR</span>
            <span class="text-lg font-mono font-bold text-emerald-800">Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>
        </div>
        <div class="bg-white p-3 rounded-lg border border-rose-300 shadow-sm">
            <span class="text-[10px] text-rose-500 block font-semibold uppercase">SISA HUTANG TEMPO</span>
            <span class="text-lg font-mono font-bold text-rose-700">Rp {{ number_format($totalDebt, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-2.5 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">Tabel Data Laporan Pembelian</h3>
            <span class="text-[10px] text-green-200">Total: {{ $purchases->total() }} Nota</span>
        </div>

        <x-table-toolbar tableId="reportPurchasesTable" excelName="Laporan_Pembelian" placeholder="Cari invoice, supplier..." />

        <div class="overflow-x-auto">
            <table id="reportPurchasesTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th>TANGGAL</th>
                        <th>NO TRANSAKSI</th>
                        <th>SUPPLIER</th>
                        <th class="text-center">QTY</th>
                        <th class="text-right">SUBTOTAL</th>
                        <th class="text-right">POTONGAN</th>
                        <th class="text-right">TOTAL AKHIR</th>
                        <th class="text-right">DIBAYAR</th>
                        <th class="text-right">HUTANG</th>
                        <th class="text-center">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $idx => $p)
                        <tr>
                            <td class="text-center text-slate-500 font-semibold">{{ $purchases->firstItem() + $idx }}</td>
                            <td class="whitespace-nowrap">{{ $p->date->format('d/m/Y') }}</td>
                            <td class="font-mono font-bold text-emerald-900">{{ $p->invoice_number }}</td>
                            <td class="font-bold text-slate-800">{{ $p->supplier->name ?? '-' }}</td>
                            <td class="text-center font-mono font-bold">{{ $p->items->sum('qty') }}</td>
                            <td class="text-right font-mono">Rp {{ number_format($p->subtotal, 0, ',', '.') }}</td>
                            <td class="text-right font-mono text-slate-500">{{ $p->discount > 0 ? 'Rp ' . number_format($p->discount, 0, ',', '.') : '-' }}</td>
                            <td class="text-right font-mono font-bold text-slate-900">Rp {{ number_format($p->total, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-emerald-800">Rp {{ number_format($p->paid_amount, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold {{ $p->remaining_debt > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                {{ $p->remaining_debt > 0 ? 'Rp ' . number_format($p->remaining_debt, 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $p->status === 'LUNAS' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ $p->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-6 text-slate-400">Tidak ada data pembelian pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-2 border-t border-slate-200 bg-slate-50">
            {{ $purchases->links() }}
        </div>
    </div>
</div>
@endsection
