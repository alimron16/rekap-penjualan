@extends('layouts.app')

@section('title', 'Pembayaran Hutang')

@section('content')
<div class="space-y-4">

    <!-- Header Controls -->
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>PEMBAYARAN HUTANG SUPPLIER (KULAKAN TEMPO)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Pelunasan nota hutang pembelian barang kepada supplier.</p>
        </div>

        <button onclick="document.getElementById('modalAddPayment').classList.remove('hidden')" class="btn-retro btn-save flex items-center gap-1.5 w-full sm:w-auto justify-center">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>BAYAR HUTANG SUPPLIER</span>
        </button>
    </div>

    <!-- Outstanding Debt Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm">
            <span class="text-[11px] text-slate-500 block font-semibold">Total Nota Belum Lunas</span>
            <span class="text-xl font-bold font-mono text-rose-600">{{ $unpaidPurchases->count() }} Nota</span>
        </div>
        <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm">
            <span class="text-[11px] text-slate-500 block font-semibold">Total Sisa Hutang Berjalan</span>
            <span class="text-xl font-bold font-mono text-rose-700">Rp {{ number_format($unpaidPurchases->sum('remaining_debt'), 0, ',', '.') }}</span>
        </div>
        <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm">
            <span class="text-[11px] text-slate-500 block font-semibold">Total Pembayaran Periode Ini</span>
            <span class="text-xl font-bold font-mono text-emerald-700">Rp {{ number_format($payments->sum('amount_paid'), 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Date Period Filter Form -->
    <form action="{{ route('purchase.debt_payments') }}" method="GET" class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm flex flex-wrap items-center gap-3 text-xs">
        <div class="flex items-center gap-2">
            <span class="font-bold text-slate-700">Awal:</span>
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-2 py-1 border border-slate-300 rounded font-medium">
        </div>
        <span class="text-slate-400 font-bold">-</span>
        <div class="flex items-center gap-2">
            <span class="font-bold text-slate-700">Akhir:</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-2 py-1 border border-slate-300 rounded font-medium">
        </div>
        <button type="submit" class="btn-retro btn-refresh">
            <span>FILTER TANGGAL</span>
        </button>
    </form>

    <!-- Table History Pembayaran Hutang -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-2.5 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Histori Transaksi Pembayaran Hutang</span>
            </h3>
            <span class="text-[10px] text-green-200">Total: {{ $payments->total() }} Data</span>
        </div>

        <x-table-toolbar tableId="debtPaymentsTable" excelName="Rekap_Bayar_Hutang" placeholder="Cari pembayaran, supplier..." />

        <div class="overflow-x-auto">
            <table id="debtPaymentsTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th>NO PEMBAYARAN</th>
                        <th>TANGGAL</th>
                        <th>SUPPLIER</th>
                        <th>AKUN PEMBAYARAN</th>
                        <th class="text-right">POTONGAN</th>
                        <th class="text-right">JUMLAH DIBAYAR</th>
                        <th>KETERANGAN</th>
                        <th class="text-center w-24">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $idx => $p)
                        <tr>
                            <td class="text-center text-slate-500 font-semibold">{{ $payments->firstItem() + $idx }}</td>
                            <td class="font-mono font-bold text-emerald-900">{{ $p->payment_number }}</td>
                            <td class="whitespace-nowrap">{{ $p->date->format('d/m/Y') }}</td>
                            <td class="font-bold text-slate-800">{{ $p->supplier->name ?? '-' }}</td>
                            <td><span class="px-2 py-0.5 rounded bg-slate-100 font-mono text-[11px] font-semibold">{{ $p->account->name ?? '-' }}</span></td>
                            <td class="text-right font-mono text-slate-500">Rp {{ number_format($p->discount, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-emerald-800">Rp {{ number_format($p->amount_paid, 0, ',', '.') }}</td>
                            <td class="text-slate-600">{{ $p->notes ?? '-' }}</td>
                            <td class="text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button onclick='editDebtPayment(@json($p))' class="p-1 rounded bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-semibold flex items-center gap-0.5" title="Koreksi Pembayaran">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <form action="{{ route('purchase.debt_payments.destroy', $p) }}" method="POST" onsubmit="return confirm('Batalkan pembayaran hutang ini? Sisa hutang pembelian akan dikembalikan.');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold flex items-center gap-0.5" title="Batalkan Pembayaran">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-6 text-slate-400">Tidak ada riwayat pembayaran hutang pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-2 border-t border-slate-200 bg-slate-50">
            {{ $payments->links() }}
        </div>
    </div>

</div>

<!-- Modal Bayar Hutang -->
<div id="modalAddPayment" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>Pembayaran Hutang Supplier</span>
            </h3>
            <button onclick="document.getElementById('modalAddPayment').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('purchase.debt_payments.store') }}" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Tanggal *</label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-1.5 border border-slate-300 rounded font-medium">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Supplier *</label>
                    <select name="supplier_id" id="selectSupplier" required class="w-full px-3 py-1.5 border border-slate-300 rounded">
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Pilih Nota Hutang Pembelian (Opsional)</label>
                <select name="purchase_id" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono">
                    <option value="">-- Pembayaran Hutang Umum --</option>
                    @foreach($unpaidPurchases as $up)
                        <option value="{{ $up->id }}">{{ $up->invoice_number }} ({{ $up->supplier->name ?? '-' }}) - Sisa: Rp {{ number_format($up->remaining_debt, 0, ',', '.') }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Akun Sumber Pembayaran *</label>
                <select name="account_id" required class="w-full px-3 py-1.5 border border-slate-300 rounded">
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Potongan Hutang (Rp)</label>
                    <input type="number" step="1" name="discount" value="0" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono text-right">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Jumlah Bayar (Rp) *</label>
                    <input type="number" step="1" name="amount_paid" required placeholder="Contoh: 500000" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono text-right font-bold text-emerald-800">
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Keterangan</label>
                <input type="text" name="notes" placeholder="Pelunasan Nota Grosir" class="w-full px-3 py-1.5 border border-slate-300 rounded">
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalAddPayment').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN PEMBAYARAN</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pembayaran Hutang -->
<div id="modalEditDebtPayment" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Edit / Koreksi Pembayaran Hutang</span>
            </h3>
            <button onclick="document.getElementById('modalEditDebtPayment').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form id="formEditDebtPayment" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            @method('PUT')
            
            <div>
                <label class="font-bold text-slate-700 block mb-1">Tanggal Transaksi *</label>
                <input type="date" name="date" id="edit_dp_date" required class="w-full px-3 py-1.5 border border-slate-300 rounded font-medium">
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Akun Sumber Pembayaran *</label>
                <select name="account_id" id="edit_dp_account" required class="w-full px-3 py-1.5 border border-slate-300 rounded">
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Potongan Hutang (Rp)</label>
                    <input type="number" step="1" name="discount" id="edit_dp_discount" value="0" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono text-right">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Jumlah Bayar (Rp) *</label>
                    <input type="number" step="1" name="amount_paid" id="edit_dp_amount" required class="w-full px-3 py-1.5 border border-emerald-500 rounded font-mono text-right font-bold text-emerald-800 bg-slate-50">
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Keterangan</label>
                <input type="text" name="notes" id="edit_dp_notes" class="w-full px-3 py-1.5 border border-slate-300 rounded">
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalEditDebtPayment').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN KOREKSI</button>
            </div>
        </form>
    </div>
</div>

<script>
function editDebtPayment(data) {
    const form = document.getElementById('formEditDebtPayment');
    form.action = "{{ url('/purchase/debt-payments') }}/" + data.id;
    const dateVal = data.date ? data.date.substring(0, 10) : new Date().toISOString().substring(0, 10);
    document.getElementById('edit_dp_date').value = dateVal;
    document.getElementById('edit_dp_account').value = data.account_id || '';
    document.getElementById('edit_dp_discount').value = data.discount || 0;
    document.getElementById('edit_dp_amount').value = data.amount_paid || 0;
    document.getElementById('edit_dp_notes').value = data.notes || '';
    document.getElementById('modalEditDebtPayment').classList.remove('hidden');
}
</script>
@endsection
