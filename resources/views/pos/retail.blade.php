@extends('layouts.app')

@section('title', 'Kasir Penjualan Retail')

@section('content')
<div class="space-y-4">
    
    <!-- Top Bar & Active Customer -->
    <div class="bg-white rounded-xl p-3 sm:p-4 border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <div>
                <h2 class="text-xs sm:text-sm font-bold text-slate-800 uppercase tracking-wide">
                    Kasir Penjualan Retail (Eceran)
                </h2>
                <p class="text-[11px] text-slate-500">Pilih produk, tentukan kuantitas, dan proses transaksi langsung tercatat di buku besar.</p>
            </div>
        </div>

        <div class="flex items-center gap-2 self-end md:self-auto w-full md:w-auto">
            <button type="button" onclick="openWithdrawModal()" class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-xs flex items-center gap-1.5 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Tarik Tunai</span>
            </button>
            <span class="text-xs font-semibold text-slate-600 whitespace-nowrap">Pelanggan:</span>
            <select id="posCustomerSelect" class="text-xs px-3 py-1.5 border border-slate-300 rounded-lg font-semibold bg-white w-full md:w-48 focus:ring-1 focus:ring-emerald-600 focus:outline-none">
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
            <span>Katalog & Cari</span>
        </button>
        <button type="button" id="tabBtnCart" onclick="switchMobileTab('cart')" class="flex-1 py-2 text-xs font-bold rounded-md bg-white text-slate-700 hover:bg-slate-50 flex items-center justify-center gap-1.5 border border-slate-200 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Keranjang</span>
            <span id="mobileCartBadge" class="bg-emerald-800 text-white px-1.5 py-0.2 rounded-full text-[10px] font-extrabold ml-1">0</span>
        </button>
    </div>

    <!-- TOP SECTION: WORKSTATION KASIR (LEGA & RESPONSIF) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        
        <!-- SISI KIRI: PENCARIAN & KATALOG PRODUK (Cols 7 - Luas & Nyaman) -->
        <div id="panelCatalog" class="lg:col-span-7 bg-white rounded-xl border border-slate-200 shadow-xs flex flex-col overflow-hidden">
            <!-- Header Pencarian & Katalog -->
            <div class="bg-[#133e1c] text-white px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <h3 class="font-bold text-xs uppercase tracking-wider text-white">Katalog & Pencarian Barang</h3>
                </div>
                <span class="text-[11px] text-emerald-200 font-bold font-mono">{{ $products->count() }} Produk Tersedia</span>
            </div>

            <!-- Input Pencarian & Filter Kategori -->
            <div class="p-3 sm:p-4 bg-slate-50 border-b border-slate-200 space-y-3">
                <div class="relative">
                    <input type="text" id="posSearchInput" placeholder="Ketik nama atau kode barang (misal: VIS, VTS, SP, CHARGER)..." class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-xs sm:text-sm focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 bg-white font-medium shadow-xs" autofocus>
                    <div class="absolute left-3.5 top-3 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                <!-- Quick Product Filter Pills -->
                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                    <span class="text-[11px] font-semibold text-slate-500 mr-1">Filter:</span>
                    <button type="button" onclick="filterCatalog('ALL')" class="pill-btn px-3 py-1.5 rounded-lg bg-emerald-700 text-white font-semibold text-xs shadow-xs">SEMUA</button>
                    <button type="button" onclick="filterCatalog('VOCER')" class="pill-btn px-3 py-1.5 rounded-lg bg-white text-slate-600 hover:bg-slate-100 font-medium border border-slate-200 text-xs">VOCER</button>
                    <button type="button" onclick="filterCatalog('PERDANA')" class="pill-btn px-3 py-1.5 rounded-lg bg-white text-slate-600 hover:bg-slate-100 font-medium border border-slate-200 text-xs">PERDANA</button>
                    <button type="button" onclick="filterCatalog('ACC')" class="pill-btn px-3 py-1.5 rounded-lg bg-white text-slate-600 hover:bg-slate-100 font-medium border border-slate-200 text-xs">ACC</button>
                    <button type="button" onclick="filterCatalog('KABEL')" class="pill-btn px-3 py-1.5 rounded-lg bg-white text-slate-600 hover:bg-slate-100 font-medium border border-slate-200 text-xs">KABEL</button>
                </div>

                <!-- Area Grid Katalog Produk (Sangat Lega, scrollable max-h-[460px]) -->
                <div id="quickCatalogBox" class="max-h-[460px] overflow-y-auto grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2 sm:gap-2.5 pt-1 pr-1">
                    @foreach($products as $prod)
                        <div onclick="addToCart({{ $prod->id }})" data-type="{{ $prod->type }}" data-code="{{ strtolower($prod->item_code) }}" data-name="{{ strtolower($prod->name) }}" class="catalog-item bg-white p-3 rounded-lg border border-slate-200 hover:border-emerald-600 hover:shadow-xs cursor-pointer transition select-none flex flex-col justify-between group">
                            <div>
                                <span class="font-mono font-bold text-[11px] text-emerald-700 block">{{ $prod->item_code }}</span>
                                <span class="text-xs font-semibold text-slate-800 line-clamp-2 leading-snug mt-1">{{ $prod->name }}</span>
                            </div>
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 text-[11px]">
                                <span class="text-slate-500">Stok: <strong class="{{ $prod->stock <= 0 ? 'text-rose-600' : 'text-slate-700' }} font-mono">{{ (float)$prod->stock }}</strong></span>
                                <span class="font-mono font-extrabold text-slate-900 text-xs">Rp {{ number_format($prod->retail_price, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- SISI KANAN: KERANJANG KASIR & PEMBAYARAN (Cols 5) -->
        <div id="panelCart" class="hidden lg:flex lg:col-span-5 bg-white rounded-xl border border-slate-200 shadow-xs flex-col overflow-hidden">
            <!-- Header Keranjang Kasir -->
            <div class="bg-[#133e1c] text-white px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <h3 class="font-bold text-xs uppercase tracking-wider text-white">Keranjang Kasir</h3>
                </div>
                <span class="text-[10px] text-emerald-200 font-mono font-bold">Auto ID: PR-{{ date('Ymd-His') }}</span>
            </div>

            <!-- Tabel Item Keranjang -->
            <div class="flex-1 overflow-y-auto max-h-[290px] p-2">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 sticky top-0">
                        <tr>
                            <th class="py-2 px-2 text-left">PRODUK</th>
                            <th class="py-2 px-2 text-center w-20">QTY</th>
                            <th class="py-2 px-2 text-right w-20">HARGA</th>
                            <th class="py-2 px-2 text-right w-24">TOTAL</th>
                            <th class="py-2 px-1 text-center w-6"></th>
                        </tr>
                    </thead>
                    <tbody id="cartTableBody" class="divide-y divide-slate-100">
                        <!-- Populated by JavaScript -->
                    </tbody>
                </table>
                <div id="cartEmptyNotice" class="text-center py-12 text-slate-400 text-xs italic">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                    Keranjang kasir masih kosong.<br>Klik produk di sebelah kiri untuk memasukkan ke keranjang.
                </div>
            </div>

            <!-- Ringkasan Checkout & Pembayaran Kasir -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 space-y-3">
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal Item:</span>
                        <span id="posSubtotalLabel" class="font-mono font-bold text-slate-800">Rp 0</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Potongan Diskon (Rp):</span>
                        <input type="number" step="500" id="posDiscountInput" value="0" min="0" oninput="renderCartSummary()" class="w-28 px-2.5 py-1 border border-slate-300 rounded-lg text-right font-mono text-xs bg-white">
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-slate-200">
                        <span class="font-bold text-xs uppercase tracking-wider text-slate-700">TOTAL BELANJA:</span>
                        <span id="posGrandTotalLabel" class="font-mono font-extrabold text-xl text-emerald-800">Rp 0</span>
                    </div>
                </div>

                <!-- Input Pembayaran -->
                <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Uang Diterima (Rp)</label>
                        <input type="number" step="500" id="posPaidInput" value="0" oninput="calculateChange()" class="w-full px-2.5 py-1.5 border border-slate-300 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 rounded-lg font-mono font-bold text-sm text-slate-900 bg-white">
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Kembalian / Sisa Piutang</label>
                        <div id="posChangeLabel" class="px-2.5 py-1.5 border border-slate-200 rounded-lg font-mono font-bold text-sm text-slate-800 bg-white flex items-center justify-between">
                            <span class="text-xs text-slate-500">Kembali:</span>
                            <span id="posChangeVal" class="text-emerald-700 font-extrabold">Rp 0</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <button type="button" onclick="submitCheckout()" class="flex-1 btn-retro btn-save py-2.5 text-xs sm:text-sm justify-center rounded-lg shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>SIMPAN & CETAK (F9)</span>
                    </button>
                    <button type="button" onclick="clearCart()" class="px-3.5 py-2.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg font-semibold text-xs transition">
                        RESET (F4)
                    </button>
                </div>
            </div>

        </div>

    </div>

    <!-- BOTTOM SECTION: RIWAYAT TRANSAKSI KASIR RETAIL (FULL WIDTH) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <h3 class="font-bold text-xs uppercase tracking-wider text-white">Riwayat Transaksi Kasir Hari Ini & Audit Jejak Stok</h3>
            </div>
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
                        <th class="text-right">HARGA SATUAN</th>
                        <th class="text-right">SUBTOTAL</th>
                        <th class="text-center">AUDIT STOK</th>
                        <th class="text-center">CETAK</th>
                    </tr>
                </thead>
                <tbody id="posHistoryTable">
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
                                        <span>Struk</span>
                                    </a>
                                    <a href="{{ route('receipt.invoice', $sale->id) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-semibold border border-emerald-200 ml-1">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>Faktur A4</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-10 text-slate-400">Belum ada transaksi kasir retail hari ini.</td>
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
            panelCart.classList.remove('hidden');
            panelCart.classList.add('flex');
            tabBtnCatalog.className = 'flex-1 py-2 text-xs font-bold rounded-md bg-white text-slate-700 hover:bg-slate-50 flex items-center justify-center gap-1.5 border border-slate-200 transition';
            tabBtnCart.className = 'flex-1 py-2 text-xs font-bold rounded-md bg-emerald-700 text-white flex items-center justify-center gap-1.5 shadow-xs transition';
        }
    }

    // Filter quick items
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

    // Search bar filter & Enter hotkey
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

    // Global Hotkeys (F9 = Simpan & Cetak, F4 = Reset)
    window.addEventListener('keydown', function(e) {
        if (e.key === 'F9') {
            e.preventDefault();
            submitCheckout();
        } else if (e.key === 'F4') {
            e.preventDefault();
            clearCart();
        }
    });

    // Add to cart
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
                price: parseFloat(prod.retail_price),
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
        if (confirm('Kosongkan keranjang belanja kasir?')) {
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
        const change = paid - grandTotal;

        const labelContainer = document.getElementById('posChangeLabel');
        const valSpan = document.getElementById('posChangeVal');

        if (change >= 0) {
            labelContainer.firstElementChild.innerText = 'Kembali:';
            valSpan.className = 'text-emerald-700 font-mono font-bold';
            valSpan.innerText = 'Rp ' + change.toLocaleString('id-ID');
        } else {
            labelContainer.firstElementChild.innerText = 'Sisa Piutang:';
            valSpan.className = 'text-rose-600 font-mono font-bold';
            valSpan.innerText = 'Rp ' + Math.abs(change).toLocaleString('id-ID');
        }
    }

    // Submit Checkout
    function submitCheckout() {
        if (cart.length === 0) {
            alert('Pilih minimal 1 barang untuk transaksi!');
            return;
        }

        const customerId = document.getElementById('posCustomerSelect').value;
        const discount = parseFloat(document.getElementById('posDiscountInput').value || 0);
        const paidAmount = parseFloat(document.getElementById('posPaidInput').value || 0);

        const payload = {
            sale_type: 'retail',
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
                window.open(data.redirect_url, '_blank', 'width=400,height=600');
                window.location.reload();
            } else {
                alert('Gagal: ' + (data.message || 'Terjadi kesalahan sistem'));
            }
        })
        .catch(err => {
            alert('Error transaksi: ' + err.message);
        });
    }

    // Modal Tarik Tunai
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
                            {{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})
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
@endpush
