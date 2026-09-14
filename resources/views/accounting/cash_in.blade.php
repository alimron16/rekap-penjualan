@extends('layouts.app')

@section('title', 'Kas Masuk')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>KAS MASUK (PENERIMAAN DANA / INFLOW)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Penerimaan dana kas non-penjualan (tambahan modal, pendapatan lain, pelunasan non-nota).</p>
        </div>

        <button onclick="document.getElementById('modalAddCashIn').classList.remove('hidden')" class="btn-retro btn-save flex items-center gap-1.5 w-full sm:w-auto justify-center">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>CATAT KAS MASUK</span>
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-2.5 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">Histori Penerimaan Kas Masuk</h3>
            <span class="text-[10px] text-green-200">Total: {{ $history->total() }} Data</span>
        </div>

        <x-table-toolbar tableId="cashInTable" excelName="Rekap_Kas_Masuk" placeholder="Cari nomor transaksi, kasir, catatan..." />

        <div class="overflow-x-auto">
            <table id="cashInTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th>NO TRANSAKSI</th>
                        <th>TIMESTAMP</th>
                        <th>KODE DEBET</th>
                        <th>MASUK KE AKUN (DEBET)</th>
                        <th class="text-right">NOMINAL</th>
                        <th>KODE KREDIT</th>
                        <th>SUMBER DANA (KREDIT)</th>
                        <th>KETERANGAN</th>
                        <th class="text-center w-24">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $idx => $item)
                        <tr>
                            <td class="text-center text-slate-500 font-semibold">{{ $history->firstItem() + $idx }}</td>
                            <td class="font-mono font-bold text-emerald-900">{{ $item->transaction_number }}</td>
                            <td class="whitespace-nowrap">{{ $item->date->format('d/m/Y H:i') }}</td>
                            <td class="font-mono text-xs">{{ $item->debitAccount->code ?? '-' }}</td>
                            <td class="font-bold text-slate-800">{{ $item->debitAccount->name ?? '-' }}</td>
                            <td class="text-right font-mono font-bold text-emerald-800">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                            <td class="font-mono text-xs">{{ $item->creditAccount->code ?? '-' }}</td>
                            <td class="font-semibold text-slate-700">{{ $item->creditAccount->name ?? '-' }}</td>
                            <td class="text-slate-600">{{ $item->notes ?? '-' }}</td>
                            <td class="text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" onclick="editCashIn({{ json_encode($item) }})" title="Edit / Koreksi Kas Masuk" class="p-1 rounded hover:bg-slate-100 text-slate-600 hover:text-emerald-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <form action="{{ route('accounting.cash_in.destroy', $item) }}" method="POST" onsubmit="return confirm('Batalkan transaksi [{{ $item->transaction_number }}]? Jurnal terkait akan dihapus.')">
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
                            <td colspan="10" class="text-center py-6 text-slate-400">Belum ada transaksi kas masuk.</td>
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

<!-- Modal Kas Masuk -->
<div id="modalAddCashIn" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>Catat Kas Masuk</span>
            </h3>
            <button onclick="document.getElementById('modalAddCashIn').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('accounting.cash_in.store') }}" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            <div>
                <label class="font-bold text-slate-700 block mb-1">Masuk Ke Akun Kas / Bank (Debet) *</label>
                <select name="debit_account_id" required class="w-full px-3 py-1.5 border border-slate-300 rounded">
                    @foreach($destAccounts as $da)
                        <option value="{{ $da->id }}" {{ $da->code === '1-1110' ? 'selected' : '' }}>{{ $da->code }} - {{ $da->name }} (Saldo: Rp {{ number_format($da->current_balance, 0, ',', '.') }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Sumber Dana / Kategori Penerimaan (Kredit) *</label>
                <select name="credit_account_id" required class="w-full px-3 py-1.5 border border-slate-300 rounded">
                    @foreach($sourceAccounts as $sa)
                        <option value="{{ $sa->id }}">{{ $sa->code }} - {{ $sa->name }} [{{ $sa->group }}]</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Nominal Penerimaan (Rp) *</label>
                <input type="number" step="1" name="amount" required placeholder="Contoh: 1000000" class="w-full px-3 py-1.5 border border-emerald-500 rounded font-mono font-bold text-sm text-emerald-800 text-right bg-slate-50">
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Keterangan Penerimaan</label>
                <input type="text" name="notes" placeholder="Contoh: Tambahan modal awal bos" class="w-full px-3 py-1.5 border border-slate-300 rounded">
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalAddCashIn').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN KAS MASUK</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kas Masuk -->
<div id="modalEditCashIn" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Edit / Koreksi Kas Masuk</span>
            </h3>
            <button onclick="document.getElementById('modalEditCashIn').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form id="formEditCashIn" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            @method('PUT')
            
            <div>
                <label class="font-bold text-slate-700 block mb-1">Tanggal Transaksi *</label>
                <input type="date" name="date" id="edit_ci_date" required class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Masuk Ke Akun Kas / Bank (Debet) *</label>
                <select name="debit_account_id" id="edit_ci_debit" required class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                    @foreach($destAccounts as $da)
                        <option value="{{ $da->id }}">{{ $da->code }} - {{ $da->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Sumber Dana / Kategori Penerimaan (Kredit) *</label>
                <select name="credit_account_id" id="edit_ci_credit" required class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
                    @foreach($sourceAccounts as $sa)
                        <option value="{{ $sa->id }}">{{ $sa->code }} - {{ $sa->name }} [{{ $sa->group }}]</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Nominal Penerimaan (Rp) *</label>
                <input type="number" step="1" name="amount" id="edit_ci_amount" required class="w-full px-3 py-1.5 border border-emerald-500 rounded font-mono font-bold text-sm text-emerald-800 text-right bg-slate-50 focus:ring-2 focus:ring-emerald-600">
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Keterangan Penerimaan</label>
                <input type="text" name="notes" id="edit_ci_notes" class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-emerald-600">
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalEditCashIn').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN KOREKSI</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCashIn(data) {
    const form = document.getElementById('formEditCashIn');
    form.action = "{{ url('/accounting/cash-in') }}/" + data.id;
    const dateVal = data.date ? data.date.substring(0, 10) : new Date().toISOString().substring(0, 10);
    document.getElementById('edit_ci_date').value = dateVal;
    document.getElementById('edit_ci_debit').value = data.debit_account_id || '';
    document.getElementById('edit_ci_credit').value = data.credit_account_id || '';
    document.getElementById('edit_ci_amount').value = data.amount || 0;
    document.getElementById('edit_ci_notes').value = data.notes || '';
    document.getElementById('modalEditCashIn').classList.remove('hidden');
}
</script>
@endsection
