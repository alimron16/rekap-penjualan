<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentTransfer;
use App\Models\Account;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MobileApiController extends Controller
{
    /**
     * Mobile Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau kata sandi tidak valid.',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda sedang dinonaktifkan.',
            ], 403);
        }

        // Generate simple API token
        $token = bin2hex(random_bytes(32));
        $user->remember_token = $token;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'store_name' => $user->store_name,
                'phone' => $user->phone,
                'permissions' => $user->permissions,
            ],
        ]);
    }

    /**
     * Dashboard Summary Stats
     */
    public function dashboard(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $today = Carbon::today();

        $totalSalesToday = Sale::whereDate('sale_date', $today)->sum('grand_total');
        $trxCountToday = Sale::whereDate('sale_date', $today)->count();
        $pendingTransfers = AgentTransfer::where('status', 'pending')->count();
        $totalProducts = Product::count();

        $recentTransfers = AgentTransfer::with(['user', 'processedBy'])
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'user' => $user,
            'stats' => [
                'sales_today' => (float) $totalSalesToday,
                'trx_today' => $trxCountToday,
                'pending_transfers' => $pendingTransfers,
                'total_products' => $totalProducts,
            ],
            'recent_transfers' => $recentTransfers,
        ]);
    }

    /**
     * Get Products Catalog
     */
    public function products(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $search = $request->query('q');
        $query = Product::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
        }

        $products = $query->orderBy('name')->take(100)->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Get Transfer List
     */
    public function transfers(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $query = AgentTransfer::with(['user', 'processedBy', 'sourceAccount'])->latest();

        if ($user->isToko()) {
            $query->where('user_id', $user->id);
        }

        $status = $request->query('status');
        if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        $transfers = $query->paginate(30);

        // Bank Accounts for admin
        $bankAccounts = Account::where('group', 'like', '%AKTIVA%')->where('type', 'D')->take(10)->get();

        return response()->json([
            'success' => true,
            'data' => $transfers,
            'bank_accounts' => $bankAccounts,
        ]);
    }

    /**
     * Submit Transfer Request from Store (Kasir Toko)
     */
    public function storeTransfer(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'bank_name' => 'required|string|max:50',
            'account_number' => 'required|string|max:50',
            'account_holder' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1000',
            'notes' => 'nullable|string|max:255',
        ]);

        $reference = 'TF-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        $adminFee = 0; // standard fee
        $totalAmount = $request->amount + $adminFee;

        $transfer = AgentTransfer::create([
            'reference_no' => $reference,
            'user_id' => $user->id,
            'bank_name' => strtoupper($request->bank_name),
            'account_number' => $request->account_number,
            'account_holder' => strtoupper($request->account_holder),
            'amount' => $request->amount,
            'admin_fee' => $adminFee,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan transfer berhasil dikirim!',
            'data' => $transfer,
        ]);
    }

    /**
     * Admin Approve & Upload Proof Image
     */
    public function approveTransfer(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || (!$user->isAdmin() && !$user->isSuperAdmin())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $transfer = AgentTransfer::findOrFail($id);

        $request->validate([
            'source_account_id' => 'required|exists:accounts,id',
            'proof_image' => 'required|image|max:10240',
            'notes' => 'nullable|string|max:255',
        ]);

        $path = $request->file('proof_image')->store('proofs', 'public');
        $sourceAccount = Account::findOrFail($request->source_account_id);

        $transfer->update([
            'status' => 'approved',
            'processed_by' => $user->id,
            'source_account_id' => $sourceAccount->id,
            'proof_image' => $path,
            'notes' => $request->notes ?? $transfer->notes,
            'processed_at' => Carbon::now(),
        ]);

        $sourceAccount->current_balance -= $transfer->total_amount;
        $sourceAccount->save();

        return response()->json([
            'success' => true,
            'message' => 'Transfer berhasil disetujui & bukti tersimpan!',
            'data' => $transfer,
        ]);
    }

    /**
     * Helper to authenticate token
     */
    private function getUserFromToken(Request $request)
    {
        $header = $request->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }
        $token = substr($header, 7);
        return User::where('remember_token', $token)->first();
    }
}
