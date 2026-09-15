<?php

namespace App\Http\Controllers;

use App\Models\MonthlyTarget;
use App\Models\StoreSetting;
use App\Models\YearlyClosing;
use App\Services\YearlyClosingService;
use Exception;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        protected YearlyClosingService $closingService
    ) {}

    /**
     * Pengaturan Toko
     */
    public function index()
    {
        $setting = StoreSetting::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'ELEPHANT CELL GROUP',
                'phone' => '088212283661',
                'address' => 'Kav. Virlania Tridaya Sakti, Kec. Tambun Selatan Kab. Bekasi',
                'receipt_footer' => "Terima kasih telah berbelanja!\nBarang yang sudah dibeli tidak dapat ditukar/dikembalikan.",
                'active_year' => 2026,
            ]
        );

        return view('settings.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'receipt_footer' => 'nullable|string',
            'active_year' => 'required|integer',
        ]);

        $setting = StoreSetting::first();
        $setting->update($data);

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil disimpan!');
    }

    /**
     * Template & Target Bulanan
     */
    public function templates(Request $request)
    {
        $year = $request->input('year', 2026);
        $targets = MonthlyTarget::where('year', $year)->orderBy('month')->get();

        return view('settings.templates', compact('targets', 'year'));
    }

    public function updateTarget(Request $request, MonthlyTarget $target)
    {
        $data = $request->validate([
            'target_profit' => 'required|numeric|min:0',
            'target_vocer' => 'required|integer|min:0',
            'target_perdana' => 'required|integer|min:0',
            'target_acc' => 'required|integer|min:0',
            'target_transfer' => 'required|numeric|min:0',
            'target_elektrik' => 'required|numeric|min:0',
        ]);

        $target->update($data);

        return redirect()->back()->with('success', "Target bulan {$target->month} berhasil diupdate!");
    }

    /**
     * Tutup Buku Tahunan
     */
    public function yearlyClosing(Request $request)
    {
        $year = (int) $request->input('year', 2026);
        $closingDate = $request->input('closing_date', date('Y-m-d'));
        $preview = null;

        if ($request->has('prepare')) {
            $preview = $this->closingService->prepareClosing($year);
        }

        $closings = YearlyClosing::orderByDesc('year')->get();

        return view('settings.yearly_closing', compact('year', 'closingDate', 'preview', 'closings'));
    }

    public function processClosing(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer',
            'closing_date' => 'required|date',
        ]);

        try {
            $this->closingService->executeClosing((int) $data['year'], $data['closing_date']);
            return redirect()->route('settings.yearly_closing', ['year' => $data['year']])
                ->with('success', "Tutup buku tahun {$data['year']} telah berhasil diproses!");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
