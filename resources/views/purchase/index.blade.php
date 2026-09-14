@extends('layouts.app')

@section('title', 'Daftar Pembelian')

@section('content')
<div class="space-y-4">
    
    <!-- Header Controls & Warning -->
    <div class="bg-white rounded-lg p-3.5 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span>INPUT PEMBELIAN & LOG HISTORI KULAKAN (MOVING AVERAGE HPP)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Sistem otomatis menghitung Moving Average HPP dan mencatat jejak audit stok before & after.</p>
        </div>

        <div class="bg-slate-50 border border-slate-200 px-3 py-1.5 rounded text-[11px] text-slate-800 flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span><strong>RUMUS HPP:</strong> ((Stok Lama * HPP Lama) + (Qty Beli * Harga Beli)) / (Stok Lama + Qty Beli)</span>
        </div>
    </div>

    <!-- DUAL PANE LAYOUT (Kiri: Form Input Pembelian | Kanan: History Log Tabel) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- SISI KIRI: FORM INPUT PEMBELIAN (Cols 5) -->
        <div class="lg:col-span-5 bg-white rounded-lg border-2 border-[#14421b] shadow-md overflow-hidden flex flex-col">
            <div class="bg-[#14421b] text-white px-4 py-2.5 flex items-center justify-between">
                <h3 class="font-bold text-xs uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Input Pembelian Baru</span>
                </h3>
                <span class="text-[10px] text-white/80 font-mono font-bold">Auto Numbering PB-</span>
            </div>

            <form action="{{ route('purchase.store') }}" method="POST" id="purchaseForm" class="p-4 space-y-3.5 text-xs flex-1 flex flex-col justify-between">
                @csrf
                
                <div class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Tanggal Transaksi *</label>
                            <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full px-2.5 py-1.5 border border-slate-300 rounded font-medium">
                        </div>
                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Supplier *</label>
                            <select name="supplier_id" required class="w-full px-2.5 py-1.5 border border-slate-300 rounded font-medium">
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Items Container -->
                    <div class="border border-slate-200 rounded-lg p-3 bg-slate-50 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800">Daftar Barang Kulakan</span>
                            <button type="button" onclick="addPurchaseRow()" class="text-[11px] font-bold text-emerald-700 hover:text-emerald-900 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Tambah Baris</span>
                            </button>
                        </div>

                        <div id="purchaseItemsContainer" class="space-y-2">
                            <!-- Row 0 -->
                            <div class="purchase-row grid grid-cols-12 gap-1.5 items-center p-2 rounded bg-white border border-slate-200">
                                <div class="col-span-6">
                                    <select name="items[0][product_id]" required onchange="handleProductSelect(this, 0)" class="w-full px-2 py-1 border border-slate-300 rounded text-xs font-semibold">
                                        <option value="">-- Pilih Barang --</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-hpp="{{ $p->hpp }}" data-stock="{{ $p->stock }}">{{ $p->item_code }} - {{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <input type="number" step="1" name="items[0][qty]" value="1" min="1" required placeholder="Qty" oninput="calculateRow(0)" class="row-qty w-full px-2 py-1 border border-slate-300 rounded text-xs text-center font-mono">
                                </div>
                                <div class="col-span-3">
                                    <input type="number" step="1" name="items[0][buy_price]" value="0" min="0" required placeholder="Harga Beli" oninput="calculateRow(0)" class="row-price w-full px-2 py-1 border border-slate-300 rounded text-xs text-right font-mono font-bold">
                                </div>
                                <div class="col-span-1 text-center">
                                    <button type="button" onclick="removePurchaseRow(this)" class="text-rose-500 font-bold hover:text-rose-700">&times;</button>
                                </div>
                                <div class="col-span-12 flex justify-between text-[10px] text-slate-500 px-1 pt-1 border-t border-slate-100">
                                    <span class="row-stock-info">Stok: 0 | HPP Lama: Rp 0</span>
                                    <span class="row-subtotal-info font-bold text-slate-800">Subtotal: Rp 0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Terms & Accounts -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Metode Pembayaran</label>
                            <select name="payment_method" id="paymentMethod" onchange="togglePaymentInputs()" class="w-full px-2.5 py-1.5 border border-slate-300 rounded">
                                <option value="Tunai">Tunai (Lunas Kasir)</option>
                                <option value="Transfer">Transfer Bank</option>
                                <option value="Hutang">Hutang (Tempo)</option>
                            </select>
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Potongan / Diskon (Rp)</label>
                            <input type="number" step="1" name="discount" id="purchaseDiscount" value="0" oninput="calculateGrandTotal()" class="w-full px-2.5 py-1.5 border border-slate-300 rounded font-mono text-right">
                        </div>
                    </div>

                    <div id="accountField">
                        <label class="font-bold text-slate-700 block mb-1">Akun Kas/Bank Pembayaran</label>
                        <select name="account_id" class="w-full px-2.5 py-1.5 border border-slate-300 rounded">
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ $acc->code === '1-1110' ? 'selected' : '' }}>{{ $acc->code }} - {{ $acc->name }} (Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Jumlah Dibayar Sekarang (Rp)</label>
                        <input type="number" step="1" name="paid_amount" id="purchasePaidAmount" value="0" oninput="calculateGrandTotal()" class="w-full px-2.5 py-1.5 border border-slate-300 rounded font-mono text-right font-bold text-sm text-emerald-800">
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Catatan Kulakan</label>
                        <input type="text" name="notes" placeholder="Contoh: Kulakan Sales Telkomsel / Grosir" class="w-full px-2.5 py-1.5 border border-slate-300 rounded">
                    </div>
                </div>

                <!-- Grand Total Box & Save Button -->
                <div class="pt-3 border-t-2 border-slate-200 mt-2 space-y-2.5">
                    <div class="bg-slate-50 p-2.5 rounded border border-slate-200 flex justify-between items-center text-xs">
                        <span class="font-bold text-slate-800">TOTAL PEMBELIAN:</span>
                        <span id="labelGrandTotal" class="font-mono font-extrabold text-base text-emerald-900">Rp 0</span>
                    </div>

                    <div class="flex justify-between items-center text-xs px-1 text-slate-600">
                        <span>Sisa Hutang: <strong id="labelSisaHutang" class="text-rose-600 font-mono">Rp 0</strong></span>
                    </div>

                    <button type="submit" class="w-full btn-retro btn-save py-2 text-sm justify-center flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        <span>SIMPAN PEMBELIAN</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- SISI KANAN: TABEL HISTORY LOG PEMBELIAN (Cols 7) -->
        <div class="lg:col-span-7 bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="bg-[#133e1c] text-white px-4 py-2.5 flex items-center justify-between">
                <h3 class="font-bold text-xs uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Log Riwayat Transaksi Pembelian & Audit Stok</span>
                </h3>
                <span class="text-[10px] text-green-200">Total: {{ $history->total() }} Nota</span>
            </div>

            <x-table-toolbar tableId="purchasesTable" excelName="Rekap_Pembelian" placeholder="Cari invoice, supplier..." />

            <div class="overflow-x-auto flex-1">
                <table id="purchasesTable" class="excel-table">
                    <thead>
                        <tr>
                            <th>NO TRANSAKSI</th>
                            <th>TANGGAL</th>
                            <th>SUPPLIER</th>
                            <th>BARANG</th>
                            <th class="text-center">QTY</th>
                            <th class="text-right">HARGA BELI</th>
                            <th class="text-center">STOK BEFORE &rarr; AFTER</th>
                            <th class="text-right">HPP BEFORE &rarr; AFTER</th>
                            <th class="text-right">SUBTOTAL</th>
                            <th class="text-center">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $p)
                            @foreach($p->items as $item)
                                <tr>
                                    <td class="font-mono font-bold text-xs text-emerald-900">{{ $p->invoice_number }}</td>
                                    <td class="whitespace-nowrap text-slate-600">{{ $p->date->format('d/m/Y') }}</td>
                                    <td class="font-semibold">{{ $p->supplier->name ?? '-' }}</td>
                                    <td class="font-medium text-slate-800">{{ $item->product->name ?? '-' }}</td>
                                    <td class="text-center font-bold font-mono">{{ (float)$item->qty }}</td>
                                    <td class="text-right font-mono text-slate-700">Rp {{ number_format($item->buy_price, 0, ',', '.') }}</td>
                                    <td class="text-center font-mono text-[11px] whitespace-nowrap">
                                        <span class="text-slate-500">{{ (float)$item->stock_before }}</span>
                                        <span class="text-emerald-700 font-bold">&rarr; {{ (float)$item->stock_after }}</span>
                                    </td>
                                    <td class="text-right font-mono text-[11px] whitespace-nowrap">
                                        <span class="text-slate-500">{{ number_format($item->hpp_before, 0) }}</span>
                                        <span class="text-slate-500 font-bold">&rarr; {{ number_format($item->hpp_after, 0) }}</span>
                                    </td>
                                    <td class="text-right font-mono font-bold text-slate-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $p->status === 'LUNAS' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                            {{ $p->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-8 text-slate-400">Belum ada riwayat pembelian barang.</td>
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

</div>
@endsection

@push('scripts')
<script>
    let rowIndex = 1;
    const productsData = @json($products);

    function addPurchaseRow() {
        const container = document.getElementById('purchaseItemsContainer');
        const div = document.createElement('div');
        div.className = 'purchase-row grid grid-cols-12 gap-1.5 items-center p-2 rounded bg-white border border-slate-200';
        
        let options = '<option value="">-- Pilih Barang --</option>';
        productsData.forEach(p => {
            options += `<option value="${p.id}" data-hpp="${p.hpp}" data-stock="${p.stock}">${p.item_code} - ${p.name}</option>`;
        });

        div.innerHTML = `
            <div class="col-span-6">
                <select name="items[${rowIndex}][product_id]" required onchange="handleProductSelect(this, ${rowIndex})" class="w-full px-2 py-1 border border-slate-300 rounded text-xs font-semibold">
                    ${options}
                </select>
            </div>
            <div class="col-span-2">
                <input type="number" step="1" name="items[${rowIndex}][qty]" value="1" min="1" required placeholder="Qty" oninput="calculateRow(${rowIndex})" class="row-qty w-full px-2 py-1 border border-slate-300 rounded text-xs text-center font-mono">
            </div>
            <div class="col-span-3">
                <input type="number" step="1" name="items[${rowIndex}][buy_price]" value="0" min="0" required placeholder="Harga Beli" oninput="calculateRow(${rowIndex})" class="row-price w-full px-2 py-1 border border-slate-300 rounded text-xs text-right font-mono font-bold">
            </div>
            <div class="col-span-1 text-center">
                <button type="button" onclick="removePurchaseRow(this)" class="text-rose-500 font-bold hover:text-rose-700">&times;</button>
            </div>
            <div class="col-span-12 flex justify-between text-[10px] text-slate-500 px-1 pt-1 border-t border-slate-100">
                <span class="row-stock-info">Stok: 0 | HPP Lama: Rp 0</span>
                <span class="row-subtotal-info font-bold text-slate-800">Subtotal: Rp 0</span>
            </div>
        `;
        container.appendChild(div);
        rowIndex++;
    }

    function removePurchaseRow(btn) {
        const rows = document.querySelectorAll('.purchase-row');
        if (rows.length > 1) {
            btn.closest('.purchase-row').remove();
            calculateGrandTotal();
        } else {
            alert('Minimal harus ada 1 barang dalam pembelian!');
        }
    }

    function handleProductSelect(select, idx) {
        const opt = select.options[select.selectedIndex];
        const row = select.closest('.purchase-row');
        if (opt && opt.value) {
            const hpp = parseFloat(opt.dataset.hpp || 0);
            const stock = parseFloat(opt.dataset.stock || 0);
            row.querySelector('.row-price').value = hpp;
            row.querySelector('.row-stock-info').innerText = `Stok: ${stock} | HPP Lama: Rp ${hpp.toLocaleString('id-ID')}`;
            calculateRow(idx);
        }
    }

    function calculateRow(idx) {
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let subtotal = 0;
        document.querySelectorAll('.purchase-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.row-qty').value || 0);
            const price = parseFloat(row.querySelector('.row-price').value || 0);
            const lineSubtotal = qty * price;
            subtotal += lineSubtotal;
            row.querySelector('.row-subtotal-info').innerText = `Subtotal: Rp ${lineSubtotal.toLocaleString('id-ID')}`;
        });

        const discount = parseFloat(document.getElementById('purchaseDiscount').value || 0);
        const grandTotal = Math.max(0, subtotal - discount);

        document.getElementById('labelGrandTotal').innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');

        const method = document.getElementById('paymentMethod').value;
        const paidInput = document.getElementById('purchasePaidAmount');

        if (method === 'Tunai' || method === 'Transfer') {
            paidInput.value = grandTotal;
        }

        const paid = parseFloat(paidInput.value || 0);
        const sisaHutang = Math.max(0, grandTotal - paid);
        document.getElementById('labelSisaHutang').innerText = 'Rp ' + sisaHutang.toLocaleString('id-ID');
    }

    function togglePaymentInputs() {
        const method = document.getElementById('paymentMethod').value;
        const paidInput = document.getElementById('purchasePaidAmount');
        const accField = document.getElementById('accountField');

        if (method === 'Hutang') {
            paidInput.value = 0;
            accField.classList.add('hidden');
        } else {
            accField.classList.remove('hidden');
            calculateGrandTotal();
        }
        calculateGrandTotal();
    }
</script>
@endpush
