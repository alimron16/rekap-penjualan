@extends('layouts.app')

@section('title', 'Kasir Penjualan Grosir')

@section('content')
<div class="space-y-4">
    
    <!-- Top Bar & Active Customer -->
    <div class="bg-white rounded-xl p-3 sm:p-4 border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div>
                <h2 class="text-xs sm:text-sm font-bold text-slate-800 uppercase tracking-wide">
                    Kasir Penjualan Grosir
                </h2>
                <p class="text-[11px] text-slate-500">Transaksi partai besar dengan harga grosir, cetak struk thermal atau faktur surat jalan A4/A5.</p>
            </div>
        </div>

        <div class="flex items-center gap-2 self-end md:self-auto w-full md:w-auto flex-wrap">
            <button type="button" onclick="openExpenseModal()" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs flex items-center gap-1.5 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Kas Keluar Toko</span>
            </button>
            <button type="button" onclick="openWithdrawModal()" class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-xs flex items-center gap-1.5 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Tarik Tunai</span>
            </button>
            <button type="button" onclick="openShiftModal()" class="px-3 py-1.5 rounded-lg bg-[#133e1c] hover:bg-[#0d2c14] text-white font-bold text-xs shadow-xs flex items-center gap-1.5 transition border border-emerald-800">
                <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Tutup Shift & Setor</span>
            </button>
            <span class="text-xs font-semibold text-slate-600 whitespace-nowrap">Pelanggan Grosir:</span>
            <select id="posCustomerSelect" class="text-xs px-3 py-1.5 border border-slate-300 rounded-lg font-semibold bg-white w-full md:w-56 focus:ring-1 focus:ring-emerald-600 focus:outline-none">
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ $c->name === 'UMUM' ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Mobile Viewport Switcher Tabs (Only visible on screens < 1024px) -->
    <div class="lg:hidden flex bg-slate-200/80 p-1 rounded-lg gap-1 border border-slate-200">
        <button type="button" id="tabBtnCatalog" onclick="switchMobileTab('catalog')" class="flex-1 py-2 text-xs font-bold rounded-md bg-emerald-700 text-white flex items-center justify-center gap-1.5 shadow-xs transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <span>Katalog Grosir</span>
        </button>
        <button type="button" id="tabBtnCart" onclick="switchMobileTab('cart')" class="flex-1 py-2 text-xs font-bold rounded-md bg-white text-slate-700 hover:bg-slate-50 flex items-center justify-center gap-1.5 border border-slate-200 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Nota Grosir</span>
            <span id="mobileCartBadge" class="bg-emerald-800 text-white px-1.5 py-0.2 rounded-full text-[10px] font-extrabold ml-1">0</span>
        </button>
    </div>

    <!-- TOP SECTION: WORKSTATION KASIR GROSIR (LEGA & RESPONSIF) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        
        <!-- SISI KIRI: PENCARIAN & KATALOG PRODUK GROSIR (Cols 7) -->
        <div id="panelCatalog" class="lg:col-span-7 bg-white rounded-xl border border-slate-200 shadow-xs flex flex-col overflow-hidden">
            <div class="bg-[#133e1c] text-white px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <h3 class="font-bold text-xs uppercase tracking-wider text-white">Katalog & Pencarian Barang Grosir</h3>
                </div>
                <span class="text-[11px] text-emerald-200 font-mono font-bold">{{ $products->count() }} Produk Tersedia</span>
            </div>

            <div class="p-3 sm:p-4 bg-slate-50 border-b border-slate-200 space-y-3">
                <div class="relative">
                    <input type="text" id="posSearchInput" placeholder="Ketik nama atau kode barang grosir..." class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-xs sm:text-sm focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 bg-white font-medium shadow-xs" autofocus>
                    <div class="absolute left-3.5 top-3 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                    <span class="text-[11px] font-semibold text-slate-500 mr-1">Filter:</span>
                    <button type="button" onclick="filterCatalog('ALL')" class="pill-btn px-3 py-1.5 rounded-lg bg-emerald-700 text-white font-semibold text-xs shadow-xs">SEMUA</button>
                    <button type="button" onclick="filterCatalog('VOCER')" class="pill-btn px-3 py-1.5 rounded-lg bg-white text-slate-600 hover:bg-slate-100 font-medium border border-slate-200 text-xs">VOCER</button>
                    <button type="button" onclick="filterCatalog('PERDANA')" class="pill-btn px-3 py-1.5 rounded-lg bg-white text-slate-600 hover:bg-slate-100 font-medium border border-slate-200 text-xs">PERDANA</button>
                    <button type="button" onclick="filterCatalog('ACC')" class="pill-btn px-3 py-1.5 rounded-lg bg-white text-slate-600 hover:bg-slate-100 font-medium border border-slate-200 text-xs">ACC</button>
                </div>

                <div id="quickCatalogBox" class="max-h-[460px] overflow-y-auto grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2 sm:gap-2.5 pt-1 pr-1">
                    @foreach($products as $prod)
                        <div onclick="addToCart({{ $prod->id }})" data-type="{{ $prod->type }}" data-code="{{ strtolower($prod->item_code) }}" data-name="{{ strtolower($prod->name) }}" class="catalog-item bg-white p-3 rounded-lg border border-slate-200 hover:border-emerald-600 hover:shadow-xs cursor-pointer transition select-none flex flex-col justify-between group">
                            <div>
                                <span class="font-mono font-bold text-[11px] text-emerald-700 block">{{ $prod->item_code }}</span>
                                <span class="text-xs font-semibold text-slate-800 line-clamp-2 leading-snug mt-1">{{ $prod->name }}</span>
                            </div>
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 text-[11px]">
                                <span class="text-slate-500">Stok: <strong class="font-mono {{ $prod->stock <= 0 ? 'text-rose-600' : 'text-slate-700' }}">{{ (float)$prod->stock }}</strong></span>
                                <span class="font-mono font-extrabold text-slate-900 text-xs">Rp {{ number_format($prod->wholesale_price, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- SISI KANAN: KERANJANG GROSIR & PEMBAYARAN (Cols 5) -->
        <div id="panelCart" class="hidden lg:flex lg:col-span-5 bg-white rounded-xl border border-slate-200 shadow-xs flex-col overflow-hidden">
            <div class="bg-[#133e1c] text-white px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <h3 class="font-bold text-xs uppercase tracking-wider text-white">Keranjang Penjualan Grosir</h3>
                </div>
                <span class="text-[10px] text-emerald-200 font-mono font-bold">Auto ID: PG-{{ date('Ymd-His') }}</span>
            </div>

            <!-- Cart Table Items -->
            <div class="flex-1 overflow-y-auto max-h-[290px] p-2">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 sticky top-0">
                        <tr>
                            <th class="py-2 px-2 text-left">PRODUK</th>
                            <th class="py-2 px-2 text-center w-20">QTY</th>
                            <th class="py-2 px-2 text-right w-20">HRG GROSIR</th>
                            <th class="py-2 px-2 text-right w-24">TOTAL</th>
                            <th class="py-2 px-1 text-center w-6"></th>
                        </tr>
                    </thead>
                    <tbody id="cartTableBody" class="divide-y divide-slate-100">
                    </tbody>
                </table>
                <div id="cartEmptyNotice" class="text-center py-12 text-slate-400 text-xs italic">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    Keranjang kasir grosir masih kosong.<br>Klik produk di sebelah kiri untuk memasukkan ke nota.
                </div>
            </div>

            <!-- Summary & Controls -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 space-y-3">
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal Grosir:</span>
                        <span id="posSubtotalLabel" class="font-mono font-bold text-slate-800">Rp 0</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Potongan Diskon Nota (Rp):</span>
                        <input type="number" step="1000" id="posDiscountInput" value="0" min="0" oninput="renderCartSummary()" class="w-28 px-2.5 py-1 border border-slate-300 rounded-lg text-right font-mono text-xs bg-white">
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-slate-200">
                        <span class="font-bold text-xs uppercase tracking-wider text-slate-700">TOTAL NOTA GROSIR:</span>
                        <span id="posGrandTotalLabel" class="font-mono font-extrabold text-xl text-emerald-800">Rp 0</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Jumlah Bayar / DP (Rp)</label>
                        <input type="number" step="1000" id="posPaidInput" value="0" oninput="calculateChange()" class="w-full px-2.5 py-1.5 border border-slate-300 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 rounded-lg font-mono font-bold text-sm text-slate-900 bg-white">
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Sisa Piutang / Kembali</label>
                        <div id="posChangeLabel" class="px-2.5 py-1.5 border border-slate-200 rounded-lg font-mono font-bold text-sm text-slate-800 bg-white flex items-center justify-between">
                            <span class="text-xs text-slate-500">Status:</span>
                            <span id="posChangeVal" class="text-emerald-700 font-extrabold">LUNAS</span>
                        </div>
                    </div>
                </div>

                <!-- Tombol Cetak Struk Saja -->
                <div class="flex items-center gap-2 pt-1">
                    <button type="button" onclick="submitCheckout('thermal')" class="flex-1 btn-retro btn-save py-2.5 text-xs sm:text-sm justify-center rounded-lg shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>SIMPAN & CETAK STRUK (F9)</span>
                    </button>
                    <button type="button" onclick="clearCart()" class="px-3.5 py-2.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg font-semibold text-xs transition">
                        RESET (F4)
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- BOTTOM SECTION: LOG RIWAYAT GROSIR (FULL WIDTH 12 COLS) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden mt-5">
        <div class="bg-[#133e1c] text-white px-4 py-3 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider flex items-center gap-2 text-white">
                <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span>Riwayat Transaksi Grosir Hari Ini & Audit Jejak Stok</span>
            </h3>
            <span class="text-[11px] text-emerald-200 font-bold font-mono">{{ $recentSales->count() }} Transaksi</span>
        </div>

        <div class="overflow-x-auto">
            <table class="excel-table">
                <thead>
                    <tr>
                        <th class="text-left">NO TRANSAKSI</th>
                        <th class="text-left">WAKTU</th>
                        <th class="text-left">PELANGGAN</th>
                        <th class="text-left">NAMA BARANG</th>
                        <th class="text-center">QTY</th>
                        <th class="text-right">HARGA GROSIR</th>
                        <th class="text-right">SUBTOTAL</th>
                        <th class="text-center">AUDIT STOK</th>
                        <th class="text-center">CETAK</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSales as $sale)
                        @foreach($sale->items as $item)
                            <tr>
                                <td class="font-mono font-bold text-xs text-emerald-800">{{ $sale->invoice_number }}</td>
                                <td class="whitespace-nowrap text-[11px] text-slate-500 font-mono">{{ $sale->date->format('d/m/Y H:i') }}</td>
                                <td class="font-medium text-slate-800">{{ $sale->customer->name ?? 'UMUM' }}</td>
                                <td class="font-medium text-slate-800">{{ $item->product->name ?? '-' }}</td>
                                <td class="text-center font-mono font-bold text-slate-800">{{ (float)$item->qty }}</td>
                                <td class="text-right font-mono text-slate-600">Rp {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                                <td class="text-right font-mono font-bold text-slate-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                <td class="text-center font-mono text-[11px] whitespace-nowrap">
                                    <span class="text-slate-500">{{ (float)$item->stock_before }}</span>
                                    <span class="text-emerald-600 font-bold mx-1">→</span>
                                    <span class="text-emerald-700 font-extrabold">{{ (float)$item->stock_after }}</span>
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    <a href="{{ route('receipt.thermal', $sale->id) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold border border-slate-200">
                                        <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        <span>Thermal</span>
                                    </a>
                                    <a href="{{ route('receipt.invoice', $sale->id) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-semibold border border-emerald-200 ml-1">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>A4</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-10 text-slate-400">Belum ada transaksi penjualan grosir.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    const productsCatalog = @json($products);
    let cart = [];

    // Mobile Tab Switcher
    function switchMobileTab(tab) {
        const panelCatalog = document.getElementById('panelCatalog');
        const panelCart = document.getElementById('panelCart');
        const tabBtnCatalog = document.getElementById('tabBtnCatalog');
        const tabBtnCart = document.getElementById('tabBtnCart');

        if (tab === 'catalog') {
            panelCatalog.classList.remove('hidden');
            panelCart.classList.add('hidden');
            panelCart.classList.remove('flex');
            tabBtnCatalog.className = 'flex-1 py-2 text-xs font-bold rounded-md bg-emerald-700 text-white flex items-center justify-center gap-1.5 shadow-xs transition';
            tabBtnCart.className = 'flex-1 py-2 text-xs font-bold rounded-md bg-white text-slate-700 hover:bg-slate-50 flex items-center justify-center gap-1.5 border border-slate-200 transition';
        } else {
            panelCatalog.classList.add('hidden');
            panelCart.classList.remove('hidden');
            panelCart.classList.add('flex');
            tabBtnCatalog.className = 'flex-1 py-2 text-xs font-bold rounded-md bg-white text-slate-700 hover:bg-slate-50 flex items-center justify-center gap-1.5 border border-slate-200 transition';
            tabBtnCart.className = 'flex-1 py-2 text-xs font-bold rounded-md bg-emerald-700 text-white flex items-center justify-center gap-1.5 shadow-xs transition';
        }
    }

    function filterCatalog(type) {
        document.querySelectorAll('.pill-btn').forEach(b => {
            b.className = 'pill-btn px-3 py-1.5 rounded-lg bg-white text-slate-600 hover:bg-slate-100 font-medium border border-slate-200 text-xs';
        });
        event.target.className = 'pill-btn px-3 py-1.5 rounded-lg bg-emerald-700 text-white font-semibold text-xs shadow-xs';

        document.querySelectorAll('.catalog-item').forEach(item => {
            if (type === 'ALL' || item.dataset.type === type) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
    }

    const searchInput = document.getElementById('posSearchInput');
    searchInput.addEventListener('input', function() {
        const val = this.value.toLowerCase().trim();
        document.querySelectorAll('.catalog-item').forEach(item => {
            const match = item.dataset.code.includes(val) || item.dataset.name.includes(val);
            if (match) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const visibleItems = Array.from(document.querySelectorAll('.catalog-item:not(.hidden)'));
            if (visibleItems.length > 0) {
                visibleItems[0].click();
                this.value = '';
                document.querySelectorAll('.catalog-item').forEach(i => i.classList.remove('hidden'));
            }
        }
    });

    function addToCart(productId) {
        const prod = productsCatalog.find(p => p.id === productId);
        if (!prod) return;

        const existing = cart.find(c => c.product_id === productId);
        if (existing) {
            existing.qty += 1;
        } else {
            cart.push({
                product_id: prod.id,
                code: prod.item_code,
                name: prod.name,
                price: parseFloat(prod.wholesale_price || prod.retail_price),
                qty: 1,
                stock: parseFloat(prod.stock)
            });
        }
        renderCart();
    }

    function updateCartQty(productId, delta) {
        const item = cart.find(c => c.product_id === productId);
        if (!item) return;
        item.qty += delta;
        if (item.qty <= 0) {
            cart = cart.filter(c => c.product_id !== productId);
        }
        renderCart();
    }

    function removeCartItem(productId) {
        cart = cart.filter(c => c.product_id !== productId);
        renderCart();
    }

    function clearCart() {
        if (confirm('Kosongkan keranjang belanja kasir grosir?')) {
            cart = [];
            renderCart();
        }
    }

    function renderCart() {
        const tbody = document.getElementById('cartTableBody');
        const emptyNotice = document.getElementById('cartEmptyNotice');
        tbody.innerHTML = '';

        const totalItemsCount = cart.reduce((sum, item) => sum + item.qty, 0);
        document.getElementById('mobileCartBadge').innerText = totalItemsCount;

        if (cart.length === 0) {
            emptyNotice.classList.remove('hidden');
        } else {
            emptyNotice.classList.add('hidden');
            cart.forEach(item => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50/50';
                tr.innerHTML = `
                    <td class="py-2 px-2">
                        <span class="font-mono font-bold text-[10px] text-emerald-800">${item.code}</span>
                        <div class="font-semibold text-slate-800 line-clamp-1">${item.name}</div>
                    </td>
                    <td class="py-2 px-2 text-center whitespace-nowrap">
                        <button type="button" onclick="updateCartQty(${item.product_id}, -1)" class="w-5 h-5 rounded bg-slate-200 text-slate-800 font-bold hover:bg-slate-300">-</button>
                        <span class="font-mono font-bold mx-1.5">${item.qty}</span>
                        <button type="button" onclick="updateCartQty(${item.product_id}, 1)" class="w-5 h-5 rounded bg-slate-200 text-slate-800 font-bold hover:bg-slate-300">+</button>
                    </td>
                    <td class="py-2 px-2 text-right font-mono">Rp ${item.price.toLocaleString('id-ID')}</td>
                    <td class="py-2 px-2 text-right font-mono font-bold text-slate-900">Rp ${(item.qty * item.price).toLocaleString('id-ID')}</td>
                    <td class="py-2 px-1 text-center">
                        <button type="button" onclick="removeCartItem(${item.product_id})" class="text-rose-500 hover:text-rose-700 font-bold text-base p-1">&times;</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        renderCartSummary();
    }

    function renderCartSummary() {
        let subtotal = 0;
        cart.forEach(c => subtotal += (c.qty * c.price));

        const discount = parseFloat(document.getElementById('posDiscountInput').value || 0);
        const grandTotal = Math.max(0, subtotal - discount);

        document.getElementById('posSubtotalLabel').innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
        document.getElementById('posGrandTotalLabel').innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');

        const paidInput = document.getElementById('posPaidInput');
        if (parseFloat(paidInput.value || 0) === 0 || parseFloat(paidInput.value || 0) < grandTotal) {
            paidInput.value = grandTotal;
        }

        calculateChange();
    }

    function calculateChange() {
        let subtotal = 0;
        cart.forEach(c => subtotal += (c.qty * c.price));
        const discount = parseFloat(document.getElementById('posDiscountInput').value || 0);
        const grandTotal = Math.max(0, subtotal - discount);

        const paid = parseFloat(document.getElementById('posPaidInput').value || 0);
        const diff = paid - grandTotal;

        const valSpan = document.getElementById('posChangeVal');

        if (diff >= 0) {
            valSpan.className = 'text-emerald-700 font-mono font-bold';
            valSpan.innerText = diff === 0 ? 'LUNAS' : 'Kembali Rp ' + diff.toLocaleString('id-ID');
        } else {
            valSpan.className = 'text-rose-600 font-mono font-bold';
            valSpan.innerText = 'Sisa Piutang Rp ' + Math.abs(diff).toLocaleString('id-ID');
        }
    }

    function submitCheckout(format = 'thermal') {
        if (cart.length === 0) {
            alert('Pilih minimal 1 barang untuk nota grosir!');
            return;
        }

        const customerId = document.getElementById('posCustomerSelect').value;
        const discount = parseFloat(document.getElementById('posDiscountInput').value || 0);
        const paidAmount = parseFloat(document.getElementById('posPaidInput').value || 0);

        const payload = {
            sale_type: 'grosir',
            customer_id: customerId,
            discount: discount,
            paid_amount: paidAmount,
            payment_method: 'Tunai',
            items: cart.map(i => ({
                product_id: i.product_id,
                qty: i.qty,
                price: i.price
            }))
        };

        fetch("{{ route('pos.checkout') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const targetUrl = format === 'invoice' ? data.invoice_url : data.redirect_url;
                window.open(targetUrl, '_blank', 'width=800,height=700');
                window.location.reload();
            } else {
                alert('Gagal: ' + (data.message || 'Terjadi kesalahan'));
            }
        })
        .catch(err => {
            alert('Error: ' + err.message);
        });
    }

    // --- Modal Tarik Tunai di Kasir POS ---
    function openWithdrawModal() {
        document.getElementById('modalWithdrawPos').classList.remove('hidden');
    }
    function closeWithdrawModal() {
        document.getElementById('modalWithdrawPos').classList.add('hidden');
    }
    function submitWithdrawPos(e) {
        e.preventDefault();
        const sourceAccountId = document.getElementById('withdrawSourceAccount').value;
        const amount = parseFloat(document.getElementById('withdrawAmount').value || 0);
        const adminFee = parseFloat(document.getElementById('withdrawAdminFee').value || 0);
        const customerName = document.getElementById('withdrawCustomerName').value || 'Pelanggan';
        const notes = document.getElementById('withdrawNotes').value || '';

        if (amount < 1000) {
            alert('Nominal tarik tunai minimal Rp 1.000');
            return;
        }

        const btn = document.getElementById('btnSubmitWithdraw');
        btn.disabled = true;
        btn.innerText = 'Memproses...';

        fetch("{{ route('pos.withdraw') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                source_account_id: sourceAccountId,
                amount: amount,
                admin_fee: adminFee,
                customer_name: customerName,
                notes: notes
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = 'PROSES TARIK TUNAI';
            if (data.success) {
                alert(data.message);
                closeWithdrawModal();
                window.location.reload();
            } else {
                alert('Gagal: ' + (data.message || 'Terjadi kesalahan'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = 'PROSES TARIK TUNAI';
            alert('Error: ' + err.message);
        });
    }

    // --- Modal Kas Keluar Toko (Makan, Sampah, Operasional) ---
    function openExpenseModal() {
        document.getElementById('modalExpensePos').classList.remove('hidden');
    }
    function closeExpenseModal() {
        document.getElementById('modalExpensePos').classList.add('hidden');
    }
    function setQuickExpense(desc) {
        document.getElementById('expenseNotes').value = desc;
    }
    function submitExpensePos(e) {
        e.preventDefault();
        const sourceAccId = document.getElementById('expenseSourceAccount').value;
        const debitAccId = document.getElementById('expenseCategoryAccount').value;
        const amount = parseFloat(document.getElementById('expenseAmount').value || 0);
        const notes = document.getElementById('expenseNotes').value || 'Kas Keluar Toko';

        if (amount <= 0) {
            alert('Nominal kas keluar harus lebih dari 0');
            return;
        }

        const btn = document.getElementById('btnSubmitExpense');
        btn.disabled = true;
        btn.innerText = 'Menyimpan...';

        fetch("{{ route('accounting.cash_out.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                credit_account_id: sourceAccId,
                debit_account_id: debitAccId,
                amount: amount,
                notes: notes
            })
        })
        .then(res => {
            btn.disabled = false;
            btn.innerText = 'SIMPAN KAS KELUAR';
            alert('Pengeluaran kas berhasil dicatat!');
            closeExpenseModal();
            window.location.reload();
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = 'SIMPAN KAS KELUAR';
            alert('Error: ' + err.message);
        });
    }

    // --- Modal Tutup Shift & Setor Kasir ---
    function openShiftModal() {
        fetch("/api/pos/shift-summary?date={{ date('Y-m-d') }}", {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('shiftDrawerBalanceLabel').innerText = 'Rp ' + Number(data.cash_drawer_balance).toLocaleString('id-ID');
                document.getElementById('shiftRecommendedLabel').innerText = 'Rp ' + Number(data.recommended_deposit).toLocaleString('id-ID');
                document.getElementById('shiftDepositAmount').value = data.recommended_deposit > 0 ? data.recommended_deposit : 0;
            }
            document.getElementById('modalShiftPos').classList.remove('hidden');
        })
        .catch(() => {
            document.getElementById('modalShiftPos').classList.remove('hidden');
        });
    }
    function closeShiftModal() {
        document.getElementById('modalShiftPos').classList.add('hidden');
    }
    function submitShiftDeposit(e) {
        e.preventDefault();
        const amount = parseFloat(document.getElementById('shiftDepositAmount').value || 0);
        const notes = document.getElementById('shiftNotes').value || 'Tutup Shift Kasir';

        if (amount <= 0) {
            alert('Nominal setoran harus lebih dari 0');
            return;
        }

        if (!confirm(`Uang tunai sebesar Rp ${amount.toLocaleString('id-ID')} akan disetorkan ke brankas/pusat.\nSisa modal awal Rp 400.000 akan tetap di laci kasir.\n\nLanjutkan?`)) {
            return;
        }

        const btn = document.getElementById('btnSubmitShift');
        btn.disabled = true;
        btn.innerText = 'Memproses...';

        fetch("/api/pos/close-shift", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                deposit_amount: amount,
                notes: notes
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = 'SETOR UANG & TUTUP SHIFT';
            if (data.success) {
                alert(data.message);
                closeShiftModal();
                window.location.reload();
            } else {
                alert('Gagal: ' + (data.message || 'Terjadi kesalahan'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = 'SETOR UANG & TUTUP SHIFT';
            alert('Error: ' + err.message);
        });
    }
</script>

<!-- Modal Tarik Tunai di Kasir POS -->
<div id="modalWithdrawPos" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4 backdrop-blur-xs">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-amber-600 text-white px-5 py-3.5 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Tarik Tunai di Kasir (POS)</span>
            </h3>
            <button onclick="closeWithdrawModal()" class="text-white/80 hover:text-white text-xl font-bold">&times;</button>
        </div>

        <form onsubmit="submitWithdrawPos(event)" class="p-5 space-y-3.5 text-xs">
            <div>
                <label class="font-bold text-slate-700 block mb-1">Sumber Kas Uang Keluar (Diberikan ke Nasabah) *</label>
                <select id="withdrawSourceAccount" required class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ $acc->code === '1-1110' ? 'selected' : '' }}>
                            {{ $acc->code }} - {{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
                <span class="text-[10px] text-slate-400 mt-1 block">Pilih kas laci atau rekening yang uang fisiknya Anda keluarkan untuk nasabah.</span>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Nama Nasabah / Pelanggan *</label>
                <input type="text" id="withdrawCustomerName" placeholder="Contoh: Budi Santoso" required class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white text-slate-900 focus:ring-2 focus:ring-amber-500 focus:outline-none font-medium">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Nominal Tarik Tunai (Rp) *</label>
                    <input type="number" id="withdrawAmount" step="1000" min="1000" placeholder="500000" required class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold text-sm text-slate-900 bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Biaya Admin / Fee (Rp)</label>
                    <input type="number" id="withdrawAdminFee" step="500" min="0" value="5000" class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold text-sm text-emerald-700 bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Catatan Tambahan (Opsional)</label>
                <input type="text" id="withdrawNotes" placeholder="No referensi transfer / catatan bank" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white text-slate-800 focus:ring-2 focus:ring-amber-500 focus:outline-none">
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="closeWithdrawModal()" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 font-bold text-slate-700">Batal</button>
                <button type="submit" id="btnSubmitWithdraw" class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold shadow-xs">PROSES TARIK TUNAI</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Kas Keluar Operasional Toko (Makan, Sampah, dll) -->
<div id="modalExpensePos" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4 backdrop-blur-xs">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-rose-600 text-white px-5 py-3.5 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Catat Kas Keluar Toko (Operasional)</span>
            </h3>
            <button onclick="closeExpenseModal()" class="text-white/80 hover:text-white text-xl font-bold">&times;</button>
        </div>

        <form onsubmit="submitExpensePos(event)" class="p-5 space-y-3.5 text-xs">
            <div>
                <label class="font-bold text-slate-700 block mb-1">Pilihan Cepat Beban:</label>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" onclick="setQuickExpense('Uang makan siang kasir toko')" class="px-2.5 py-1 rounded-md bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-semibold text-[11px]">🍱 Uang Makan</button>
                    <button type="button" onclick="setQuickExpense('Iuran sampah & kebersihan toko')" class="px-2.5 py-1 rounded-md bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-semibold text-[11px]">🗑️ Sampah / Kebersihan</button>
                    <button type="button" onclick="setQuickExpense('Beli kantong plastik kresek')" class="px-2.5 py-1 rounded-md bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-semibold text-[11px]">🛍️ Kresek / ATK</button>
                    <button type="button" onclick="setQuickExpense('Beli token listrik toko')" class="px-2.5 py-1 rounded-md bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-semibold text-[11px]">💡 Token Listrik</button>
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Ambil Uang Dari (Kas Sumber) *</label>
                <select id="expenseSourceAccount" required class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white font-semibold focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ $acc->code === '1-1110' ? 'selected' : '' }}>
                            {{ $acc->code }} - {{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Kategori Beban / Pengeluaran *</label>
                <select id="expenseCategoryAccount" required class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white font-semibold focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    @foreach($expenseAccounts as $exp)
                        <option value="{{ $exp->id }}" {{ $exp->code === '6-2300' ? 'selected' : '' }}>
                            {{ $exp->code }} - {{ $exp->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Nominal Kas Keluar (Rp) *</label>
                <input type="number" id="expenseAmount" step="1000" min="1000" placeholder="15000" required class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold text-sm text-rose-700 bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Keterangan Pengeluaran *</label>
                <input type="text" id="expenseNotes" placeholder="Contoh: Uang makan siang kasir" required class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white text-slate-800 focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="closeExpenseModal()" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 font-bold text-slate-700">Batal</button>
                <button type="submit" id="btnSubmitExpense" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-xs">SIMPAN KAS KELUAR</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tutup Shift & Setor Penjualan -->
<div id="modalShiftPos" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center hidden p-4 backdrop-blur-xs">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="bg-[#133e1c] text-white px-5 py-3.5 flex items-center justify-between">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Rekap Shift & Setor Penjualan</span>
            </h3>
            <button onclick="closeShiftModal()" class="text-white/80 hover:text-white text-xl font-bold">&times;</button>
        </div>

        <form onsubmit="submitShiftDeposit(event)" class="p-5 space-y-3.5 text-xs">
            <div class="p-3 bg-emerald-50 rounded-lg border border-emerald-200">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-slate-600">Saldo Kas Laci Fisik:</span>
                    <span id="shiftDrawerBalanceLabel" class="font-mono font-bold text-slate-900 text-sm">-</span>
                </div>
                <div class="flex justify-between items-center mb-1">
                    <span class="text-slate-600">Wajib Cadangan Modal:</span>
                    <span class="font-mono font-bold text-amber-700">Rp 400.000</span>
                </div>
                <div class="flex justify-between items-center pt-1 border-t border-emerald-200">
                    <span class="font-bold text-emerald-900">Rekomendasi Setoran:</span>
                    <span id="shiftRecommendedLabel" class="font-mono font-extrabold text-emerald-800 text-sm">-</span>
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Nominal Uang Disetor ke Brankas/Pusat (Rp) *</label>
                <input type="number" id="shiftDepositAmount" step="1000" min="1000" required class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold text-base text-slate-900 bg-white focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                <span class="text-[10px] text-slate-400 mt-1 block">Sisa modal awal Rp 400.000 akan otomatis ditinggal di laci kasir untuk shift berikutnya.</span>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Catatan Setoran</label>
                <input type="text" id="shiftNotes" placeholder="Contoh: Setoran uang shift 1" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white text-slate-800 focus:ring-2 focus:ring-emerald-600 focus:outline-none">
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="closeShiftModal()" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 font-bold text-slate-700">Batal</button>
                <button type="submit" id="btnSubmitShift" class="px-4 py-2 rounded-lg bg-[#133e1c] hover:bg-[#0d2c14] text-white font-bold shadow-xs">SETOR UANG & TUTUP SHIFT</button>
            </div>
        </form>
    </div>
</div>
@endpush
