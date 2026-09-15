@extends('layouts.app')

@section('title', 'Kelola Pengguna & Hak Akses')

@section('content')
<div class="space-y-5">
    
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h1 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span>Kelola Pengguna & Hak Akses</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola akun Super Admin, Admin, dan Toko serta atur modul apa saja yang boleh diakses</p>
        </div>
        <div>
            <button onclick="openAddUserModal()" class="w-full sm:w-auto px-4 py-2 bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span>Tambah Pengguna</span>
            </button>
        </div>
    </div>

    <!-- Quick Role Statistics -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Akun</div>
            <div class="text-xl font-extrabold text-slate-900 mt-1">{{ $users->count() }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-bold text-purple-600 uppercase tracking-wider">Super Admin</div>
            <div class="text-xl font-extrabold text-purple-900 mt-1">{{ $users->where('role', 'super_admin')->count() }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-bold text-blue-600 uppercase tracking-wider">Admin</div>
            <div class="text-xl font-extrabold text-blue-900 mt-1">{{ $users->where('role', 'admin')->count() }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Akun Toko</div>
            <div class="text-xl font-extrabold text-emerald-900 mt-1">{{ $users->where('role', 'toko')->count() }}</div>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
            <div class="text-xs font-bold text-slate-800 uppercase tracking-wider">Daftar Pengguna Sistem</div>
            <div class="text-xs text-slate-500">Menampilkan {{ $users->count() }} pengguna terdaftar</div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-600 font-bold uppercase text-[10px] tracking-wider">
                        <th class="py-3 px-4">Nama & Kontak</th>
                        <th class="py-3 px-4">Role / Peran</th>
                        <th class="py-3 px-4">Toko / Cabang</th>
                        <th class="py-3 px-4">Hak Akses Modul</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($users as $u)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 flex items-center gap-2">
                                    <span>{{ $u->name }}</span>
                                    @if($u->id === auth()->id())
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-emerald-100 text-emerald-800">Anda</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $u->email }}</div>
                                @if($u->phone)
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $u->phone }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($u->role === 'super_admin')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                        Super Admin
                                    </span>
                                @elseif($u->role === 'admin')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        Admin Operasional
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Kasir Toko
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-medium text-slate-800">
                                @if($u->outlet)
                                    <div class="font-bold text-emerald-800">{{ $u->outlet->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $u->outlet->code }}</div>
                                @else
                                    <span>{{ $u->store_name ?? 'Kantor Pusat' }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($u->isSuperAdmin())
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-[10px] font-bold border border-slate-200">Semua Akses (Penuh)</span>
                                @else
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        @php
                                            $moduleLabels = [
                                                'pos' => 'Kasir POS',
                                                'transfer' => 'Transfer Agen',
                                                'master' => 'Master Data',
                                                'purchase' => 'Pembelian',
                                                'accounting' => 'Akuntansi',
                                                'reports' => 'Laporan',
                                                'settings' => 'Pengaturan',
                                                'users' => 'Kelola User',
                                            ];
                                        @endphp
                                        @foreach($moduleLabels as $modKey => $modLabel)
                                            @if($u->hasPermission($modKey))
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    {{ $modLabel }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($u->is_active)
                                    <span class="inline-flex items-center gap-1 text-emerald-700 font-bold text-[11px]">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-rose-600 font-bold text-[11px]">
                                        <span class="w-2 h-2 rounded-full bg-rose-500"></span> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" onclick="openEditUserModal({{ json_encode($u) }})" class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition" title="Edit Akun & Akses">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    @if($u->id !== auth()->id())
                                        <form action="{{ route('settings.users.toggle_status', $u) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-lg text-slate-500 hover:text-amber-700 hover:bg-amber-50 transition" title="{{ $u->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            </button>
                                        </form>

                                        @if(auth()->user()->isSuperAdmin())
                                            <form action="{{ route('settings.users.destroy', $u) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ $u->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Hapus Akun">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">Belum ada akun pengguna tambahan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tambah / Edit Pengguna -->
<div id="userModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 my-8 animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 id="modalTitle" class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Tambah Pengguna Baru</span>
            </h3>
            <button onclick="closeUserModal()" class="text-slate-400 hover:text-slate-700 p-1 text-xl font-bold leading-none">&times;</button>
        </div>

        <form id="userForm" method="POST" action="{{ route('settings.users.store') }}" class="py-4 space-y-4 text-xs">
            @csrf
            <div id="methodContainer"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                    <input type="text" id="formName" name="name" required placeholder="Contoh: Budi Santoso"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Email Login *</label>
                    <input type="email" id="formEmail" name="email" required placeholder="budi@elephantcell.com"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Peran / Role *</label>
                    <select id="formRole" name="role" required onchange="onRoleChanged(this.value)"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600">
                        @if(auth()->user()->isSuperAdmin())
                            <option value="super_admin">Super Admin (Akses Penuh)</option>
                        @endif
                        <option value="admin">Admin Operasional</option>
                        <option value="toko" selected>Kasir Toko (Cabang)</option>
                    </select>
                </div>
                <div id="outletContainer">
                    <label class="block font-semibold text-slate-700 mb-1">Pilih Cabang Toko *</label>
                    <select id="formOutletId" name="outlet_id"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600">
                        <option value="">-- Pilih Cabang --</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->code }} - {{ $outlet->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" id="formStoreName" name="store_name" value="">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nomor WhatsApp / HP</label>
                    <input type="text" id="formPhone" name="phone" placeholder="08123456789"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 font-mono">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">
                        <span id="labelPassword">Kata Sandi *</span>
                        <span id="passwordHelp" class="hidden text-[10px] text-slate-400 font-normal">(Kosongkan jika tak diubah)</span>
                    </label>
                    <input type="password" id="formPassword" name="password" minlength="6" placeholder="Minimal 6 karakter"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 font-mono">
                </div>
            </div>

            <!-- Modul Hak Akses Dinamis -->
            <div class="pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <label class="font-bold text-slate-800 text-xs">Pilihan Hak Akses Modul:</label>
                    <span class="text-[10px] text-slate-400">Centang modul yang boleh dibuka</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <label class="flex items-center gap-2 text-slate-700 cursor-pointer">
                        <input type="checkbox" id="perm_pos" name="permissions[pos]" value="1" class="rounded text-emerald-700 focus:ring-emerald-600">
                        <span>Kasir POS</span>
                    </label>
                    <label class="flex items-center gap-2 text-slate-700 cursor-pointer">
                        <input type="checkbox" id="perm_transfer" name="permissions[transfer]" value="1" class="rounded text-emerald-700 focus:ring-emerald-600">
                        <span>Transfer Agen</span>
                    </label>
                    <label class="flex items-center gap-2 text-slate-700 cursor-pointer">
                        <input type="checkbox" id="perm_master" name="permissions[master]" value="1" class="rounded text-emerald-700 focus:ring-emerald-600">
                        <span>Master Data</span>
                    </label>
                    <label class="flex items-center gap-2 text-slate-700 cursor-pointer">
                        <input type="checkbox" id="perm_purchase" name="permissions[purchase]" value="1" class="rounded text-emerald-700 focus:ring-emerald-600">
                        <span>Pembelian</span>
                    </label>
                    <label class="flex items-center gap-2 text-slate-700 cursor-pointer">
                        <input type="checkbox" id="perm_accounting" name="permissions[accounting]" value="1" class="rounded text-emerald-700 focus:ring-emerald-600">
                        <span>Akuntansi & COA</span>
                    </label>
                    <label class="flex items-center gap-2 text-slate-700 cursor-pointer">
                        <input type="checkbox" id="perm_reports" name="permissions[reports]" value="1" class="rounded text-emerald-700 focus:ring-emerald-600">
                        <span>Laporan Lengkap</span>
                    </label>
                    <label class="flex items-center gap-2 text-slate-700 cursor-pointer">
                        <input type="checkbox" id="perm_settings" name="permissions[settings]" value="1" class="rounded text-emerald-700 focus:ring-emerald-600">
                        <span>Pengaturan Toko</span>
                    </label>
                    @if(auth()->user()->isSuperAdmin())
                        <label class="flex items-center gap-2 text-slate-700 cursor-pointer">
                            <input type="checkbox" id="perm_users" name="permissions[users]" value="1" class="rounded text-emerald-700 focus:ring-emerald-600">
                            <span>Kelola Pengguna</span>
                        </label>
                    @endif
                </div>
            </div>

            <div class="pt-3 flex gap-2 border-t border-slate-100">
                <button type="submit" class="flex-1 py-2.5 bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white font-bold text-xs rounded-xl shadow-xs transition">
                    Simpan Pengguna
                </button>
                <button type="button" onclick="closeUserModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const permKeys = ['pos', 'transfer', 'master', 'purchase', 'accounting', 'reports', 'settings', 'users'];

    function openAddUserModal() {
        document.getElementById('modalTitle').innerHTML = '<span>Tambah Pengguna Baru</span>';
        document.getElementById('userForm').action = "{{ route('settings.users.store') }}";
        document.getElementById('methodContainer').innerHTML = '';
        
        document.getElementById('formName').value = '';
        document.getElementById('formEmail').value = '';
        document.getElementById('formOutletId').value = "{{ $outlets->first()?->id ?? '' }}";
        document.getElementById('formPhone').value = '';
        document.getElementById('formPassword').value = '';
        document.getElementById('formPassword').required = true;
        document.getElementById('passwordHelp').classList.add('hidden');
        document.getElementById('formRole').value = 'toko';

        onRoleChanged('toko');

        document.getElementById('userModal').classList.remove('hidden');
    }

    function openEditUserModal(user) {
        document.getElementById('modalTitle').innerHTML = '<span>Edit Pengguna: ' + user.name + '</span>';
        document.getElementById('userForm').action = "/settings/users/" + user.id;
        document.getElementById('methodContainer').innerHTML = '@method("PUT")';

        document.getElementById('formName').value = user.name;
        document.getElementById('formEmail').value = user.email;
        document.getElementById('formOutletId').value = user.outlet_id || '';
        document.getElementById('formPhone').value = user.phone || '';
        document.getElementById('formPassword').value = '';
        document.getElementById('formPassword').required = false;
        document.getElementById('passwordHelp').classList.remove('hidden');
        document.getElementById('formRole').value = user.role;

        onRoleChanged(user.role);

        // Set permissions
        permKeys.forEach(key => {
            const el = document.getElementById('perm_' + key);
            if (el) {
                if (user.role === 'super_admin') {
                    el.checked = true;
                } else if (user.permissions && typeof user.permissions[key] !== 'undefined') {
                    el.checked = Boolean(user.permissions[key]);
                } else {
                    el.checked = false;
                }
            }
        });

        document.getElementById('userModal').classList.remove('hidden');
    }

    function closeUserModal() {
        document.getElementById('userModal').classList.add('hidden');
    }

    function onRoleChanged(role) {
        const outletContainer = document.getElementById('outletContainer');
        if (role === 'toko') {
            if (outletContainer) outletContainer.style.display = 'block';
        } else {
            if (outletContainer) outletContainer.style.display = 'none';
        }

        if (role === 'super_admin') {
            permKeys.forEach(k => {
                const el = document.getElementById('perm_' + k);
                if (el) el.checked = true;
            });
        } else if (role === 'admin') {
            permKeys.forEach(k => {
                const el = document.getElementById('perm_' + k);
                if (el) el.checked = (k !== 'users' || "{{ auth()->user()->isSuperAdmin() ? '1' : '0' }}" === '1');
            });
        } else if (role === 'toko') {
            permKeys.forEach(k => {
                const el = document.getElementById('perm_' + k);
                if (el) el.checked = (k === 'pos' || k === 'transfer' || k === 'reports');
            });
        }
    }
</script>
@endsection
