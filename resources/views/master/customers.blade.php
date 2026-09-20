@extends('layouts.app')

@section('title', 'Daftar Pelanggan')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                DAFTAR PELANGGAN (RETAIL & GROSIR)
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Daftar pelanggan toko retail dan konter mitra grosir.</p>
        </div>

        <button onclick="document.getElementById('modalAddCustomer').classList.remove('hidden')" class="btn-retro btn-save flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
            <span>TAMBAH PELANGGAN</span>
        </button>
    </div>

    <!-- Keterangan Toko & Pilihan Toko untuk Admin -->
    <div class="rounded-lg p-3.5 border flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs {{ $selectedOutlet ? 'bg-emerald-50 border-emerald-200 text-emerald-950' : 'bg-blue-50 border-blue-200 text-blue-950' }}">
        <div class="flex items-center gap-2.5">
            <span class="p-2 rounded-full {{ $selectedOutlet ? 'bg-emerald-200 text-emerald-800' : 'bg-blue-200 text-blue-800' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </span>
            <div>
                @if($selectedOutlet)
                    <div class="font-bold uppercase tracking-wider text-emerald-800 flex items-center gap-1.5">
                        <span>DATA PELANGGAN: TOKO {{ $selectedOutlet->name }} ({{ $selectedOutlet->code }})</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-600 text-white font-semibold">Toko Aktif</span>
                    </div>
                    <div class="text-[11px] text-emerald-700 mt-0.5">
                        Menampilkan pelanggan yang terdaftar di toko <strong>{{ $selectedOutlet->name }}</strong> & pelanggan umum nasional.
                    </div>
                @else
                    <div class="font-bold uppercase tracking-wider text-blue-800 flex items-center gap-1.5">
                        <span>DATA PELANGGAN: SEMUA TOKO / KONSOLIDASI</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-blue-600 text-white font-semibold">Seluruh Cabang</span>
                    </div>
                    <div class="text-[11px] text-blue-700 mt-0.5">
                        Menampilkan <strong>seluruh data pelanggan</strong> dari semua cabang & toko Elephant Cell.
                    </div>
                @endif
            </div>
        </div>

        @if(auth()->user() && auth()->user()->isAdmin() && $outlets->isNotEmpty())
            <div class="flex items-center gap-2 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-200">
                <span class="text-[11px] font-bold text-slate-700">Pilih Toko:</span>
                <div class="flex flex-wrap gap-1.5">
                    <a href="{{ route('master.customers', array_merge(request()->except('outlet_id'), ['outlet_id' => ''])) }}" 
                       class="px-2.5 py-1 rounded-md text-[11px] font-bold border transition-colors {{ empty($outletId) ? 'bg-blue-700 text-white border-blue-700 shadow-sm' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100' }}">
                        Semua Toko
                    </a>
                    @foreach($outlets as $ot)
                        <a href="{{ route('master.customers', array_merge(request()->except('outlet_id'), ['outlet_id' => $ot->id])) }}" 
                           class="px-2.5 py-1 rounded-md text-[11px] font-bold border transition-colors {{ (string)$outletId === (string)$ot->id ? 'bg-emerald-700 text-white border-emerald-700 shadow-sm' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100' }}">
                            {{ $ot->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Toolbar Filter & Export -->
    <x-table-toolbar tableId="customersTable" excelName="Rekap_Daftar_Pelanggan" placeholder="Cari nama pelanggan, kontak, alamat..." />

    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="customersTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th>NAMA PELANGGAN</th>
                        <th>CABANG / TOKO</th>
                        <th>KONTAK / NO HP</th>
                        <th>ALAMAT</th>
                        <th>KETERANGAN</th>
                        <th class="text-center">TOTAL TRANSAKSI</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center w-24">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $idx => $c)
                        <tr>
                            <td class="text-center text-slate-500 font-semibold">{{ $customers->firstItem() + $idx }}</td>
                            <td class="font-bold text-slate-900">{{ $c->name }}</td>
                            <td>
                                @if($c->outlet)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        {{ $c->outlet->name }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                        <svg class="w-3 h-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Semua Toko (Global)
                                    </span>
                                @endif
                            </td>
                            <td class="font-mono">{{ $c->phone ?? '-' }}</td>
                            <td>{{ $c->address ?? '-' }}</td>
                            <td>{{ $c->notes ?? '-' }}</td>
                            <td class="text-center font-bold font-mono">{{ $c->sales_count }} Nota</td>
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $c->status === 'Aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $c->status }}
                                </span>
                            </td>
                            <td class="text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" onclick="editCustomer({{ json_encode($c) }})" title="Edit Pelanggan" class="p-1 rounded hover:bg-slate-100 text-slate-600 hover:text-emerald-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    @if(strtoupper($c->name) !== 'UMUM' && $c->sales_count == 0)
                                        <form action="{{ route('master.customers.destroy', $c) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus pelanggan [{{ $c->name }}]?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus Pelanggan" class="p-1 rounded hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-6 text-slate-400">Belum ada data pelanggan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-2 border-t border-slate-200 bg-slate-50">
            {{ $customers->links() }}
        </div>
    </div>
</div>

<!-- Modal Tambah Pelanggan -->
<div id="modalAddCustomer" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                <span>Tambah Pelanggan Baru</span>
            </h3>
            <button onclick="document.getElementById('modalAddCustomer').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>
        <form action="{{ route('master.customers.store') }}" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            <div>
                <label class="font-bold text-slate-700 block mb-1">Nama Pelanggan *</label>
                <input type="text" name="name" required placeholder="Contoh: Toko Berkah / UMUM" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            @if(auth()->user() && auth()->user()->isAdmin())
            <div>
                <label class="font-bold text-slate-700 block mb-1">Toko / Cabang</label>
                <select name="outlet_id" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                    <option value="">Semua Toko / Global (Pelanggan Umum)</option>
                    @foreach($outlets as $ot)
                        <option value="{{ $ot->id }}">{{ $ot->name }} ({{ $ot->code }})</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="font-bold text-slate-700 block mb-1">Nomor HP</label>
                <input type="text" name="phone" placeholder="Contoh: 0812XXXXXXXX" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            <div>
                <label class="font-bold text-slate-700 block mb-1">Alamat</label>
                <input type="text" name="address" placeholder="Contoh: Tambun Selatan" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            <div>
                <label class="font-bold text-slate-700 block mb-1">Keterangan</label>
                <input type="text" name="notes" placeholder="Pelanggan Konter Grosir / Retail" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            <div>
                <label class="font-bold text-slate-700 block mb-1">Status</label>
                <select name="status" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>
                </select>
            </div>
            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalAddCustomer').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN PELANGGAN</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pelanggan -->
<div id="modalEditCustomer" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                <span>Edit Data Pelanggan</span>
            </h3>
            <button onclick="document.getElementById('modalEditCustomer').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>
        <form id="formEditCustomer" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label class="font-bold text-slate-700 block mb-1">Nama Pelanggan *</label>
                <input type="text" name="name" id="edit_cust_name" required class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            @if(auth()->user() && auth()->user()->isAdmin())
            <div>
                <label class="font-bold text-slate-700 block mb-1">Toko / Cabang</label>
                <select name="outlet_id" id="edit_cust_outlet_id" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                    <option value="">Semua Toko / Global (Pelanggan Umum)</option>
                    @foreach($outlets as $ot)
                        <option value="{{ $ot->id }}">{{ $ot->name }} ({{ $ot->code }})</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="font-bold text-slate-700 block mb-1">Nomor HP</label>
                <input type="text" name="phone" id="edit_cust_phone" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            <div>
                <label class="font-bold text-slate-700 block mb-1">Alamat</label>
                <input type="text" name="address" id="edit_cust_address" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            <div>
                <label class="font-bold text-slate-700 block mb-1">Keterangan</label>
                <input type="text" name="notes" id="edit_cust_notes" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>
            <div>
                <label class="font-bold text-slate-700 block mb-1">Status</label>
                <select name="status" id="edit_cust_status" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>
                </select>
            </div>
            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalEditCustomer').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN PERUBAHAN</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCustomer(data) {
    const form = document.getElementById('formEditCustomer');
    form.action = "{{ url('/master/customers') }}/" + data.id;
    document.getElementById('edit_cust_name').value = data.name || '';
    const outletSelect = document.getElementById('edit_cust_outlet_id');
    if (outletSelect) {
        outletSelect.value = data.outlet_id || '';
    }
    document.getElementById('edit_cust_phone').value = data.phone || '';
    document.getElementById('edit_cust_address').value = data.address || '';
    document.getElementById('edit_cust_notes').value = data.notes || '';
    document.getElementById('edit_cust_status').value = data.status || 'Aktif';
    document.getElementById('modalEditCustomer').classList.remove('hidden');
}
</script>
@endsection
