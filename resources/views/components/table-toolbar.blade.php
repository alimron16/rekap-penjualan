@props([
    'tableId' => 'dataTable',
    'excelName' => 'Data_Export',
    'placeholder' => 'Cari data...',
    'perPage' => request('per_page', 25)
])

<div class="bg-white p-3 rounded-lg border border-slate-200 shadow-xs flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 text-xs mb-3">
    <!-- Left: Live Search Box -->
    <div class="relative flex-1 max-w-md">
        <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </span>
        <input 
            type="text" 
            placeholder="{{ $placeholder }}" 
            oninput="filterTable('{{ $tableId }}', this.value)" 
            class="w-full pl-8 pr-3 py-1.5 border border-slate-300 rounded-md focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600 font-medium text-slate-700 bg-slate-50 placeholder-slate-400">
    </div>

    <!-- Right: Per-Page Selector & Excel Download -->
    <div class="flex items-center gap-2.5 justify-between sm:justify-end">
        <!-- Tampilkan Data Selector -->
        <div class="flex items-center gap-1.5 whitespace-nowrap text-slate-600 font-medium">
            <span>Tampilkan:</span>
            <select onchange="changeTablePerPage(this)" class="px-2 py-1.5 border border-slate-300 rounded-md font-semibold text-slate-800 bg-slate-50 focus:ring-1 focus:ring-emerald-600">
                <option value="10" {{ (string)$perPage === '10' ? 'selected' : '' }}>10</option>
                <option value="25" {{ (string)$perPage === '25' || !$perPage ? 'selected' : '' }}>25</option>
                <option value="50" {{ (string)$perPage === '50' ? 'selected' : '' }}>50</option>
                <option value="100" {{ (string)$perPage === '100' ? 'selected' : '' }}>100</option>
                <option value="all" {{ (string)$perPage === 'all' ? 'selected' : '' }}>Semua</option>
            </select>
        </div>

        <!-- Download Excel Button -->
        <button 
            type="button" 
            onclick="exportTableToExcel('{{ $tableId }}', '{{ $excelName }}')" 
            class="btn-retro bg-emerald-800 hover:bg-emerald-900 text-white border border-emerald-950 flex items-center gap-1.5 py-1.5 px-3 rounded-md font-bold tracking-wide transition shadow-xs"
            title="Download Spreadsheet Excel">
            <svg class="w-4 h-4 text-emerald-300" fill="currentColor" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm1.5 12.5L13.4 17l2.1 2.5h-1.8l-1.2-1.6-1.2 1.6H9.5L11.6 17l-2.1-2.5h1.8l1.2 1.6 1.2-1.6h1.8zM13 9V3.5L18.5 9H13z"/>
            </svg>
            <span>DOWNLOAD EXCEL</span>
        </button>
    </div>
</div>
