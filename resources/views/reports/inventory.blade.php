@extends('layouts.app')

@section('title', 'Laporan Persediaan')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <span>LAPORAN MUTASI PERSEDIAAN BARANG</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Laporan penambahan dan pengurangan barang: Item Masuk, Item Keluar, dan Stok Opname.</p>
        </div>

        <form action="{{ route('reports.inventory') }}" method="GET" class="flex flex-wrap items-center gap-2 text-xs">
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-2.5 py-1.5 border border-slate-300 rounded font-medium">
            <span class="text-slate-400 font-bold">-</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-2.5 py-1.5 border border-slate-300 rounded font-medium">
            <button type="submit" class="btn-retro btn-refresh">
                <span>REFRESH</span>
            </button>
        </form>
    </div>

    <!-- 1. LAPORAN ITEM MASUK -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#14421b] text-white px-4 py-2 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">1. Laporan Item Masuk (Penambahan Non-Beli)</h3>
            <span class="text-[10px] text-green-200">{{ $itemsIn->count() }} Baris</span>
        </div>
        <x-table-toolbar tableId="reportItemInTable" excelName="Laporan_Item_Masuk" placeholder="Cari nomor, barang, catatan..." />
        <div class="overflow-x-auto max-h-60">
            <table id="reportItemInTable" class="excel-table">
                <thead>
                    <tr>
                        <th>TANGGAL</th>
                        <th>NO TRANSAKSI</th>
                        <th>KODE BARANG</th>
                        <th>NAMA BARANG</th>
                        <th class="text-center">QTY</th>
                        <th class="text-right">HARGA</th>
                        <th class="text-right">TOTAL</th>
                        <th>KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($itemsIn as $in)
                        <tr>
                            <td class="whitespace-nowrap">{{ $in->date->format('d/m/Y') }}</td>
                            <td class="font-mono font-bold text-emerald-900">{{ $in->adjustment_number }}</td>
                            <td class="font-mono font-semibold">{{ $in->product->item_code ?? '-' }}</td>
                            <td class="font-bold text-slate-800">{{ $in->product->name ?? '-' }}</td>
                            <td class="text-center font-bold text-emerald-700">+{{ (float)$in->qty }}</td>
                            <td class="text-right font-mono">Rp {{ number_format($in->cost_price, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-slate-900">Rp {{ number_format($in->total_value, 0, ',', '.') }}</td>
                            <td class="text-slate-600">{{ $in->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4 text-slate-400">Tidak ada mutasi item masuk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. LAPORAN ITEM KELUAR -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#133e1c] text-white px-4 py-2 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">2. Laporan Item Keluar (Barang Rusak / Cacat / Display)</h3>
            <span class="text-[10px] text-green-200">{{ $itemsOut->count() }} Baris</span>
        </div>
        <x-table-toolbar tableId="reportItemOutTable" excelName="Laporan_Item_Keluar" placeholder="Cari nomor, barang, catatan..." />
        <div class="overflow-x-auto max-h-60">
            <table id="reportItemOutTable" class="excel-table">
                <thead>
                    <tr>
                        <th>TANGGAL</th>
                        <th>NO TRANSAKSI</th>
                        <th>KODE BARANG</th>
                        <th>NAMA BARANG</th>
                        <th class="text-center">QTY</th>
                        <th class="text-right">HPP</th>
                        <th class="text-right">TOTAL BEBAN</th>
                        <th>KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($itemsOut as $out)
                        <tr>
                            <td class="whitespace-nowrap">{{ $out->date->format('d/m/Y') }}</td>
                            <td class="font-mono font-bold text-emerald-900">{{ $out->adjustment_number }}</td>
                            <td class="font-mono font-semibold">{{ $out->product->item_code ?? '-' }}</td>
                            <td class="font-bold text-slate-800">{{ $out->product->name ?? '-' }}</td>
                            <td class="text-center font-bold text-rose-600">-{{ (float)$out->qty }}</td>
                            <td class="text-right font-mono">Rp {{ number_format($out->cost_price, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-slate-900">Rp {{ number_format($out->total_value, 0, ',', '.') }}</td>
                            <td class="text-slate-600">{{ $out->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4 text-slate-400">Tidak ada mutasi item keluar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. LAPORAN STOK OPNAME -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-[#0b3c1a] text-white px-4 py-2 flex items-center justify-between">
            <h3 class="font-bold text-xs uppercase tracking-wider">3. Laporan Penyesuaian Stok Opname Fisik</h3>
            <span class="text-[10px] text-white/80">{{ $stockOpname->count() }} Baris</span>
        </div>
        <x-table-toolbar tableId="reportStockOpnameTable" excelName="Laporan_Stok_Opname" placeholder="Cari nomor, barang, catatan..." />
        <div class="overflow-x-auto max-h-60">
            <table id="reportStockOpnameTable" class="excel-table">
                <thead>
                    <tr>
                        <th>TANGGAL</th>
                        <th>NO TRANSAKSI</th>
                        <th>KODE BARANG</th>
                        <th>NAMA BARANG</th>
                        <th class="text-center">STOK SISTEM</th>
                        <th class="text-center">STOK FISIK</th>
                        <th class="text-center">SELISIH</th>
                        <th class="text-right">HPP</th>
                        <th class="text-right">NILAI SELISIH</th>
                        <th>KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stockOpname as $so)
                        <tr>
                            <td class="whitespace-nowrap">{{ $so->date->format('d/m/Y') }}</td>
                            <td class="font-mono font-bold text-emerald-900">{{ $so->adjustment_number }}</td>
                            <td class="font-mono font-semibold">{{ $so->product->item_code ?? '-' }}</td>
                            <td class="font-bold text-slate-800">{{ $so->product->name ?? '-' }}</td>
                            <td class="text-center font-mono">{{ (float)$so->system_stock }}</td>
                            <td class="text-center font-mono font-bold text-emerald-800">{{ (float)$so->actual_stock }}</td>
                            <td class="text-center font-mono font-extrabold {{ $so->diff_qty < 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                                {{ $so->diff_qty > 0 ? '+' : '' }}{{ (float)$so->diff_qty }}
                            </td>
                            <td class="text-right font-mono">Rp {{ number_format($so->cost_price, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-slate-900">Rp {{ number_format($so->total_value, 0, ',', '.') }}</td>
                            <td class="text-slate-600">{{ $so->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center py-4 text-slate-400">Tidak ada data stok opname.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
