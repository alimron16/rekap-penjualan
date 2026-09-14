@extends('layouts.app')

@section('title', 'Bagan Perkiraan (COA)')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                <span>DAFTAR PERKIRAAN (CHART OF ACCOUNTS / COA)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Struktur akun akuntansi berpasangan (Double-Entry Bookkeeping) terhubung otomatis ke kasir dan laporan.</p>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="document.getElementById('modalAddAccount').classList.remove('hidden')" class="btn-retro btn-save flex items-center gap-1.5 w-full sm:w-auto justify-center">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>TAMBAH AKUN BARU</span>
            </button>
        </div>
    </div>

    <!-- Info Box Aturan Sistem -->
    <div class="bg-slate-50 border border-slate-200 p-3 rounded-lg text-xs text-slate-800 flex items-start gap-2.5">
        <svg class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="leading-relaxed">
            <strong>PANDUAN STATUS AKUN COA:</strong><br>
            • <strong class="text-slate-700">Status Locked (Terkunci):</strong> Akun inti sistem (Kas Retail kasir, Persediaan, Pendapatan Retail/Grosir, HPP, dll). Kodenya diproteksi sistem agar jurnal otomatis tidak error, namun <em>Nama Akun</em> dan <em>Saldo Awal</em> tetap dapat disesuaikan.<br>
            • <strong class="text-emerald-800">Status Terbuka:</strong> Akun fleksibel (rekening bank tambahan, e-wallet, pos biaya operasional) yang dapat diedit kode, nama, kelompok, maupun dihapus bila belum ada transaksi.
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-2.5 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">Tabel Bagan Perkiraan Toko</h3>
            <span class="text-[10px] text-green-200">Total: {{ method_exists($accounts, 'total') ? $accounts->total() : $accounts->count() }} Akun</span>
        </div>

        <x-table-toolbar tableId="accountsTable" excelName="Bagan_Akun_COA" placeholder="Cari kode atau nama akun..." />

        <div class="overflow-x-auto">
            <table id="accountsTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-28">KODE AKUN</th>
                        <th>NAMA AKUN</th>
                        <th class="text-center w-16">TIPE</th>
                        <th>KELOMPOK</th>
                        <th class="text-right">SALDO AWAL</th>
                        <th class="text-right">SALDO BERJALAN SAAT INI</th>
                        <th class="text-center">STATUS KUNCI</th>
                        <th class="text-center w-24">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts as $acc)
                        <tr class="{{ $acc->type === 'H' ? 'bg-emerald-50/70 font-bold' : '' }}">
                            <td class="font-mono font-bold text-xs {{ $acc->type === 'H' ? 'text-emerald-950' : 'text-slate-700' }}">
                                {{ $acc->code }}
                            </td>
                            <td class="{{ $acc->type === 'H' ? 'text-emerald-950 uppercase font-extrabold' : 'text-slate-800 font-semibold pl-6' }}">
                                {{ $acc->name }}
                            </td>
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $acc->type === 'H' ? 'bg-emerald-200 text-emerald-950' : ($acc->type === 'D' ? 'bg-sky-100 text-sky-800' : 'bg-slate-100 text-slate-800') }}">
                                    {{ $acc->type }}
                                </span>
                            </td>
                            <td>
                                <span class="text-[11px] font-medium text-slate-600">{{ $acc->group }}</span>
                            </td>
                            <td class="text-right font-mono text-slate-600">
                                {{ $acc->initial_balance > 0 ? 'Rp ' . number_format($acc->initial_balance, 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-right font-mono font-bold {{ $acc->current_balance < 0 ? 'text-rose-600' : 'text-slate-900' }}">
                                {{ $acc->type === 'H' ? '-' : 'Rp ' . number_format($acc->current_balance, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($acc->is_system_locked)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-bold">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                        Locked
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Terbuka
                                    </span>
                                @endif
                            </td>
                            <td class="text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button onclick='editAccount(@json($acc))' class="p-1 rounded bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-semibold flex items-center gap-0.5" title="Edit Akun">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    @if(!$acc->is_system_locked)
                                        <form action="{{ route('accounting.accounts.destroy', $acc) }}" method="POST" onsubmit="return confirm('Hapus akun [{{ $acc->code }} - {{ $acc->name }}]? Tindakan ini tidak bisa dibatalkan.');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold flex items-center gap-0.5" title="Hapus Akun">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(method_exists($accounts, 'hasPages') && $accounts->hasPages())
        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
            {{ $accounts->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Akun Baru -->
<div id="modalAddAccount" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Akun Perkiraan Baru</span>
            </h3>
            <button onclick="document.getElementById('modalAddAccount').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('accounting.accounts.store') }}" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Kode Akun *</label>
                    <input type="text" name="code" required placeholder="Contoh: 1-1124" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono font-bold uppercase">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Tipe Akun *</label>
                    <select name="type" required class="w-full px-3 py-1.5 border border-slate-300 rounded">
                        <option value="D">D - Detail / Debet</option>
                        <option value="K">K - Kredit</option>
                        <option value="H">H - Header / Induk</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Nama Akun *</label>
                <input type="text" name="name" required placeholder="Contoh: SALDO BANK JAGO" class="w-full px-3 py-1.5 border border-slate-300 rounded font-medium uppercase">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Kelompok Akun *</label>
                    <select name="group" required class="w-full px-3 py-1.5 border border-slate-300 rounded">
                        <option value="AKTIVA">AKTIVA</option>
                        <option value="KEWAJIBAN">KEWAJIBAN</option>
                        <option value="MODAL">MODAL</option>
                        <option value="PENDAPATAN">PENDAPATAN</option>
                        <option value="HPP">HPP</option>
                        <option value="BIAYA">BIAYA</option>
                        <option value="PENDAPATAN LAIN">PENDAPATAN LAIN</option>
                        <option value="BIAYA LAIN">BIAYA LAIN</option>
                    </select>
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Saldo Awal (Rp)</label>
                    <input type="number" step="1" name="initial_balance" value="0" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono text-right font-bold text-slate-800">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalAddAccount').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN AKUN</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Akun -->
<div id="modalEditAccount" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Edit / Sesuaikan Akun Perkiraan</span>
            </h3>
            <button onclick="document.getElementById('modalEditAccount').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form id="formEditAccount" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            @method('PUT')

            <div id="lockedNotice" class="hidden p-2.5 rounded bg-amber-50 border border-amber-200 text-amber-900 text-[11px] leading-relaxed">
                <span class="font-bold">Akun Sistem Terkunci:</span> Kode, tipe, dan kelompok akun dilindungi sistem untuk menjamin keakuratan jurnal kasir. Anda dapat mengubah <em>Nama Akun</em> dan <em>Saldo Awal</em>.
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Kode Akun</label>
                    <input type="text" name="code" id="edit_acc_code" required class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono font-bold uppercase">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Tipe Akun</label>
                    <select name="type" id="edit_acc_type" required class="w-full px-3 py-1.5 border border-slate-300 rounded">
                        <option value="D">D - Detail / Debet</option>
                        <option value="K">K - Kredit</option>
                        <option value="H">H - Header / Induk</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Nama Akun *</label>
                <input type="text" name="name" id="edit_acc_name" required class="w-full px-3 py-1.5 border border-slate-300 rounded font-bold uppercase">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Kelompok Akun</label>
                    <select name="group" id="edit_acc_group" required class="w-full px-3 py-1.5 border border-slate-300 rounded">
                        <option value="AKTIVA">AKTIVA</option>
                        <option value="KEWAJIBAN">KEWAJIBAN</option>
                        <option value="MODAL">MODAL</option>
                        <option value="PENDAPATAN">PENDAPATAN</option>
                        <option value="HPP">HPP</option>
                        <option value="BIAYA">BIAYA</option>
                        <option value="PENDAPATAN LAIN">PENDAPATAN LAIN</option>
                        <option value="BIAYA LAIN">BIAYA LAIN</option>
                    </select>
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Saldo Awal (Rp)</label>
                    <input type="number" step="1" name="initial_balance" id="edit_acc_initial" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono text-right font-bold text-slate-800">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalEditAccount').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN PERUBAHAN</button>
            </div>
        </form>
    </div>
</div>

<script>
function editAccount(data) {
    const form = document.getElementById('formEditAccount');
    form.action = "{{ url('/accounting/accounts') }}/" + data.id;

    const codeInput = document.getElementById('edit_acc_code');
    const typeSelect = document.getElementById('edit_acc_type');
    const groupSelect = document.getElementById('edit_acc_group');
    const lockedNotice = document.getElementById('lockedNotice');

    codeInput.value = data.code || '';
    document.getElementById('edit_acc_name').value = data.name || '';
    typeSelect.value = data.type || 'D';
    groupSelect.value = data.group || 'AKTIVA';
    document.getElementById('edit_acc_initial').value = data.initial_balance || 0;

    if (data.is_system_locked) {
        lockedNotice.classList.remove('hidden');
        codeInput.readOnly = true;
        codeInput.classList.add('bg-slate-100', 'cursor-not-allowed');
        typeSelect.disabled = true;
        typeSelect.classList.add('bg-slate-100', 'cursor-not-allowed');
        groupSelect.disabled = true;
        groupSelect.classList.add('bg-slate-100', 'cursor-not-allowed');
    } else {
        lockedNotice.classList.add('hidden');
        codeInput.readOnly = false;
        codeInput.classList.remove('bg-slate-100', 'cursor-not-allowed');
        typeSelect.disabled = false;
        typeSelect.classList.remove('bg-slate-100', 'cursor-not-allowed');
        groupSelect.disabled = false;
        groupSelect.classList.remove('bg-slate-100', 'cursor-not-allowed');
    }

    document.getElementById('modalEditAccount').classList.remove('hidden');
}
</script>
@endsection
