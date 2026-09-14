@extends('layouts.app')

@section('title', 'Daftar Supplier')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                DAFTAR SUPPLIER (KULAKAN & DISTRIBUTOR)
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Daftar mitra distributor kulakan barang fisik, kartu perdana dan voucher.</p>
        </div>

        <button onclick="document.getElementById('modalAddSupplier').classList.remove('hidden')" class="btn-retro btn-save flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>TAMBAH SUPPLIER</span>
        </button>
    </div>

    <!-- Toolbar Filter & Export -->
    <x-table-toolbar tableId="suppliersTable" excelName="Rekap_Daftar_Supplier" placeholder="Cari nama supplier, no hp, bank, rekening..." />

    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="suppliersTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th>NAMA SUPPLIER</th>
                        <th>NOMOR HP / KONTAK</th>
                        <th>ALAMAT</th>
                        <th>BANK</th>
                        <th>NO REKENING</th>
                        <th>ATAS NAMA REK</th>
                        <th class="text-center">TOTAL KULAKAN</th>
                        <th class="text-center w-24">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $idx => $s)
                        <tr>
                            <td class="text-center text-slate-500 font-semibold">{{ $suppliers->firstItem() + $idx }}</td>
                            <td class="font-bold text-slate-900">{{ $s->name }}</td>
                            <td class="font-mono">{{ $s->phone ?? '-' }}</td>
                            <td>{{ $s->address ?? '-' }}</td>
                            <td><span class="px-2 py-0.5 rounded bg-slate-100 font-semibold text-[10px]">{{ $s->bank_name ?? '-' }}</span></td>
                            <td class="font-mono text-emerald-800 font-bold">{{ $s->account_number ?? '-' }}</td>
                            <td>{{ $s->account_name ?? '-' }}</td>
                            <td class="text-center font-bold font-mono">{{ $s->purchases_count }} Nota</td>
                            <td class="text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" onclick="editSupplier({{ json_encode($s) }})" title="Edit Supplier" class="p-1 rounded hover:bg-slate-100 text-slate-600 hover:text-emerald-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    @if($s->purchases_count == 0)
                                        <form action="{{ route('master.suppliers.destroy', $s) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus supplier [{{ $s->name }}]?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus Supplier" class="p-1 rounded hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-6 text-slate-400">Belum ada data supplier.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-2 border-t border-slate-200 bg-slate-50">
            {{ $suppliers->links() }}
        </div>
    </div>
</div>

<!-- Modal Tambah Supplier -->
<div id="modalAddSupplier" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Tambah Supplier Baru</span>
            </h3>
            <button onclick="document.getElementById('modalAddSupplier').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>
        <form action="{{ route('master.suppliers.store') }}" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            <div>
                <label class="font-bold text-slate-700 block mb-1">Nama Supplier *</label>
                <input type="text" name="name" required placeholder="Contoh: Grosir Terdekat / Sales Provider" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            <div>
                <label class="font-bold text-slate-700 block mb-1">Nomor HP</label>
                <input type="text" name="phone" placeholder="Contoh: 0812XXXXXXXX" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            <div>
                <label class="font-bold text-slate-700 block mb-1">Alamat</label>
                <input type="text" name="address" placeholder="Contoh: Bekasi" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Bank</label>
                    <input type="text" name="bank_name" placeholder="BCA" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                </div>
                <div class="col-span-2">
                    <label class="font-bold text-slate-700 block mb-1">Nomor Rekening</label>
                    <input type="text" name="account_number" placeholder="5655555555" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>
            <div>
                <label class="font-bold text-slate-700 block mb-1">Atas Nama Rekening</label>
                <input type="text" name="account_name" placeholder="GROSIR" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalAddSupplier').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN SUPPLIER</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Supplier -->
<div id="modalEditSupplier" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                <span>Edit Data Supplier</span>
            </h3>
            <button onclick="document.getElementById('modalEditSupplier').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>
        <form id="formEditSupplier" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label class="font-bold text-slate-700 block mb-1">Nama Supplier *</label>
                <input type="text" name="name" id="edit_supp_name" required class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            <div>
                <label class="font-bold text-slate-700 block mb-1">Nomor HP</label>
                <input type="text" name="phone" id="edit_supp_phone" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            <div>
                <label class="font-bold text-slate-700 block mb-1">Alamat</label>
                <input type="text" name="address" id="edit_supp_address" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Bank</label>
                    <input type="text" name="bank_name" id="edit_supp_bank" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                </div>
                <div class="col-span-2">
                    <label class="font-bold text-slate-700 block mb-1">Nomor Rekening</label>
                    <input type="text" name="account_number" id="edit_supp_account_no" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>
            <div>
                <label class="font-bold text-slate-700 block mb-1">Atas Nama Rekening</label>
                <input type="text" name="account_name" id="edit_supp_account_name" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalEditSupplier').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN PERUBAHAN</button>
            </div>
        </form>
    </div>
</div>

<script>
function editSupplier(data) {
    const form = document.getElementById('formEditSupplier');
    form.action = "{{ url('/master/suppliers') }}/" + data.id;
    document.getElementById('edit_supp_name').value = data.name || '';
    document.getElementById('edit_supp_phone').value = data.phone || '';
    document.getElementById('edit_supp_address').value = data.address || '';
    document.getElementById('edit_supp_bank').value = data.bank_name || '';
    document.getElementById('edit_supp_account_no').value = data.account_number || '';
    document.getElementById('edit_supp_account_name').value = data.account_name || '';
    document.getElementById('modalEditSupplier').classList.remove('hidden');
}
</script>
@endsection
