@extends('layouts.app')

@section('title', 'Item Masuk')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path></svg>
                ITEM MASUK (PENAMBAHAN STOK NON-PEMBELIAN)
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Mencatat penambahan stok fisik barang dari bonus supplier, titipan, atau penyesuaian.</p>
        </div>

        <button onclick="document.getElementById('modalAddItemIn').classList.remove('hidden')" class="btn-retro btn-save flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>CATAT ITEM MASUK</span>
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-2.5 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Log Riwayat Item Masuk</span>
            </h3>
            <span class="text-[10px] text-green-200">Total: {{ $history->total() }} Data</span>
        </div>

        <x-table-toolbar tableId="itemInTable" excelName="Rekap_Item_Masuk" placeholder="Cari nomor, barang..." />

        <div class="overflow-x-auto">
            <table id="itemInTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th>NO TRANSAKSI</th>
                        <th>TIMESTAMP</th>
                        <th>KODE BARANG</th>
                        <th>NAMA BARANG</th>
                        <th class="text-center">QTY MASUK</th>
                        <th class="text-right">HARGA SATUAN</th>
                        <th class="text-right">TOTAL NILAI</th>
                        <th>KETERANGAN</th>
                        <th class="text-center w-24">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $idx => $item)
                        <tr>
                            <td class="text-center text-slate-500 font-semibold">{{ $history->firstItem() + $idx }}</td>
                            <td class="font-mono font-bold text-emerald-900">{{ $item->adjustment_number }}</td>
                            <td class="whitespace-nowrap">{{ $item->date->format('d/m/Y H:i') }}</td>
                            <td class="font-mono font-semibold">{{ $item->product->item_code ?? '-' }}</td>
                            <td class="font-bold text-slate-800">{{ $item->product->name ?? '-' }}</td>
                            <td class="text-center font-mono font-bold text-emerald-700">+{{ (float)$item->qty }}</td>
                            <td class="text-right font-mono">Rp {{ number_format($item->cost_price, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-slate-900">Rp {{ number_format($item->total_value, 0, ',', '.') }}</td>
                            <td class="text-slate-600">{{ $item->notes ?? 'BARANG MASUK' }}</td>
                            <td class="text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" onclick="editItemIn({{ json_encode($item) }})" title="Edit / Koreksi Item Masuk" class="p-1 rounded hover:bg-slate-100 text-slate-600 hover:text-emerald-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <form action="{{ route('inventory.item_in.destroy', $item) }}" method="POST" onsubmit="return confirm('Batalkan transaksi [{{ $item->adjustment_number }}]? Stok barang akan dikurangi kembali.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus / Batal" class="p-1 rounded hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-6 text-slate-400">Belum ada transaksi item masuk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-2 border-t border-slate-200 bg-slate-50">
            {{ $history->links() }}
        </div>
    </div>
</div>

<!-- Modal Tambah Item Masuk -->
<div id="modalAddItemIn" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path></svg>
                <span>Catat Item Masuk</span>
            </h3>
            <button onclick="document.getElementById('modalAddItemIn').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('inventory.item_in.store') }}" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            <div>
                <label class="font-bold text-slate-700 block mb-1">Pilih Produk *</label>
                <select name="product_id" id="itemInProductSelect" required onchange="handleItemInSelect()" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                    <option value="">-- Pilih Barang --</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" data-hpp="{{ $p->hpp }}" data-stock="{{ $p->stock }}">{{ $p->item_code }} - {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Qty Masuk *</label>
                    <input type="number" step="any" name="qty" value="1" min="0.01" required class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono text-center font-bold focus:ring-2 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Harga Satuan (Rp)</label>
                    <input type="number" step="any" name="cost_price" id="itemInCostPrice" value="0" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono text-right focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Keterangan / Alasan</label>
                <input type="text" name="notes" placeholder="Contoh: Bonus sales / sample produk" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalAddItemIn').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN ITEM MASUK</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Item Masuk -->
<div id="modalEditItemIn" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                <span>Edit / Koreksi Item Masuk</span>
            </h3>
            <button onclick="document.getElementById('modalEditItemIn').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form id="formEditItemIn" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            @method('PUT')
            
            <div>
                <label class="font-bold text-slate-700 block mb-1">Tanggal Transaksi *</label>
                <input type="date" name="date" id="edit_iin_date" required class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Nama Barang</label>
                <input type="text" id="edit_iin_product" disabled class="w-full px-3 py-1.5 border border-slate-200 bg-slate-100 rounded text-slate-600 font-bold">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Qty Masuk *</label>
                    <input type="number" step="any" name="qty" id="edit_iin_qty" min="0.01" required class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono text-center font-bold focus:ring-2 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Harga Satuan (Rp)</label>
                    <input type="number" step="any" name="cost_price" id="edit_iin_cost" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono text-right focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Keterangan / Alasan</label>
                <input type="text" name="notes" id="edit_iin_notes" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalEditItemIn').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN KOREKSI</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function editItemIn(data) {
    const form = document.getElementById('formEditItemIn');
    form.action = "{{ url('/inventory/item-in') }}/" + data.id;
    const dateVal = data.date ? data.date.substring(0, 10) : new Date().toISOString().substring(0, 10);
    document.getElementById('edit_iin_date').value = dateVal;
    document.getElementById('edit_iin_product').value = (data.product ? data.product.name : '') + ' (' + (data.product ? data.product.item_code : '') + ')';
    document.getElementById('edit_iin_qty').value = data.qty || 0;
    document.getElementById('edit_iin_cost').value = data.cost_price || 0;
    document.getElementById('edit_iin_notes').value = data.notes || '';
    document.getElementById('modalEditItemIn').classList.remove('hidden');
}

function handleItemInSelect() {
    const select = document.getElementById('itemInProductSelect');
    const opt = select.options[select.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('itemInCostPrice').value = opt.dataset.hpp || 0;
    }
}
</script>
@endpush
