@extends('layouts.app')

@section('title', 'Laporan Kas & Bank')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>LAPORAN KAS & BANK (ARUS KAS MASUK, KELUAR & TRANSFER)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Rekapitulasi seluruh mutasi penerimaan kas, biaya pengeluaran operasional, dan kas transfer agen.</p>
        </div>

        <form action="{{ route('reports.cash') }}" method="GET" class="flex flex-wrap items-center gap-2 text-xs">
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-2.5 py-1.5 border border-slate-300 rounded font-medium">
            <span class="text-slate-400 font-bold">-</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-2.5 py-1.5 border border-slate-300 rounded font-medium">
            <button type="submit" class="btn-retro btn-refresh">
                <span>REFRESH</span>
            </button>
        </form>
    </div>

    <!-- 1. LAPORAN KAS MASUK -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#14421b] text-white px-4 py-2 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">1. Laporan Kas Masuk (Penerimaan Kas Non-Penjualan)</h3>
            <span class="font-mono text-xs font-bold text-white/80">Total: Rp {{ number_format($kasMasuk->sum('amount'), 0, ',', '.') }}</span>
        </div>
        <x-table-toolbar tableId="reportCashInTable" excelName="Laporan_Kas_Masuk" placeholder="Cari transaksi, kasir, catatan..." />
        <div class="overflow-x-auto max-h-56">
            <table id="reportCashInTable" class="excel-table">
                <thead>
                    <tr>
                        <th>NO TRANSAKSI</th>
                        <th>TIMESTAMP</th>
                        <th>KODE DEBET</th>
                        <th>NAMA DEBET (AKUN KAS)</th>
                        <th class="text-right">NOMINAL</th>
                        <th>KODE KREDIT</th>
                        <th>SUMBER DANA (KREDIT)</th>
                        <th>KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kasMasuk as $km)
                        <tr>
                            <td class="font-mono font-bold text-emerald-900">{{ $km->transaction_number }}</td>
                            <td class="whitespace-nowrap">{{ $km->date->format('d/m/Y H:i') }}</td>
                            <td class="font-mono text-xs">{{ $km->debitAccount->code ?? '-' }}</td>
                            <td class="font-bold text-slate-800">{{ $km->debitAccount->name ?? '-' }}</td>
                            <td class="text-right font-mono font-bold text-emerald-800">Rp {{ number_format($km->amount, 0, ',', '.') }}</td>
                            <td class="font-mono text-xs">{{ $km->creditAccount->code ?? '-' }}</td>
                            <td>{{ $km->creditAccount->name ?? '-' }}</td>
                            <td class="text-slate-600">{{ $km->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4 text-slate-400">Tidak ada data kas masuk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. LAPORAN KAS KELUAR -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-2 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">2. Laporan Kas Keluar (Beban Operasional & Jajan)</h3>
            <span class="font-mono text-xs font-bold text-white/80">Total: Rp {{ number_format($kasKeluar->sum('amount'), 0, ',', '.') }}</span>
        </div>
        <x-table-toolbar tableId="reportCashOutTable" excelName="Laporan_Kas_Keluar" placeholder="Cari transaksi, beban, keterangan..." />
        <div class="overflow-x-auto max-h-56">
            <table id="reportCashOutTable" class="excel-table">
                <thead>
                    <tr>
                        <th>NO TRANSAKSI</th>
                        <th>TIMESTAMP</th>
                        <th>KODE DEBET</th>
                        <th>NAMA DEBET (BIAYA / BEBAN)</th>
                        <th class="text-right">NOMINAL</th>
                        <th>KODE KREDIT</th>
                        <th>KELUAR DARI KAS (KREDIT)</th>
                        <th>KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kasKeluar as $kk)
                        <tr>
                            <td class="font-mono font-bold text-emerald-900">{{ $kk->transaction_number }}</td>
                            <td class="whitespace-nowrap">{{ $kk->date->format('d/m/Y H:i') }}</td>
                            <td class="font-mono text-xs">{{ $kk->debitAccount->code ?? '-' }}</td>
                            <td class="font-bold text-slate-800">{{ $kk->debitAccount->name ?? '-' }}</td>
                            <td class="text-right font-mono font-bold text-rose-600">Rp {{ number_format($kk->amount, 0, ',', '.') }}</td>
                            <td class="font-mono text-xs">{{ $kk->creditAccount->code ?? '-' }}</td>
                            <td>{{ $kk->creditAccount->name ?? '-' }}</td>
                            <td class="text-slate-600">{{ $kk->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4 text-slate-400">Tidak ada data kas keluar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. LAPORAN KAS TRANSFER -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#0b3c1a] text-white px-4 py-2 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">3. Laporan Kas Transfer Agen (Transfer Tunai / Tarik Tunai)</h3>
            <span class="font-mono text-xs font-bold text-white/80">Fee Admin Terkumpul: Rp {{ number_format($kasTransfer->sum('admin_fee'), 0, ',', '.') }}</span>
        </div>
        <x-table-toolbar tableId="reportCashTransferTable" excelName="Laporan_Kas_Transfer" placeholder="Cari transfer, rekening, keterangan..." />
        <div class="overflow-x-auto max-h-56">
            <table id="reportCashTransferTable" class="excel-table">
                <thead>
                    <tr>
                        <th>NO TRANSAKSI</th>
                        <th>TIMESTAMP</th>
                        <th>DARI AKUN (BANK)</th>
                        <th>TRANSFER KE (KASIR)</th>
                        <th class="text-right">NOMINAL TRANSAKSI</th>
                        <th class="text-right">BIAYA ADMIN (LABA FEE)</th>
                        <th>KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kasTransfer as $kt)
                        <tr>
                            <td class="font-mono font-bold text-emerald-900">{{ $kt->transaction_number }}</td>
                            <td class="whitespace-nowrap">{{ $kt->date->format('d/m/Y H:i') }}</td>
                            <td>{{ $kt->creditAccount->name ?? '-' }}</td>
                            <td>{{ $kt->debitAccount->name ?? '-' }}</td>
                            <td class="text-right font-mono font-bold">Rp {{ number_format($kt->amount, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-emerald-700">+Rp {{ number_format($kt->admin_fee, 0, ',', '.') }}</td>
                            <td class="text-slate-600">{{ $kt->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-slate-400">Tidak ada data kas transfer.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
