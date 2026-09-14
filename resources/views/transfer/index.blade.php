@extends('layouts.app')

@section('title', 'Transfer Agen & Bank')

@section('content')
<div class="space-y-5">
    
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h1 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Transfer Antar Agen & Bank</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                @if(auth()->user()->isAdmin())
                    Pusat persetujuan transfer agen: terima notifikasi, transfer dana, dan unggah bukti struk perbankan.
                @else
                    Ajukan pengajuan transfer dari kasir toko ke admin pusat secara real-time.
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if(auth()->user()->isAdmin())
                <div id="adminNotificationBadge" class="hidden sm:flex items-center gap-2 px-3 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-bold shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                    <span id="pendingTextCount">{{ $pendingCount }} Pengajuan Menunggu</span>
                </div>
            @endif

            <button onclick="openRequestTransferModal()" class="w-full sm:w-auto px-4 py-2 bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Ajukan Transfer</span>
            </button>
        </div>
    </div>

    <!-- Quick Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Menunggu Persetujuan</div>
                <div class="text-xl font-extrabold text-amber-900 mt-0.5" id="statPendingCount">{{ $pendingCount }} Antrean</div>
            </div>
            <div class="p-3 bg-amber-50 rounded-xl text-amber-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Selesai Hari Ini</div>
                <div class="text-xl font-extrabold text-emerald-900 mt-0.5 font-mono">Rp {{ number_format($approvedTodayTotal, 0, ',', '.') }}</div>
            </div>
            <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Akun Login</div>
                <div class="text-sm font-bold text-slate-900 mt-0.5">{{ auth()->user()->name }}</div>
                <div class="text-[10px] text-slate-400 font-medium">{{ auth()->user()->store_name ?? 'Kantor Pusat' }}</div>
            </div>
            <div class="p-3 bg-slate-100 rounded-xl text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
        </div>
    </div>

    <!-- Transfers Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
            <div class="text-xs font-bold text-slate-800 uppercase tracking-wider">Daftar Transaksi Transfer Agen</div>
            <div class="text-xs text-slate-500">Auto-refresh & notifikasi aktif</div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-600 font-bold uppercase text-[10px] tracking-wider">
                        <th class="py-3 px-4">No. Ref & Waktu</th>
                        <th class="py-3 px-4">Toko Pengaju</th>
                        <th class="py-3 px-4">Bank & Rekening Tujuan</th>
                        <th class="py-3 px-4 text-right">Nominal</th>
                        <th class="py-3 px-4 text-right">Biaya Admin</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Bukti Struk</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($transfers as $tf)
                        <tr class="hover:bg-slate-50/80 transition {{ $tf->status === 'pending' ? 'bg-amber-50/30' : '' }}">
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 font-mono text-[11px]">{{ $tf->reference_no }}</div>
                                <div class="text-[10px] text-slate-400">{{ $tf->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-800">{{ $tf->store_name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $tf->user->name ?? 'Kasir' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                    <span class="px-1.5 py-0.5 bg-slate-200 text-slate-800 font-mono rounded text-[10px] font-extrabold">{{ $tf->bank_name }}</span>
                                    <span class="font-mono text-[11px]">{{ $tf->account_number }}</span>
                                </div>
                                <div class="text-[11px] text-slate-600 uppercase font-medium mt-0.5">a.n {{ $tf->account_holder }}</div>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                                Rp {{ number_format($tf->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-slate-500">
                                Rp {{ number_format($tf->admin_fee, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($tf->status === 'approved')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        Berhasil
                                    </span>
                                @elseif($tf->status === 'rejected')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        Ditolak
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 animate-pulse">
                                        Menunggu Admin
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($tf->proof_image)
                                    <button type="button" onclick="viewProofModal('{{ asset('storage/' . $tf->proof_image) }}', '{{ $tf->reference_no }}')" class="group relative inline-block p-1 rounded-lg border border-slate-200 hover:border-emerald-500 transition">
                                        <img src="{{ asset('storage/' . $tf->proof_image) }}" alt="Bukti" class="w-10 h-10 object-cover rounded shadow-xs">
                                        <div class="absolute inset-0 bg-slate-900/40 rounded opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </div>
                                    </button>
                                @else
                                    <span class="text-[10px] text-slate-400 italic">- Belum Ada -</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if($tf->status === 'pending' && auth()->user()->isAdmin())
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" onclick="openApproveModal({{ json_encode($tf) }})" class="px-2.5 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-lg shadow-xs transition flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Proses & Transfer</span>
                                        </button>
                                        <button type="button" onclick="openRejectModal({{ json_encode($tf) }})" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="Tolak Pengajuan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                @elseif($tf->status === 'approved' && $tf->proof_image)
                                    <button type="button" onclick="viewProofModal('{{ asset('storage/' . $tf->proof_image) }}', '{{ $tf->reference_no }}')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px] rounded-lg transition">
                                        Lihat Bukti
                                    </button>
                                @else
                                    <span class="text-slate-400 text-[11px]">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">Belum ada catatan transfer agen.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transfers->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $transfers->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal 1: Ajukan Transfer Baru (Toko) -->
<div id="requestModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Ajukan Transfer Agen Baru</span>
            </h3>
            <button onclick="closeRequestModal()" class="text-slate-400 hover:text-slate-700 p-1 text-xl font-bold leading-none">&times;</button>
        </div>

        <form method="POST" action="{{ route('transfer.store') }}" class="py-4 space-y-3.5 text-xs">
            @csrf

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Bank / E-Wallet Tujuan *</label>
                <select name="bank_name" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 font-bold focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600">
                    <option value="BCA">BCA (Bank Central Asia)</option>
                    <option value="BRI">BRI (Bank Rakyat Indonesia)</option>
                    <option value="MANDIRI">MANDIRI</option>
                    <option value="BNI">BNI (Bank Negara Indonesia)</option>
                    <option value="SEABANK">SEABANK</option>
                    <option value="DANA">DANA (E-Wallet)</option>
                    <option value="OVO">OVO (E-Wallet)</option>
                    <option value="GOPAY">GOPAY (E-Wallet)</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nomor Rekening *</label>
                    <input type="text" name="account_number" required placeholder="Contoh: 1234567890"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 font-mono font-bold focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Atas Nama Pemilik *</label>
                    <input type="text" name="account_holder" required placeholder="Contoh: IMRON ROSADI"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 uppercase font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nominal Transfer (Rp) *</label>
                    <input type="number" name="amount" min="1000" step="500" required placeholder="500000"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 font-mono font-bold focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Biaya Admin (Rp)</label>
                    <input type="number" name="admin_fee" min="0" step="500" value="2500"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Catatan / Keterangan</label>
                <input type="text" name="notes" placeholder="Contoh: Pelanggan TF BCA tunai lunas di toko"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600">
            </div>

            <div class="pt-3 flex gap-2 border-t border-slate-100">
                <button type="submit" class="flex-1 py-2.5 bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white font-bold text-xs rounded-xl shadow-xs transition">
                    Kirim Pengajuan ke Admin
                </button>
                <button type="button" onclick="closeRequestModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Proses & Unggah Bukti Transfer (Admin) -->
<div id="approveModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Konfirmasi Transfer & Bukti Struk</span>
            </h3>
            <button onclick="closeApproveModal()" class="text-slate-400 hover:text-slate-700 p-1 text-xl font-bold leading-none">&times;</button>
        </div>

        <form id="approveForm" method="POST" enctype="multipart/form-data" action="" class="py-4 space-y-4 text-xs">
            @csrf

            <!-- Transfer Summary Card -->
            <div class="bg-emerald-50/70 border border-emerald-200 rounded-xl p-3.5 space-y-1">
                <div class="flex justify-between text-slate-600">
                    <span>No. Pengajuan:</span>
                    <span id="apprRef" class="font-mono font-bold text-slate-900">-</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Tujuan Transfer:</span>
                    <span id="apprTarget" class="font-bold text-slate-900">-</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Atas Nama:</span>
                    <span id="apprHolder" class="font-semibold text-slate-900 uppercase">-</span>
                </div>
                <div class="flex justify-between text-slate-800 border-t border-emerald-200 pt-1 mt-1 font-bold text-sm">
                    <span>Total Transfer:</span>
                    <span id="apprAmount" class="text-emerald-800 font-mono">-</span>
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Pilih Rekening Bank Sumber (Kas Pusat) *</label>
                <select name="source_account_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600">
                    @foreach($bankAccounts as $acc)
                        <option value="{{ $acc->id }}">
                            {{ $acc->code }} - {{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Unggah Foto / Struk Bukti Transfer *</label>
                <input type="file" name="proof_image" accept="image/*" capture="environment" required
                       class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer">
                <p class="text-[10px] text-slate-400 mt-1">Bisa ambil langsung via Kamera HP atau pilih foto struk dari Galeri HP (Maks 5MB)</p>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Catatan Admin (Opsional)</label>
                <input type="text" name="notes" placeholder="Contoh: Transfer sukses via BCA Mobile"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600">
            </div>

            <div class="pt-3 flex gap-2 border-t border-slate-100">
                <button type="submit" class="flex-1 py-2.5 bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Selesaikan & Simpan Struk</span>
                </button>
                <button type="button" onclick="closeApproveModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Tolak Pengajuan -->
<div id="rejectModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-200">
        <h3 class="font-extrabold text-sm text-slate-900 mb-2">Tolak Pengajuan Transfer</h3>
        <form id="rejectForm" method="POST" action="" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Alasan Penolakan *</label>
                <textarea name="notes" required rows="3" placeholder="Contoh: No rekening tidak valid / Saldo rekening toko tidak mencukupi"
                          class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500"></textarea>
            </div>
            <div class="pt-2 flex gap-2">
                <button type="submit" class="flex-1 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl transition">
                    Konfirmasi Tolak
                </button>
                <button type="button" onclick="closeRejectModal()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 4: Lihat Bukti Struk Gambar (Lightbox) -->
<div id="proofLightbox" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-xs hidden flex items-center justify-center p-4" onclick="closeProofModal()">
    <div class="bg-white rounded-2xl max-w-md w-full p-4 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-200" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-bold text-xs text-slate-800 font-mono" id="proofRefTitle">Struk Bukti Transfer</h3>
            <button onclick="closeProofModal()" class="text-slate-400 hover:text-slate-700 p-1 text-xl font-bold leading-none">&times;</button>
        </div>
        <div class="py-3 flex items-center justify-center bg-slate-100 rounded-xl my-2 overflow-hidden max-h-[70vh]">
            <img id="proofImageEl" src="" alt="Bukti Transfer" class="max-h-full max-w-full object-contain rounded-lg">
        </div>
        <div class="text-right">
            <a id="proofDownloadLink" href="" download class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Unduh Gambar</span>
            </a>
        </div>
    </div>
</div>

<script>
    function openRequestTransferModal() {
        document.getElementById('requestModal').classList.remove('hidden');
    }
    function closeRequestModal() {
        document.getElementById('requestModal').classList.add('hidden');
    }

    function openApproveModal(tf) {
        document.getElementById('approveForm').action = "/transfer/" + tf.id + "/approve";
        document.getElementById('apprRef').innerText = tf.reference_no;
        document.getElementById('apprTarget').innerText = tf.bank_name + ' - ' + tf.account_number;
        document.getElementById('apprHolder').innerText = tf.account_holder;
        document.getElementById('apprAmount').innerText = 'Rp ' + Number(tf.total_amount).toLocaleString('id-ID');
        document.getElementById('approveModal').classList.remove('hidden');
    }
    function closeApproveModal() {
        document.getElementById('approveModal').classList.add('hidden');
    }

    function openRejectModal(tf) {
        document.getElementById('rejectForm').action = "/transfer/" + tf.id + "/reject";
        document.getElementById('rejectModal').classList.remove('hidden');
    }
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }

    function viewProofModal(url, ref) {
        document.getElementById('proofRefTitle').innerText = 'Bukti: ' + ref;
        document.getElementById('proofImageEl').src = url;
        document.getElementById('proofDownloadLink').href = url;
        document.getElementById('proofLightbox').classList.remove('hidden');
    }
    function closeProofModal() {
        document.getElementById('proofLightbox').classList.add('hidden');
    }

    // Real-time Poll for Admins
    @if(auth()->user()->isAdmin())
    let lastPendingCount = {{ $pendingCount }};
    setInterval(() => {
        fetch("{{ route('transfer.check_pending') }}")
            .then(res => res.json())
            .then(data => {
                if (data.count > lastPendingCount) {
                    // Play notification sound
                    try {
                        const audio = new Audio("data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbqWEzMTCc2+nCbzY2M5zb58FlLTIzmdnnwGUuMTGY2ufBZS0yM5ja5sBlLjExmNrnwGUuMTGY2ufBZS0yM5na58FlLjExmNrmwGU=");
                        audio.play();
                    } catch (e) {}

                    // Show push notification if permitted
                    if ('Notification' in window && Notification.permission === 'granted' && data.latest) {
                        new Notification('Pengajuan Transfer Baru!', {
                            body: data.latest.store + ' mengajukan transfer Rp ' + data.latest.amount + ' ke ' + data.latest.bank,
                            icon: '/icons/icon-192x192.png'
                        });
                    }
                }
                lastPendingCount = data.count;
                const statEl = document.getElementById('statPendingCount');
                if (statEl) statEl.innerText = data.count + ' Antrean';
                const badgeEl = document.getElementById('pendingTextCount');
                if (badgeEl) badgeEl.innerText = data.count + ' Pengajuan Menunggu';
            })
            .catch(() => {});
    }, 8000);
    @endif
</script>
@endsection
