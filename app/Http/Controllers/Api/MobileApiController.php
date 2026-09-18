<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AgentTransfer;
use App\Models\CashTransaction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DebtPayment;
use App\Models\DigitalProduct;
use App\Models\DigitalSale;
use App\Models\InventoryAdjustment;
use App\Models\MonthlyTarget;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\StoreSetting;
use App\Models\Supplier;
use App\Models\User;
use App\Models\YearlyClosing;
use App\Services\AccountingService;
use App\Services\FinancialReportService;
use App\Services\PosTransactionService;
use App\Services\YearlyClosingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MobileApiController extends Controller
{
    public function __construct(
        protected PosTransactionService $posService,
        protected AccountingService $accountingService,
        protected FinancialReportService $reportService,
        protected YearlyClosingService $closingService
    ) {}

    /**
     * Mobile Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with('outlet')->where('email', $request->email)->first();

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
                'store_name' => $user->outlet ? $user->outlet->name : $user->store_name,
                'phone' => $user->phone,
                'permissions' => $user->permissions,
                'outlet_id' => $user->outlet_id,
                'outlet' => $user->outlet ? [
                    'id' => $user->outlet->id,
                    'code' => $user->outlet->code,
                    'name' => $user->outlet->name,
                    'address' => $user->outlet->address,
                    'phone' => $user->outlet->phone,
                    'status' => $user->outlet->status,
                ] : null,
            ],
        ]);
    }

    /**
     * Dashboard Summary Stats (100% Parity with Web Dashboard)
     */
    public function dashboard(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) {
            return response()->json([
                'success' => false,
                'is_auth_error' => true,
                'message' => 'Sesi login tidak valid atau telah berakhir. Silakan login kembali.',
            ], 401);
        }

        try {
            $startDate = $request->query('start_date', date('Y-m-01'));
            $endDate = $request->query('end_date', date('Y-m-d'));

            // Multi-Outlet Filter & Scoping
            $outlets = Outlet::where('status', 'active')->orderBy('name')->get();
            $outletId = $request->query('outlet_id');
            if ($user->isToko()) {
                $outletId = $user->outlet_id;
            }

            // Financial report for period (using optimized SQL)
            $pl = $this->reportService->getProfitAndLoss($startDate, $endDate, $outletId ? (int)$outletId : null);

            // Core KPI Cards (fast pure SQL aggregates)
            $totalPersediaan = (float) DB::table('products')->selectRaw('COALESCE(SUM(stock * hpp), 0) as val')->value('val');
            $totalHutang = (float) Purchase::where('status', 'BELUM LUNAS')->sum('remaining_debt');
            $totalPiutang = (float) Sale::when($outletId, fn($q) => $q->where('outlet_id', $outletId))
                ->where('status', 'BELUM LUNAS')
                ->sum('remaining_receivable');

            $totalKasBank = (float) Account::where('group', 'AKTIVA')
                ->whereIn('code', ['1-1110', '1-1111', '1-1112', '1-1113', '1-1120', '1-1121', '1-1122', '1-1123', '1-1130', '1-1131', '1-1190'])
                ->sum('current_balance');

            $salesQuery = Sale::when($outletId, fn($q) => $q->where('outlet_id', $outletId))
                ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"]);

            $salesCount = (clone $salesQuery)->count();
            $retailSalesCount = (clone $salesQuery)->where('sale_type', 'retail')->count();
            $grosirSalesCount = (clone $salesQuery)->where('sale_type', 'grosir')->count();

            // Top 10 Best-selling items
            $topProducts = SaleItem::select('product_id', DB::raw('SUM(qty) as total_sold'))
                ->whereHas('sale', function ($q) use ($startDate, $endDate, $outletId) {
                    $q->when($outletId, fn($sq) => $sq->where('outlet_id', $outletId))
                      ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"]);
                })
                ->groupBy('product_id')
                ->orderByDesc('total_sold')
                ->limit(10)
                ->with('product')
                ->get();

            // Target Profit for current month
            $currentYear = (int) date('Y', strtotime($startDate));
            $currentMonth = (int) date('m', strtotime($startDate));
            $target = MonthlyTarget::where('year', $currentYear)->where('month', $currentMonth)->first();
            $targetProfit = $target ? (float)$target->target_profit : 15000000.0;
            $realizedProfit = (float)($pl['net_profit'] ?? 0);
            $remainingTarget = max(0, $targetProfit - $realizedProfit);
            $progressPct = $targetProfit > 0 ? min(100, round(($realizedProfit / $targetProfit) * 100, 1)) : 0;

            // Daily trend data for Chart
            $dailySales = Sale::select(DB::raw("DATE(date) as day"), DB::raw("SUM(total) as revenue"))
                ->when($outletId, fn($q) => $q->where('outlet_id', $outletId))
                ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('revenue', 'day')
                ->toArray();
            $dailySalesObj = empty($dailySales) ? (object)[] : $dailySales;

            // Accounts list for cash & bank breakdown
            $cashAccounts = Account::whereIn('code', ['1-1110', '1-1111', '1-1112', '1-1113', '1-1120', '1-1131'])
                ->get()
                ->map(function ($acc) {
                    return [
                        'id' => $acc->id,
                        'code' => $acc->code,
                        'name' => $acc->name,
                        'current_balance' => (float)$acc->current_balance,
                    ];
                });

            $pendingTransfers = AgentTransfer::where('status', 'pending')
                ->when($outletId, fn($q) => $q->where('outlet_id', $outletId))
                ->count();
            $totalProducts = Product::count();

            $recentTransfers = AgentTransfer::with(['user', 'processedBy', 'outlet'])
                ->when($outletId, fn($q) => $q->where('outlet_id', $outletId))
                ->latest()
                ->take(5)
                ->get();

            // Top Outlet Performance comparison for Admin
            $outletPerformances = Outlet::withSum(['sales' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"]);
            }], 'total')
            ->withCount(['sales' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"]);
            }])
            ->get()
            ->sortByDesc('sales_sum_total')
            ->values();

            return response()->json([
                'success' => true,
                'user' => $user,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'totalPersediaan' => (float)$totalPersediaan,
                'totalHutang' => (float)$totalHutang,
                'totalPiutang' => (float)$totalPiutang,
                'totalKasBank' => (float)$totalKasBank,
                'pl' => $pl,
                'salesCount' => $salesCount,
                'retailSalesCount' => $retailSalesCount,
                'grosirSalesCount' => $grosirSalesCount,
                'targetProfit' => (float)$targetProfit,
                'realizedProfit' => (float)$realizedProfit,
                'remainingTarget' => (float)$remainingTarget,
                'progressPct' => (float)$progressPct,
                'cashAccounts' => $cashAccounts,
                'topProducts' => $topProducts,
                'dailySales' => $dailySalesObj,
                'pending_transfers' => $pendingTransfers,
                'total_products' => $totalProducts,
                'recent_transfers' => $recentTransfers,
                'outlets' => $outlets,
                'selected_outlet_id' => $outletId,
                'outlet_performances' => $outletPerformances,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses data dashboard: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Polling endpoint for background notifications (WorkManager & Local polling)
     */
    public function pollNotifications(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) {
            return response()->json([
                'success' => false,
                'is_auth_error' => true,
                'message' => 'Sesi login tidak valid atau telah berakhir. Silakan login kembali.',
            ], 401);
        }

        $pendingTransfersCount = AgentTransfer::where('status', 'pending')->count();
        $latestPending = AgentTransfer::where('status', 'pending')->with('user')->latest()->first();

        // Check if there is an approved or rejected transfer recently for the user
        $myRecentUpdated = null;
        if (!$user->isAdmin()) {
            $myRecentUpdated = AgentTransfer::where('user_id', $user->id)
                ->whereIn('status', ['approved', 'rejected'])
                ->where('updated_at', '>=', now()->subMinutes(15))
                ->latest('updated_at')
                ->first();
        }

        return response()->json([
            'success' => true,
            'pending_transfers_count' => $pendingTransfersCount,
            'latest_pending' => $latestPending,
            'my_recent_updated' => $myRecentUpdated,
        ]);
    }

    // ==========================================
    // 1. MASTER DATA CRUD ENDPOINTS
    // ==========================================

    public function products(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $search = $request->query('q') ?? $request->query('search');
        $type = $request->query('type');
        $brand = $request->query('brand');

        $query = Product::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        if ($type && $type !== 'ALL' && $type !== 'SEMUA') {
            $query->where('type', $type);
        }

        if ($brand && $brand !== 'ALL') {
            $query->where('brand', $brand);
        }

        $products = $query->orderBy('name')->get();
        $categories = Category::whereIn('type', ['physical_type', 'physical_brand'])->orderBy('type')->orderBy('name')->get();
        $types = Category::where('type', 'physical_type')->pluck('name');
        $brands = Category::where('type', 'physical_brand')->pluck('name');

        return response()->json([
            'success' => true,
            'data' => $products,
            'categories' => $categories,
            'types' => $types,
            'brands' => $brands,
        ]);
    }

    public function storeProduct(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'item_code' => 'required|string|unique:products,item_code',
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'brand' => 'nullable|string',
            'stock' => 'required|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'hpp' => 'required|numeric|min:0',
            'retail_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:Masih Dijual,Tidak Dijual',
        ]);

        if (empty($data['wholesale_price'])) {
            $data['wholesale_price'] = $data['retail_price'];
        }

        // Auto create category if not exists
        if (!empty($data['type'])) {
            Category::firstOrCreate(['type' => 'physical_type', 'name' => strtoupper(trim($data['type']))]);
        }
        if (!empty($data['brand'])) {
            Category::firstOrCreate(['type' => 'physical_brand', 'name' => strtoupper(trim($data['brand']))]);
        }

        $product = Product::create($data);
        return response()->json(['success' => true, 'message' => 'Produk berhasil ditambahkan!', 'data' => $product]);
    }

    public function updateProduct(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $product = Product::findOrFail($id);

        $data = $request->validate([
            'item_code' => 'required|string|unique:products,item_code,' . $product->id,
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'brand' => 'nullable|string',
            'stock' => 'required|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'hpp' => 'required|numeric|min:0',
            'retail_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:Masih Dijual,Tidak Dijual',
        ]);

        if (empty($data['wholesale_price'])) {
            $data['wholesale_price'] = $data['retail_price'];
        }

        if (!empty($data['type'])) {
            Category::firstOrCreate(['type' => 'physical_type', 'name' => strtoupper(trim($data['type']))]);
        }
        if (!empty($data['brand'])) {
            Category::firstOrCreate(['type' => 'physical_brand', 'name' => strtoupper(trim($data['brand']))]);
        }

        // FL Toko cannot edit stock physical quantity unless granted permission
        if (!$user->isSuperAdmin() && (!$user->hasPermission('edit_stock') || $user->isToko())) {
            $data['stock'] = $product->stock;
        }

        $product->update($data);
        return response()->json(['success' => true, 'message' => "Item [{$product->name}] berhasil diperbarui!", 'data' => $product]);
    }

    public function destroyProduct(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $product = Product::findOrFail($id);
        $name = $product->name;

        if ($product->saleItems()->exists() || $product->purchaseItems()->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Item [{$name}] tidak dapat dihapus karena sudah memiliki riwayat transaksi! Ubah status menjadi 'Tidak Dijual' jika ingin menonaktifkan.",
            ], 422);
        }

        $product->delete();
        return response()->json(['success' => true, 'message' => "Item [{$name}] berhasil dihapus!"]);
    }

    public function multiProducts(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $search = $request->query('q') ?? $request->query('search');
        $trxType = $request->query('trx_type');
        $category = $request->query('category');

        $query = DigitalProduct::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('product_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($trxType && $trxType !== 'ALL' && $trxType !== 'SEMUA') {
            $query->where('trx_type', $trxType);
        }

        if ($category && $category !== 'ALL' && $category !== 'SEMUA') {
            $query->where('category', $category);
        }

        $products = $query->orderBy('trx_type')->orderBy('name')->get();
        $trxTypes = Category::where('type', 'digital_type')->pluck('name');
        $categories = Category::where('type', 'digital_category')->pluck('name');

        return response()->json([
            'success' => true,
            'data' => $products,
            'trx_types' => $trxTypes,
            'categories' => $categories,
        ]);
    }

    public function storeMultiProduct(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'product_code' => 'required|string|unique:digital_products,product_code',
            'name' => 'required|string',
            'trx_type' => 'required|string',
            'category' => 'required|string',
            'hpp' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'status' => 'required|string|in:OPEN,CLOSE',
        ]);

        if (!empty($data['trx_type'])) {
            Category::firstOrCreate(['type' => 'digital_type', 'name' => strtoupper(trim($data['trx_type']))]);
        }
        if (!empty($data['category'])) {
            Category::firstOrCreate(['type' => 'digital_category', 'name' => strtoupper(trim($data['category']))]);
        }

        $product = DigitalProduct::create($data);
        return response()->json(['success' => true, 'message' => 'Produk multi berhasil ditambahkan!', 'data' => $product]);
    }

    public function updateMultiProduct(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $product = DigitalProduct::findOrFail($id);

        $data = $request->validate([
            'product_code' => 'required|string|unique:digital_products,product_code,' . $product->id,
            'name' => 'required|string',
            'trx_type' => 'required|string',
            'category' => 'required|string',
            'hpp' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'status' => 'required|string|in:OPEN,CLOSE',
        ]);

        if (!empty($data['trx_type'])) {
            Category::firstOrCreate(['type' => 'digital_type', 'name' => strtoupper(trim($data['trx_type']))]);
        }
        if (!empty($data['category'])) {
            Category::firstOrCreate(['type' => 'digital_category', 'name' => strtoupper(trim($data['category']))]);
        }

        $product->update($data);
        return response()->json(['success' => true, 'message' => "Produk Multi [{$product->name}] berhasil diperbarui!", 'data' => $product]);
    }

    public function destroyMultiProduct(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $product = DigitalProduct::findOrFail($id);
        $name = $product->name;

        if ($product->sales()->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Produk [{$name}] tidak dapat dihapus karena sudah memiliki riwayat penjualan elektrik! Ubah status menjadi 'CLOSE' jika ingin menonaktifkan.",
            ], 422);
        }

        $product->delete();
        return response()->json(['success' => true, 'message' => "Produk Multi [{$name}] berhasil dihapus!"]);
    }

    public function customers(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $customers = Customer::orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $customers]);
    }

    public function storeCustomer(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|string|in:Aktif,Nonaktif',
        ]);

        $customer = Customer::create($data);
        return response()->json(['success' => true, 'message' => 'Pelanggan berhasil disimpan!', 'data' => $customer]);
    }

    public function updateCustomer(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $customer = Customer::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|string|in:Aktif,Nonaktif',
        ]);

        $customer->update($data);
        return response()->json(['success' => true, 'message' => "Pelanggan [{$customer->name}] berhasil diperbarui!", 'data' => $customer]);
    }

    public function destroyCustomer(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $customer = Customer::findOrFail($id);
        $name = $customer->name;

        if ($customer->sales()->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Pelanggan [{$name}] tidak dapat dihapus karena sudah memiliki riwayat transaksi! Ubah status menjadi 'Nonaktif'.",
            ], 422);
        }

        $customer->delete();
        return response()->json(['success' => true, 'message' => "Pelanggan [{$name}] berhasil dihapus!"]);
    }

    public function suppliers(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $suppliers = Supplier::orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $suppliers]);
    }

    public function storeSupplier(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'account_name' => 'nullable|string',
        ]);

        $supplier = Supplier::create($data);
        return response()->json(['success' => true, 'message' => 'Supplier berhasil disimpan!', 'data' => $supplier]);
    }

    public function updateSupplier(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $supplier = Supplier::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'account_name' => 'nullable|string',
        ]);

        $supplier->update($data);
        return response()->json(['success' => true, 'message' => "Supplier [{$supplier->name}] berhasil diperbarui!", 'data' => $supplier]);
    }

    public function destroySupplier(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $supplier = Supplier::findOrFail($id);
        $name = $supplier->name;

        if ($supplier->purchases()->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Supplier [{$name}] tidak dapat dihapus karena sudah memiliki riwayat faktur pembelian!",
            ], 422);
        }

        $supplier->delete();
        return response()->json(['success' => true, 'message' => "Supplier [{$name}] berhasil dihapus!"]);
    }

    public function accounts(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $accounts = Account::orderBy('code')->get();
        return response()->json(['success' => true, 'data' => $accounts]);
    }

    public function storeAccount(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'code' => 'required|string|unique:accounts,code|max:20',
            'name' => 'required|string|max:100',
            'group' => 'required|string|in:AKTIVA,KEWAJIBAN,MODAL,PENDAPATAN,HPP,BIAYA,PENDAPATAN LAIN,BIAYA LAIN',
            'type' => 'required|string|in:H,D,K',
            'initial_balance' => 'nullable|numeric|min:0',
        ]);

        $initial = (float) ($data['initial_balance'] ?? 0);
        $account = Account::create([
            'code' => strtoupper($data['code']),
            'name' => strtoupper($data['name']),
            'group' => $data['group'],
            'type' => $data['type'],
            'initial_balance' => $initial,
            'current_balance' => $initial,
            'is_system_locked' => false,
        ]);

        return response()->json(['success' => true, 'message' => 'Akun COA berhasil dibuat!', 'data' => $account]);
    }

    public function updateAccount(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $account = Account::findOrFail($id);
        if ($account->is_system_locked) {
            return response()->json(['success' => false, 'message' => 'Akun sistem tidak dapat diubah.'], 403);
        }

        $data = $request->validate([
            'code' => 'required|string|max:20|unique:accounts,code,' . $account->id,
            'name' => 'required|string|max:100',
            'group' => 'required|string|in:AKTIVA,KEWAJIBAN,MODAL,PENDAPATAN,HPP,BIAYA,PENDAPATAN LAIN,BIAYA LAIN',
            'type' => 'required|string|in:H,D,K',
            'initial_balance' => 'nullable|numeric|min:0',
        ]);

        $account->update([
            'code' => strtoupper($data['code']),
            'name' => strtoupper($data['name']),
            'group' => $data['group'],
            'type' => $data['type'],
            'initial_balance' => (float) ($data['initial_balance'] ?? 0),
        ]);

        return response()->json(['success' => true, 'message' => "Akun [{$account->name}] berhasil diperbarui!", 'data' => $account]);
    }

    public function destroyAccount(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $account = Account::findOrFail($id);
        $name = $account->name;

        if ($account->is_system_locked) {
            return response()->json(['success' => false, 'message' => 'Akun sistem terkunci dan tidak dapat dihapus!'], 403);
        }

        if ($account->journalLines()->exists()) {
            return response()->json(['success' => false, 'message' => "Akun [{$name}] tidak dapat dihapus karena sudah memiliki riwayat mutasi jurnal akuntansi!"], 422);
        }

        $account->delete();
        return response()->json(['success' => true, 'message' => "Akun [{$name}] berhasil dihapus!"]);
    }

    // ==========================================
    // 2. POS & PENJUALAN
    // ==========================================

    public function posData(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();
        $customers = Customer::where('status', 'Aktif')->orderBy('name')->get();
        $cashAndBankAccounts = Account::where('group', 'AKTIVA')
            ->where('type', 'D')
            ->where(function ($q) {
                $q->where('code', 'LIKE', '1-111%')
                  ->orWhere('code', 'LIKE', '1-112%')
                  ->orWhere('name', 'LIKE', '%KAS%')
                  ->orWhere('name', 'LIKE', '%SALDO%')
                  ->orWhere('name', 'LIKE', '%BRANGKAS%');
            })
            ->where('code', 'NOT LIKE', '1-2%') // Exclude Persediaan Barang
            ->orderBy('code')
            ->get();
        $setting = StoreSetting::first();

        return response()->json([
            'success' => true,
            'products' => $products,
            'customers' => $customers,
            'accounts' => $cashAndBankAccounts,
            'all_accounts' => Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->orderBy('code')->get(),
            'setting' => $setting,
            'outlet' => $user->outlet,
        ]);
    }

    public function posCheckout(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'sale_type' => 'required|in:retail,grosir',
            'customer_id' => 'nullable|exists:customers,id',
            'discount' => 'nullable|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'account_id' => 'nullable|exists:accounts,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.price' => 'nullable|numeric|min:0',
        ]);

        try {
            $data['outlet_id'] = $user->outlet_id;
            $data['user_id'] = $user->id;
            $sale = $this->posService->checkoutPos($data);
            $sale->load(['items.product', 'customer', 'outlet', 'user']);
            return response()->json([
                'success' => true,
                'message' => 'Transaksi POS berhasil disimpan!',
                'sale' => $sale,
                'setting' => StoreSetting::first(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ==========================================
    // 3. DIGITAL PULSA & PPOB
    // ==========================================

    public function digitalData(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $products = DigitalProduct::where('status', 'OPEN')->orderBy('name')->get();
        $depositAccounts = Account::whereIn('code', ['1-1131', '1-1113', '1-1120'])->get();
        $cashAccounts = Account::whereIn('code', ['1-1110', '1-1112'])->get();
        $saldoMulti = Account::where('code', '1-1131')->value('current_balance') ?? 0;

        return response()->json([
            'success' => true,
            'products' => $products,
            'deposit_accounts' => $depositAccounts,
            'cash_accounts' => $cashAccounts,
            'saldo_multi' => (float) $saldoMulti,
        ]);
    }

    public function digitalCheckout(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'digital_product_id' => 'required|exists:digital_products,id',
            'customer_number' => 'required|string',
            'deposit_account_id' => 'required|exists:accounts,id',
            'cash_account_id' => 'required|exists:accounts,id',
            'selling_price' => 'nullable|numeric|min:0',
            'hpp' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $digitalSale = $this->posService->processDigitalSale($data);
            $digitalSale->load(['digitalProduct', 'depositAccount', 'cashAccount']);
            return response()->json([
                'success' => true,
                'message' => 'Transaksi Pulsa / Elektrik berhasil!',
                'digital_sale' => $digitalSale,
                'setting' => StoreSetting::first(),
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function posWithdraw(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'source_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1000',
            'admin_fee' => 'nullable|numeric|min:0',
            'customer_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $fee = (float) ($data['admin_fee'] ?? 0);
        $amount = (float) $data['amount'];
        $customerName = $data['customer_name'] ?? 'Pelanggan';

        $bankAcc = Account::where('code', '1-1113')->first() // default SALDO BCA
            ?: Account::where('code', '1-1111')->first() // CASH TRANSFER
            ?: Account::find($data['source_account_id']);

        try {
            $trxNumber = $this->posService->generateTransactionNumber('TT');

            $trx = CashTransaction::create([
                'transaction_number' => $trxNumber,
                'type' => 'TRANSFER',
                'date' => now(),
                'debit_account_id' => $bankAcc->id,
                'credit_account_id' => $data['source_account_id'],
                'amount' => $amount,
                'admin_fee' => $fee,
                'notes' => "Tarik Tunai [{$customerName}] - " . ($data['notes'] ?? 'POS Mobile'),
            ]);

            $this->accountingService->recordCashTransaction($trx);

            return response()->json([
                'success' => true,
                'message' => 'Tarik Tunai Rp ' . number_format($amount, 0, ',', '.') . ' berhasil!',
                'transaction' => $trx,
                'setting' => StoreSetting::first(),
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function topupMulti(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'source_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1000',
            'notes' => 'nullable|string',
        ]);

        $multiAccount = Account::where('code', '1-1131')->firstOrFail();

        try {
            $trxNumber = $this->posService->generateTransactionNumber('TP');

            $trx = CashTransaction::create([
                'transaction_number' => $trxNumber,
                'type' => 'OUT',
                'date' => now(),
                'debit_account_id' => $multiAccount->id,
                'credit_account_id' => $data['source_account_id'],
                'amount' => $data['amount'],
                'admin_fee' => 0,
                'notes' => $data['notes'] ?? 'Top Up Saldo Multi Server',
            ]);

            $this->accountingService->recordCashTransaction($trx);

            return response()->json([
                'success' => true,
                'message' => 'Top Up Saldo Multi sebesar Rp ' . number_format($data['amount'], 0, ',', '.') . ' berhasil!',
                'transaction' => $trx,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ==========================================
    // 4. PIUTANG & RETUR PENJUALAN
    // ==========================================

    public function receivables(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $unpaidSales = Sale::where('status', 'BELUM LUNAS')->with('customer')->orderByDesc('date')->get();
        $payments = ReceivablePayment::with(['customer', 'account'])->latest()->take(30)->get();
        $accounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();

        return response()->json([
            'success' => true,
            'unpaid_sales' => $unpaidSales,
            'payments' => $payments,
            'accounts' => $accounts,
        ]);
    }

    public function storeReceivablePayment(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'date' => 'required|date',
            'sale_id' => 'required|exists:sales,id',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->posService->processReceivablePayment($data);
            return response()->json(['success' => true, 'message' => 'Pembayaran piutang berhasil dicatat!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function returns(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $returns = SaleReturn::with(['sale', 'customer', 'product', 'account'])->latest()->take(30)->get();
        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();
        $accounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();
        $customers = Customer::where('status', 'Aktif')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'returns' => $returns,
            'products' => $products,
            'accounts' => $accounts,
            'customers' => $customers,
        ]);
    }

    public function storeReturn(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'date' => 'required|date',
            'sale_id' => 'nullable|exists:sales,id',
            'customer_id' => 'nullable|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'refund_amount' => 'required|numeric|min:0',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->posService->processSaleReturn($data);
            return response()->json(['success' => true, 'message' => 'Retur penjualan berhasil dicatat!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ==========================================
    // 5. PEMBELIAN & PEMBAYARAN HUTANG
    // ==========================================

    public function purchases(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $purchases = Purchase::with(['supplier', 'items.product'])->latest()->take(30)->get();
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();
        $accounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();
        $debts = Purchase::where('status', 'BELUM LUNAS')->with('supplier')->get();

        return response()->json([
            'success' => true,
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'products' => $products,
            'accounts' => $accounts,
            'debts' => $debts,
        ]);
    }

    public function storePurchase(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'payment_method' => 'required|string',
            'account_id' => 'nullable|exists:accounts,id',
            'paid_amount' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.buy_price' => 'required|numeric|min:0',
        ]);

        try {
            $purchase = $this->posService->processPurchase($data);
            return response()->json(['success' => true, 'message' => 'Faktur pembelian berhasil disimpan!', 'data' => $purchase]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function storeDebtPayment(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'date' => 'required|date',
            'purchase_id' => 'required|exists:purchases,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:1',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->posService->processDebtPayment($data);
            return response()->json(['success' => true, 'message' => 'Pembayaran hutang berhasil dicatat!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ==========================================
    // 6. PERSEDIAAN STOK (INVENTORY)
    // ==========================================

    public function inventoryAdjustments(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $adjustments = InventoryAdjustment::with('product')->latest()->take(50)->get();
        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'adjustments' => $adjustments,
            'products' => $products,
        ]);
    }

    public function storeInventoryAdjustment(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'type' => 'required|in:IN,OUT',
            'cost_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->posService->processInventoryAdjustment($data);
            return response()->json(['success' => true, 'message' => 'Penyesuaian stok berhasil disimpan!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ==========================================
    // 7. TRANSFER ANTAR AGEN & BANK
    // ==========================================

    public function transfers(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $query = AgentTransfer::with(['user', 'processedBy', 'approvedBy', 'sourceAccount', 'outlet'])->latest();

        $selectedOutletId = $request->query('outlet_id');
        if ($user->isToko()) {
            if ($user->outlet_id) {
                $query->where('outlet_id', $user->outlet_id);
            } else {
                $query->where('user_id', $user->id);
            }
        } elseif ($selectedOutletId) {
            $query->where('outlet_id', $selectedOutletId);
        }

        $status = $request->query('status');
        if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        $transfers = $query->take(50)->get();
        $transfers->transform(function ($t) {
            $t->proof_image_url = $t->proof_image ? asset('storage/' . $t->proof_image) : null;
            return $t;
        });

        $bankAccounts = Account::where('group', 'like', '%AKTIVA%')->where('type', 'D')->take(15)->get();
        $outlets = Outlet::where('status', 'active')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $transfers,
            'bank_accounts' => $bankAccounts,
            'outlets' => $outlets,
            'selected_outlet_id' => $selectedOutletId,
        ]);
    }

    public function storeTransfer(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'bank_name' => 'required|string|max:50',
            'account_number' => 'required|string|max:50',
            'account_holder' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1000',
            'admin_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $reference = 'TF-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        $adminFee = (float)($request->admin_fee ?? 2500);
        $totalAmount = (float)$request->amount + $adminFee;

        $transfer = AgentTransfer::create([
            'reference_no' => $reference,
            'user_id' => $user->id,
            'outlet_id' => $user->outlet_id,
            'store_name' => $user->outlet ? $user->outlet->name : ($user->store_name ?? 'Kasir Cabang'),
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

    public function approveTransfer(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || (!$user->isAdmin() && !$user->isSuperAdmin())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $transfer = AgentTransfer::findOrFail($id);

        $request->validate([
            'source_account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string|max:255',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof_image')) {
            $file = $request->file('proof_image');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $allowedExts)) {
                return response()->json(['error' => 'Format file bukti harus berupa gambar (JPG, PNG, WEBP).'], 422);
            }
            if ($file->getSize() > 5 * 1024 * 1024) {
                return response()->json(['error' => 'Ukuran file gambar maksimal 5MB.'], 422);
            }

            $year = date('Y');
            $month = date('m');
            $filename = 'proof_' . time() . '_' . uniqid() . '.' . $ext;
            $destinationDir = storage_path("app/public/transfers/proofs/{$year}/{$month}");
            if (!file_exists($destinationDir)) {
                mkdir($destinationDir, 0755, true);
            }
            $file->move($destinationDir, $filename);
            $proofPath = "transfers/proofs/{$year}/{$month}/{$filename}";
        }

        $sourceAccount = Account::findOrFail($request->source_account_id);

        $transfer->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => Carbon::now(),
            'processed_by' => $user->id,
            'processed_at' => Carbon::now(),
            'source_account_id' => $sourceAccount->id,
            'proof_image' => $proofPath ?? $transfer->proof_image,
            'notes' => $request->notes ?? $transfer->notes,
        ]);

        $sourceAccount->current_balance -= $transfer->total_amount;
        $sourceAccount->save();

        $transfer->proof_image_url = $transfer->proof_image ? asset('storage/' . $transfer->proof_image) : null;

        return response()->json([
            'success' => true,
            'message' => 'Transfer berhasil disetujui' . ($proofPath ? ' & bukti struk tersimpan!' : '!'),
            'data' => $transfer,
        ]);
    }

    public function rejectTransfer(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || (!$user->isAdmin() && !$user->isSuperAdmin())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $transfer = AgentTransfer::findOrFail($id);
        $request->validate([
            'notes' => 'required|string|max:255',
        ]);

        $transfer->update([
            'status' => 'rejected',
            'processed_by' => $user->id,
            'notes' => $request->notes,
            'processed_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan transfer telah ditolak.',
            'data' => $transfer,
        ]);
    }

    public function checkPendingTransfers(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $query = AgentTransfer::where('status', 'pending');
        $selectedOutletId = $request->query('outlet_id');
        if ($user->isToko()) {
            if ($user->outlet_id) {
                $query->where('outlet_id', $user->outlet_id);
            } else {
                $query->where('user_id', $user->id);
            }
        } elseif ($selectedOutletId) {
            $query->where('outlet_id', $selectedOutletId);
        }

        $pendingCount = (clone $query)->count();
        $latest = (clone $query)->with(['user', 'outlet'])->latest()->first();

        return response()->json([
            'success' => true,
            'count' => $pendingCount,
            'latest' => $latest ? [
                'id' => $latest->id,
                'ref' => $latest->reference_no,
                'user' => $latest->user?->name ?? 'Kasir Agen',
                'store' => $latest->outlet ? $latest->outlet->name : ($latest->store_name ?? 'Kasir Cabang'),
                'bank' => $latest->bank_name,
                'amount' => (float) $latest->amount,
                'time' => $latest->created_at->diffForHumans(),
            ] : null,
        ]);
    }

    // ==========================================
    // 8. AKUNTANSI & KAS
    // ==========================================

    public function cashTransactions(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $type = strtoupper((string) $request->query('type', ''));
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = CashTransaction::with(['debitAccount', 'creditAccount'])->latest('date');

        if (!empty($type) && in_array($type, ['IN', 'OUT', 'TRANSFER'])) {
            $query->where('type', $type);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('date', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"]);
        } elseif ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        $transactions = $query->take(100)->get()->map(function ($trx) {
            return [
                'id' => $trx->id,
                'transaction_number' => $trx->transaction_number,
                'type' => strtolower($trx->type),
                'amount' => (float) $trx->amount,
                'admin_fee' => (float) $trx->admin_fee,
                'description' => $trx->notes,
                'transaction_date' => $trx->date ? $trx->date->format('Y-m-d H:i') : '',
                'debit_account' => $trx->debitAccount ? [
                    'id' => $trx->debitAccount->id,
                    'code' => $trx->debitAccount->code,
                    'name' => $trx->debitAccount->name,
                ] : null,
                'credit_account' => $trx->creditAccount ? [
                    'id' => $trx->creditAccount->id,
                    'code' => $trx->creditAccount->code,
                    'name' => $trx->creditAccount->name,
                ] : null,
            ];
        });

        // Expense categories for cash out & income categories for cash in
        $expenseAccounts = Account::whereIn('group', ['BIAYA', 'BIAYA LAIN', 'KEWAJIBAN'])
            ->where('type', 'D')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $incomeAccounts = Account::whereIn('group', ['PENDAPATAN', 'PENDAPATAN LAIN', 'MODAL'])
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $cashAccounts = Account::where('group', 'AKTIVA')
            ->where('type', 'D')
            ->where(function ($q) {
                $q->where('code', 'LIKE', '1-111%')
                  ->orWhere('name', 'LIKE', '%KAS%')
                  ->orWhere('name', 'LIKE', '%SALDO%')
                  ->orWhere('name', 'LIKE', '%BRANGKAS%');
            })
            ->where('code', 'NOT LIKE', '1-2%')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'current_balance']);

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'expense_accounts' => $expenseAccounts,
            'income_accounts' => $incomeAccounts,
            'cash_accounts' => $cashAccounts,
        ]);
    }

    public function storeCashTransaction(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'type' => 'required|in:in,out,transfer,IN,OUT,TRANSFER',
            'account_id' => 'required|exists:accounts,id', // Kas sumber / penampung
            'opposite_account_id' => 'nullable|exists:accounts,id', // Akun lawan (kategori beban/pendapatan)
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'transaction_date' => 'nullable|date',
        ]);

        $type = strtoupper($data['type']);
        $prefix = $type === 'IN' ? 'KM' : ($type === 'OUT' ? 'KK' : 'KT');
        $trxNumber = $this->posService->generateTransactionNumber($prefix);

        // Map debit and credit accounts based on transaction type:
        // IN: Debit = Kas/Bank (account_id), Credit = Pendapatan/Modal (opposite_account_id)
        // OUT: Debit = Beban/Pengeluaran (opposite_account_id), Credit = Kas/Bank (account_id)
        $debitAccountId = $type === 'IN' ? $data['account_id'] : ($data['opposite_account_id'] ?? Account::where('code', '6-2300')->value('id') ?? $data['account_id']);
        $creditAccountId = $type === 'IN' ? ($data['opposite_account_id'] ?? Account::where('code', '4-2000')->value('id') ?? $data['account_id']) : $data['account_id'];

        try {
            $trx = CashTransaction::create([
                'transaction_number' => $trxNumber,
                'type' => $type,
                'date' => $data['transaction_date'] ?? now(),
                'debit_account_id' => $debitAccountId,
                'credit_account_id' => $creditAccountId,
                'amount' => $data['amount'],
                'admin_fee' => 0,
                'notes' => $data['description'],
            ]);

            $this->accountingService->recordCashTransaction($trx);

            return response()->json([
                'success' => true,
                'message' => ($type === 'IN' ? 'Kas Masuk' : 'Kas Keluar') . ' berhasil dicatat!',
                'data' => $trx,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ==========================================
    // 9. LAPORAN KEUANGAN LENGKAP & CEPAT
    // ==========================================

    public function financialReports(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-d'));

        $profitLoss = $this->reportService->getProfitAndLoss($startDate, $endDate);
        $balanceSheet = $this->reportService->getBalanceSheet($endDate);

        $salesSummary = Sale::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])->sum('total');
        $purchaseSummary = Purchase::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])->sum('total');

        return response()->json([
            'success' => true,
            'profit_loss' => $profitLoss,
            'balance_sheet' => $balanceSheet,
            'sales_summary' => (float) $salesSummary,
            'purchase_summary' => (float) $purchaseSummary,
        ]);
    }

    public function reportSales(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-d'));
        $saleType = $request->query('sale_type', 'all');

        $unified = collect();

        // 1. Penjualan Fisik (Retail / Grosir)
        if ($saleType !== 'digital') {
            $query = Sale::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
                ->with(['customer', 'items.product', 'outlet', 'user']);

            if ($saleType !== 'all') {
                $query->where('sale_type', $saleType);
            }

            $sales = $query->orderByDesc('date')->get();
            foreach ($sales as $s) {
                $unified->push([
                    'id' => $s->id,
                    'is_digital' => false,
                    'date' => $s->date ? $s->date->format('Y-m-d H:i:s') : null,
                    'invoice_number' => $s->invoice_number,
                    'sale_type' => $s->sale_type,
                    'customer' => $s->customer,
                    'customer_name' => $s->customer->name ?? 'UMUM',
                    'items_qty' => (float) $s->items->sum('qty'),
                    'subtotal' => (float) $s->subtotal,
                    'discount' => (float) $s->discount,
                    'total' => (float) $s->total,
                    'paid_amount' => (float) $s->paid_amount,
                    'remaining_receivable' => (float) $s->remaining_receivable,
                    'payment_method' => $s->payment_method,
                    'status' => $s->status,
                    'items' => $s->items,
                    'outlet' => $s->outlet,
                    'user' => $s->user,
                ]);
            }
        }

        // 2. Penjualan Elektrik / Multi Pulsa (Digital)
        if ($saleType === 'all' || $saleType === 'digital') {
            $digitalSales = DigitalSale::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
                ->with(['digitalProduct', 'depositAccount', 'cashAccount'])
                ->orderByDesc('date')
                ->get();

            foreach ($digitalSales as $ds) {
                $productName = $ds->digitalProduct->name ?? 'Pulsa / Elektrik';
                $unified->push([
                    'id' => $ds->id,
                    'is_digital' => true,
                    'date' => $ds->date ? $ds->date->format('Y-m-d H:i:s') : null,
                    'invoice_number' => $ds->transaction_number,
                    'sale_type' => 'digital',
                    'customer' => ['name' => $productName . ' (' . $ds->customer_number . ')'],
                    'customer_name' => $productName . ' (' . $ds->customer_number . ')',
                    'items_qty' => 1.0,
                    'subtotal' => (float) $ds->selling_price,
                    'discount' => 0.0,
                    'total' => (float) $ds->selling_price,
                    'paid_amount' => (float) $ds->selling_price,
                    'remaining_receivable' => 0.0,
                    'payment_method' => 'TUNAI',
                    'status' => $ds->status,
                    'notes' => $ds->notes,
                    'digital_product' => $ds->digitalProduct,
                    'items' => [
                        [
                            'product' => ['name' => $productName],
                            'product_name' => $productName,
                            'qty' => 1.0,
                            'price' => (float) $ds->selling_price,
                            'subtotal' => (float) $ds->selling_price,
                        ]
                    ],
                ]);
            }
        }

        $sorted = $unified->sortByDesc('date')->values()->take(150);

        $totalQty = $sorted->sum('items_qty');
        $totalSubtotal = $sorted->sum('subtotal');
        $totalDiscount = $sorted->sum('discount');
        $totalFinal = $sorted->sum('total');
        $totalPaid = $sorted->sum('paid_amount');
        $totalReceivable = $sorted->sum('remaining_receivable');

        return response()->json([
            'success' => true,
            'data' => $sorted,
            'summary' => [
                'total_qty' => $totalQty,
                'total_subtotal' => (float) $totalSubtotal,
                'total_discount' => (float) $totalDiscount,
                'total_final' => (float) $totalFinal,
                'total_paid' => (float) $totalPaid,
                'total_receivable' => (float) $totalReceivable,
            ]
        ]);
    }

    public function reportPurchases(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-d'));

        $purchases = Purchase::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with(['supplier', 'items.product'])
            ->orderByDesc('date')
            ->take(100)
            ->get();

        $totalQty = 0;
        $totalSubtotal = 0;
        $totalDiscount = 0;
        $totalPaid = 0;
        $totalDebt = 0;

        foreach ($purchases as $p) {
            $totalQty += $p->items->sum('qty');
            $totalSubtotal += $p->subtotal;
            $totalDiscount += $p->discount;
            $totalPaid += $p->paid_amount;
            $totalDebt += $p->remaining_debt;
        }

        return response()->json([
            'success' => true,
            'data' => $purchases,
            'summary' => [
                'total_qty' => $totalQty,
                'total_subtotal' => (float) $totalSubtotal,
                'total_discount' => (float) $totalDiscount,
                'total_paid' => (float) $totalPaid,
                'total_debt' => (float) $totalDebt,
            ]
        ]);
    }

    public function reportCash(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-d'));

        $kasMasuk = CashTransaction::where('type', 'IN')
            ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with(['debitAccount', 'creditAccount'])
            ->orderByDesc('date')
            ->get();

        $kasKeluar = CashTransaction::where('type', 'OUT')
            ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with(['debitAccount', 'creditAccount'])
            ->orderByDesc('date')
            ->get();

        $kasTransfer = CashTransaction::where('type', 'TRANSFER')
            ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with(['debitAccount', 'creditAccount'])
            ->orderByDesc('date')
            ->get();

        return response()->json([
            'success' => true,
            'kas_masuk' => $kasMasuk,
            'kas_keluar' => $kasKeluar,
            'kas_transfer' => $kasTransfer,
            'summary' => [
                'total_masuk' => (float) $kasMasuk->sum('amount'),
                'total_keluar' => (float) $kasKeluar->sum('amount'),
                'total_transfer' => (float) $kasTransfer->sum('amount'),
            ]
        ]);
    }

    public function reportProfitLoss(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-d'));

        $pl = $this->reportService->getProfitAndLoss($startDate, $endDate);
        return response()->json(['success' => true, 'data' => $pl]);
    }

    public function reportBalanceSheet(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $asOfDate = $request->query('as_of_date', date('Y-m-d'));
        $bs = $this->reportService->getBalanceSheet($asOfDate);
        return response()->json(['success' => true, 'data' => $bs]);
    }

    public function reportDebtsReceivables(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $debts = Purchase::where('status', 'BELUM LUNAS')
            ->where('remaining_debt', '>', 0)
            ->with('supplier')
            ->orderByDesc('date')
            ->get();

        $receivables = Sale::where('status', 'BELUM LUNAS')
            ->where('remaining_receivable', '>', 0)
            ->with('customer')
            ->orderByDesc('date')
            ->get();

        return response()->json([
            'success' => true,
            'debts' => $debts,
            'total_debts' => (float) $debts->sum('remaining_debt'),
            'receivables' => $receivables,
            'total_receivables' => (float) $receivables->sum('remaining_receivable'),
        ]);
    }

    // ==========================================
    // 10. PENGATURAN, TUTUP BUKU & USERS
    // ==========================================

    public function users(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$user->isAdmin()) return response()->json(['error' => 'Unauthorized'], 403);

        $users = User::with('outlet')->orderByRaw("FIELD(role, 'super_admin', 'admin', 'toko')")->orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $users]);
    }

    public function storeUser(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$user->isAdmin()) return response()->json(['error' => 'Unauthorized'], 403);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:super_admin,admin,toko',
            'outlet_id' => 'nullable|exists:outlets,id',
            'store_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'permissions' => 'nullable',
        ]);

        $perms = is_array($request->permissions) ? $request->permissions : (is_string($request->permissions) ? json_decode($request->permissions, true) : []);
        if (empty($perms)) {
            $perms = $validated['role'] === 'toko' ? [
                'pos' => true,
                'digital' => true,
                'cash_withdrawal' => true,
                'transfer' => true,
                'master' => false,
                'edit_stock' => false,
                'multi_topup' => false,
                'purchase' => false,
                'accounting' => false,
                'manage_modal' => false,
                'view_final_balance' => false,
                'reports' => false,
                'settings' => false,
                'users' => false,
            ] : [
                'pos' => true,
                'digital' => true,
                'cash_withdrawal' => true,
                'transfer' => true,
                'master' => true,
                'edit_stock' => true,
                'multi_topup' => true,
                'purchase' => true,
                'accounting' => true,
                'manage_modal' => true,
                'view_final_balance' => true,
                'reports' => true,
                'settings' => true,
                'users' => $validated['role'] === 'super_admin',
            ];
        }

        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'outlet_id' => $validated['outlet_id'] ?? null,
            'store_name' => $validated['store_name'],
            'phone' => $validated['phone'],
            'is_active' => true,
            'permissions' => $perms,
        ]);

        return response()->json(['success' => true, 'message' => 'Pengguna berhasil dibuat!', 'data' => $newUser->load('outlet')]);
    }

    public function updateUser(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$user->isAdmin()) return response()->json(['error' => 'Unauthorized'], 403);

        $targetUser = User::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email,' . $targetUser->id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:super_admin,admin,toko',
            'outlet_id' => 'nullable|exists:outlets,id',
            'store_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'permissions' => 'nullable',
        ]);

        $targetUser->name = $validated['name'];
        $targetUser->email = $validated['email'];
        if (!empty($validated['password'])) {
            $targetUser->password = Hash::make($validated['password']);
        }
        $targetUser->role = $validated['role'];
        $targetUser->outlet_id = $validated['outlet_id'] ?? null;
        $targetUser->store_name = $validated['store_name'];
        $targetUser->phone = $validated['phone'];

        if ($request->has('permissions')) {
            $perms = is_array($request->permissions) ? $request->permissions : (is_string($request->permissions) ? json_decode($request->permissions, true) : null);
            if ($perms !== null) {
                $targetUser->permissions = $perms;
            }
        }

        $targetUser->save();

        return response()->json(['success' => true, 'message' => "Pengguna [{$targetUser->name}] berhasil diperbarui!", 'data' => $targetUser->load('outlet')]);
    }

    public function destroyUser(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$user->isAdmin()) return response()->json(['error' => 'Unauthorized'], 403);

        $targetUser = User::findOrFail($id);
        if ($targetUser->id === $user->id) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus akun Anda sendiri yang sedang login.'], 422);
        }

        $name = $targetUser->name;
        $targetUser->delete();
        return response()->json(['success' => true, 'message' => "Pengguna [{$name}] berhasil dihapus!"]);
    }

    public function toggleUserStatus(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$user->isAdmin()) return response()->json(['error' => 'Unauthorized'], 403);

        $targetUser = User::findOrFail($id);
        if ($targetUser->id === $user->id) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat menonaktifkan akun sendiri.'], 422);
        }

        $targetUser->is_active = !$targetUser->is_active;
        $targetUser->save();

        $statusText = $targetUser->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return response()->json(['success' => true, 'message' => "Akun [{$targetUser->name}] berhasil {$statusText}!", 'data' => $targetUser]);
    }

    // ==========================================
    // 11. MASTER DATA OUTLET (CABANG TOKO)
    // ==========================================

    public function outlets(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $outlets = Outlet::withCount(['users', 'sales'])->orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $outlets]);
    }

    public function storeOutlet(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$user->isAdmin()) return response()->json(['error' => 'Unauthorized'], 403);

        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:outlets,code',
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'status' => 'required|in:active,inactive',
        ]);

        $outlet = Outlet::create($validated);
        return response()->json(['success' => true, 'message' => 'Cabang toko berhasil ditambahkan!', 'data' => $outlet]);
    }

    public function updateOutlet(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$user->isAdmin()) return response()->json(['error' => 'Unauthorized'], 403);

        $outlet = Outlet::findOrFail($id);
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:outlets,code,' . $outlet->id,
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'status' => 'required|in:active,inactive',
        ]);

        $outlet->update($validated);
        return response()->json(['success' => true, 'message' => 'Cabang toko berhasil diperbarui!', 'data' => $outlet]);
    }

    public function destroyOutlet(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$user->isAdmin()) return response()->json(['error' => 'Unauthorized'], 403);

        $outlet = Outlet::findOrFail($id);

        if ($outlet->users()->count() > 0 || $outlet->sales()->count() > 0 || $outlet->transfers()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cabang [{$outlet->name}] tidak dapat dihapus karena sudah memiliki data transaksi atau kasir terhubung.",
            ], 422);
        }

        $name = $outlet->name;
        $outlet->delete();
        return response()->json(['success' => true, 'message' => "Cabang [{$name}] berhasil dihapus!"]);
    }

    public function toggleOutletStatus(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$user->isAdmin()) return response()->json(['error' => 'Unauthorized'], 403);

        $outlet = Outlet::findOrFail($id);
        $outlet->status = $outlet->status === 'active' ? 'inactive' : 'active';
        $outlet->save();

        return response()->json(['success' => true, 'message' => "Status cabang [{$outlet->name}] diubah menjadi {$outlet->status}!", 'data' => $outlet]);
    }

    public function storeSettings(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

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
        $closingHistory = YearlyClosing::latest()->take(5)->get();

        return response()->json(['success' => true, 'setting' => $setting, 'closing_history' => $closingHistory]);
    }

    public function updateSettings(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$user->isAdmin()) return response()->json(['error' => 'Unauthorized'], 403);

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'store_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'receipt_footer' => 'nullable|string',
            'active_year' => 'nullable|integer',
        ]);

        $setting = StoreSetting::firstOrCreate(['id' => 1]);
        $name = $validated['store_name'] ?? $validated['name'] ?? $setting->name;

        $setting->update([
            'name' => $name,
            'phone' => $validated['phone'] ?? $setting->phone,
            'address' => $validated['address'] ?? $setting->address,
            'receipt_footer' => $validated['receipt_footer'] ?? $setting->receipt_footer,
            'active_year' => $validated['active_year'] ?? $setting->active_year,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan toko & struk berhasil disimpan!',
            'setting' => $setting,
        ]);
    }

    public function uploadSettingsLogo(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$user->isAdmin()) return response()->json(['error' => 'Unauthorized'], 403);

        if (!$request->hasFile('logo')) {
            return response()->json(['error' => 'Berkas logo tidak ditemukan.'], 422);
        }

        $file = $request->file('logo');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExts)) {
            return response()->json(['error' => 'Format logo harus berupa gambar (PNG, JPG, WEBP).'], 422);
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            return response()->json(['error' => 'Ukuran logo maksimal 5MB.'], 422);
        }

        $setting = StoreSetting::firstOrCreate(['id' => 1]);

        $filename = 'logo_' . time() . '.' . $ext;
        $destinationDir = storage_path('app/public/settings/logos');
        if (!file_exists($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }
        $file->move($destinationDir, $filename);
        $setting->logo_path = 'settings/logos/' . $filename;
        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Logo toko berhasil diperbarui!',
            'logo_url' => asset('storage/' . $setting->logo_path),
            'setting' => $setting,
        ]);
    }

    // ==========================================
    // 11. SHIFT KASIR & SETOR PENJUALAN
    // ==========================================

    /**
     * Get real-time shift summary for cashier
     */
    public function shiftSummary(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $date = $request->query('date', date('Y-m-d'));
        $outletId = $user->outlet_id;

        // Sales today
        $salesQuery = Sale::whereDate('date', $date);
        if ($outletId) $salesQuery->where('outlet_id', $outletId);

        $sales = $salesQuery->with('customer')->get();
        $cashSales = $sales->where('payment_method', 'cash')->sum('paid_amount');
        $nonCashSales = $sales->where('payment_method', '!=', 'cash')->sum('paid_amount');
        $receivableSales = $sales->sum('remaining_receivable');

        // Tarik Tunai today
        $withdrawQuery = CashTransaction::where('type', 'TRANSFER')
            ->where('notes', 'like', 'Tarik Tunai%')
            ->whereDate('date', $date);
        $totalWithdraw = (float) $withdrawQuery->sum('amount');
        $totalWithdrawFee = (float) $withdrawQuery->sum('admin_fee');

        // Kas Keluar (Beban Makan, Sampah, Operasional)
        $expenseQuery = CashTransaction::where('type', 'OUT')
            ->whereDate('date', $date);
        $totalExpense = (float) $expenseQuery->sum('amount');

        // Laci Cash Retail current balance
        $cashRetailAcc = Account::where('code', '1-1110')->first();
        $currentCashDrawer = (float) ($cashRetailAcc?->current_balance ?? 0);

        // Required drawer reserve (modal awal)
        $requiredReserve = 400000.0;
        $recommendedDeposit = max(0.0, $currentCashDrawer - $requiredReserve);

        return response()->json([
            'success' => true,
            'date' => $date,
            'cash_drawer_balance' => $currentCashDrawer,
            'required_reserve' => $requiredReserve,
            'recommended_deposit' => $recommendedDeposit,
            'summary' => [
                'total_sales' => (float) $sales->sum('total'),
                'cash_sales' => (float) $cashSales,
                'non_cash_sales' => (float) $nonCashSales,
                'receivable_sales' => (float) $receivableSales,
                'total_withdraw_cash' => $totalWithdraw,
                'total_withdraw_fee' => $totalWithdrawFee,
                'total_expense' => $totalExpense,
                'total_transactions' => $sales->count(),
            ],
            'user' => [
                'name' => $user->name,
                'store_name' => $user->outlet?->name ?? 'Toko Kasir',
            ],
        ]);
    }

    /**
     * Close shift / Setor Uang Penjualan dan Sisakan Modal Awal
     */
    public function closeShift(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'deposit_amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
            'destination_account_id' => 'nullable|exists:accounts,id',
        ]);

        $cashRetailAcc = Account::where('code', '1-1110')->firstOrFail();
        $brangkasAcc = $request->destination_account_id 
            ? Account::find($request->destination_account_id)
            : (Account::where('name', 'like', '%BRANGKAS%')->first() ?: Account::where('code', '1-1113')->first() ?: $cashRetailAcc);

        $amount = (float) $request->deposit_amount;
        $trxNumber = $this->posService->generateTransactionNumber('ST'); // Setor Toko

        try {
            // Transfer from CASH RETAIL to BRANGKAS / PUSAT
            $trx = CashTransaction::create([
                'transaction_number' => $trxNumber,
                'type' => 'TRANSFER',
                'date' => now(),
                'debit_account_id' => $brangkasAcc->id,
                'credit_account_id' => $cashRetailAcc->id,
                'amount' => $amount,
                'admin_fee' => 0,
                'notes' => "Setor Kas Penjualan Shift [{$user->name}] - " . ($request->notes ?? 'Tutup Shift'),
            ]);

            $this->accountingService->recordCashTransaction($trx);

            $remainingDrawer = (float) $cashRetailAcc->fresh()->current_balance;

            return response()->json([
                'success' => true,
                'message' => 'Setor uang penjualan Rp ' . number_format($amount, 0, ',', '.') . ' berhasil!',
                'remaining_drawer' => $remainingDrawer,
                'transaction' => $trx,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Unified Store Operational Logs (Tarik Tunai, Barang Masuk, Barang Keluar, Retur)
     */
    public function unifiedLogs(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $type = $request->query('type', 'all'); // 'withdraw', 'in', 'out', 'return'
        $date = $request->query('date');
        $startDate = $request->query('start_date', $date);
        $endDate = $request->query('end_date', $date);

        $applyDateFilter = function ($q) use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                $q->whereBetween('date', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"]);
            } elseif ($startDate) {
                $q->whereDate('date', '>=', $startDate);
            } elseif ($endDate) {
                $q->whereDate('date', '<=', $endDate);
            }
        };

        // 1. Tarik Tunai
        $withdrawals = CashTransaction::where('type', 'TRANSFER')
            ->where('notes', 'like', 'Tarik Tunai%')
            ->when($startDate || $endDate, $applyDateFilter)
            ->latest('date')
            ->take(100)
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'type' => 'withdraw',
                    'badge' => 'TARIK TUNAI',
                    'number' => $t->transaction_number,
                    'date' => $t->date->format('d/m/Y H:i'),
                    'title' => $t->notes,
                    'amount' => (float) $t->amount,
                    'fee' => (float) $t->admin_fee,
                    'is_negative' => true,
                ];
            });

        // 2. Barang Masuk (Pembelian & Opname IN)
        $purchases = Purchase::with(['supplier', 'items.product'])
            ->when($startDate || $endDate, $applyDateFilter)
            ->latest('date')
            ->take(100)
            ->get()
            ->map(function ($p) {
                $itemNames = $p->items->map(fn($it) => ($it->product->name ?? 'Item') . ' (' . (float)$it->qty . ')')->join(', ');
                return [
                    'id' => $p->id,
                    'type' => 'in',
                    'badge' => 'KULAKAN MASUK',
                    'number' => $p->invoice_number,
                    'date' => $p->date->format('d/m/Y H:i'),
                    'title' => ($p->supplier->name ?? 'Supplier') . ' - ' . $itemNames,
                    'amount' => (float) $p->total,
                    'items_count' => $p->items->sum('qty'),
                    'is_negative' => false,
                ];
            });

        // 3. Barang Keluar (Penjualan Sales Items)
        $sales = Sale::with(['customer', 'items.product'])
            ->when($startDate || $endDate, $applyDateFilter)
            ->latest('date')
            ->take(100)
            ->get()
            ->map(function ($s) {
                $itemNames = $s->items->map(fn($it) => ($it->product->name ?? 'Item') . ' (' . (float)$it->qty . ')')->join(', ');
                return [
                    'id' => $s->id,
                    'type' => 'out',
                    'badge' => 'PENJUALAN',
                    'number' => $s->invoice_number,
                    'date' => $s->date->format('d/m/Y H:i'),
                    'title' => ($s->customer->name ?? 'UMUM') . ' - ' . $itemNames,
                    'amount' => (float) $s->total,
                    'items_count' => $s->items->sum('qty'),
                    'is_negative' => true,
                ];
            });

        // 4. Retur Penjualan
        $returns = SaleReturn::with(['sale', 'customer', 'items.product'])
            ->when($startDate || $endDate, $applyDateFilter)
            ->latest('date')
            ->take(100)
            ->get()
            ->map(function ($r) {
                $itemNames = $r->items->map(fn($it) => ($it->product->name ?? 'Item') . ' (' . (float)$it->qty . ')')->join(', ');
                return [
                    'id' => $r->id,
                    'type' => 'return',
                    'badge' => 'RETUR',
                    'number' => $r->return_number,
                    'date' => $r->date->format('d/m/Y H:i'),
                    'title' => ($r->customer->name ?? 'Pelanggan') . ' - ' . $itemNames . ' [' . ($r->reason ?? 'Retur') . ']',
                    'amount' => (float) $r->total_amount,
                    'refund_method' => $r->refund_method,
                    'is_negative' => false,
                ];
            });

        return response()->json([
            'success' => true,
            'withdrawals' => $withdrawals,
            'stock_in' => $purchases,
            'stock_out' => $sales,
            'returns' => $returns,
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
