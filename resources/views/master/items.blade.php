@extends('layouts.app')

@section('title', 'Daftar Item')

@section('content')
<div class="space-y-4">
    
    <!-- Header Controls & Warning -->
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                <span>DAFTAR ITEM (KATALOG PRODUK FISIK)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Total Nilai Persediaan Stok Fisik Saat Ini: <span class="font-bold text-emerald-800 font-mono">Rp {{ number_format($totalStockValue, 0, ',', '.') }}</span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="document.getElementById('modalManagePhysicalCategories').classList.remove('hidden')" class="btn-retro btn-refresh flex items-center gap-1.5 w-full sm:w-auto justify-center">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>KELOLA JENIS & MEREK</span>
            </button>
            <button type="button" onclick="document.getElementById('modalAddItem').classList.remove('hidden')" class="btn-retro btn-save flex items-center gap-1.5 w-full sm:w-auto justify-center">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>TAMBAH ITEM BARU</span>
            </button>
        </div>
    </div>

    <!-- Excel Instruction Box -->
    <div class="bg-slate-50 border border-slate-200 p-3 rounded-lg text-xs text-slate-800 flex items-start gap-2.5">
        <svg class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="leading-relaxed">
            <strong>PETUNJUK SHEET DAFTAR ITEM:</strong> Pada Daftar Item, yang perlu diisi awal adalah Kode Barang, Nama Barang, Jenis, Merek, Harga Retail, Harga Grosir, dan Stok Min. Nilai Stok dan HPP akan <strong>terupdate otomatis</strong> dari transaksi Pembelian (Moving Average), Penjualan, dan Modul Persediaan.
        </div>
    </div>

    <!-- Filters & Search -->
    <form action="{{ route('master.items') }}" method="GET" class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm flex flex-wrap items-center gap-3 text-xs">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Kode atau Nama Produk..." class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
        </div>

        <div>
            <select name="type" class="px-2.5 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                <option value="">-- Semua Jenis --</option>
                @foreach($types as $t)
                    <option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <select name="brand" class="px-2.5 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                <option value="">-- Semua Merek --</option>
                @foreach($brands as $b)
                    <option value="{{ $b }}" {{ request('brand') == $b ? 'selected' : '' }}>{{ $b }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn-retro btn-refresh">
            <span>FILTER</span>
        </button>

        @if(request()->hasAny(['search', 'type', 'brand']))
            <a href="{{ route('master.items') }}" class="text-xs text-rose-600 hover:underline">Reset</a>
        @endif
    </form>

    <!-- Toolbar Filter & Export -->
    <x-table-toolbar tableId="itemsTable" excelName="Rekap_Daftar_Barang" placeholder="Cari nama barang, barcode, merek..." />

    <!-- Table Preview (Format Sheet Excel Daftar Item) -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="itemsTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th>KODE BARANG</th>
                        <th>NAMA BARANG</th>
                        <th>JENIS</th>
                        <th>MEREK</th>
                        <th class="text-center">STOK</th>
                        <th class="text-center">MIN</th>
                        <th class="text-right">HPP (MODAL)</th>
                        <th class="text-right">HARGA RETAIL</th>
                        <th class="text-right">HARGA GROSIR</th>
                        <th class="text-right">SUBTOTAL NILAI</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center w-24">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $idx => $item)
                        <tr>
                            <td class="text-center text-slate-500 font-semibold">{{ $items->firstItem() + $idx }}</td>
                            <td class="font-mono font-bold text-emerald-800">{{ $item->item_code }}</td>
                            <td class="font-semibold text-slate-800">{{ $item->name }}</td>
                            <td><span class="px-2 py-0.5 rounded bg-slate-100 text-[10px] font-semibold text-slate-700">{{ $item->type }}</span></td>
                            <td>{{ $item->brand ?? '-' }}</td>
                            <td class="text-center font-mono font-bold {{ $item->stock <= $item->min_stock ? 'text-rose-600 bg-rose-50' : 'text-slate-800' }}">
                                {{ (float)$item->stock }}
                            </td>
                            <td class="text-center font-mono text-slate-500">{{ (float)$item->min_stock }}</td>
                            <td class="text-right font-mono font-semibold text-slate-700">Rp {{ number_format($item->hpp, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-slate-900">Rp {{ number_format($item->retail_price, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-semibold text-emerald-800">Rp {{ number_format($item->wholesale_price, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-slate-800 bg-slate-50/50">
                                Rp {{ number_format($item->stock * $item->hpp, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $item->status === 'Masih Dijual' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" onclick="editItem({{ json_encode($item) }})" title="Edit Barang" class="p-1 rounded hover:bg-slate-100 text-slate-600 hover:text-emerald-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <form action="{{ route('master.items.destroy', $item) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus item [{{ $item->name }}]?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus Barang" class="p-1 rounded hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-6 text-slate-400">Tidak ada data item ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
            {{ $items->links() }}
        </div>
    </div>

</div>

<!-- Modal Tambah Item -->
<div id="modalAddItem" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Item Produk Baru</span>
            </h3>
            <button onclick="document.getElementById('modalAddItem').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('master.items.store') }}" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Kode Barang *</label>
                    <input type="text" name="item_code" required placeholder="Contoh: VIS6/2" class="w-full px-3 py-1.5 border border-slate-300 rounded uppercase font-mono">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Nama Barang *</label>
                    <input type="text" name="name" required placeholder="Contoh: V ISAT 6GB 2H" class="w-full px-3 py-1.5 border border-slate-300 rounded">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="font-bold text-slate-700">Jenis Barang *</label>
                        <button type="button" onclick="document.getElementById('modalAddItem').classList.add('hidden'); document.getElementById('modalManagePhysicalCategories').classList.remove('hidden');" class="text-[10px] text-emerald-700 hover:underline font-bold flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Kelola</span>
                        </button>
                    </div>
                    <select name="type" id="selectItemType" required onchange="toggleNewItemType(this)" class="w-full px-3 py-1.5 border border-slate-300 rounded bg-white">
                        <option value="">-- Pilih Jenis Barang --</option>
                        @foreach($types as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                        <option value="__NEW__" class="font-bold text-emerald-700 bg-emerald-50">+ Tulis Jenis Baru...</option>
                    </select>
                    <div id="divNewItemType" class="mt-1.5 hidden">
                        <input type="text" name="new_type" id="inputNewItemType" placeholder="Tulis Jenis Baru (Mis: SOFTCASE)" class="w-full px-2.5 py-1 text-xs border border-emerald-500 rounded bg-emerald-50/50 uppercase font-bold text-emerald-800 focus:outline-none">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="font-bold text-slate-700">Merek / Brand</label>
                        <button type="button" onclick="document.getElementById('modalAddItem').classList.add('hidden'); document.getElementById('modalManagePhysicalCategories').classList.remove('hidden');" class="text-[10px] text-slate-500 hover:underline font-bold flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Kelola</span>
                        </button>
                    </div>
                    <select name="brand" id="selectItemBrand" onchange="toggleNewItemBrand(this)" class="w-full px-3 py-1.5 border border-slate-300 rounded bg-white">
                        <option value="">-- Pilih Merek (Opsional) --</option>
                        @foreach($brands as $b)
                            <option value="{{ $b }}">{{ $b }}</option>
                        @endforeach
                        <option value="__NEW__" class="font-bold text-slate-500 bg-slate-50">+ Tulis Merek Baru...</option>
                    </select>
                    <div id="divNewItemBrand" class="mt-1.5 hidden">
                        <input type="text" name="new_brand" id="inputNewItemBrand" placeholder="Tulis Merek Baru (Mis: ROBOT / VIVAN)" class="w-full px-2.5 py-1 text-xs border border-slate-300 rounded bg-slate-50/50 uppercase font-bold text-slate-800 focus:outline-none">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Stok Awal</label>
                    <input type="number" step="1" name="stock" value="0" required class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Stok Minimal</label>
                    <input type="number" step="1" name="min_stock" value="5" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">HPP Awal (Rp)</label>
                    <input type="number" step="1" name="hpp" value="0" required class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Harga Retail (Rp)</label>
                    <input type="number" step="1" name="retail_price" value="0" required class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Harga Grosir (Rp)</label>
                    <input type="number" step="1" name="wholesale_price" value="0" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono">
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Status Penjualan</label>
                <select name="status" class="w-full px-3 py-1.5 border border-slate-300 rounded">
                    <option value="Masih Dijual">Masih Dijual</option>
                    <option value="Tidak Dijual">Tidak Dijual</option>
                </select>
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalAddItem').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN ITEM</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Kelola Jenis & Merek Fisik -->
<div id="modalManagePhysicalCategories" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Kelola Jenis & Merek Produk Fisik</span>
            </h3>
            <button onclick="document.getElementById('modalManagePhysicalCategories').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <div class="p-5 text-xs space-y-4">
            <p class="text-slate-600">
                Kelola daftar <strong>Jenis Barang</strong> (VOCER, PERDANA, ACC, dll) dan <strong>Merek / Operator</strong> (TELKOMSEL, ROBOT, dll).
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Kolom Jenis Fisik -->
                <div class="border border-emerald-300 rounded-lg p-3 bg-[#f6faf6]">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-emerald-900 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span>Jenis Barang</span>
                        </span>
                        <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-bold">{{ $types->count() }} Item</span>
                    </div>

                    <!-- Form Tambah Jenis -->
                    <form action="{{ route('master.categories.store') }}" method="POST" class="flex gap-1.5 mb-3">
                        @csrf
                        <input type="hidden" name="type" value="physical_type">
                        <input type="text" name="name" required placeholder="+ Jenis Baru (mis: SOFTCASE)" class="flex-1 px-2.5 py-1 text-xs border border-emerald-400 rounded uppercase font-bold focus:outline-none">
                        <button type="submit" class="px-3 py-1 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded text-xs shadow-sm">
                            Tambah
                        </button>
                    </form>

                    <!-- List Jenis -->
                    <div class="max-h-48 overflow-y-auto space-y-1 pr-1">
                        @forelse($allPhysicalCategories->where('type', 'physical_type') as $cat)
                            <div class="flex items-center justify-between p-1.5 bg-white rounded border border-emerald-100 shadow-2xs">
                                <span class="font-semibold text-slate-800">{{ $cat->name }}</span>
                                <form action="{{ route('master.categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Hapus jenis {{ $cat->name }}?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs px-1.5 py-0.5 hover:bg-rose-50 rounded flex items-center gap-1" title="Hapus">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-slate-400 text-center py-2">Belum ada data</p>
                        @endforelse
                    </div>
                </div>

                <!-- Kolom Merek Fisik -->
                <div class="border border-slate-200 rounded-lg p-3 bg-[#fffdf6]">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-slate-800 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <span>Merek / Brand</span>
                        </span>
                        <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded font-bold">{{ $brands->count() }} Item</span>
                    </div>

                    <!-- Form Tambah Merek -->
                    <form action="{{ route('master.categories.store') }}" method="POST" class="flex gap-1.5 mb-3">
                        @csrf
                        <input type="hidden" name="type" value="physical_brand">
                        <input type="text" name="name" required placeholder="+ Merek Baru (mis: ROBOT)" class="flex-1 px-2.5 py-1 text-xs border border-slate-200 rounded uppercase font-bold focus:outline-none">
                        <button type="submit" class="px-3 py-1 bg-amber-700 hover:bg-amber-800 text-white font-bold rounded text-xs shadow-sm">
                            Tambah
                        </button>
                    </form>

                    <!-- List Merek -->
                    <div class="max-h-48 overflow-y-auto space-y-1 pr-1">
                        @forelse($allPhysicalCategories->where('type', 'physical_brand') as $cat)
                            <div class="flex items-center justify-between p-1.5 bg-white rounded border border-slate-200 shadow-2xs">
                                <span class="font-semibold text-slate-800">{{ $cat->name }}</span>
                                <form action="{{ route('master.categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Hapus merek {{ $cat->name }}?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs px-1.5 py-0.5 hover:bg-rose-50 rounded flex items-center gap-1" title="Hapus">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-slate-400 text-center py-2">Belum ada data</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-2">
                <span class="text-[11px] text-slate-500 text-center sm:text-left">Tips: Anda juga bisa langsung mengetik jenis/merek baru di form tambah item.</span>
                <button type="button" onclick="document.getElementById('modalManagePhysicalCategories').classList.add('hidden')" class="px-4 py-1.5 rounded bg-slate-200 hover:bg-slate-300 font-bold w-full sm:w-auto">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Item -->
<div id="modalEditItem" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full border border-slate-200 overflow-hidden max-h-[90vh] flex flex-col">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between shrink-0">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Edit Data Item Barang Fisik</span>
            </h3>
            <button onclick="document.getElementById('modalEditItem').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form id="formEditItem" method="POST" class="p-5 space-y-3.5 text-xs overflow-y-auto">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Kode Barang *</label>
                    <input type="text" name="item_code" id="edit_item_code" required class="w-full px-2.5 py-1.5 border border-slate-300 rounded font-mono focus:ring-2 focus:ring-emerald-600 uppercase">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Nama Barang *</label>
                    <input type="text" name="name" id="edit_item_name" required class="w-full px-2.5 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Jenis / Kategori *</label>
                    <input type="text" name="type" id="edit_item_type" required class="w-full px-2.5 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600 uppercase">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Merek</label>
                    <input type="text" name="brand" id="edit_item_brand" class="w-full px-2.5 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600 uppercase">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Stok Fisik *</label>
                    <input type="number" step="any" name="stock" id="edit_item_stock" required min="0" class="w-full px-2.5 py-1.5 border border-slate-300 rounded font-mono focus:ring-2 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Stok Minimum Peringatan</label>
                    <input type="number" name="min_stock" id="edit_item_min_stock" min="0" class="w-full px-2.5 py-1.5 border border-slate-300 rounded font-mono focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">HPP (Harga Modal) *</label>
                    <input type="number" step="any" name="hpp" id="edit_item_hpp" required min="0" class="w-full px-2.5 py-1.5 border border-slate-300 rounded font-mono focus:ring-2 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Harga Retail *</label>
                    <input type="number" step="any" name="retail_price" id="edit_item_retail" required min="0" class="w-full px-2.5 py-1.5 border border-slate-300 rounded font-mono font-bold focus:ring-2 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Harga Grosir</label>
                    <input type="number" step="any" name="wholesale_price" id="edit_item_wholesale" min="0" class="w-full px-2.5 py-1.5 border border-slate-300 rounded font-mono focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Status Penjualan *</label>
                <select name="status" id="edit_item_status" required class="w-full px-2.5 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                    <option value="Masih Dijual">Masih Dijual (Aktif di Kasir)</option>
                    <option value="Tidak Dijual">Tidak Dijual (Diarsipkan/Nonaktif)</option>
                </select>
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="document.getElementById('modalEditItem').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN PERUBAHAN</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleNewItemType(select) {
    const div = document.getElementById('divNewItemType');
    const input = document.getElementById('inputNewItemType');
    if (select.value === '__NEW__') {
        div.classList.remove('hidden');
        input.required = true;
        input.focus();
    } else {
        div.classList.add('hidden');
        input.required = false;
        input.value = '';
    }
}

function toggleNewItemBrand(select) {
    const div = document.getElementById('divNewItemBrand');
    const input = document.getElementById('inputNewItemBrand');
    if (select.value === '__NEW__') {
        div.classList.remove('hidden');
        input.required = true;
        input.focus();
    } else {
        div.classList.add('hidden');
        input.required = false;
        input.value = '';
    }
}

function editItem(data) {
    const form = document.getElementById('formEditItem');
    form.action = "{{ url('/master/items') }}/" + data.id;
    document.getElementById('edit_item_code').value = data.item_code || '';
    document.getElementById('edit_item_name').value = data.name || '';
    document.getElementById('edit_item_type').value = data.type || '';
    document.getElementById('edit_item_brand').value = data.brand || '';
    document.getElementById('edit_item_stock').value = data.stock ?? 0;
    document.getElementById('edit_item_min_stock').value = data.min_stock ?? 0;
    document.getElementById('edit_item_hpp').value = data.hpp ?? 0;
    document.getElementById('edit_item_retail').value = data.retail_price ?? 0;
    document.getElementById('edit_item_wholesale').value = data.wholesale_price ?? data.retail_price ?? 0;
    document.getElementById('edit_item_status').value = data.status || 'Masih Dijual';
    document.getElementById('modalEditItem').classList.remove('hidden');
}
</script>
@endsection
