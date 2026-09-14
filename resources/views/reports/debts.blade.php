@extends('layouts.app')

@section('title', 'Laporan Hutang')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>LAPORAN HUTANG (NOTA KULAKAN BELUM LUNAS)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Daftar faktur pembelian barang yang masih memiliki saldo kewajiban kepada supplier.</p>
        </div>

        <div class="bg-rose-50 border border-rose-300 px-4 py-2 rounded text-right">
            <span class="text-[10px] text-slate-500 uppercase font-bold block">Total Sisa Hutang:</span>
            <span class="font-mono font-extrabold text-base text-rose-700">Rp {{ number_format($totalHutang, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Excel Instruction -->
    <div class="bg-slate-50 border border-slate-200 p-3 rounded-lg text-xs text-slate-800 flex items-start gap-2.5">
        <svg class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="leading-relaxed">
            <strong>PETUNJUK EXCEL LAPORAN HUTANG:</strong> Laporan Hutang untuk melihat transaksi pembelian yang masih memiliki nilai kredit (Hutang). Jika nota hutang pembelian sudah dibayar lunas melalui menu <strong>Pembayaran Hutang</strong>, maka transaksi akan hilang otomatis dari tabel ini.
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-2.5 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">Faktur Pembelian Belum Lunas</h3>
            <span class="text-[10px] text-green-200">{{ $debts->total() }} Nota Aktif</span>
        </div>

        <x-table-toolbar tableId="reportDebtsTable" excelName="Laporan_Hutang_Belum_Lunas" placeholder="Cari nota, supplier..." />

        <div class="overflow-x-auto">
            <table id="reportDebtsTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th>TANGGAL NOTA</th>
                        <th>NO TRANSAKSI</th>
                        <th>SUPPLIER</th>
                        <th class="text-right">TOTAL PEMBELIAN</th>
                        <th class="text-right">TOTAL DIBAYAR</th>
                        <th class="text-right">POTONGAN</th>
                        <th class="text-right">SISA HUTANG</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($debts as $idx => $d)
                        <tr>
                            <td class="text-center text-slate-500 font-semibold">{{ $debts->firstItem() + $idx }}</td>
                            <td class="whitespace-nowrap">{{ $d->date->format('d/m/Y') }}</td>
                            <td class="font-mono font-bold text-emerald-900">{{ $d->invoice_number }}</td>
                            <td class="font-bold text-slate-800">{{ $d->supplier->name ?? '-' }}</td>
                            <td class="text-right font-mono">Rp {{ number_format($d->total, 0, ',', '.') }}</td>
                            <td class="text-right font-mono text-emerald-700">Rp {{ number_format($d->paid_amount, 0, ',', '.') }}</td>
                            <td class="text-right font-mono text-slate-500">{{ $d->discount > 0 ? 'Rp ' . number_format($d->discount, 0, ',', '.') : '-' }}</td>
                            <td class="text-right font-mono font-extrabold text-rose-700">Rp {{ number_format($d->remaining_debt, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                    {{ $d->status }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('purchase.debt_payments') }}" class="px-2.5 py-1 rounded bg-[#14421b] text-white hover:bg-emerald-800 text-[10px] font-bold inline-flex items-center gap-1">
                                    <span>Bayar</span>
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-8 text-slate-400 font-medium italic">
                                Tidak ada tagihan hutang supplier! Seluruh pembelian lunas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-2 border-t border-slate-200 bg-slate-50">
            {{ $debts->links() }}
        </div>
    </div>
</div>
@endsection
