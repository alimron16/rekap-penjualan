<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'POS & Akuntansi') - ELEPHANT CELL GROUP</title>
    
    <!-- PWA & Mobile Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#113819">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Elephant POS">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/icons/icon-512x512.png">
    
    <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        forest: {
                            800: '#14421b',
                            900: '#0b3c1a',
                            950: '#062610',
                        },
                        cream: {
                            50: '#fdfcf7',
                            100: '#fdf6e2',
                            200: '#fef5d9',
                            300: '#faeec1',
                        },
                        moss: {
                            800: '#1a5929',
                            900: '#164e23',
                            950: '#0d3844',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Global Table Helpers (Live Filter, Per-Page, Offline Excel Export) -->
    <script src="{{ asset('js/app-table.js') }}"></script>

    <style>
        body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace !important;
        }

        /* Clean Unified Table Styling */
        .excel-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.815rem;
            background-color: #ffffff;
        }
        .excel-table th {
            background-color: #133e1c;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 9px 12px;
            border: 1px solid #0d2c14;
            font-size: 0.725rem;
        }
        .excel-table td {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            color: #1e293b;
        }
        .excel-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .excel-table tr:hover td {
            background-color: #f0fdf4;
        }

        /* Clean Unified Button Styling */
        .btn-retro {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.45rem 0.9rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.06);
            transition: all 0.15s ease;
            letter-spacing: 0.025em;
            cursor: pointer;
        }
        .btn-retro:active {
            transform: translateY(1px);
        }
        .btn-refresh {
            background-color: #133e1c;
            color: #ffffff;
            border: 1px solid #0d2c14;
        }
        .btn-refresh:hover {
            background-color: #0d2c14;
        }
        .btn-save {
            background-color: #15803d;
            color: #ffffff;
            border: 1px solid #166534;
        }
        .btn-save:hover {
            background-color: #166534;
        }
        .btn-prepare {
            background-color: #0284c7;
            color: #ffffff;
            border: 1px solid #0369a1;
        }
        .btn-prepare:hover {
            background-color: #0369a1;
        }
        .btn-process {
            background-color: #0f766e;
            color: #ffffff;
            border: 1px solid #115e59;
        }
        .btn-process:hover {
            background-color: #115e59;
        }

        /* Custom Scrollbars */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <div class="flex min-h-screen relative">
        
        <!-- Mobile Backdrop Overlay -->
        <div id="sidebarBackdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/60 z-40 hidden lg:hidden backdrop-blur-xs transition-opacity duration-300"></div>

        <!-- SIDEBAR (Fixed/Sticky on desktop, Slide-over on mobile) -->
        <aside id="mainSidebar" class="fixed lg:sticky top-0 inset-y-0 left-0 z-40 w-64 h-screen bg-[#113819] text-white flex flex-col flex-shrink-0 shadow-xl select-none transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
            
            <!-- Sidebar Header Brand -->
            <div class="px-4 py-3.5 border-b border-white/10 flex items-center justify-between bg-[#0b2811]">
                <div class="flex items-center gap-2.5">
                    <img src="/logo.png" alt="Logo" class="w-9 h-9 object-contain rounded-lg bg-white p-0.5 flex-shrink-0 shadow-xs">
                    <div class="leading-tight">
                        <h1 class="font-extrabold text-sm tracking-wide text-white">
                            ELEPHANT CELL
                        </h1>
                        <p class="text-[9px] text-white/80 uppercase font-medium tracking-wider">POS & Akuntansi Mandiri</p>
                    </div>
                </div>
                <!-- Close Button (Mobile Only) -->
                <button onclick="toggleMobileSidebar()" class="lg:hidden text-white/80 hover:text-white p-1 rounded-md hover:bg-white/10" aria-label="Tutup Menu">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Sidebar Navigation Menu Links -->
            <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-1 text-xs">
                
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('dashboard') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span>Dashboard</span>
                </a>

                @if(!auth()->check() || auth()->user()->hasPermission('master'))
                <!-- 1. Master Data Group -->
                <div class="pt-2">
                    <div class="px-3 pb-1 text-[10px] font-bold text-white/70 uppercase tracking-wider flex items-center justify-between">
                        <span>Master Data</span>
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('master.items') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('master.items') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <span>Daftar Item</span>
                        </a>
                        <a href="{{ route('master.multi') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('master.multi') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>Produk Multi</span>
                        </a>
                        <a href="{{ route('master.suppliers') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('master.suppliers') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <span>Supplier</span>
                        </a>
                        <a href="{{ route('master.customers') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('master.customers') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Pelanggan</span>
                        </a>
                    </div>
                </div>
                @endif

                @if(!auth()->check() || auth()->user()->hasPermission('purchase'))
                <!-- 2. Pembelian Group -->
                <div class="pt-2">
                    <div class="px-3 pb-1 text-[10px] font-bold text-white/70 uppercase tracking-wider flex items-center justify-between">
                        <span>Pembelian</span>
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('purchase.index') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('purchase.index') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            <span>Daftar Pembelian</span>
                        </a>
                        <a href="{{ route('purchase.debt_payments') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('purchase.debt_payments') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span>Pembayaran Hutang</span>
                        </a>
                    </div>
                </div>
                @endif

                @if(!auth()->check() || auth()->user()->hasPermission('pos'))
                <!-- 3. Penjualan Group -->
                <div class="pt-2">
                    <div class="px-3 pb-1 text-[10px] font-bold text-white/70 uppercase tracking-wider flex items-center justify-between">
                        <span>Penjualan</span>
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('pos.retail') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('pos.retail') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <span>Retail (Kasir Eceran)</span>
                        </a>
                        <a href="{{ route('pos.wholesale') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('pos.wholesale') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span>Grosir (Kasir Grosir)</span>
                        </a>
                        <a href="{{ route('digital.index') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('digital.index') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <span>Elektrik (Pulsa/PLN)</span>
                        </a>
                        <a href="{{ route('receivable.payments') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('receivable.payments') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Pembayaran Piutang</span>
                        </a>
                        <a href="{{ route('receivable.returns') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('receivable.returns') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Retur Penjualan</span>
                        </a>
                    </div>
                </div>
                @endif

                @if(!auth()->check() || auth()->user()->hasPermission('transfer'))
                <!-- Transfer Agen Group -->
                <div class="pt-2">
                    <div class="px-3 pb-1 text-[10px] font-bold text-white/70 uppercase tracking-wider flex items-center justify-between">
                        <span>Transfer Agen</span>
                        @php
                            $pendingTransferCount = \App\Models\AgentTransfer::where('status', 'pending')->count();
                        @endphp
                        @if($pendingTransferCount > 0 && auth()->check() && auth()->user()->isAdmin())
                            <span class="px-1.5 py-0.2 rounded-full bg-amber-400 text-slate-950 text-[9px] font-extrabold animate-pulse">{{ $pendingTransferCount }}</span>
                        @endif
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('transfer.index') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('transfer.*') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            <span>Transfer Agen & Bank</span>
                        </a>
                    </div>
                </div>
                @endif

                @if(!auth()->check() || auth()->user()->hasPermission('master'))
                <!-- 4. Persediaan Group -->
                <div class="pt-2">
                    <div class="px-3 pb-1 text-[10px] font-bold text-white/70 uppercase tracking-wider flex items-center justify-between">
                        <span>Persediaan</span>
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('inventory.adjustments') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('inventory.adjustments') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                            <span>Penyesuaian Stok</span>
                        </a>
                        <a href="{{ route('inventory.opname') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('inventory.opname') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            <span>Stok Opname</span>
                        </a>
                    </div>
                </div>
                @endif

                @if(!auth()->check() || auth()->user()->hasPermission('accounting'))
                <!-- 5. Akuntansi Group -->
                <div class="pt-2">
                    <div class="px-3 pb-1 text-[10px] font-bold text-white/70 uppercase tracking-wider flex items-center justify-between">
                        <span>Akuntansi</span>
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('accounting.accounts') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('accounting.accounts') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            <span>Bagan Akun (COA)</span>
                        </a>
                        <a href="{{ route('accounting.cash_in') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('accounting.cash_in') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Kas Masuk</span>
                        </a>
                        <a href="{{ route('accounting.cash_out') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('accounting.cash_out') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                            <span>Kas Keluar</span>
                        </a>
                        <a href="{{ route('accounting.cash_transfer') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('accounting.cash_transfer') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            <span>Kas Transfer Antar Bank</span>
                        </a>
                    </div>
                </div>
                @endif

                @if(!auth()->check() || auth()->user()->hasPermission('reports'))
                <!-- 6. Laporan Group -->
                <div class="pt-2">
                    <div class="px-3 pb-1 text-[10px] font-bold text-white/70 uppercase tracking-wider flex items-center justify-between">
                        <span>Laporan Keuangan</span>
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('reports.purchases') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('reports.purchases') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Lap. Pembelian</span>
                        </a>
                        <a href="{{ route('reports.sales') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('reports.sales') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Lap. Penjualan</span>
                        </a>
                        <a href="{{ route('reports.debts') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('reports.debts') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Lap. Hutang</span>
                        </a>
                        <a href="{{ route('reports.receivables') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('reports.receivables') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Lap. Piutang</span>
                        </a>
                        <a href="{{ route('reports.inventory') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('reports.inventory') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Lap. Persediaan</span>
                        </a>
                        <a href="{{ route('reports.cash') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('reports.cash') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Lap. Kas & Bank</span>
                        </a>
                        <a href="{{ route('reports.profit_sales') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('reports.profit_sales') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            <span>Lap. Laba Jual</span>
                        </a>
                        <a href="{{ route('reports.profit_loss') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('reports.profit_loss') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <span>Lap. Laba Rugi</span>
                        </a>
                        <a href="{{ route('reports.balance_sheet') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('reports.balance_sheet') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                            <span>Neraca (Skontro)</span>
                        </a>
                    </div>
                </div>
                @endif

                @if(!auth()->check() || auth()->user()->hasPermission('settings'))
                <!-- 7. Pengaturan & Tutup Buku -->
                <div class="pt-2 pb-2">
                    <div class="px-3 pb-1 text-[10px] font-bold text-white/70 uppercase tracking-wider flex items-center justify-between">
                        <span>Pengaturan</span>
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('settings.index') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('settings.index') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <span>Pengaturan Toko</span>
                        </a>
                        <a href="{{ route('settings.templates') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('settings.templates') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <span>Target Bulanan</span>
                        </a>
                        <a href="{{ route('settings.yearly_closing') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('settings.yearly_closing') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>Tutup Buku Tahunan</span>
                        </a>
                        @if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->hasPermission('users')))
                        <a href="{{ route('settings.users.index') }}" class="group flex items-center gap-2.5 px-3 py-1.5 rounded-lg transition {{ request()->routeIs('settings.users.*') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Kelola Pengguna</span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Download Aplikasi -->
                <div class="pt-2 pb-4">
                    <button type="button" id="btnInstallAppSidebar" onclick="triggerPwaInstall()" class="w-full group flex items-center gap-2.5 px-3 py-2 rounded-lg text-white/90 hover:bg-white/10 hover:text-white transition text-xs font-medium text-left">
                        <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Download Aplikasi</span>
                    </button>
                </div>

            </nav>
        </aside>

        <!-- MAIN CONTENT AREA (Sleek Modern Slate #f1f5f9) -->
        <main class="flex-1 flex flex-col min-w-0 bg-slate-100 min-h-screen">
            
            <!-- Top Status Bar (Sticky Header) -->
            <header class="sticky top-0 z-30 h-14 bg-white border-b border-slate-200 px-4 lg:px-6 flex items-center justify-between shadow-xs flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <!-- Hamburger Toggle Button (Mobile & Tablet) -->
                    <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-1.5 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none" aria-label="Buka Menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>

                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-slate-500">
                            Lokasi: <span class="font-semibold text-slate-800">{{ auth()->check() && auth()->user()->store_name ? auth()->user()->store_name : 'Tambun Selatan, Bekasi' }}</span>
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 text-xs font-semibold text-slate-600">
                    <div class="hidden md:flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-md border border-slate-200 text-[11px] text-slate-600">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
                    </div>

                    @if(!auth()->check() || auth()->user()->hasPermission('pos'))
                    <a href="{{ route('pos.retail') }}" class="btn-retro btn-save text-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>KASIR</span>
                    </a>
                    @endif

                    @if(auth()->check())
                    <!-- User Profile & Logout Dropdown / Button in Header -->
                    <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                        <div class="hidden sm:block text-right">
                            <div class="text-[11px] font-bold text-slate-800 leading-tight">{{ auth()->user()->name }}</div>
                            <div class="text-[9px] text-slate-500">
                                @if(auth()->user()->isSuperAdmin())
                                    Super Admin
                                @elseif(auth()->user()->isAdmin())
                                    Admin
                                @else
                                    {{ auth()->user()->store_name ?? 'Toko' }}
                                @endif
                            </div>
                        </div>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Keluar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </header>

            <!-- Alerts Banner -->
            <div class="px-4 lg:px-6 pt-3 flex-shrink-0">
                @if(session('success'))
                    <div class="bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-800 rounded-lg shadow-xs flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 font-bold text-base">&times;</button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-rose-50 border-l-4 border-rose-500 p-3 text-xs text-rose-800 rounded shadow-xs flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800 font-bold text-base">&times;</button>
                    </div>
                @endif
            </div>

            <!-- Page Content Area -->
            <div class="flex-1 px-3 sm:px-4 lg:px-6 py-4">
                @yield('content')
            </div>

        </main>
    </div>

    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        }
    </script>

    <!-- PWA Install Modal -->
    <div id="pwaInstallModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-sm w-full p-5 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <img src="/icons/icon-192x192.png" alt="App Icon" class="w-8 h-8 rounded-lg shadow-xs border border-slate-200">
                    <div>
                        <h3 class="font-bold text-sm text-slate-900">ELEPHANT POS</h3>
                        <p class="text-[11px] text-slate-500">Aplikasi Android & PWA</p>
                    </div>
                </div>
                <button onclick="closePwaModal()" class="text-slate-400 hover:text-slate-700 p-1 text-xl font-bold leading-none">&times;</button>
            </div>
            
            <div class="py-4 space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>Aplikasi dapat dipasang langsung di smartphone Android untuk penggunaan fullscreen tanpa bilah alamat browser melalui <span class="font-semibold text-slate-800">pos.moonbyte.my.id</span>.</p>
                
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-1.5 text-[11px]">
                    <div class="font-semibold text-slate-800 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-700 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span>Petunjuk Pemasangan di Android:</span>
                    </div>
                    <ul class="space-y-1 text-slate-600 pl-4 list-disc">
                        <li>Klik tombol <strong>"Download & Pasang"</strong> di bawah.</li>
                        <li>Atau buka menu browser Chrome <strong>(titik 3 di pojok kanan atas)</strong> &gt; pilih <strong>"Tambahkan ke Layar Utama"</strong> / <strong>"Install Aplikasi"</strong>.</li>
                    </ul>
                </div>

                <div class="flex items-center justify-between text-[11px] bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                    <span class="text-slate-600">Notifikasi Transfer Agen:</span>
                    <button type="button" onclick="requestNotificationPermission()" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-800 font-semibold rounded text-[10px] transition">
                        Aktifkan
                    </button>
                </div>
            </div>

            <div class="pt-2 flex flex-col gap-2">
                <a href="/download/elephant-pos.apk" download class="w-full py-2.5 bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white font-bold text-xs rounded-xl shadow transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Unduh File Installer (.APK)</span>
                </a>
                <div class="flex gap-2">
                    <button type="button" id="btnModalConfirmInstall" onclick="executeDeferredPrompt()" class="flex-1 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl transition flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span>Pasang Tanpa File (PWA)</span>
                    </button>
                    <button type="button" onclick="closePwaModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- PWA & Service Worker Logic -->
    <script>
        let deferredPrompt = null;

        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((reg) => {
                        console.log('Elephant PWA Service Worker Registered:', reg.scope);
                    })
                    .catch((err) => {
                        console.warn('Service Worker registration failed:', err);
                    });
            });
        }

        // Listen for beforeinstallprompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            console.log('beforeinstallprompt captured, Elephant POS is ready to install');
        });

        // Trigger install UI
        function triggerPwaInstall() {
            if (deferredPrompt) {
                executeDeferredPrompt();
            } else {
                openPwaModal();
            }
        }

        function executeDeferredPrompt() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the Elephant POS install prompt');
                        closePwaModal();
                    } else {
                        console.log('User dismissed the install prompt');
                    }
                    deferredPrompt = null;
                });
            } else {
                openPwaModal();
            }
        }

        function openPwaModal() {
            const modal = document.getElementById('pwaInstallModal');
            if (modal) modal.classList.remove('hidden');
        }

        function closePwaModal() {
            const modal = document.getElementById('pwaInstallModal');
            if (modal) modal.classList.add('hidden');
        }

        // Notification Permission Helper
        function requestNotificationPermission() {
            if (!('Notification' in window)) {
                alert('Browser Anda belum mendukung web notification.');
                return;
            }
            Notification.requestPermission().then((permission) => {
                if (permission === 'granted') {
                    alert('Notifikasi diaktifkan! Anda akan menerima peringatan saat ada transfer agen atau pembaruan.');
                    if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                        navigator.serviceWorker.controller.postMessage({ type: 'NOTIFICATION_GRANTED' });
                    }
                } else {
                    alert('Izin notifikasi ditolak atau diabaikan.');
                }
            });
        }

        // Track when installed
        window.addEventListener('appinstalled', (evt) => {
            console.log('Elephant POS was successfully installed on the device!');
            closePwaModal();
        });
    </script>

    @stack('scripts')
</body>
</html>
