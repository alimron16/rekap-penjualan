<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AgentTransfer;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Outlet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgentTransferController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $outlets = Outlet::orderBy('name')->get();
        $selectedOutletId = $request->query('outlet_id');
        $selectedStatus = $request->query('status');

        // Query transfers with outlet, user, processedBy, and approvedBy
        $query = AgentTransfer::with(['user', 'outlet', 'processedBy', 'approvedBy', 'sourceAccount'])
            ->latest();

        // If user is Toko, only see their outlet's transfers
        if ($user->isToko()) {
            if ($user->outlet_id) {
                $query->where('outlet_id', $user->outlet_id);
            } else {
                $query->where('user_id', $user->id);
            }
        } elseif (!empty($selectedOutletId)) {
            $query->where('outlet_id', $selectedOutletId);
        }

        if (!empty($selectedStatus)) {
            $query->where('status', $selectedStatus);
        }

        $transfers = $query->paginate(20)->withQueryString();

        // Stats (respecting scope)
        $statsQuery = AgentTransfer::query();
        if ($user->isToko() && $user->outlet_id) {
            $statsQuery->where('outlet_id', $user->outlet_id);
        } elseif (!empty($selectedOutletId)) {
            $statsQuery->where('outlet_id', $selectedOutletId);
        }

        $pendingCount = (clone $statsQuery)->where('status', 'pending')->count();
        $approvedTodayTotal = (clone $statsQuery)->where('status', 'approved')
            ->whereDate('processed_at', Carbon::today())
            ->sum('total_amount');

        // Bank Accounts available for Admin funding
        $bankAccounts = Account::where('group', 'like', '%AKTIVA%')
            ->whereIn('code', ['1-1113', '1-1120', '1-1121', '1-1122', '1-1123', '1-1130', '1-1131'])
            ->get();

        if ($bankAccounts->isEmpty()) {
            $bankAccounts = Account::where('group', 'like', '%AKTIVA%')->where('type', 'D')->take(8)->get();
        }

        return view('transfer.index', compact(
            'transfers',
            'pendingCount',
            'approvedTodayTotal',
            'bankAccounts',
            'outlets',
            'selectedOutletId',
            'selectedStatus'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'bank_name' => 'required|string|max:50',
            'account_number' => 'required|string|max:50',
            'account_holder' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1000',
            'admin_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $adminFee = $validated['admin_fee'] ?? 0;
        $amount = $validated['amount'];
        $totalAmount = $amount + $adminFee;

        // Generate clean reference
        $refNo = 'TF-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        $outletName = $user->outlet?->name ?? $user->store_name ?? 'Toko Tambun';

        $transfer = AgentTransfer::create([
            'reference_no' => $refNo,
            'user_id' => $user->id,
            'outlet_id' => $user->outlet_id,
            'store_name' => $outletName,
            'bank_name' => strtoupper($validated['bank_name']),
            'account_number' => $validated['account_number'],
            'account_holder' => strtoupper($validated['account_holder']),
            'amount' => $amount,
            'admin_fee' => $adminFee,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('transfer.index')
            ->with('success', "Pengajuan transfer {$transfer->reference_no} sebesar Rp " . number_format($amount, 0, ',', '.') . " berhasil dikirim! Menunggu persetujuan Admin.");
    }

    public function approve(Request $request, AgentTransfer $transfer)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            abort(403, 'Hanya Admin yang dapat menyetujui transfer.');
        }

        if ($transfer->status !== 'pending') {
            return back()->with('error', 'Pengajuan transfer ini sudah diproses sebelumnya.');
        }

        // Bukti transfer bersifat OPSIONAL (nullable), mimes: jpeg, png, jpg, webp, max 5MB (5120KB)
        $request->validate([
            'source_account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Upload proof of transfer jika ada (disimpan ke transfers/proofs/YYYY/MM/)
            $path = null;
            if ($request->hasFile('proof_image')) {
                $file = $request->file('proof_image');
                $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (!in_array($ext, $allowedExts)) {
                    return back()->with('error', 'Format file bukti harus berupa gambar (JPG, PNG, WEBP).');
                }
                if ($file->getSize() > 5 * 1024 * 1024) {
                    return back()->with('error', 'Ukuran file gambar maksimal 5MB.');
                }

                $year = date('Y');
                $month = date('m');
                $filename = 'proof_' . time() . '_' . uniqid() . '.' . $ext;
                $destinationDir = storage_path("app/public/transfers/proofs/{$year}/{$month}");
                if (!file_exists($destinationDir)) {
                    mkdir($destinationDir, 0755, true);
                }
                $file->move($destinationDir, $filename);
                $path = "transfers/proofs/{$year}/{$month}/{$filename}";
            }

            $sourceAccount = Account::findOrFail($request->source_account_id);

            // Update transfer record
            $transfer->update([
                'status' => 'approved',
                'processed_by' => $user->id,
                'approved_by' => $user->id,
                'source_account_id' => $sourceAccount->id,
                'proof_image' => $path ?? $transfer->proof_image,
                'notes' => $request->notes ?? $transfer->notes,
                'processed_at' => Carbon::now(),
                'approved_at' => Carbon::now(),
            ]);

            // Deduct source bank balance in accounts table
            $sourceAccount->current_balance -= $transfer->total_amount;
            $sourceAccount->save();

            // Auto-record double-entry journal entry
            // Find target Cash Transfer account (1-1111 CASH TRANSFER)
            $cashTransferAccount = Account::where('code', '1-1111')->first();
            if (!$cashTransferAccount) {
                $cashTransferAccount = Account::firstOrCreate(
                    ['code' => '1-1111'],
                    [
                        'name' => 'CASH TRANSFER',
                        'type' => 'D',
                        'group' => 'AKTIVA',
                        'initial_balance' => 0,
                        'current_balance' => 0,
                        'is_system_locked' => true,
                    ]
                );
            }

            // Increase cash transfer account balance
            $cashTransferAccount->current_balance += $transfer->amount;
            $cashTransferAccount->save();

            $journal = JournalEntry::create([
                'entry_number' => 'JRN-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                'entry_date' => Carbon::now(),
                'reference_type' => 'AGENT_TRANSFER',
                'reference_id' => $transfer->id,
                'description' => "Penyelesaian Transfer Agen Toko {$transfer->store_name} ({$transfer->bank_name} {$transfer->account_number} a.n {$transfer->account_holder})",
                'total_debit' => $transfer->total_amount,
                'total_credit' => $transfer->total_amount,
                'is_balanced' => true,
                'created_by' => $user->name,
            ]);

            // Debit: Cash Transfer
            JournalItem::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $cashTransferAccount->id,
                'account_code' => $cashTransferAccount->code,
                'account_name' => $cashTransferAccount->name,
                'debit' => $transfer->amount,
                'credit' => 0,
                'memo' => "Transfer Toko {$transfer->store_name}",
            ]);

            // Credit: Source Bank
            JournalItem::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $sourceAccount->id,
                'account_code' => $sourceAccount->code,
                'account_name' => $sourceAccount->name,
                'debit' => 0,
                'credit' => $transfer->total_amount,
                'memo' => "Debet dari {$sourceAccount->name}",
            ]);

            DB::commit();

            return redirect()->route('transfer.index')
                ->with('success', "Transfer {$transfer->reference_no} berhasil disetujui, bukti struk tersimpan, dan jurnal kas telah otomatis dicatat!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses transfer: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, AgentTransfer $transfer)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'notes' => 'required|string|max:255',
        ]);

        $transfer->update([
            'status' => 'rejected',
            'processed_by' => $user->id,
            'notes' => $request->notes,
            'processed_at' => Carbon::now(),
        ]);

        return redirect()->route('transfer.index')
            ->with('success', "Pengajuan transfer {$transfer->reference_no} telah ditolak.");
    }

    /**
     * Polling endpoint for real-time notification on admin dashboard
     */
    public function checkPending(Request $request)
    {
        $user = Auth::user();
        $query = AgentTransfer::where('status', 'pending');

        if ($user && $user->isToko() && $user->outlet_id) {
            $query->where('outlet_id', $user->outlet_id);
        } elseif ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        $pendingCount = $query->count();
        $latestPending = $query->latest()->first();

        return response()->json([
            'count' => $pendingCount,
            'latest' => $latestPending ? [
                'ref' => $latestPending->reference_no,
                'store' => $latestPending->store_name,
                'bank' => $latestPending->bank_name,
                'amount' => number_format($latestPending->amount, 0, ',', '.'),
                'time' => $latestPending->created_at->diffForHumans(),
            ] : null,
        ]);
    }
}
