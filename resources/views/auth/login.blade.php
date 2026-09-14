<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Login Masuk - ELEPHANT CELL GROUP</title>
    
    <!-- PWA & Mobile Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#113819">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
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
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4 selection:bg-emerald-500 selection:text-white">

    <div class="w-full max-w-md">
        
        <!-- Header Brand -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center p-3 rounded-2xl bg-emerald-950 border border-emerald-500/30 shadow-xl mb-3">
                <img src="/icons/icon-192x192.png" alt="Elephant Logo" class="w-12 h-12 rounded-xl shadow-xs">
            </div>
            <h1 class="text-xl font-extrabold text-white tracking-wide">ELEPHANT CELL GROUP</h1>
            <p class="text-xs text-emerald-400 font-medium tracking-wider uppercase mt-0.5">Sistem POS & Akuntansi Mandiri</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200/80 p-6 sm:p-8">
            
            <div class="mb-5">
                <h2 class="text-base font-bold text-slate-800">Masuk ke Sistem</h2>
                <p class="text-xs text-slate-500 mt-0.5">Silakan pilih akun atau masukkan email & password Anda</p>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs p-3 rounded-xl flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs p-3 rounded-xl space-y-1">
                    @foreach($errors->all() as $error)
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>{{ $error }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Alamat Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"/></svg>
                        </div>
                        <input type="email" id="email" name="email" value="{{ old('email', 'superadmin@elephantcell.com') }}" required autofocus
                               class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent transition">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">Kata Sandi</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <input type="password" id="password" name="password" value="Imron@0458" required
                               class="w-full pl-9 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent transition font-mono">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <svg id="eyeIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                        <input type="checkbox" name="remember" value="1" checked class="w-4 h-4 rounded text-emerald-700 focus:ring-emerald-600 border-slate-300">
                        <span>Ingat saya di perangkat ini</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white text-xs font-bold rounded-xl shadow-md transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                    <span>Masuk ke Dashboard</span>
                </button>
            </form>

            <!-- Quick Account Switcher for convenience -->
            <div class="mt-6 pt-5 border-t border-slate-100">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 text-center">Pilih Cepat Akun Demo:</div>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="setQuickLogin('superadmin@elephantcell.com')" class="p-2 text-center rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 transition">
                        <div class="text-[11px] font-bold text-slate-800">Super Admin</div>
                        <div class="text-[9px] text-slate-500">Akses Penuh</div>
                    </button>
                    <button type="button" onclick="setQuickLogin('admin@elephantcell.com')" class="p-2 text-center rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 transition">
                        <div class="text-[11px] font-bold text-slate-800">Admin</div>
                        <div class="text-[9px] text-slate-500">Operasional</div>
                    </button>
                    <button type="button" onclick="setQuickLogin('toko@elephantcell.com')" class="p-2 text-center rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 transition">
                        <div class="text-[11px] font-bold text-slate-800">Toko</div>
                        <div class="text-[9px] text-slate-500">Kasir Cabang</div>
                    </button>
                </div>
            </div>

        </div>

        <div class="text-center mt-6 text-[11px] text-slate-400 font-medium">
            &copy; {{ date('Y') }} ELEPHANT CELL GROUP &bull; Version 2.0 (Mobile Ready)
        </div>

    </div>

    <script>
        function setQuickLogin(email) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = 'Imron@0458';
        }

        function togglePasswordVisibility() {
            const pwdInput = document.getElementById('password');
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
            } else {
                pwdInput.type = 'password';
            }
        }
    </script>
</body>
</html>
