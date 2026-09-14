@extends('layouts.app')

@section('title', 'Produk Multi')

@section('content')
<div class="space-y-4">

    <!-- Header Controls -->
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span>PRODUK MULTI (PULSA, TOKEN PLN & DIGITAL SERVICES)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Katalog produk digital aplikasi server multi pulsa dan token listrik.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="document.getElementById('modalManageCategories').classList.remove('hidden')" class="btn-retro btn-refresh flex items-center gap-1.5 w-full sm:w-auto justify-center">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>KELOLA JENIS & KATEGORI</span>
            </button>
            <button type="button" onclick="document.getElementById('modalAddMulti').classList.remove('hidden')" class="btn-retro btn-save flex items-center gap-1.5 w-full sm:w-auto justify-center">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>TAMBAH PRODUK MULTI</span>
            </button>
        </div>
    </div>

    <!-- Excel Warning Box -->
    <div class="bg-slate-50 border border-slate-200 p-3 rounded-lg text-xs text-slate-800 flex items-start gap-2.5">
        <svg class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="leading-relaxed">
            <strong>PETUNJUK SHEET PRODUK MULTI:</strong> Selalu update HPP produk bila terjadi kenaikan harga dari aplikasi server multi pulsa agar laba margin tercatat akurat dan real-time.
        </div>
    </div>

    <!-- Filters & Search -->
    <form action="{{ route('master.multi') }}" method="GET" class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm flex flex-wrap items-center gap-3 text-xs">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Kode atau Nama Produk Multi..." class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
        </div>

        <div>
            <select name="trx_type" class="px-2.5 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                <option value="">-- Semua Jenis Trx --</option>
                @foreach($trxTypes as $tt)
                    <option value="{{ $tt }}" {{ request('trx_type') == $tt ? 'selected' : '' }}>{{ $tt }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <select name="category" class="px-2.5 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                <option value="">-- Semua Kategori --</option>
                @foreach($categories as $c)
                    <option value="{{ $c }}" {{ request('category') == $c ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn-retro btn-refresh">
            <span>FILTER</span>
        </button>

        @if(request()->hasAny(['search', 'trx_type', 'category']))
            <a href="{{ route('master.multi') }}" class="text-xs text-rose-600 hover:underline">Reset</a>
        @endif
    </form>

    <!-- Toolbar Filter & Export -->
    <x-table-toolbar tableId="multiTable" excelName="Rekap_Produk_Multi" placeholder="Cari kode produk, voucher, provider..." />

    <!-- Table Preview (Format Sheet Excel Produk Multi) -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="multiTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th>KODE PRODUK</th>
                        <th>NAMA PRODUK</th>
                        <th>JENIS TRX</th>
                        <th>KATEGORI</th>
                        <th class="text-right">HPP (MODAL SERVER)</th>
                        <th class="text-right">HARGA JUAL</th>
                        <th class="text-right">MARGIN LABA</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center w-24">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $idx => $mp)
                        <tr>
                            <td class="text-center text-slate-500 font-semibold">{{ $products->firstItem() + $idx }}</td>
                            <td class="font-mono font-bold text-emerald-800">{{ $mp->product_code }}</td>
                            <td class="font-semibold text-slate-800">{{ $mp->name }}</td>
                            <td><span class="px-2 py-0.5 rounded bg-slate-100 text-[10px] font-semibold text-slate-700">{{ $mp->trx_type }}</span></td>
                            <td><span class="px-2 py-0.5 rounded bg-slate-50 text-[10px] font-bold text-slate-500 border border-slate-200">{{ $mp->category }}</span></td>
                            <td class="text-right font-mono font-semibold text-slate-700">Rp {{ number_format($mp->hpp, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-slate-900">Rp {{ number_format($mp->selling_price, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-emerald-700">
                                +Rp {{ number_format($mp->margin, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $mp->status === 'OPEN' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }}">
                                    {{ $mp->status }}
                                </span>
                            </td>
                            <td class="text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" onclick="editMulti({{ json_encode($mp) }})" title="Edit Produk" class="p-1 rounded hover:bg-slate-100 text-slate-600 hover:text-emerald-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <form action="{{ route('master.multi.destroy', $mp) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus produk [{{ $mp->name }}]?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus Produk" class="p-1 rounded hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-6 text-slate-400">Tidak ada produk multi ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
            {{ $products->links() }}
        </div>
    </div>

</div>

<!-- Modal Tambah Produk Multi -->
<div id="modalAddMulti" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Produk Multi Baru</span>
            </h3>
            <button onclick="document.getElementById('modalAddMulti').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('master.multi.store') }}" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Kode Produk *</label>
                    <input type="text" name="product_code" required placeholder="Contoh: PLN20" class="w-full px-3 py-1.5 border border-slate-300 rounded uppercase font-mono">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Nama Produk *</label>
                    <input type="text" name="name" required placeholder="Contoh: TOKEN PLN 20,000" class="w-full px-3 py-1.5 border border-slate-300 rounded">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="font-bold text-slate-700">Jenis Trx *</label>
                        <button type="button" onclick="document.getElementById('modalAddMulti').classList.add('hidden'); document.getElementById('modalManageCategories').classList.remove('hidden');" class="text-[10px] text-emerald-700 hover:underline font-bold flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Kelola</span>
                        </button>
                    </div>
                    <select name="trx_type" id="selectTrxType" required onchange="toggleNewTrxType(this)" class="w-full px-3 py-1.5 border border-slate-300 rounded bg-white">
                        <option value="">-- Pilih Jenis Trx --</option>
                        @foreach($trxTypes as $tt)
                            <option value="{{ $tt }}">{{ $tt }}</option>
                        @endforeach
                        <option value="__NEW__" class="font-bold text-emerald-700 bg-emerald-50">+ Tulis Jenis Trx Baru...</option>
                    </select>
                    <div id="divNewTrxType" class="mt-1.5 hidden">
                        <input type="text" name="new_trx_type" id="inputNewTrxType" placeholder="Tulis Jenis Trx Baru (Mis: E-WALLET)" class="w-full px-2.5 py-1 text-xs border border-emerald-500 rounded bg-emerald-50/50 uppercase font-bold text-emerald-800 focus:outline-none">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="font-bold text-slate-700">Kategori *</label>
                        <button type="button" onclick="document.getElementById('modalAddMulti').classList.add('hidden'); document.getElementById('modalManageCategories').classList.remove('hidden');" class="text-[10px] text-slate-500 hover:underline font-bold flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Kelola</span>
                        </button>
                    </div>
                    <select name="category" id="selectCategory" required onchange="toggleNewCategory(this)" class="w-full px-3 py-1.5 border border-slate-300 rounded bg-white">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                        <option value="__NEW__" class="font-bold text-slate-500 bg-slate-50">+ Tulis Kategori Baru...</option>
                    </select>
                    <div id="divNewCategory" class="mt-1.5 hidden">
                        <input type="text" name="new_category" id="inputNewCategory" placeholder="Tulis Kategori Baru (Mis: GO-PAY)" class="w-full px-2.5 py-1 text-xs border border-slate-300 rounded bg-slate-50/50 uppercase font-bold text-slate-800 focus:outline-none">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">HPP Modal Server (Rp) *</label>
                    <input type="number" step="1" name="hpp" required placeholder="Contoh: 20100" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Harga Jual Kasir (Rp) *</label>
                    <input type="number" step="1" name="selling_price" required placeholder="Contoh: 22000" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono">
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Status Server</label>
                <select name="status" class="w-full px-3 py-1.5 border border-slate-300 rounded">
                    <option value="OPEN">OPEN (Bisa Transaksi)</option>
                    <option value="CLOSE">CLOSE (Gangguan)</option>
                </select>
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalAddMulti').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN PRODUK MULTI</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Kelola Jenis & Kategori -->
<div id="modalManageCategories" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Kelola Jenis Transaksi & Kategori Produk Multi</span>
            </h3>
            <button onclick="document.getElementById('modalManageCategories').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <div class="p-5 text-xs space-y-4">
            <p class="text-slate-600">
                Anda dapat menambahkan atau menghapus opsi <strong>Jenis Transaksi</strong> dan <strong>Kategori</strong> produk digital multi di bawah ini. Opsi baru akan otomatis muncul di dropdown pilihan.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Kolom Jenis Transaksi -->
                <div class="border border-emerald-300 rounded-lg p-3 bg-[#f6faf6]">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-emerald-900 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            <span>Jenis Trx Digital</span>
                        </span>
                        <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-bold">{{ $trxTypes->count() }} Item</span>
                    </div>

                    <!-- Form Tambah Jenis Trx -->
                    <form action="{{ route('master.categories.store') }}" method="POST" class="flex gap-1.5 mb-3">
                        @csrf
                        <input type="hidden" name="type" value="digital_type">
                        <input type="text" name="name" required placeholder="+ Jenis Baru (mis: E-WALLET)" class="flex-1 px-2.5 py-1 text-xs border border-emerald-400 rounded uppercase font-bold focus:outline-none">
                        <button type="submit" class="px-3 py-1 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded text-xs shadow-sm">
                            Tambah
                        </button>
                    </form>

                    <!-- List Jenis Trx -->
                    <div class="max-h-48 overflow-y-auto space-y-1 pr-1">
                        @forelse($allDigitalCategories->where('type', 'digital_type') as $cat)
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

                <!-- Kolom Kategori -->
                <div class="border border-slate-200 rounded-lg p-3 bg-[#fffdf6]">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-slate-800 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <span>Kategori / Operator</span>
                        </span>
                        <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded font-bold">{{ $categories->count() }} Item</span>
                    </div>

                    <!-- Form Tambah Kategori -->
                    <form action="{{ route('master.categories.store') }}" method="POST" class="flex gap-1.5 mb-3">
                        @csrf
                        <input type="hidden" name="type" value="digital_category">
                        <input type="text" name="name" required placeholder="+ Kategori Baru (mis: SHOPEEPAY)" class="flex-1 px-2.5 py-1 text-xs border border-slate-200 rounded uppercase font-bold focus:outline-none">
                        <button type="submit" class="px-3 py-1 bg-amber-700 hover:bg-amber-800 text-white font-bold rounded text-xs shadow-sm">
                            Tambah
                        </button>
                    </form>

                    <!-- List Kategori -->
                    <div class="max-h-48 overflow-y-auto space-y-1 pr-1">
                        @forelse($allDigitalCategories->where('type', 'digital_category') as $cat)
                            <div class="flex items-center justify-between p-1.5 bg-white rounded border border-slate-200 shadow-2xs">
                                <span class="font-semibold text-slate-800">{{ $cat->name }}</span>
                                <form action="{{ route('master.categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Hapus kategori {{ $cat->name }}?')" class="inline">
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
                <span class="text-[11px] text-slate-500 text-center sm:text-left">Tips: Anda juga bisa langsung mengetik jenis/kategori baru di form tambah produk.</span>
                <button type="button" onclick="document.getElementById('modalManageCategories').classList.add('hidden')" class="px-4 py-1.5 rounded bg-slate-200 hover:bg-slate-300 font-bold w-full sm:w-auto">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Produk Multi -->
<div id="modalEditMulti" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden max-h-[90vh] flex flex-col">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between shrink-0">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Edit Produk Multi / Elektrik</span>
            </h3>
            <button onclick="document.getElementById('modalEditMulti').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form id="formEditMulti" method="POST" class="p-5 space-y-3.5 text-xs overflow-y-auto">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Kode Produk *</label>
                    <input type="text" name="product_code" id="edit_multi_code" required class="w-full px-2.5 py-1.5 border border-slate-300 rounded font-mono focus:ring-2 focus:ring-emerald-600 uppercase">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Nama Produk *</label>
                    <input type="text" name="name" id="edit_multi_name" required class="w-full px-2.5 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Jenis Transaksi *</label>
                    <input type="text" name="trx_type" id="edit_multi_type" required class="w-full px-2.5 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600 uppercase">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Kategori *</label>
                    <input type="text" name="category" id="edit_multi_category" required class="w-full px-2.5 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600 uppercase">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">HPP (Modal Server) *</label>
                    <input type="number" step="any" name="hpp" id="edit_multi_hpp" required min="0" class="w-full px-2.5 py-1.5 border border-slate-300 rounded font-mono focus:ring-2 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Harga Jual Konsumen *</label>
                    <input type="number" step="any" name="selling_price" id="edit_multi_price" required min="0" class="w-full px-2.5 py-1.5 border border-slate-300 rounded font-mono font-bold focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Status Produk *</label>
                <select name="status" id="edit_multi_status" required class="w-full px-2.5 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                    <option value="OPEN">OPEN (Tersedia / Server Aktif)</option>
                    <option value="CLOSE">CLOSE (Gangguan / Ditutup)</option>
                </select>
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="document.getElementById('modalEditMulti').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN PERUBAHAN</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleNewTrxType(select) {
    const div = document.getElementById('divNewTrxType');
    const input = document.getElementById('inputNewTrxType');
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

function toggleNewCategory(select) {
    const div = document.getElementById('divNewCategory');
    const input = document.getElementById('inputNewCategory');
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

function editMulti(data) {
    const form = document.getElementById('formEditMulti');
    form.action = "{{ url('/master/multi') }}/" + data.id;
    document.getElementById('edit_multi_code').value = data.product_code || '';
    document.getElementById('edit_multi_name').value = data.name || '';
    document.getElementById('edit_multi_type').value = data.trx_type || '';
    document.getElementById('edit_multi_category').value = data.category || '';
    document.getElementById('edit_multi_hpp').value = data.hpp ?? 0;
    document.getElementById('edit_multi_price').value = data.selling_price ?? 0;
    document.getElementById('edit_multi_status').value = data.status || 'OPEN';
    document.getElementById('modalEditMulti').classList.remove('hidden');
}
</script>
@endsection
