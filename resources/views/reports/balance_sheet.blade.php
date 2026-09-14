@extends('layouts.app')

@section('title', 'Neraca Keuangan')

@section('content')
<div class="space-y-4">

    <!-- Header & Filter -->
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                </svg>
                <span>NERACA KEUANGAN (BALANCE SHEET - FORMAT SKONTRO)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Format T-Account Skontro: Sisi Kiri Aktiva vs Sisi Kanan Kewajiban & Modal.</p>
        </div>

        <form action="{{ route('reports.balance_sheet') }}" method="GET" class="flex flex-wrap items-center gap-2 text-xs">
            <span class="font-bold text-slate-700">Per Tanggal:</span>
            <input type="date" name="as_of_date" value="{{ $asOfDate }}" class="px-2.5 py-1.5 border border-slate-300 rounded font-medium">
            <button type="submit" class="btn-retro btn-refresh">
                <span>REFRESH</span>
            </button>
        </form>
    </div>

    <!-- Balance Status Bar -->
    <div class="p-3 rounded-lg flex flex-col sm:flex-row sm:items-center justify-between gap-2 {{ $bs['is_balanced'] ? 'bg-emerald-100 border border-emerald-400 text-emerald-950' : 'bg-rose-100 border border-rose-400 text-rose-950' }} text-xs font-bold shadow-sm">
        <div class="flex items-center gap-2">
            @if($bs['is_balanced'])
                <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @else
                <svg class="w-4 h-4 text-rose-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            @endif
            <span>STATUS PERSAMAAN DASAR AKUNTANSI: {{ $bs['is_balanced'] ? 'SEIMBANG (AKTIVA = KEWAJIBAN + MODAL)' : 'SELISIH TERDETEKSI' }}</span>
        </div>
        <div class="font-mono text-sm">
            Selisih: Rp {{ number_format(abs($bs['difference']), 2, ',', '.') }}
        </div>
    </div>

    <!-- FORMAT SKONTRO 2 KOLOM (Kiri: Aktiva | Kanan: Kewajiban & Modal) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        
        <!-- SISI KIRI: AKTIVA -->
        <div class="bg-white rounded-lg border-2 border-[#14421b] shadow-md flex flex-col justify-between overflow-hidden">
            <div>
                <div class="bg-[#14421b] text-white px-4 py-2.5 flex items-center justify-between">
                    <h3 class="font-extrabold text-xs uppercase tracking-wider">AKTIVA (ASET PERUSAHAAN)</h3>
                    <span class="text-[10px] text-green-200">HARTA TOKO</span>
                </div>

                <div class="p-4 space-y-4 text-xs">
                    
                    <!-- Kas & Bank -->
                    <div>
                        <h4 class="font-bold text-slate-800 uppercase pb-1 border-b border-slate-200 text-[11px]">
                            1. KAS & BANK (CASH & EQUIVALENTS)
                        </h4>
                        <div class="divide-y divide-slate-100 mt-1">
                            @foreach($bs['aktiva']['accounts'] as $acc)
                                @if(str_starts_with($acc['code'], '1-11'))
                                    <div class="flex justify-between py-1.5 px-2 hover:bg-slate-50">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-slate-400 text-[10px]">{{ $acc['code'] }}</span>
                                            <span class="font-medium text-slate-800">{{ $acc['name'] }}</span>
                                        </div>
                                        <span class="font-mono font-bold text-slate-900">Rp {{ number_format($acc['balance'], 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <!-- Piutang Usaha -->
                    <div>
                        <h4 class="font-bold text-slate-800 uppercase pb-1 border-b border-slate-200 text-[11px]">
                            2. PIUTANG (RECEIVABLES)
                        </h4>
                        <div class="divide-y divide-slate-100 mt-1">
                            @foreach($bs['aktiva']['accounts'] as $acc)
                                @if(str_starts_with($acc['code'], '1-12'))
                                    <div class="flex justify-between py-1.5 px-2 hover:bg-slate-50">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-slate-400 text-[10px]">{{ $acc['code'] }}</span>
                                            <span class="font-medium text-slate-800">{{ $acc['name'] }}</span>
                                        </div>
                                        <span class="font-mono font-bold text-slate-900">Rp {{ number_format($acc['balance'], 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <!-- Persediaan Barang -->
                    <div>
                        <h4 class="font-bold text-slate-800 uppercase pb-1 border-b border-slate-200 text-[11px]">
                            3. PERSEDIAAN BARANG (INVENTORY)
                        </h4>
                        <div class="divide-y divide-slate-100 mt-1">
                            @foreach($bs['aktiva']['accounts'] as $acc)
                                @if(str_starts_with($acc['code'], '1-20'))
                                    <div class="flex justify-between py-1.5 px-2 hover:bg-slate-50">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-slate-400 text-[10px]">{{ $acc['code'] }}</span>
                                            <span class="font-medium text-slate-800">{{ $acc['name'] }} (Nilai Stok Fisik)</span>
                                        </div>
                                        <span class="font-mono font-bold text-slate-900">Rp {{ number_format($acc['balance'], 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>

            <!-- TOTAL AKTIVA FOOTER -->
            <div class="bg-slate-50 p-4 border-t-2 border-[#14421b] flex justify-between items-center font-extrabold text-sm">
                <span class="text-slate-900">TOTAL AKTIVA:</span>
                <span class="font-mono text-base text-emerald-900">Rp {{ number_format($bs['aktiva']['total'], 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- SISI KANAN: KEWAJIBAN & MODAL -->
        <div class="bg-white rounded-lg border-2 border-[#0d3844] shadow-md flex flex-col justify-between overflow-hidden">
            <div>
                <div class="bg-[#0d3844] text-white px-4 py-2.5 flex items-center justify-between">
                    <h3 class="font-extrabold text-xs uppercase tracking-wider">KEWAJIBAN & MODAL</h3>
                    <span class="text-[10px] text-cyan-200">PASIVA</span>
                </div>

                <div class="p-4 space-y-5 text-xs">
                    
                    <!-- 1. Kewajiban / Hutang -->
                    <div>
                        <h4 class="font-bold text-slate-800 uppercase pb-1 border-b border-slate-200 text-[11px] text-rose-800">
                            1. KEWAJIBAN LANCAR (HUTANG USAHA)
                        </h4>
                        <div class="divide-y divide-slate-100 mt-1">
                            @forelse($bs['kewajiban']['accounts'] as $acc)
                                <div class="flex justify-between py-1.5 px-2 hover:bg-slate-50">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-slate-400 text-[10px]">{{ $acc['code'] }}</span>
                                        <span class="font-medium text-slate-800">{{ $acc['name'] }}</span>
                                    </div>
                                    <span class="font-mono font-bold text-rose-700">Rp {{ number_format($acc['balance'], 0, ',', '.') }}</span>
                                </div>
                            @empty
                                <div class="py-2 text-slate-400 italic text-center">Tidak ada saldo hutang.</div>
                            @endforelse
                        </div>

                        <div class="flex justify-between font-bold text-slate-800 bg-slate-50 px-2 py-1.5 mt-1 rounded text-[11px]">
                            <span>Subtotal Kewajiban:</span>
                            <span class="font-mono text-rose-700">Rp {{ number_format($bs['kewajiban']['total'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- 2. Ekuitas Modal -->
                    <div>
                        <h4 class="font-bold text-slate-800 uppercase pb-1 border-b border-slate-200 text-[11px] text-cyan-900">
                            2. EKUITAS & MODAL PEMILIK
                        </h4>
                        <div class="divide-y divide-slate-100 mt-1">
                            @foreach($bs['modal']['accounts'] as $acc)
                                <div class="flex justify-between py-1.5 px-2 hover:bg-slate-50">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-slate-400 text-[10px]">{{ $acc['code'] }}</span>
                                        <span class="font-medium text-slate-800">{{ $acc['name'] }}</span>
                                    </div>
                                    <span class="font-mono font-bold text-slate-900">Rp {{ number_format($acc['balance'], 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex justify-between font-bold text-slate-800 bg-slate-50 px-2 py-1.5 mt-1 rounded text-[11px]">
                            <span>Subtotal Ekuitas Modal:</span>
                            <span class="font-mono text-emerald-800">Rp {{ number_format($bs['modal']['total'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TOTAL PASIVA FOOTER -->
            <div class="bg-slate-50 p-4 border-t-2 border-[#0d3844] flex justify-between items-center font-extrabold text-sm">
                <span class="text-slate-900">TOTAL KEWAJIBAN & MODAL:</span>
                <span class="font-mono text-base text-cyan-950">Rp {{ number_format($bs['total_kewajiban_modal'], 0, ',', '.') }}</span>
            </div>
        </div>

    </div>

</div>
@endsection
