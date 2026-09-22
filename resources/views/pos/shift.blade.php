@extends('layouts.app')

@section('title', 'Rekap Shift & Setor Penjualan')

@section('content')
<div class="space-y-6">
    
    <!-- HEADER BAR WITH OUTLET SELECTOR & STATUS -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex items-start sm:items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-emerald-700 text-white flex items-center justify-center flex-shrink-0 shadow-md shadow-emerald-700/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-base sm:text-lg font-extrabold text-slate-800 tracking-tight">
                        Rekap Shift & Setor Penjualan
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse mr-1.5"></span>
                        Shift #{{ $summary['shift_number'] }} Aktif
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5 flex-wrap">
                    <span>Mulai: <strong class="text-slate-700">{{ $summary['start_time_formatted'] }}</strong></span>
                    <span>&bull;</span>
                    <span>Durasi: <strong class="text-slate-700">{{ $summary['duration'] }}</strong></span>
                    <span>&bull;</span>
                    <span>Petugas: <strong class="text-slate-700">{{ $summary['user_name'] }}</strong></span>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap self-stretch lg:self-auto justify-end">
            <!-- Filter Toko (Khusus Admin / Superadmin) -->
            @if($isAdmin)
            <div class="flex items-center gap-1.5 bg-slate-50 p-1.5 rounded-xl border border-slate-200">
                <svg class="w-4 h-4 text-slate-400 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <select id="outletSelectFilter" onchange="window.location.href='{{ route('pos.shift') }}?outlet_id=' + this.value" class="text-xs font-bold bg-transparent text-slate-700 focus:outline-none pr-3 cursor-pointer">
                    <option value="" {{ empty($selectedOutletId) ? 'selected' : '' }}>Semua Cabang / Pusat</option>
                    @foreach($outlets as $o)
                        <option value="{{ $o->id }}" {{ $selectedOutletId == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <div class="px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-bold text-slate-700 border border-slate-200 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>{{ $summary['outlet_name'] }}</span>
            </div>
            @endif

            <button type="button" onclick="window.location.reload()" class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition border border-slate-200" title="Muat Ulang Data">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </button>

            <button type="button" onclick="openShiftModal()" class="px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white font-bold text-xs shadow-sm shadow-emerald-700/30 flex items-center gap-2 transition cursor-pointer">
                <svg class="w-4 h-4 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Tutup Shift & Setor</span>
            </button>
        </div>
    </div>

    <!-- 3 KANTONG KAS (CASH RETAIL, CASH MULTI, CASH TRANSFER) -->
    <div>
        <div class="flex items-center justify-between mb-3 px-1">
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Posisi 3 Kantong Kas Fisik (Laci & Saldo)</span>
            </h2>
            <span class="text-[11px] text-slate-400">Dipisahkan agar rekonsiliasi setor akurat</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <!-- 1. CASH RETAIL -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-emerald-200/80 shadow-xs relative overflow-hidden group hover:border-emerald-400 transition">
                <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-50 rounded-bl-full -z-0 opacity-60"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-2.5">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-sm">
                                💵
                            </div>
                            <div>
                                <h3 class="text-xs font-extrabold text-slate-800">Cash Retail</h3>
                                <p class="text-[10px] text-slate-400">Kas Tunai Penjualan Toko (1-1110)</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if($isAdmin)
                            <button type="button" onclick="openAdjustRetailModal()" class="px-2 py-0.5 rounded-md bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold text-[10px] shadow-xs flex items-center gap-1 transition cursor-pointer" title="Koreksi Kas Laci (+ / -)">
                                <svg class="w-3 h-3 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>+/- Edit Kas</span>
                            </button>
                            @endif
                            <span class="text-[10px] px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 font-bold border border-emerald-200/60">Kas Laci</span>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <div class="text-[11px] text-slate-500 font-medium">Saldo Fisik di Laci:</div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Rp {{ number_format($summary['cash_retail']['balance'], 0, ',', '.') }}
                        </div>
                    </div>

                    <div class="mt-3 bg-emerald-50/60 rounded-xl p-2.5 border border-emerald-100/80 text-xs space-y-1">
                        <div class="flex justify-between text-slate-600 text-[11px]">
                            <span>Penjualan Tunai (+):</span>
                            <span class="font-bold text-slate-800">+Rp {{ number_format($summary['cash_retail']['shift_cash_sales'], 0, ',', '.') }}</span>
                        </div>
                        @if(($summary['cash_retail']['shift_cash_in'] ?? 0) > 0)
                        <div class="flex justify-between text-emerald-700 text-[11px]">
                            <span>Kas Masuk / Koreksi (+):</span>
                            <span class="font-bold">+Rp {{ number_format($summary['cash_retail']['shift_cash_in'], 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if(($summary['cash_retail']['shift_cash_out'] ?? 0) > 0)
                        <div class="flex justify-between text-rose-600 text-[11px]">
                            <span>Kas Keluar / Beban (-):</span>
                            <span class="font-bold">-Rp {{ number_format($summary['cash_retail']['shift_cash_out'], 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between text-slate-600 text-[11px] pt-1 border-t border-emerald-200/40">
                            <span>Cadangan Modal Awal:</span>
                            <span class="font-bold text-slate-800">Rp {{ number_format($summary['cash_retail']['required_reserve'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-emerald-800 font-bold text-xs pt-1 border-t border-emerald-200/50">
                            <span>Rekomendasi Setor:</span>
                            <span>Rp {{ number_format($summary['cash_retail']['recommended_deposit'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. CASH MULTI -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-sky-200/80 shadow-xs relative overflow-hidden group hover:border-sky-400 transition">
                <div class="absolute top-0 right-0 w-24 h-24 bg-sky-50 rounded-bl-full -z-0 opacity-60"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-2.5">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-800 flex items-center justify-center font-bold text-sm">
                                📱
                            </div>
                            <div>
                                <h3 class="text-xs font-extrabold text-slate-800">Cash Multi</h3>
                                <p class="text-[10px] text-slate-400">Pulsa, PLN, Paket Data (1-1131)</p>
                            </div>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded-md bg-sky-50 text-sky-700 font-bold border border-sky-200/60">Digital</span>
                    </div>

                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <div class="text-[11px] text-slate-500 font-medium">Penjualan Multi Shift Ini:</div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Rp {{ number_format($summary['cash_multi']['shift_sales'], 0, ',', '.') }}
                        </div>
                    </div>

                    <div class="mt-3 bg-sky-50/60 rounded-xl p-2.5 border border-sky-100/80 text-xs space-y-1">
                        <div class="flex justify-between text-slate-600 text-[11px]">
                            <span>Total Transaksi Sukses:</span>
                            <span class="font-bold text-slate-800">{{ $summary['cash_multi']['shift_count'] }} Transaksi</span>
                        </div>
                        <div class="flex justify-between text-sky-800 font-bold text-xs pt-1 border-t border-sky-200/50">
                            <span>Margin Laba Pulsa:</span>
                            <span class="text-emerald-700">+Rp {{ number_format($summary['cash_multi']['shift_profit'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. CASH TRANSFER -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-purple-200/80 shadow-xs relative overflow-hidden group hover:border-purple-400 transition">
                <div class="absolute top-0 right-0 w-24 h-24 bg-purple-50 rounded-bl-full -z-0 opacity-60"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-2.5">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-sm">
                                🔁
                            </div>
                            <div>
                                <h3 class="text-xs font-extrabold text-slate-800">Cash Transfer</h3>
                                <p class="text-[10px] text-slate-400">Transfer Agen & Tarik Tunai (1-1111)</p>
                            </div>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 font-bold border border-purple-200/60">Agen</span>
                    </div>

                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <div class="text-[11px] text-slate-500 font-medium">Saldo Kas Transfer Laci:</div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Rp {{ number_format($summary['cash_transfer']['balance'], 0, ',', '.') }}
                        </div>
                    </div>

                    <div class="mt-3 bg-purple-50/60 rounded-xl p-2.5 border border-purple-100/80 text-xs space-y-1">
                        <div class="flex justify-between text-slate-600 text-[11px]">
                            <span>Transfer Masuk ({{ $summary['cash_transfer']['shift_transfers_count'] }} trx):</span>
                            <span class="font-bold text-slate-800">Rp {{ number_format($summary['cash_transfer']['shift_transfers_in'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-purple-800 font-bold text-xs pt-1 border-t border-purple-200/50">
                            <span>Fee Admin Masuk:</span>
                            <span class="text-emerald-700">+Rp {{ number_format($summary['cash_transfer']['shift_transfers_fee'] + $summary['cash_transfer']['shift_withdrawals_fee'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- RINCIAN OPERASIONAL SHIFT INI (GRID RINGKAS & TIDAK PUSING) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-extrabold text-slate-800">Rincian Operasional Shift Berjalan</h3>
                <p class="text-xs text-slate-500">Kalkulasi otomatis transaksi dari jam {{ $summary['start_time_formatted'] }} sampai sekarang</p>
            </div>
            <div class="text-right">
                <span class="text-xs font-bold text-slate-500">Total Transaksi Nota:</span>
                <span class="ml-1 text-sm font-black text-slate-800 bg-slate-100 px-2 py-0.5 rounded-lg border border-slate-200">{{ $summary['summary']['total_transactions'] }}</span>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                <div class="text-[10px] uppercase font-bold text-slate-400">Penjualan Tunai</div>
                <div class="text-sm font-bold text-slate-800 mt-1">Rp {{ number_format($summary['summary']['cash_sales'], 0, ',', '.') }}</div>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                <div class="text-[10px] uppercase font-bold text-slate-400">Non-Tunai (QRIS/TF)</div>
                <div class="text-sm font-bold text-slate-800 mt-1">Rp {{ number_format($summary['summary']['non_cash_sales'], 0, ',', '.') }}</div>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                <div class="text-[10px] uppercase font-bold text-slate-400">Tempo (Piutang)</div>
                <div class="text-sm font-bold text-slate-800 mt-1">Rp {{ number_format($summary['summary']['receivable_sales'], 0, ',', '.') }}</div>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                <div class="text-[10px] uppercase font-bold text-slate-400">Laba Bersih Multi</div>
                <div class="text-sm font-bold text-emerald-700 mt-1">+Rp {{ number_format($summary['summary']['total_digital_profit'], 0, ',', '.') }}</div>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                <div class="text-[10px] uppercase font-bold text-slate-400">Fee Transfer Agen</div>
                <div class="text-sm font-bold text-emerald-700 mt-1">+Rp {{ number_format($summary['summary']['total_transfer_fee'], 0, ',', '.') }}</div>
            </div>
            <div class="p-3 bg-rose-50/60 rounded-xl border border-rose-200/60">
                <div class="text-[10px] uppercase font-bold text-rose-500">Biaya Kas Keluar</div>
                <div class="text-sm font-bold text-rose-700 mt-1">-Rp {{ number_format($summary['summary']['total_expense'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <!-- HISTORI TUTUP SHIFT SEBELUMNYA (TABLE RIWAYAT SETORAN PER TOKO) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
            <div>
                <h3 class="text-sm font-extrabold text-slate-800">Histori Tutup Shift & Setoran Sebelumnya</h3>
                <p class="text-xs text-slate-500">Riwayat pergantian shift, rincian setoran, dan cetak struk penyerahan kas</p>
            </div>
            <span class="text-xs bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg font-bold border border-slate-200">
                Total Riwayat: {{ $history->total() }} Shift
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200/80">
                    <tr>
                        <th class="py-3 px-4">Waktu Shift</th>
                        <th class="py-3 px-4">Cabang</th>
                        <th class="py-3 px-4">Kasir / Petugas</th>
                        <th class="py-3 px-4 text-right">Setor Retail</th>
                        <th class="py-3 px-4 text-right">Setor Multi</th>
                        <th class="py-3 px-4 text-right">Setor Transfer</th>
                        <th class="py-3 px-4 text-right font-black text-emerald-800">Total Disetor</th>
                        <th class="py-3 px-4 text-right">Sisa Modal</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($history as $item)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-slate-800">
                                {{ $item->end_time ? $item->end_time->format('d M Y, H:i') : '-' }}
                            </div>
                            <div class="text-[10px] text-slate-400">
                                Mulai: {{ $item->start_time ? $item->start_time->format('H:i') : '-' }}
                            </div>
                        </td>
                        <td class="py-3.5 px-4 font-semibold text-slate-700">
                            {{ $item->outlet->name ?? 'Pusat' }}
                        </td>
                        <td class="py-3.5 px-4 font-semibold text-slate-700">
                            {{ $item->user->name ?? '-' }}
                        </td>
                        <td class="py-3.5 px-4 text-right text-slate-700">
                            Rp {{ number_format($item->cash_retail_deposited, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right text-slate-700">
                            Rp {{ number_format($item->cash_multi_deposited, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right text-slate-700">
                            Rp {{ number_format($item->cash_transfer_deposited, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-extrabold text-emerald-700 text-sm">
                            Rp {{ number_format($item->total_deposited, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-medium text-slate-600">
                            Rp {{ number_format($item->cash_retail_retained, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <a href="{{ route('receipt.thermal_shift', $item->id) }}" target="_blank" class="px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold border border-emerald-200 transition inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                <span>Cetak Struk</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400">
                            Belum ada riwayat tutup shift untuk outlet ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($history->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $history->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

</div>

<!-- MODAL TUTUP SHIFT & SETOR (GANTI SHIFT 3 KAS) -->
<div id="modalCloseShift" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4 backdrop-blur-xs">
    <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 max-h-[92vh] overflow-y-auto">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-lg">
                    💰
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-800">Setor Penjualan & Ganti Shift</h3>
                    <p class="text-xs text-slate-500">Tentukan setoran 3 kas & sisa modal untuk kasir shift selanjutnya</p>
                </div>
            </div>
            <button type="button" onclick="closeShiftModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center text-sm font-bold">
                ✕
            </button>
        </div>

        <form id="formCloseShift" onsubmit="submitCloseShift(event)" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="outlet_id" value="{{ $selectedOutletId }}">

            <!-- 1. KANTONG CASH RETAIL -->
            <div class="bg-emerald-50/60 rounded-2xl p-4 border border-emerald-100">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-extrabold text-emerald-900 flex items-center gap-1.5">
                        <span>💵</span> 1. Cash Retail (Laci Toko)
                    </span>
                    <span class="text-xs font-bold text-emerald-800">
                        Saldo: Rp {{ number_format($summary['cash_retail']['balance'], 0, ',', '.') }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Nominal Disetor (Rp):</label>
                        <input type="number" id="cashRetailDeposit" name="cash_retail_deposit" 
                            value="{{ (int) $summary['cash_retail']['recommended_deposit'] }}" 
                            oninput="calcTotalDeposit()"
                            class="w-full px-3 py-2 rounded-xl border border-emerald-300 text-sm font-bold text-slate-800 bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none" required min="0">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Sisa Modal di Laci (Rp):</label>
                        <input type="number" id="cashRetailRetained" name="cash_retail_retained" 
                            value="{{ (int) $summary['cash_retail']['required_reserve'] }}" 
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-800 bg-slate-100 focus:outline-none" readonly>
                    </div>
                </div>
                <p class="text-[10px] text-emerald-700 mt-1.5">Sistem menjaga modal wajib Rp 400.000 tetap di laci untuk kembalian shift selanjutnya.</p>
            </div>

            <!-- 2. KANTONG CASH MULTI -->
            <div class="bg-sky-50/60 rounded-2xl p-4 border border-sky-100">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-extrabold text-sky-900 flex items-center gap-1.5">
                        <span>📱</span> 2. Cash Multi (Pulsa/PPOB)
                    </span>
                    <span class="text-xs font-bold text-sky-800">
                        Penjualan: Rp {{ number_format($summary['cash_multi']['shift_sales'], 0, ',', '.') }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Nominal Disetor (Rp):</label>
                        <input type="number" id="cashMultiDeposit" name="cash_multi_deposit" 
                            value="{{ (int) $summary['cash_multi']['shift_sales'] }}" 
                            oninput="calcTotalDeposit()"
                            class="w-full px-3 py-2 rounded-xl border border-sky-300 text-sm font-bold text-slate-800 bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none" min="0">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Sisa Modal Multi (Rp):</label>
                        <input type="number" id="cashMultiRetained" name="cash_multi_retained" value="0" 
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-800 bg-white focus:outline-none" min="0">
                    </div>
                </div>
            </div>

            <!-- 3. KANTONG CASH TRANSFER -->
            <div class="bg-purple-50/60 rounded-2xl p-4 border border-purple-100">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-extrabold text-purple-900 flex items-center gap-1.5">
                        <span>🔁</span> 3. Cash Transfer Agen
                    </span>
                    <span class="text-xs font-bold text-purple-800">
                        Saldo: Rp {{ number_format($summary['cash_transfer']['balance'], 0, ',', '.') }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Nominal Disetor (Rp):</label>
                        <input type="number" id="cashTransferDeposit" name="cash_transfer_deposit" 
                            value="{{ (int) max(0, $summary['cash_transfer']['balance']) }}" 
                            oninput="calcTotalDeposit()"
                            class="w-full px-3 py-2 rounded-xl border border-purple-300 text-sm font-bold text-slate-800 bg-white focus:ring-2 focus:ring-purple-500 focus:outline-none" min="0">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Sisa Modal Transfer (Rp):</label>
                        <input type="number" id="cashTransferRetained" name="cash_transfer_retained" value="0" 
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-800 bg-white focus:outline-none" min="0">
                    </div>
                </div>
            </div>

            <!-- TOTAL AKUMULASI SETORAN -->
            <div class="p-3.5 bg-slate-900 text-white rounded-2xl flex items-center justify-between shadow-md">
                <div>
                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total Uang Disetor ke Brankas:</div>
                    <div class="text-xl font-black text-emerald-400" id="totalDepositLabel">Rp 0</div>
                </div>
                <div class="text-right text-[11px] text-slate-300">
                    <div>Shift otomatis reset ke <strong>0</strong></div>
                    <div class="text-[10px] text-slate-400">setelah tombol ditekan</div>
                </div>
            </div>

            <!-- CATATAN -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Shift (Opsional):</label>
                <input type="text" id="shiftNotes" name="notes" placeholder="Contoh: Operasional shift pagi lancar, kas fisik klop." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-600 focus:outline-none">
            </div>

            <div class="pt-2 flex items-center gap-3">
                <button type="button" onclick="closeShiftModal()" class="flex-1 py-3 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-100 transition">
                    Batal
                </button>
                <button type="submit" id="btnSubmitShift" class="flex-1 py-3 rounded-xl bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white font-extrabold text-xs shadow-md shadow-emerald-700/30 transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>SETOR & GANTI SHIFT</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    function openShiftModal() {
        document.getElementById('modalCloseShift').classList.remove('hidden');
        calcTotalDeposit();
    }

    function closeShiftModal() {
        document.getElementById('modalCloseShift').classList.add('hidden');
    }

    function calcTotalDeposit() {
        const ret = parseFloat(document.getElementById('cashRetailDeposit').value || 0);
        const mlt = parseFloat(document.getElementById('cashMultiDeposit').value || 0);
        const trf = parseFloat(document.getElementById('cashTransferDeposit').value || 0);
        const total = ret + mlt + trf;
        document.getElementById('totalDepositLabel').innerText = 'Rp ' + total.toLocaleString('id-ID');
    }

    function submitCloseShift(e) {
        e.preventDefault();
        const ret = parseFloat(document.getElementById('cashRetailDeposit').value || 0);
        const mlt = parseFloat(document.getElementById('cashMultiDeposit').value || 0);
        const trf = parseFloat(document.getElementById('cashTransferDeposit').value || 0);
        const total = ret + mlt + trf;

        if (total < 0) {
            alert('Nominal setoran tidak valid!');
            return;
        }

        if (!confirm(`Konfirmasi Tutup Shift & Setor:\n\nTotal uang disetor ke brankas: Rp ${total.toLocaleString('id-ID')}\n\nSetelah ini, semua indikator penjualan shift akan dibuat 0 lagi untuk kasir berikutnya.\n\nLanjutkan?`)) {
            return;
        }

        const btn = document.getElementById('btnSubmitShift');
        btn.disabled = true;
        btn.innerText = 'Memproses Ganti Shift...';

        const form = document.getElementById('formCloseShift');
        const formData = new FormData(form);

        fetch("{{ route('pos.shift.close') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = 'SETOR & GANTI SHIFT';
            if (data.success) {
                alert(data.message);
                if (data.print_url) {
                    window.open(data.print_url, '_blank');
                }
                closeShiftModal();
                window.location.reload();
            } else {
                alert('Gagal: ' + (data.message || 'Terjadi kesalahan sistem'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = 'SETOR & GANTI SHIFT';
            alert('Error: ' + err.message);
        });
    }

    function openAdjustRetailModal() {
        document.getElementById('modalAdjustRetail').classList.remove('hidden');
    }

    function closeAdjustRetailModal() {
        document.getElementById('modalAdjustRetail').classList.add('hidden');
    }
</script>

<!-- MODAL KOREKSI CASH RETAIL (+ / -) -->
<div id="modalAdjustRetail" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4 backdrop-blur-xs">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-emerald-800 text-white px-5 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-base">💵</span>
                <h3 class="font-bold text-sm">Edit / Koreksi Cash Retail (Kas Laci)</h3>
            </div>
            <button onclick="closeAdjustRetailModal()" class="text-white/80 hover:text-white text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('pos.shift.adjust_cash_retail') }}" method="POST" class="p-5 space-y-4 text-xs">
            @csrf
            <input type="hidden" name="outlet_id" value="{{ $selectedOutletId ?? $outlets->first()?->id }}">

            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 flex justify-between items-center">
                <div>
                    <span class="text-[10px] text-slate-400 block uppercase font-bold">Cabang / Toko</span>
                    <span class="font-bold text-slate-800">{{ $summary['outlet_name'] }}</span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] text-slate-400 block uppercase font-bold">Saldo Laci Saat Ini</span>
                    <span class="font-extrabold text-emerald-800 text-sm">Rp {{ number_format($summary['cash_retail']['balance'], 0, ',', '.') }}</span>
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1.5">Pilih Aksi Koreksi Kas *</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 p-2.5 border border-emerald-300 rounded-xl bg-emerald-50 cursor-pointer hover:bg-emerald-100 transition">
                        <input type="radio" name="type" value="ADD" checked class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="font-bold text-emerald-900 block text-xs">(+) Tambah Kas</span>
                            <span class="text-[10px] text-emerald-700">Modal masuk / koreksi lebih</span>
                        </div>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 border border-rose-300 rounded-xl bg-rose-50 cursor-pointer hover:bg-rose-100 transition">
                        <input type="radio" name="type" value="SUBTRACT" class="text-rose-600 focus:ring-rose-500">
                        <div>
                            <span class="font-bold text-rose-900 block text-xs">(-) Kurang Kas</span>
                            <span class="text-[10px] text-rose-700">Ambil kas / koreksi kurang</span>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Nominal (Rp) *</label>
                <input type="number" step="1000" min="1" name="amount" required placeholder="Contoh: 50000" class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono font-bold text-sm text-slate-900 bg-white focus:ring-2 focus:ring-emerald-600 focus:outline-none">
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Keterangan / Alasan Koreksi *</label>
                <input type="text" name="notes" required placeholder="Misal: Tambah modal uang receh / Koreksi fisik kasir" class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white text-slate-800 focus:ring-2 focus:ring-emerald-600 focus:outline-none">
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="closeAdjustRetailModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 font-bold text-slate-700">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold shadow-xs">SIMPAN KOREKSI</button>
            </div>
        </form>
    </div>
</div>
@endsection
