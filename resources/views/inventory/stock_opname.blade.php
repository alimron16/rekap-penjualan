@extends('layouts.app')

@section('title', 'Stok Opname')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                STOK OPNAME FISIK GUDANG & ETALASE
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Penyesuaian stok sistem dengan perhitungan fisik riil di etalase toko.</p>
        </div>

        <button onclick="document.getElementById('modalAddOpname').classList.remove('hidden')" class="btn-retro btn-save flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>INPUT STOK OPNAME</span>
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-2.5 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Log Riwayat Penyesuaian Stok Opname</span>
            </h3>
            <span class="text-[10px] text-green-200">Total: {{ $history->total() }} Data</span>
        </div>

        <x-table-toolbar tableId="stockOpnameTable" excelName="Rekap_Stok_Opname" placeholder="Cari nomor, barang..." />

        <div class="overflow-x-auto">
            <table id="stockOpnameTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th>NO TRANSAKSI</th>
                        <th>TIMESTAMP</th>
                        <th>KODE BARANG</th>
                        <th>NAMA BARANG</th>
                        <th class="text-center">STOK SISTEM</th>
                        <th class="text-center">STOK FISIK RIIL</th>
                        <th class="text-center">SELISIH QTY</th>
                        <th class="text-right">HPP</th>
                        <th class="text-right">NILAI SELISIH</th>
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
                            <td class="text-center font-mono">{{ (float)$item->system_stock }}</td>
                            <td class="text-center font-mono font-bold text-emerald-800">{{ (float)$item->actual_stock }}</td>
                            <td class="text-center font-mono font-extrabold {{ $item->diff_qty < 0 ? 'text-rose-600' : ($item->diff_qty > 0 ? 'text-emerald-600' : 'text-slate-500') }}">
                                {{ $item->diff_qty > 0 ? '+' : '' }}{{ (float)$item->diff_qty }}
                            </td>
                            <td class="text-right font-mono">Rp {{ number_format($item->cost_price, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold {{ $item->diff_qty < 0 ? 'text-rose-600' : 'text-slate-800' }}">
                                Rp {{ number_format($item->total_value, 0, ',', '.') }}
                            </td>
                            <td class="text-slate-600">{{ $item->notes ?? 'STOK OPNAME' }}</td>
                            <td class="text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" onclick="editOpname({{ json_encode($item) }})" title="Edit / Koreksi Stok Opname" class="p-1 rounded hover:bg-slate-100 text-slate-600 hover:text-emerald-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <form action="{{ route('inventory.stock_opname.destroy', $item) }}" method="POST" onsubmit="return confirm('Batalkan transaksi opname [{{ $item->adjustment_number }}]? Stok akan dikembalikan ke saldo sistem awal.')">
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
                            <td colspan="12" class="text-center py-6 text-slate-400">Belum ada data stok opname.</td>
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

<!-- Modal Tambah Opname -->
<div id="modalAddOpname" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                <span>Form Penyesuaian Stok Opname</span>
            </h3>
            <button onclick="document.getElementById('modalAddOpname').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('inventory.stock_opname.store') }}" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            <div>
                <label class="font-bold text-slate-700 block mb-1">Pilih Produk Yang Dihitung *</label>
                <select name="product_id" id="opnameProductSelect" required onchange="handleOpnameSelect()" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                    <option value="">-- Pilih Barang --</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" data-stock="{{ $p->stock }}" data-hpp="{{ $p->hpp }}">{{ $p->item_code }} - {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Stok Sistem Saat Ini</label>
                    <input type="number" id="opnameSystemStock" readonly disabled value="0" class="w-full px-3 py-1.5 border border-slate-200 bg-slate-100 rounded font-mono text-center font-bold text-slate-600">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Hasil Hitung Fisik Riil *</label>
                    <input type="number" step="any" name="actual_stock" id="opnameActualStock" value="0" min="0" required oninput="calculateOpnameDiff()" class="w-full px-3 py-1.5 border border-emerald-500 rounded font-mono text-center font-bold text-emerald-800 bg-slate-50 focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>

            <div class="p-2.5 rounded bg-slate-50 border border-slate-200 flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-600">Selisih Varian:</span>
                <span id="opnameDiffLabel" class="font-mono font-bold text-xs text-slate-700">0 pcs</span>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Keterangan Tambahan</label>
                <input type="text" name="notes" placeholder="Opname berkala etalase" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalAddOpname').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SESUAIKAN STOK</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Opname -->
<div id="modalEditOpname" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                <span>Edit / Koreksi Hasil Stok Opname</span>
            </h3>
            <button onclick="document.getElementById('modalEditOpname').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form id="formEditOpname" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            @method('PUT')
            
            <div>
                <label class="font-bold text-slate-700 block mb-1">Nama Barang</label>
                <input type="text" id="edit_opname_product" disabled class="w-full px-3 py-1.5 border border-slate-200 bg-slate-100 rounded text-slate-600 font-bold">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Stok Sistem</label>
                    <input type="number" id="edit_opname_system" disabled class="w-full px-3 py-1.5 border border-slate-200 bg-slate-100 rounded font-mono text-center font-bold text-slate-600">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Koreksi Fisik Riil *</label>
                    <input type="number" step="any" name="actual_stock" id="edit_opname_actual" min="0" required oninput="calculateEditOpnameDiff()" class="w-full px-3 py-1.5 border border-emerald-500 rounded font-mono text-center font-bold text-emerald-800 bg-slate-50 focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>

            <div class="p-2.5 rounded bg-slate-50 border border-slate-200 flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-600">Selisih Baru:</span>
                <span id="editOpnameDiffLabel" class="font-mono font-bold text-xs text-slate-700">0 pcs</span>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Keterangan Koreksi</label>
                <input type="text" name="notes" id="edit_opname_notes" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalEditOpname').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN KOREKSI</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function editOpname(data) {
    const form = document.getElementById('formEditOpname');
    form.action = "{{ url('/inventory/stock-opname') }}/" + data.id;
    document.getElementById('edit_opname_product').value = (data.product ? data.product.name : '') + ' (' + (data.product ? data.product.item_code : '') + ')';
    document.getElementById('edit_opname_system').value = data.system_stock || 0;
    document.getElementById('edit_opname_actual').value = data.actual_stock || 0;
    document.getElementById('edit_opname_notes').value = data.notes || '';
    calculateEditOpnameDiff();
    document.getElementById('modalEditOpname').classList.remove('hidden');
}

function calculateEditOpnameDiff() {
    const sys = parseFloat(document.getElementById('edit_opname_system').value || 0);
    const act = parseFloat(document.getElementById('edit_opname_actual').value || 0);
    const diff = act - sys;
    const label = document.getElementById('editOpnameDiffLabel');
    if (diff < 0) {
        label.className = 'font-mono font-extrabold text-xs text-rose-600';
        label.innerText = `${diff} pcs (Kurang)`;
    } else if (diff > 0) {
        label.className = 'font-mono font-extrabold text-xs text-emerald-700';
        label.innerText = `+${diff} pcs (Lebih)`;
    } else {
        label.className = 'font-mono font-extrabold text-xs text-slate-600';
        label.innerText = `0 pcs (Sesuai)`;
    }
}

function handleOpnameSelect() {
    const select = document.getElementById('opnameProductSelect');
    const opt = select.options[select.selectedIndex];
    if (opt && opt.value) {
        const stock = parseFloat(opt.dataset.stock || 0);
        document.getElementById('opnameSystemStock').value = stock;
        document.getElementById('opnameActualStock').value = stock;
        calculateOpnameDiff();
    }
}

function calculateOpnameDiff() {
    const sys = parseFloat(document.getElementById('opnameSystemStock').value || 0);
    const act = parseFloat(document.getElementById('opnameActualStock').value || 0);
    const diff = act - sys;

    const label = document.getElementById('opnameDiffLabel');
    if (diff < 0) {
        label.className = 'font-mono font-extrabold text-sm text-rose-600';
        label.innerText = `${diff} pcs (Hilang / Kurang)`;
    } else if (diff > 0) {
        label.className = 'font-mono font-extrabold text-sm text-emerald-700';
        label.innerText = `+${diff} pcs (Lebih)`;
    } else {
        label.className = 'font-mono font-extrabold text-sm text-slate-600';
        label.innerText = `0 pcs (Cocok Sesuai)`;
    }
}
</script>
@endpush
