@extends('layouts.app')

@section('title', 'Master Data Cabang Toko')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                MASTER DATA CABANG TOKO (MULTI-OUTLET)
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Kelola seluruh cabang toko, informasi kontak, dan mapping pengguna kasir per outlet.</p>
        </div>

        <button onclick="openAddOutletModal()" class="btn-retro btn-save flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>TAMBAH CABANG BARU</span>
        </button>
    </div>

    <!-- Toolbar Filter & Search -->
    <div class="bg-white rounded-lg p-3 border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
        <form method="GET" action="{{ route('master.outlets.index') }}" class="flex-1 flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama cabang, kode, alamat, atau no telp..."
                       class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-600">
                <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs focus:bg-white">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>

            <button type="submit" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-800 text-white rounded-lg font-semibold">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('master.outlets.index') }}" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg font-semibold">
                    Reset
                </a>
            @endif
        </form>

        <div class="text-slate-500 font-medium">
            Total: <span class="font-bold text-slate-800">{{ $outlets->total() }}</span> Cabang Terdaftar
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th class="w-28">KODE CABANG</th>
                        <th>NAMA TOKO / OUTLET</th>
                        <th>ALAMAT LENGKAP</th>
                        <th>NO. TELEPON / WA</th>
                        <th class="text-center w-24">STATUS</th>
                        <th class="text-center w-24">KASIR / USER</th>
                        <th class="text-center w-28">TRANSAKSI</th>
                        <th class="text-center w-28">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($outlets as $idx => $outlet)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="text-center text-slate-500 font-semibold">{{ $outlets->firstItem() + $idx }}</td>
                            <td class="font-mono font-bold text-emerald-900">
                                <span class="px-2 py-0.5 rounded bg-emerald-50 border border-emerald-200">{{ $outlet->code }}</span>
                            </td>
                            <td class="font-bold text-slate-900">
                                {{ $outlet->name }}
                            </td>
                            <td class="text-slate-600 text-[11px] max-w-xs truncate" title="{{ $outlet->address }}">
                                {{ $outlet->address ?? '-' }}
                            </td>
                            <td class="font-mono text-[11px]">{{ $outlet->phone ?? '-' }}</td>
                            <td class="text-center">
                                @if($outlet->status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="text-center font-bold text-slate-700">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-[11px]">{{ $outlet->users_count }} Akun</span>
                            </td>
                            <td class="text-center text-[11px] text-slate-600 font-mono">
                                <div>{{ $outlet->sales_count }} Penjualan</div>
                                <div class="text-[10px] text-slate-400">{{ $outlet->transfers_count }} Transfer</div>
                            </td>
                            <td class="text-center">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" onclick="openEditOutletModal({{ json_encode($outlet) }})"
                                            class="p-1 text-blue-600 hover:bg-blue-50 rounded transition" title="Edit Cabang">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>

                                    <form method="POST" action="{{ route('master.outlets.toggle_status', $outlet) }}" class="inline" onsubmit="return confirm('Ubah status aktif cabang {{ $outlet->name }}?')">
                                        @csrf
                                        <button type="submit" class="p-1 {{ $outlet->status === 'active' ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} rounded transition"
                                                title="{{ $outlet->status === 'active' ? 'Nonaktifkan Cabang' : 'Aktifkan Cabang' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('master.outlets.destroy', $outlet) }}" class="inline" onsubmit="return confirm('Hapus cabang {{ $outlet->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-rose-600 hover:bg-rose-50 rounded transition" title="Hapus Cabang">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-8 text-slate-400">Belum ada data cabang toko.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($outlets->hasPages())
            <div class="p-3 border-t border-slate-200">
                {{ $outlets->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Cabang -->
<div id="modalAddOutlet" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Cabang / Outlet Baru</span>
            </h3>
            <button onclick="closeAddOutletModal()" class="text-slate-400 hover:text-slate-700 text-xl font-bold leading-none">&times;</button>
        </div>

        <form method="POST" action="{{ route('master.outlets.store') }}" class="py-4 space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Kode Cabang *</label>
                <input type="text" name="code" required placeholder="Contoh: OUT-003"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono font-bold uppercase focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600">
                <p class="text-[10px] text-slate-400 mt-0.5">Kode unik pembeda cabang toko (misal: OUT-001, CBG-BEKASI)</p>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Nama Toko / Cabang *</label>
                <input type="text" name="name" required placeholder="Contoh: Elephant Cell Cikarang"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Alamat Lengkap</label>
                <textarea name="address" rows="2" placeholder="Jl. Raya Cikarang No. 88..."
                          class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">No. Telepon / WhatsApp</label>
                    <input type="text" name="phone" placeholder="08123456789"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Status Cabang *</label>
                    <select name="status" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600">
                        <option value="active" selected>Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="pt-3 flex gap-2 border-t border-slate-100">
                <button type="submit" class="flex-1 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-xs transition">
                    Simpan Cabang Baru
                </button>
                <button type="button" onclick="closeAddOutletModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Cabang -->
<div id="modalEditOutlet" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>Edit Cabang / Outlet</span>
            </h3>
            <button onclick="closeEditOutletModal()" class="text-slate-400 hover:text-slate-700 text-xl font-bold leading-none">&times;</button>
        </div>

        <form id="editOutletForm" method="POST" action="" class="py-4 space-y-3 text-xs">
            @csrf
            @method('PUT')

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Kode Cabang *</label>
                <input type="text" id="editCode" name="code" required
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono font-bold uppercase focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Nama Toko / Cabang *</label>
                <input type="text" id="editName" name="name" required
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Alamat Lengkap</label>
                <textarea id="editAddress" name="address" rows="2"
                          class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">No. Telepon / WA</label>
                    <input type="text" id="editPhone" name="phone"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Status Cabang *</label>
                    <select id="editStatus" name="status" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600">
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="pt-3 flex gap-2 border-t border-slate-100">
                <button type="submit" class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                    Perbarui Cabang
                </button>
                <button type="button" onclick="closeEditOutletModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddOutletModal() {
        document.getElementById('modalAddOutlet').classList.remove('hidden');
    }
    function closeAddOutletModal() {
        document.getElementById('modalAddOutlet').classList.add('hidden');
    }

    function openEditOutletModal(outlet) {
        document.getElementById('editOutletForm').action = "/master/outlets/" + outlet.id;
        document.getElementById('editCode').value = outlet.code;
        document.getElementById('editName').value = outlet.name;
        document.getElementById('editAddress').value = outlet.address || '';
        document.getElementById('editPhone').value = outlet.phone || '';
        document.getElementById('editStatus').value = outlet.status;
        document.getElementById('modalEditOutlet').classList.remove('hidden');
    }
    function closeEditOutletModal() {
        document.getElementById('modalEditOutlet').classList.add('hidden');
    }
</script>
@endsection
