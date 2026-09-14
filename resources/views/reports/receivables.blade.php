@extends('layouts.app')

@section('title', 'Laporan Piutang')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>LAPORAN PIUTANG (NOTA PENJUALAN BELUM LUNAS)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Daftar tagihan penjualan retail & grosir tempo kepada pelanggan.</p>
        </div>

        <div class="bg-sky-50 border border-sky-300 px-4 py-2 rounded text-right">
            <span class="text-[10px] text-slate-500 uppercase font-bold block">Total Piutang Belum Tertagih:</span>
            <span class="font-mono font-extrabold text-base text-sky-800">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Excel Instruction -->
    <div class="bg-slate-50 border border-slate-200 p-3 rounded-lg text-xs text-slate-800 flex items-start gap-2.5">
        <svg class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="leading-relaxed">
            <strong>PETUNJUK EXCEL LAPORAN PIUTANG:</strong> Laporan Piutang untuk melihat transaksi penjualan yang masih memiliki nilai kredit (Piutang). Jika nota piutang penjualan sudah dibayar lunas melalui menu <strong>Pembayaran Piutang</strong>, maka transaksi akan hilang otomatis dari tabel ini.
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-2.5 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">Nota Penjualan Belum Lunas</h3>
            <span class="text-[10px] text-green-200">{{ $receivables->total() }} Nota Aktif</span>
        </div>

        <x-table-toolbar tableId="reportReceivablesTable" excelName="Laporan_Piutang_Belum_Lunas" placeholder="Cari nota, pelanggan..." />

        <div class="overflow-x-auto">
            <table id="reportReceivablesTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th>TANGGAL NOTA</th>
                        <th>NO TRANSAKSI</th>
                        <th>PELANGGAN</th>
                        <th class="text-right">TOTAL PENJUALAN</th>
                        <th class="text-right">TOTAL DIBAYAR</th>
                        <th class="text-right">POTONGAN</th>
                        <th class="text-right">SISA PIUTANG</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receivables as $idx => $r)
                        <tr>
                            <td class="text-center text-slate-500 font-semibold">{{ $receivables->firstItem() + $idx }}</td>
                            <td class="whitespace-nowrap">{{ $r->date->format('d/m/Y H:i') }}</td>
                            <td class="font-mono font-bold text-emerald-900">{{ $r->invoice_number }}</td>
                            <td class="font-bold text-slate-800">{{ $r->customer->name ?? 'UMUM' }}</td>
                            <td class="text-right font-mono">Rp {{ number_format($r->total, 0, ',', '.') }}</td>
                            <td class="text-right font-mono text-emerald-700">Rp {{ number_format($r->paid_amount, 0, ',', '.') }}</td>
                            <td class="text-right font-mono text-slate-500">{{ $r->discount > 0 ? 'Rp ' . number_format($r->discount, 0, ',', '.') : '-' }}</td>
                            <td class="text-right font-mono font-extrabold text-sky-800">Rp {{ number_format($r->remaining_receivable, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800">
                                    {{ $r->status }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('receivable.payments') }}" class="px-2.5 py-1 rounded bg-[#14421b] text-white hover:bg-emerald-800 text-[10px] font-bold inline-flex items-center gap-1">
                                    <span>Terima</span>
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-8 text-slate-400 font-medium italic">
                                Tidak ada piutang tertunggak! Seluruh penjualan lunas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-2 border-t border-slate-200 bg-slate-50">
            {{ $receivables->links() }}
        </div>
    </div>
</div>
@endsection
