@extends('layouts.app')

@section('title', 'Pengaturan Toko')

@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span>PENGATURAN IDENTITAS TOKO</span>
        </h2>
        <p class="text-xs text-slate-500 mt-0.5">Informasi resmi toko yang tercetak pada header struk kasir thermal dan faktur A4/A5.</p>
    </div>

    <div class="bg-white rounded-lg border-2 border-[#14421b] shadow-md overflow-hidden">
        <div class="bg-[#14421b] text-white px-5 py-3 font-bold text-xs uppercase tracking-wider">
            Identitas Perusahaan & Tahun Buku
        </div>

        <form action="{{ route('settings.update') }}" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            <div>
                <label class="font-bold text-slate-800 block mb-1">Nama Toko / Usaha *</label>
                <input type="text" name="name" value="{{ $setting->name }}" required class="w-full px-3 py-2 border border-slate-300 rounded font-bold text-sm text-slate-900 bg-slate-50">
            </div>

            <div>
                <label class="font-bold text-slate-800 block mb-1">Nomor Telepon Toko *</label>
                <input type="text" name="phone" value="{{ $setting->phone }}" required class="w-full px-3 py-2 border border-slate-300 rounded font-mono font-bold text-slate-900">
            </div>

            <div>
                <label class="font-bold text-slate-800 block mb-1">Alamat Lengkap Toko *</label>
                <textarea name="address" rows="3" required class="w-full px-3 py-2 border border-slate-300 rounded leading-relaxed text-slate-800">{{ $setting->address }}</textarea>
            </div>

            <div>
                <label class="font-bold text-slate-800 block mb-1">Tahun Buku Fiskal Aktif *</label>
                <input type="number" name="active_year" value="{{ $setting->active_year }}" required class="w-32 px-3 py-2 border border-slate-300 rounded font-mono font-bold text-sm text-emerald-900">
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end">
                <button type="submit" class="btn-retro btn-save px-6 py-2.5 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    <span>SIMPAN PENGATURAN</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
