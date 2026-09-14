@extends('layouts.app')

@section('title', 'Tutup Buku Tahunan')

@section('content')
<div class="space-y-4">
    
    <!-- Top Header & Excel Instructions -->
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>TUTUP BUKU TAHUNAN (YEAR-END CLOSING)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Memindahkan laba berjalan tahunan ke Laba Ditahan, me-reset akun nominal pendapatan/biaya ke 0.</p>
        </div>

        <div class="flex items-center gap-2">
            @php
                $isCurrentClosed = $closings->where('year', $year)->where('status', 'DIPROSES')->count() > 0;
            @endphp
            <span class="text-xs font-bold px-3 py-1 rounded-full {{ $isCurrentClosed ? 'bg-emerald-100 text-emerald-800 border border-emerald-400' : 'bg-slate-100 text-slate-800 border border-slate-200' }}">
                Status Tahun {{ $year }}: {{ $isCurrentClosed ? 'SUDAH DITUTUP (CLOSED)' : 'BELUM DIPROSES' }}
            </span>
        </div>
    </div>

    <!-- Excel Instruction Box -->
    <div class="bg-slate-50 border border-slate-200 p-3 rounded-lg text-xs text-slate-800 flex items-start gap-2.5">
        <svg class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="leading-relaxed">
            <strong>PETUNJUK SHEET EXCEL TUTUP BUKU TAHUNAN:</strong><br>
            1. Pilih <strong>Tahun Tutup Buku</strong> dan <strong>Tanggal Proses</strong>.<br>
            2. Klik Tombol <strong>PREPARE</strong> untuk meninjau pratinjau saldo akun sebelum penutupan.<br>
            3. Klik Tombol <strong>PROSES</strong> untuk menjalankan tutup buku resmi.<br>
            <strong>Hasilnya:</strong> Laba Tahun Berjalan akan menjadi <em>Laba Ditahan</em> (Modal Utama). Akun Pendapatan dan Biaya akan di-reset menjadi 0 untuk memulai tahun buku baru.
        </div>
    </div>

    <!-- Control Bar (Tahun, Tanggal, Tombol PREPARE & PROSES) -->
    <div class="bg-white rounded-lg p-4 border-2 border-[#14421b] shadow-md flex flex-wrap items-center justify-between gap-4">
        <form action="{{ route('settings.yearly_closing') }}" method="GET" class="flex flex-wrap items-center gap-3 text-xs">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800">Tahun:</span>
                <input type="number" name="year" value="{{ $year }}" class="w-24 px-2.5 py-1.5 border border-slate-300 rounded font-mono font-bold text-sm">
            </div>

            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800">Tanggal:</span>
                <input type="date" name="closing_date" value="{{ $closingDate }}" class="px-2.5 py-1.5 border border-slate-300 rounded font-medium">
            </div>

            <button type="submit" name="prepare" value="1" class="btn-retro btn-prepare flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span>PREPARE (PREVIEW)</span>
            </button>
        </form>

        @if(!$isCurrentClosed)
            <form action="{{ route('settings.yearly_closing.process') }}" method="POST" onsubmit="return confirm('PERINGATAN AKUNTANSI:\n\nApakah Anda yakin ingin MEMPROSES TUTUP BUKU tahun {{ $year }}?\nAkun pendapatan & biaya akan direset ke 0 dan laba bersih dialihkan ke Laba Ditahan.')">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="closing_date" value="{{ $closingDate }}">
                <button type="submit" class="btn-retro btn-process text-sm py-2 px-5 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>PROSES TUTUP BUKU TAHUNAN</span>
                </button>
            </form>
        @else
            <span class="text-xs text-slate-400 italic">Tahun {{ $year }} sudah ditutup secara permanen.</span>
        @endif
    </div>

    <!-- PREPARE TABLE PREVIEW (Jika diklik PREPARE) -->
    @if($preview)
        <div class="bg-white rounded-lg border-2 border-slate-200 shadow-lg overflow-hidden space-y-2">
            <div class="bg-[#14421b] text-white px-4 py-3 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-xs uppercase tracking-wider">Pratinjau Hasil Tutup Buku Tahun {{ $preview['year'] }}</h3>
                    <p class="text-[11px] text-white/80">Estimasi Laba Bersih yang dialihkan ke Laba Ditahan: <strong>Rp {{ number_format($preview['net_profit'], 0, ',', '.') }}</strong></p>
                </div>
                <span class="px-3 py-1 rounded bg-amber-400 text-slate-950 font-extrabold text-xs">PREPARE MODE</span>
            </div>

            <div class="overflow-x-auto">
                <table class="excel-table">
                    <thead>
                        <tr>
                            <th class="w-24">KODE</th>
                            <th>NAMA AKUN</th>
                            <th class="text-center w-14">TIPE</th>
                            <th>KELOMPOK</th>
                            <th class="text-right">SALDO BERJALAN</th>
                            <th class="text-center">TERMASUK DITUTUP?</th>
                            <th class="text-right">SALDO SETELAH TUTUP</th>
                            <th>CATATAN TUTUP BUKU</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preview['accounts'] as $row)
                            <tr class="{{ $row['account']->type === 'H' ? 'bg-slate-100 font-bold' : '' }}">
                                <td class="font-mono font-bold text-xs text-slate-700">{{ $row['account']->code }}</td>
                                <td class="font-semibold text-slate-800">{{ $row['account']->name }}</td>
                                <td class="text-center">{{ $row['account']->type }}</td>
                                <td>{{ $row['account']->group }}</td>
                                <td class="text-right font-mono font-bold text-slate-900">
                                    {{ $row['account']->type === 'H' ? '-' : 'Rp ' . number_format($row['current_balance'], 0, ',', '.') }}
                                </td>
                                <td class="text-center font-bold text-xs">
                                    @if($row['is_closed'])
                                        <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800">YA (RESET)</span>
                                    @elseif($row['account']->type === 'H')
                                        <span class="text-slate-400">-</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">TIDAK (CARRY)</span>
                                    @endif
                                </td>
                                <td class="text-right font-mono font-extrabold {{ $row['is_closed'] ? 'text-slate-400' : 'text-emerald-900' }}">
                                    {{ $row['account']->type === 'H' ? '-' : 'Rp ' . number_format($row['balance_after'], 0, ',', '.') }}
                                </td>
                                <td>
                                    <span class="font-semibold text-[11px] {{ $row['is_closed'] ? 'text-rose-700' : ($row['account']->code === '3-2000' ? 'text-emerald-700 font-bold' : 'text-slate-500') }}">
                                        {{ $row['note'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Histori Riwayat Tutup Buku -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-slate-800 text-white px-4 py-2 text-xs font-bold uppercase tracking-wider">
            Riwayat Tahun Buku Yang Pernah Ditutup
        </div>
        <table class="excel-table">
            <thead>
                <tr>
                    <th class="text-center w-24">TAHUN</th>
                    <th>TANGGAL PROSES</th>
                    <th class="text-right">LABA BERSIH DIALIHKAN</th>
                    <th class="text-center">STATUS</th>
                    <th>CATATAN</th>
                </tr>
            </thead>
            <tbody>
                @forelse($closings as $c)
                    <tr>
                        <td class="text-center font-mono font-extrabold text-sm text-emerald-900">{{ $c->year }}</td>
                        <td>{{ $c->closing_date->format('d/m/Y') }}</td>
                        <td class="text-right font-mono font-bold text-emerald-700">Rp {{ number_format($c->net_profit, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                {{ $c->status }}
                            </span>
                        </td>
                        <td class="text-slate-600">{{ $c->notes ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-slate-400">Belum ada tahun buku yang ditutup.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
