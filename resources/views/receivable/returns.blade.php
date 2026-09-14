@extends('layouts.app')

@section('title', 'Reture Penjualan')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>RETUR PENJUALAN</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Pengembalian barang oleh pelanggan karena cacat, rusak atau salah input nota.</p>
        </div>

        <button onclick="document.getElementById('modalAddReturn').classList.remove('hidden')" class="btn-retro btn-save flex items-center gap-1.5 w-full sm:w-auto justify-center">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>INPUT RETUR PENJUALAN</span>
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-2.5 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Histori Transaksi Retur Penjualan</span>
            </h3>
            <span class="text-[10px] text-green-200">Total: {{ $returns->total() }} Data</span>
        </div>

        <x-table-toolbar tableId="returnsTable" excelName="Rekap_Retur_Penjualan" placeholder="Cari nota retur, barang..." />

        <div class="overflow-x-auto">
            <table id="returnsTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th>NO RETUR</th>
                        <th>TANGGAL</th>
                        <th>NO TRANSAKSI ASAL</th>
                        <th>PELANGGAN</th>
                        <th>NAMA BARANG</th>
                        <th class="text-center">QTY RETUR</th>
                        <th class="text-right">NILAI REFUND</th>
                        <th>ALASAN</th>
                        <th>KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $idx => $r)
                        <tr>
                            <td class="text-center text-slate-500 font-semibold">{{ $returns->firstItem() + $idx }}</td>
                            <td class="font-mono font-bold text-emerald-900">{{ $r->return_number }}</td>
                            <td class="whitespace-nowrap">{{ $r->date->format('d/m/Y') }}</td>
                            <td class="font-mono text-slate-700">{{ $r->originalSale->invoice_number ?? '-' }}</td>
                            <td class="font-bold text-slate-800">{{ $r->customer->name ?? '-' }}</td>
                            <td class="font-semibold">{{ $r->product->name ?? '-' }}</td>
                            <td class="text-center font-mono font-bold text-rose-600">+{{ (float)$r->qty }}</td>
                            <td class="text-right font-mono font-bold text-slate-900">Rp {{ number_format($r->amount, 0, ',', '.') }}</td>
                            <td><span class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 text-[10px] font-bold">{{ $r->reason ?? 'SALAH INPUT' }}</span></td>
                            <td class="text-slate-500">{{ $r->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-6 text-slate-400">Belum ada data retur penjualan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-2 border-t border-slate-200 bg-slate-50">
            {{ $returns->links() }}
        </div>
    </div>
</div>

<!-- Modal Retur Penjualan -->
<div id="modalAddReturn" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Input Retur Penjualan</span>
            </h3>
            <button onclick="document.getElementById('modalAddReturn').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('receivable.returns.store') }}" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Tanggal *</label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-1.5 border border-slate-300 rounded font-medium">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Pelanggan</label>
                    <select name="customer_id" class="w-full px-3 py-1.5 border border-slate-300 rounded">
                        <option value="">-- UMUM --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Barang Yang Diretur *</label>
                <select name="product_id" required class="w-full px-3 py-1.5 border border-slate-300 rounded">
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->item_code }} - {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Qty Retur (Pcs) *</label>
                    <input type="number" step="1" name="qty" value="1" min="1" required class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono text-center font-bold">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Nilai Refund (Rp) *</label>
                    <input type="number" step="1" name="amount" value="0" min="0" required class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono text-right font-bold text-emerald-800">
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Akun Refund Kasir (Kas Keluar)</label>
                <select name="refund_account_id" class="w-full px-3 py-1.5 border border-slate-300 rounded">
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ $acc->code === '1-1110' ? 'selected' : '' }}>{{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Alasan Retur</label>
                <select name="reason" class="w-full px-3 py-1.5 border border-slate-300 rounded">
                    <option value="SALAH INPUT">SALAH INPUT</option>
                    <option value="BARANG RUSAK/CACAT">BARANG RUSAK / CACAT</option>
                    <option value="SALAH BELI PELANGGAN">SALAH BELI PELANGGAN</option>
                </select>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Keterangan Tambahan</label>
                <input type="text" name="notes" placeholder="Catatan retur..." class="w-full px-3 py-1.5 border border-slate-300 rounded">
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalAddReturn').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN RETUR</button>
            </div>
        </form>
    </div>
</div>
@endsection
