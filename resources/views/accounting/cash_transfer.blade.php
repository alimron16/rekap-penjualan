@extends('layouts.app')

@section('title', 'Kas Transfer & Agen')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span>KAS TRANSFER (AGEN TRANSFER TUNAI & TARIK TUNAI)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Layanan transfer uang dan tarik tunai bank/e-wallet dengan pendapatan fee admin instan.</p>
        </div>

        <button onclick="document.getElementById('modalAddTransfer').classList.remove('hidden')" class="btn-retro btn-save flex items-center gap-1.5 w-full sm:w-auto justify-center">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>TRANSAKSI KAS TRANSFER</span>
        </button>
    </div>

    <!-- Excel Guideline Box -->
    <div class="bg-slate-50 border border-slate-200 p-3 rounded-lg text-xs text-slate-800 flex items-start gap-2.5">
        <svg class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="leading-relaxed">
            <strong>RUMUS SHEET EXCEL KAS TRANSFER:</strong><br>
            <span class="font-mono font-bold text-emerald-900 bg-emerald-100 px-2 py-0.5 rounded">SALDO TRANSFER (Bank) + BIAYA ADMIN = CASH TRANSFER (Diterima Kasir)</span><br>
            Biaya admin langsung diakui sebagai pendapatan jasa operasional toko (Akun: 4-1200 PENDAPATAN JASA).
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-2.5 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">Histori Transaksi Kas Transfer Agen</h3>
            <span class="text-[10px] text-green-200">Total: {{ $history->total() }} Data</span>
        </div>

        <x-table-toolbar tableId="cashTransferTable" excelName="Rekap_Kas_Transfer" placeholder="Cari nomor transfer, rekening..." />

        <div class="overflow-x-auto">
            <table id="cashTransferTable" class="excel-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">NO</th>
                        <th>NO TRANSAKSI</th>
                        <th>TIMESTAMP</th>
                        <th>DARI AKUN (KREDIT)</th>
                        <th>TRANSFER KE (DEBET)</th>
                        <th class="text-right">NOMINAL TRANSFER</th>
                        <th class="text-right">BIAYA ADMIN (FEE)</th>
                        <th class="text-right">TOTAL DITERIMA KASIR</th>
                        <th>KETERANGAN / REKENING TUJUAN</th>
                        <th class="text-center w-24">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $idx => $item)
                        <tr>
                            <td class="text-center text-slate-500 font-semibold">{{ $history->firstItem() + $idx }}</td>
                            <td class="font-mono font-bold text-emerald-900">{{ $item->transaction_number }}</td>
                            <td class="whitespace-nowrap">{{ $item->date->format('d/m/Y H:i') }}</td>
                            <td><span class="px-2 py-0.5 rounded bg-slate-100 font-mono text-xs font-semibold">{{ $item->creditAccount->name ?? '-' }}</span></td>
                            <td><span class="px-2 py-0.5 rounded bg-slate-50 font-mono text-xs font-bold text-slate-800 border border-slate-200">{{ $item->debitAccount->name ?? '-' }}</span></td>
                            <td class="text-right font-mono font-bold text-slate-900">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-extrabold text-emerald-700">+Rp {{ number_format($item->admin_fee, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-extrabold text-slate-800 bg-slate-50">Rp {{ number_format($item->amount + $item->admin_fee, 0, ',', '.') }}</td>
                            <td class="text-slate-600 font-medium">{{ $item->notes ?? '-' }}</td>
                            <td class="text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button onclick='editCashTransfer(@json($item))' class="p-1 rounded bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-semibold flex items-center gap-0.5" title="Koreksi Transaksi">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <form action="{{ route('accounting.cash_transfer.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus/batalkan transaksi transfer {{ $item->transaction_number }}? Saldo kas akan direkonsiliasi.');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold flex items-center gap-0.5" title="Hapus Transaksi">
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
                            <td colspan="10" class="text-center py-6 text-slate-400">Belum ada transaksi kas transfer.</td>
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

<!-- Modal Kas Transfer -->
<div id="modalAddTransfer" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span>Transaksi Transfer Tunai / Tarik Tunai</span>
            </h3>
            <button onclick="document.getElementById('modalAddTransfer').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('accounting.cash_transfer.store') }}" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            <div>
                <label class="font-bold text-slate-700 block mb-1">Dari Akun Sumber Dana (Kredit) *</label>
                <select name="credit_account_id" required class="w-full px-3 py-1.5 border border-slate-300 rounded">
                    @foreach($sourceAccounts as $sa)
                        <option value="{{ $sa->id }}" {{ $sa->code === '1-1113' ? 'selected' : '' }}>{{ $sa->name }} (Saldo: Rp {{ number_format($sa->current_balance, 0, ',', '.') }})</option>
                    @endforeach
                </select>
                <span class="text-[10px] text-slate-500">Saldo rekening bank yang terpotong untuk transfer</span>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Transfer Ke Akun Penerima (Debet) *</label>
                <select name="debit_account_id" required class="w-full px-3 py-1.5 border border-slate-300 rounded font-bold text-emerald-900">
                    @foreach($destAccounts as $da)
                        <option value="{{ $da->id }}" {{ $da->code === '1-1111' ? 'selected' : '' }}>{{ $da->name }} (CASH TRANSFER)</option>
                    @endforeach
                </select>
                <span class="text-[10px] text-slate-500">Uang tunai kasir yang diterima dari pelanggan (Nominal + Admin)</span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Nominal Transfer (Rp) *</label>
                    <input type="number" step="1000" id="transferNominal" name="amount" required placeholder="Contoh: 100000" oninput="calculateTransferTotal()" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono font-bold text-slate-900 text-right">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Biaya Admin / Fee (Rp)</label>
                    <input type="number" step="500" id="transferAdminFee" name="admin_fee" value="5000" oninput="calculateTransferTotal()" class="w-full px-3 py-1.5 border border-emerald-500 rounded font-mono font-bold text-emerald-800 text-right bg-slate-50">
                </div>
            </div>

            <!-- Total Diterima Kasir -->
            <div class="p-2.5 rounded bg-slate-50 border border-slate-200 flex justify-between items-center text-xs">
                <span class="font-bold text-slate-800">Total Tagihan Tunai Ke Pelanggan:</span>
                <span id="transferTotalCash" class="font-mono font-extrabold text-base text-emerald-900">Rp 0</span>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Keterangan / Data Rekening Penerima *</label>
                <input type="text" name="notes" required placeholder="Contoh: BCA 0661201111 AGUS SEDIH" class="w-full px-3 py-1.5 border border-slate-300 rounded">
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalAddTransfer').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">PROSES TRANSFER</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kas Transfer -->
<div id="modalEditTransfer" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Edit / Koreksi Kas Transfer</span>
            </h3>
            <button onclick="document.getElementById('modalEditTransfer').classList.add('hidden')" class="text-white hover:text-white/80 text-xl font-bold">&times;</button>
        </div>

        <form id="formEditTransfer" method="POST" class="p-5 space-y-3 text-xs">
            @csrf
            @method('PUT')

            <div>
                <label class="font-bold text-slate-700 block mb-1">Tanggal Transaksi *</label>
                <input type="date" name="date" id="edit_kt_date" required class="w-full px-3 py-1.5 border border-slate-300 rounded font-medium">
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Dari Akun Sumber Dana (Kredit) *</label>
                <select name="credit_account_id" id="edit_kt_credit" required class="w-full px-3 py-1.5 border border-slate-300 rounded">
                    @foreach($sourceAccounts as $sa)
                        <option value="{{ $sa->id }}">{{ $sa->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Transfer Ke Akun Penerima (Debet) *</label>
                <select name="debit_account_id" id="edit_kt_debit" required class="w-full px-3 py-1.5 border border-slate-300 rounded font-bold text-emerald-900">
                    @foreach($destAccounts as $da)
                        <option value="{{ $da->id }}">{{ $da->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Nominal Transfer (Rp) *</label>
                    <input type="number" step="1000" id="edit_kt_amount" name="amount" required oninput="calculateEditTransferTotal()" class="w-full px-3 py-1.5 border border-slate-300 rounded font-mono font-bold text-slate-900 text-right">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Biaya Admin / Fee (Rp)</label>
                    <input type="number" step="500" id="edit_kt_fee" name="admin_fee" oninput="calculateEditTransferTotal()" class="w-full px-3 py-1.5 border border-emerald-500 rounded font-mono font-bold text-emerald-800 text-right bg-slate-50">
                </div>
            </div>

            <!-- Total Diterima Kasir -->
            <div class="p-2.5 rounded bg-slate-50 border border-slate-200 flex justify-between items-center text-xs">
                <span class="font-bold text-slate-800">Total Tagihan Kasir:</span>
                <span id="edit_kt_total" class="font-mono font-extrabold text-base text-emerald-900">Rp 0</span>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Keterangan / Rekening Tujuan *</label>
                <input type="text" name="notes" id="edit_kt_notes" required class="w-full px-3 py-1.5 border border-slate-300 rounded">
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalEditTransfer').classList.add('hidden')" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 font-bold">Batal</button>
                <button type="submit" class="btn-retro btn-save">SIMPAN KOREKSI</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function calculateTransferTotal() {
        const nom = parseFloat(document.getElementById('transferNominal').value || 0);
        const fee = parseFloat(document.getElementById('transferAdminFee').value || 0);
        const total = nom + fee;
        document.getElementById('transferTotalCash').innerText = 'Rp ' + total.toLocaleString('id-ID');
    }

    function calculateEditTransferTotal() {
        const nom = parseFloat(document.getElementById('edit_kt_amount').value || 0);
        const fee = parseFloat(document.getElementById('edit_kt_fee').value || 0);
        const total = nom + fee;
        document.getElementById('edit_kt_total').innerText = 'Rp ' + total.toLocaleString('id-ID');
    }

    function editCashTransfer(data) {
        const form = document.getElementById('formEditTransfer');
        form.action = "{{ url('/accounting/cash-transfer') }}/" + data.id;
        const dateVal = data.date ? data.date.substring(0, 10) : new Date().toISOString().substring(0, 10);
        document.getElementById('edit_kt_date').value = dateVal;
        document.getElementById('edit_kt_credit').value = data.credit_account_id || '';
        document.getElementById('edit_kt_debit').value = data.debit_account_id || '';
        document.getElementById('edit_kt_amount').value = data.amount || 0;
        document.getElementById('edit_kt_fee').value = data.admin_fee || 0;
        document.getElementById('edit_kt_notes').value = data.notes || '';
        calculateEditTransferTotal();
        document.getElementById('modalEditTransfer').classList.remove('hidden');
    }
</script>
@endpush
