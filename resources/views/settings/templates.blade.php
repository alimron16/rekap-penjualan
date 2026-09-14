@extends('layouts.app')

@section('title', 'Template Target Bulanan')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>TEMPLATE & TARGET PROFIT BULANAN</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Penetapan target omzet dan target profit bulanan per kategori produk (Sheet Template Excel).</p>
        </div>

        <form action="{{ route('settings.templates') }}" method="GET" class="flex items-center gap-2 text-xs">
            <span class="font-bold text-slate-700">Tahun:</span>
            <input type="number" name="year" value="{{ $year }}" class="w-24 px-2.5 py-1.5 border border-slate-300 rounded font-mono font-bold">
            <button type="submit" class="btn-retro btn-refresh">
                <span>PILIH TAHUN</span>
            </button>
        </form>
    </div>

    <!-- Excel Instruction -->
    <div class="bg-slate-50 border border-slate-200 p-3 rounded-lg text-xs text-slate-800 flex items-start gap-2.5">
        <svg class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="leading-relaxed">
            <strong>PETUNJUK SHEET TEMPLATE:</strong> Isi target untuk menetapkan target performa toko sesuai tahun dan bulan.<br>
            • <strong>Target Profit:</strong> Target laba bersih dari seluruh penjualan selama 1 bulan (Rupiah)<br>
            • <strong>Target Vocer / Perdana:</strong> Target penjualan fisik dalam satuan Pcs<br>
            • <strong>Target Transfer / Elektrik:</strong> Target transaksi agen & multi digital
        </div>
    </div>

    <!-- Table Target Bulanan -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-2.5 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">Matriks Target Bulanan Tahun {{ $year }}</h3>
            <span class="text-[10px] text-green-200">12 Bulan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">BULAN</th>
                        <th class="text-right">TARGET PROFIT (RP)</th>
                        <th class="text-center">TARGET VOCER</th>
                        <th class="text-center">TARGET PERDANA</th>
                        <th class="text-center">TARGET ACC</th>
                        <th class="text-right">TARGET TRANSFER</th>
                        <th class="text-right">TARGET ELEKTRIK</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $bulanNama = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                    @endphp
                    @foreach($targets as $t)
                        <tr>
                            <td class="font-bold text-slate-800">
                                <span class="w-6 h-6 inline-flex items-center justify-center rounded-full bg-slate-100 text-xs font-mono mr-1">{{ $t->month }}</span>
                                {{ $bulanNama[$t->month] ?? $t->month }}
                            </td>
                            <td class="text-right font-mono font-bold text-emerald-800">
                                Rp {{ number_format($t->target_profit, 0, ',', '.') }}
                            </td>
                            <td class="text-center font-mono font-semibold">{{ $t->target_vocer }} pcs</td>
                            <td class="text-center font-mono font-semibold">{{ $t->target_perdana }} pcs</td>
                            <td class="text-center font-mono font-semibold">{{ $t->target_acc }} pcs</td>
                            <td class="text-right font-mono">Rp {{ number_format($t->target_transfer, 0, ',', '.') }}</td>
                            <td class="text-right font-mono">Rp {{ number_format($t->target_elektrik, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <button type="button" onclick="openEditTarget({{ json_encode($t) }})" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-amber-200 text-slate-800 text-[10px] font-bold border border-slate-200 inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                    <span>Edit</span>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Edit Target -->
<div id="modalEditTarget" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2" id="modalTargetTitle">
                <svg class="w-4 h-4 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Edit Target Bulanan</span>
            </h3>
            <button onclick="document.getElementById('modalEditTarget').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form id="formEditTarget" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            <div>
                <label class="font-bold text-slate-700 block mb-1">Target Profit Laba Bersih (Rp) *</label>
                <input type="number" step="100000" id="inputTargetProfit" name="target_profit" required class="w-full px-3 py-1.5 border border-emerald-500 rounded font-mono font-bold text-sm text-emerald-900 bg-slate-50">
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Vocer (Pcs)</label>
                    <input type="number" step="10" id="inputTargetVocer" name="target_vocer" required class="w-full px-2 py-1.5 border border-slate-300 rounded font-mono text-center">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Perdana (Pcs)</label>
                    <input type="number" step="10" id="inputTargetPerdana" name="target_perdana" required class="w-full px-2 py-1.5 border border-slate-300 rounded font-mono text-center">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Acc (Pcs)</label>
                    <input type="number" step="10" id="inputTargetAcc" name="target_acc" required class="w-full px-2 py-1.5 border border-slate-300 rounded font-mono text-center">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Target Transfer (Rp)</label>
                    <input type="number" step="500000" id="inputTargetTransfer" name="target_transfer" required class="w-full px-2 py-1.5 border border-slate-300 rounded font-mono text-right">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Target Elektrik (Rp)</label>
                    <input type="number" step="500000" id="inputTargetElektrik" name="target_elektrik" required class="w-full px-2 py-1.5 border border-slate-300 rounded font-mono text-right">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalEditTarget').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN TARGET</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openEditTarget(target) {
        document.getElementById('modalTargetTitle').querySelector('span').innerText = `Edit Target Bulan ${target.month} Tahun ${target.year}`;
        document.getElementById('formEditTarget').action = `/settings/templates/${target.id}`;
        document.getElementById('inputTargetProfit').value = target.target_profit;
        document.getElementById('inputTargetVocer').value = target.target_vocer;
        document.getElementById('inputTargetPerdana').value = target.target_perdana;
        document.getElementById('inputTargetAcc').value = target.target_acc;
        document.getElementById('inputTargetTransfer').value = target.target_transfer;
        document.getElementById('inputTargetElektrik').value = target.target_elektrik;
        document.getElementById('modalEditTarget').classList.remove('hidden');
    }
</script>
@endpush
