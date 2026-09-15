import 'dart:async';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/formatters.dart';
import '../utils/theme_config.dart';

// Native Screens for Drawer Navigation
import 'pos_screen.dart';
import 'digital_screen.dart';
import 'receivables_screen.dart';
import 'returns_screen.dart';
import 'transfer_screen.dart';
import 'products_screen.dart';
import 'customers_screen.dart';
import 'suppliers_screen.dart';
import 'purchases_screen.dart';
import 'inventory_screen.dart';
import 'accounts_screen.dart';
import 'cash_screen.dart';
import 'reports_screen.dart';
import 'users_screen.dart';
import 'settings_screen.dart';
import 'outlets_screen.dart';

class AppColors {
  static const Color slateBorder = Color(0xFFE2E8F0);
  static const Color slateText = Color(0xFF64748B);
  static const Color emeraldLight = Color(0xFFECFDF5);
  static const Color emeraldIcon = Color(0xFF047857);
}

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Map<String, dynamic>? _userData;
  Map<String, dynamic>? _dashboardData;
  bool _isLoading = true;
  bool _isSyncing = false;
  bool _isOfflineMode = false;
  bool _isPolling = false;
  String? _errorMessage;

  late String _startDate;
  late String _endDate;

  Timer? _pollingTimer;
  int _lastPendingTransferCount = 0;
  int? _selectedOutletId;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _startDate = DateFormat('yyyy-MM-01').format(now);
    _endDate = DateFormat('yyyy-MM-dd').format(now);

    NotificationService.requestPermission();
    // Setup FCM: register device token + handle foreground messages
    NotificationService.setupFcm();
    _initDashboardWithCache();
    _startPendingTransferPolling();
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    super.dispose();
  }

  void _initDashboardWithCache() async {
    // 1. Instantly load cached user and cached dashboard in 0 milliseconds!
    final user = await ApiService.getUser();
    final cached = await ApiService.getCachedDashboard();

    if (mounted) {
      setState(() {
        _userData = user;
        if (cached != null) {
          _dashboardData = cached;
          _isLoading = false; // ZERO DELAY: Display dashboard immediately!
          _isSyncing = true;
          final pendingCount = Formatters.parseInt(cached['pending_transfers']);
          _lastPendingTransferCount = pendingCount;
        }
      });
    }

    // 2. Fetch fresh live data from server in the background
    _loadDashboardData(isBackgroundSync: cached != null);
  }

  void _startPendingTransferPolling() {
    _pollingTimer = Timer.periodic(const Duration(seconds: 30), (_) async {
      if (_isPolling) return;
      final role = _userData?['role']?.toString().toLowerCase();
      if (role == 'admin' || role == 'super_admin' || role == 'superadmin') {
        _isPolling = true;
        try {
          final res = await ApiService.pollNotifications();
          if (res['success'] == true) {
            final int currentCount = Formatters.parseInt(res['pending_transfers_count']);
            if (currentCount > _lastPendingTransferCount && currentCount > 0) {
              final latest = res['latest_pending'];
              final String sender = latest?['user']?['name'] ?? 'Kasir Agen';
              final String bank = latest?['bank_name'] ?? 'Bank';
              final double amount = Formatters.parseDouble(latest?['amount']);

              NotificationService.showNotification(
                id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                title: '⚠️ Pengajuan Transfer Baru! 🏦',
                body: '$sender mengajukan transfer $bank sebesar ${Formatters.formatRupiah(amount)}. Menunggu persetujuan Anda.',
              );
            }
            if (mounted) {
              setState(() {
                _lastPendingTransferCount = currentCount;
              });
            }
          }
        } catch (_) {} finally {
          _isPolling = false;
        }
      }
    });
  }

  void _loadDashboardData({bool isBackgroundSync = false}) async {
    if (!mounted) return;

    if (!isBackgroundSync) {
      setState(() {
        _isLoading = _dashboardData == null;
        _isSyncing = true;
        _errorMessage = null;
      });
    } else {
      setState(() {
        _isSyncing = true;
      });
    }

    try {
      final user = await ApiService.getUser();
      final data = await ApiService.getDashboard(startDate: _startDate, endDate: _endDate, outletId: _selectedOutletId);

      if (!mounted) return;

      // Handle 401 Session Expiry
      if (data['is_auth_error'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Sesi login telah berakhir. Silakan masuk kembali.'),
            backgroundColor: Colors.redAccent,
            duration: Duration(seconds: 4),
          ),
        );
        Navigator.pushNamedAndRemoveUntil(context, '/login', (route) => false);
        return;
      }

      setState(() {
        _userData = user;
        _isSyncing = false;

        if (data['success'] == true) {
          _dashboardData = data;
          _errorMessage = null;
          _isOfflineMode = false;

          final pendingCount = Formatters.parseInt(data['pending_transfers']);
          _lastPendingTransferCount = pendingCount;
          final role = (_userData?['role'] ?? '').toString().toLowerCase();

          if (pendingCount > 0 && (role == 'admin' || role == 'super_admin' || role == 'superadmin')) {
            NotificationService.showNotification(
              id: 101,
              title: '⚠️ Pengajuan Transfer Menunggu ACC',
              body: 'Ada $pendingCount pengajuan transfer agen yang butuh persetujuan Anda.',
            );
          }
        } else {
          // If we already have cached data, don't wipe the screen!
          if (_dashboardData != null) {
            _isOfflineMode = true;
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Row(
                  children: [
                    const Icon(Icons.wifi_off_rounded, color: Colors.white, size: 18),
                    const SizedBox(width: 8),
                    Expanded(child: Text(data['message'] ?? 'Koneksi lambat. Menampilkan data tersimpan.')),
                  ],
                ),
                backgroundColor: Colors.amber.shade900,
                duration: const Duration(seconds: 3),
              ),
            );
          } else {
            _errorMessage = data['message'] ?? 'Gagal memuat ringkasan dashboard';
          }
        }
        _isLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isSyncing = false;
        if (_dashboardData != null) {
          _isOfflineMode = true;
        } else {
          _errorMessage = 'Koneksi gagal: $e';
        }
        _isLoading = false;
      });
    }
  }

  void _selectDate(bool isStart) async {
    final current = isStart ? DateTime.parse(_startDate) : DateTime.parse(_endDate);
    final picked = await showDatePicker(
      context: context,
      initialDate: current,
      firstDate: DateTime(2020),
      lastDate: DateTime(2030),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(primary: ThemeConfig.primary),
          ),
          child: child!,
        );
      },
    );

    if (picked != null) {
      final formatted = DateFormat('yyyy-MM-dd').format(picked);
      setState(() {
        if (isStart) {
          _startDate = formatted;
        } else {
          _endDate = formatted;
        }
      });
      _loadDashboardData();
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).padding.bottom;
    final role = (_userData?['role'] ?? 'toko').toString().toLowerCase();
    final isAdmin = role == 'admin' || role == 'super_admin' || role == 'superadmin';

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Dashboard Operasional', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
            Text(
              _userData?['store_name'] ?? 'ELEPHANT CELL',
              style: const TextStyle(fontSize: 11, color: Colors.white70),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: _isSyncing
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.refresh),
            tooltip: 'Segarkan Dashboard',
            onPressed: () => _loadDashboardData(isBackgroundSync: false),
          ),
          IconButton(
            icon: Stack(
              children: [
                const Icon(Icons.notifications_outlined),
                if (_lastPendingTransferCount > 0 && isAdmin)
                  Positioned(
                    right: 0,
                    top: 0,
                    child: Container(
                      padding: const EdgeInsets.all(3),
                      decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                      child: Text(
                        '$_lastPendingTransferCount',
                        style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
              ],
            ),
            onPressed: () {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const TransferScreen()));
            },
          ),
        ],
      ),
      drawer: _buildAppDrawer(),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
          : (_errorMessage != null && _dashboardData == null)
              ? Center(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.all(28.0),
                    child: Card(
                      elevation: 3,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Container(
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: Colors.red.shade50,
                                shape: BoxShape.circle,
                              ),
                              child: Icon(Icons.cloud_off_rounded, size: 48, color: Colors.red.shade700),
                            ),
                            const SizedBox(height: 16),
                            const Text(
                              'Koneksi Bermasalah',
                              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              _errorMessage!,
                              textAlign: TextAlign.center,
                              style: const TextStyle(fontSize: 13, color: Color(0xFF64748B), height: 1.4),
                            ),
                            const SizedBox(height: 24),
                            SizedBox(
                              width: double.infinity,
                              child: ElevatedButton.icon(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: ThemeConfig.primary,
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(vertical: 14),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                ),
                                onPressed: () => _loadDashboardData(isBackgroundSync: false),
                                icon: const Icon(Icons.refresh_rounded, size: 18),
                                label: const Text('Coba Lagi', style: TextStyle(fontWeight: FontWeight.bold)),
                              ),
                            ),
                            const SizedBox(height: 10),
                            SizedBox(
                              width: double.infinity,
                              child: OutlinedButton.icon(
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: const Color(0xFF64748B),
                                  side: BorderSide(color: Colors.grey.shade300),
                                  padding: const EdgeInsets.symmetric(vertical: 12),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                ),
                                onPressed: () async {
                                  await ApiService.logout();
                                  if (context.mounted) {
                                    Navigator.pushNamedAndRemoveUntil(context, '/login', (route) => false);
                                  }
                                },
                                icon: const Icon(Icons.logout_rounded, size: 16),
                                label: const Text('Masuk Kembali / Ganti Akun'),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                )
              : LayoutBuilder(
                  builder: (context, constraints) {
                    final isTablet = constraints.maxWidth >= 700;

                    return RefreshIndicator(
                      onRefresh: () async => _loadDashboardData(isBackgroundSync: false),
                      child: ListView(
                        padding: EdgeInsets.only(
                          left: 14,
                          right: 14,
                          top: 14,
                          bottom: bottomInset + 30, // Anti-cut navbar padding
                        ),
                        children: [
                          if (_isOfflineMode)
                            Container(
                              margin: const EdgeInsets.only(bottom: 12),
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                              decoration: BoxDecoration(
                                color: Colors.amber.shade50,
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(color: Colors.amber.shade300),
                              ),
                              child: Row(
                                children: [
                                  Icon(Icons.wifi_off_rounded, size: 18, color: Colors.amber.shade800),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      'Mode Offline: Menampilkan data tersimpan di HP.',
                                      style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Colors.amber.shade900),
                                    ),
                                  ),
                                  InkWell(
                                    onTap: () => _loadDashboardData(isBackgroundSync: false),
                                    child: const Padding(
                                      padding: EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                      child: Text(
                                        'Segarkan',
                                        style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: ThemeConfig.primary, decoration: TextDecoration.underline),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          // 1. TOP FILTER & INSTRUCTION PANEL
                          _buildTopFilterPanel(),
                          const SizedBox(height: 14),

                          // 2. 8 KOTAK KPI UTAMA (Responsive Grid)
                          _buildKpiGrid(isTablet),
                          const SizedBox(height: 16),

                          // 3. 3 RINCIAN BREAKDOWN & TARGET PROFIT
                          if (isTablet)
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(child: _buildCashBreakdownCard()),
                                const SizedBox(width: 12),
                                Expanded(child: _buildTargetProfitCard()),
                                const SizedBox(width: 12),
                                Expanded(child: _buildDailyTrendCard()),
                              ],
                            )
                          else ...[
                            _buildCashBreakdownCard(),
                            const SizedBox(height: 12),
                            _buildTargetProfitCard(),
                            const SizedBox(height: 12),
                            _buildDailyTrendCard(),
                          ],
                          const SizedBox(height: 16),

                          // 4. TABEL URUTAN PRODUK TERLARIS
                          _buildTopProductsTable(),
                        ],
                      ),
                    );
                  },
                ),
    );
  }

  // ===========================================================================
  // WIDGET COMPONENTS MATCHING WEB DASHBOARD
  // ===========================================================================

  Widget _buildTopFilterPanel() {
    final role = (_userData?['role'] ?? 'toko').toString().toLowerCase();
    final isAdmin = role == 'admin' || role == 'super_admin' || role == 'superadmin';
    final outlets = (_dashboardData?['outlets'] as List?) ?? [];

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.slateBorder),
        boxShadow: const [BoxShadow(color: Color(0x05000000), blurRadius: 4)],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.date_range, size: 16, color: ThemeConfig.primary),
              const SizedBox(width: 6),
              const Text('Periode:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
              const Spacer(),
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: ThemeConfig.primary,
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  visualDensity: VisualDensity.compact,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                icon: const Icon(Icons.sync, size: 14, color: Colors.white),
                label: const Text('REFRESH', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.white)),
                onPressed: _loadDashboardData,
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: InkWell(
                  onTap: () => _selectDate(true),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey.shade300),
                      borderRadius: BorderRadius.circular(8),
                      color: const Color(0xFFF1F5F9),
                    ),
                    child: Text(_startDate, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                  ),
                ),
              ),
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 8),
                child: Text('s/d', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Colors.grey)),
              ),
              Expanded(
                child: InkWell(
                  onTap: () => _selectDate(false),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey.shade300),
                      borderRadius: BorderRadius.circular(8),
                      color: const Color(0xFFF1F5F9),
                    ),
                    child: Text(_endDate, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                  ),
                ),
              ),
            ],
          ),
          // Cabang Toko Switcher (Admin / Super Admin)
          if (isAdmin && outlets.isNotEmpty) ...[
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.storefront, size: 15, color: ThemeConfig.primary),
                const SizedBox(width: 6),
                const Text('Cabang:', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ThemeConfig.textDark)),
                const SizedBox(width: 6),
                Expanded(
                  child: SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        ChoiceChip(
                          label: const Text('Semua Cabang', style: TextStyle(fontSize: 11)),
                          selected: _selectedOutletId == null,
                          selectedColor: ThemeConfig.primary.withOpacity(0.15),
                          labelStyle: TextStyle(
                            color: _selectedOutletId == null ? ThemeConfig.primary : Colors.black87,
                            fontWeight: _selectedOutletId == null ? FontWeight.bold : FontWeight.normal,
                          ),
                          onSelected: (selected) {
                            if (selected) {
                              setState(() => _selectedOutletId = null);
                              _loadDashboardData();
                            }
                          },
                        ),
                        const SizedBox(width: 6),
                        ...outlets.map((ot) {
                          final isSel = _selectedOutletId == ot['id'];
                          return Padding(
                            padding: const EdgeInsets.only(right: 6),
                            child: ChoiceChip(
                              label: Text('${ot['code']} - ${ot['name']}', style: const TextStyle(fontSize: 11)),
                              selected: isSel,
                              selectedColor: ThemeConfig.primary.withOpacity(0.15),
                              labelStyle: TextStyle(
                                color: isSel ? ThemeConfig.primary : Colors.black87,
                                fontWeight: isSel ? FontWeight.bold : FontWeight.normal,
                              ),
                              onSelected: (selected) {
                                setState(() => _selectedOutletId = selected ? ot['id'] : null);
                                _loadDashboardData();
                              },
                            ),
                          );
                        }).toList(),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ],
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(6),
              border: Border.all(color: AppColors.slateBorder),
            ),
            child: Row(
              children: const [
                Icon(Icons.info_outline, size: 14, color: AppColors.slateText),
                SizedBox(width: 6),
                Expanded(
                  child: Text(
                    'PETUNJUK: Data periode terakumulasi otomatis secara real-time dari database ACID.',
                    style: TextStyle(fontSize: 10, color: AppColors.slateText, fontWeight: FontWeight.w500),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildKpiGrid(bool isTablet) {
    final d = _dashboardData ?? {};
    final pl = d['pl'] ?? {};
    final revenues = pl['revenues'] ?? {};
    final expenses = pl['expenses'] ?? {};

    final totalPersediaan = Formatters.parseDouble(d['totalPersediaan']);
    final totalHutang = Formatters.parseDouble(d['totalHutang']);
    final totalPiutang = Formatters.parseDouble(d['totalPiutang']);
    final totalKasBank = Formatters.parseDouble(d['totalKasBank']);
    final totalPendapatan = Formatters.parseDouble(revenues['total']);
    final totalBiaya = Formatters.parseDouble(expenses['total']);
    final salesCount = Formatters.parseInt(d['salesCount']);
    final retailCount = Formatters.parseInt(d['retailSalesCount']);
    final grosirCount = Formatters.parseInt(d['grosirSalesCount']);
    final netProfit = Formatters.parseDouble(pl['net_profit']);

    final kpis = [
      {
        'title': 'PERSEDIAAN BARANG',
        'value': Formatters.formatRupiah(totalPersediaan),
        'desc': 'Nilai fisik stok moving HPP',
        'icon': Icons.inventory_2_outlined,
        'iconColor': AppColors.emeraldIcon,
        'iconBg': AppColors.emeraldLight,
        'isDark': false,
      },
      {
        'title': 'HUTANG SUPPLIER',
        'value': Formatters.formatRupiah(totalHutang),
        'desc': 'Sisa tagihan kulakan belum lunas',
        'icon': Icons.credit_card_outlined,
        'iconColor': AppColors.slateText,
        'iconBg': const Color(0xFFF1F5F9),
        'isDark': false,
      },
      {
        'title': 'PIUTANG PELANGGAN',
        'value': Formatters.formatRupiah(totalPiutang),
        'desc': 'Penjualan tempo belum tertagih',
        'icon': Icons.account_balance_wallet_outlined,
        'iconColor': AppColors.slateText,
        'iconBg': const Color(0xFFF1F5F9),
        'isDark': false,
      },
      {
        'title': 'TOTAL KAS & BANK',
        'value': Formatters.formatRupiah(totalKasBank),
        'desc': 'Cash laci, BCA, BRI & saldo multi',
        'icon': Icons.account_balance_outlined,
        'iconColor': AppColors.emeraldIcon,
        'iconBg': AppColors.emeraldLight,
        'isDark': false,
      },
      {
        'title': 'TOTAL PENDAPATAN',
        'value': Formatters.formatRupiah(totalPendapatan),
        'desc': 'Retail + Grosir + Multi + Jasa TF',
        'icon': Icons.trending_up,
        'iconColor': AppColors.emeraldIcon,
        'iconBg': AppColors.emeraldLight,
        'isDark': false,
      },
      {
        'title': 'BIAYA OPERASIONAL',
        'value': Formatters.formatRupiah(totalBiaya),
        'desc': 'Kas keluar operasional & listrik/sewa',
        'icon': Icons.trending_down,
        'iconColor': AppColors.slateText,
        'iconBg': const Color(0xFFF1F5F9),
        'isDark': false,
      },
      {
        'title': 'TOTAL TRANSAKSI',
        'value': '$salesCount Nota',
        'desc': 'Retail: $retailCount | Grosir: $grosirCount',
        'icon': Icons.receipt_long_outlined,
        'iconColor': AppColors.slateText,
        'iconBg': const Color(0xFFF1F5F9),
        'isDark': false,
      },
      {
        'title': 'LABA BERSIH REAL-TIME',
        'value': Formatters.formatRupiah(netProfit),
        'desc': 'Laba kotor - Biaya operasional',
        'icon': Icons.monetization_on_outlined,
        'iconColor': const Color(0xFFA7F3D0),
        'iconBg': const Color(0x26FFFFFF),
        'isDark': true,
      },
    ];

    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: isTablet ? 4 : 2,
        mainAxisSpacing: 10,
        crossAxisSpacing: 10,
        childAspectRatio: isTablet ? 1.4 : 1.3,
      ),
      itemCount: kpis.length,
      itemBuilder: (ctx, i) {
        final it = kpis[i];
        final isDark = it['isDark'] == true;

        return Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: isDark ? const Color(0xFF133E1C) : Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: isDark ? const Color(0xFF064E3B) : AppColors.slateBorder),
            boxShadow: [
              BoxShadow(
                color: isDark ? const Color(0x14000000) : const Color(0x05000000),
                blurRadius: 4,
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      it['title'] as String,
                      style: TextStyle(
                        fontSize: 9.5,
                        fontWeight: FontWeight.bold,
                        color: isDark ? const Color(0xFFA7F3D0) : const Color(0xFF64748B),
                        letterSpacing: 0.3,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  Container(
                    width: 28,
                    height: 28,
                    decoration: BoxDecoration(
                      color: it['iconBg'] as Color,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(it['icon'] as IconData, size: 16, color: it['iconColor'] as Color),
                  ),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  FittedBox(
                    fit: BoxFit.scaleDown,
                    child: Text(
                      it['value'] as String,
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w900,
                        color: isDark ? Colors.white : const Color(0xFF0F172A),
                        fontFamily: 'monospace',
                      ),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    it['desc'] as String,
                    style: TextStyle(
                      fontSize: 9.5,
                      color: isDark ? Colors.white70 : const Color(0xFF94A3B8),
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildCashBreakdownCard() {
    final d = _dashboardData ?? {};
    final cashAccounts = (d['cashAccounts'] as List?) ?? [];
    final pl = d['pl'] ?? {};
    final revenues = pl['revenues'] ?? {};
    final hpp = pl['hpp'] ?? {};

    final retailProfit = Formatters.parseDouble(revenues['retail']) - Formatters.parseDouble(hpp['retail']);
    final grosirProfit = Formatters.parseDouble(revenues['grosir']) - Formatters.parseDouble(hpp['grosir']);
    final multiProfit = Formatters.parseDouble(revenues['multi']) - Formatters.parseDouble(hpp['multi']);
    final tfProfit = Formatters.parseDouble(revenues['jasa_transfer']);
    final grossProfit = Formatters.parseDouble(pl['gross_profit']);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.slateBorder),
        boxShadow: const [BoxShadow(color: Color(0x05000000), blurRadius: 4)],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: const [
              Icon(Icons.account_balance, size: 16, color: ThemeConfig.primary),
              SizedBox(width: 6),
              Text('Rincian Saldo Kas & Bank', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            ],
          ),
          const Divider(height: 16),
          ...cashAccounts.map((acc) {
            final name = acc['name'] ?? 'Kas';
            final bal = Formatters.parseDouble(acc['current_balance']);
            return Padding(
              padding: const EdgeInsets.symmetric(vertical: 3.5),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(name, style: const TextStyle(fontSize: 12, color: Color(0xFF475569))),
                  Text(Formatters.formatRupiah(bal), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, fontFamily: 'monospace')),
                ],
              ),
            );
          }),
          const SizedBox(height: 10),
          const Divider(height: 16),
          Row(
            children: const [
              Icon(Icons.pie_chart_outline, size: 16, color: ThemeConfig.accent),
              SizedBox(width: 6),
              Text('Rincian Margin & Laba Kotor', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            ],
          ),
          const SizedBox(height: 8),
          _marginRow('Laba Retail Fisik:', Formatters.formatRupiah(retailProfit)),
          _marginRow('Laba Grosir:', Formatters.formatRupiah(grosirProfit)),
          _marginRow('Laba Multi Elektrik:', Formatters.formatRupiah(multiProfit)),
          _marginRow('Pendapatan Jasa TF:', Formatters.formatRupiah(tfProfit), color: ThemeConfig.primary),
          const Divider(height: 12),
          _marginRow('Total Laba Kotor:', Formatters.formatRupiah(grossProfit), isBold: true, color: ThemeConfig.primary),
        ],
      ),
    );
  }

  Widget _marginRow(String label, String val, {bool isBold = false, Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2.5),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontSize: 11.5, fontWeight: isBold ? FontWeight.bold : FontWeight.normal, color: const Color(0xFF475569))),
          Text(
            val,
            style: TextStyle(
              fontSize: 12,
              fontWeight: isBold ? FontWeight.w900 : FontWeight.w600,
              fontFamily: 'monospace',
              color: color ?? const Color(0xFF0F172A),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTargetProfitCard() {
    final d = _dashboardData ?? {};
    final targetProfit = Formatters.parseDouble(d['targetProfit']);
    final realizedProfit = Formatters.parseDouble(d['realizedProfit']);
    final remainingTarget = Formatters.parseDouble(d['remainingTarget']);
    final progressPct = Formatters.parseDouble(d['progressPct']);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.slateBorder),
        boxShadow: const [BoxShadow(color: Color(0x05000000), blurRadius: 4)],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: const [
              Icon(Icons.flag_outlined, size: 16, color: ThemeConfig.primary),
              SizedBox(width: 6),
              Text('Target Profit Bulan Ini', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            ],
          ),
          const Divider(height: 16),
          Row(
            children: [
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: AppColors.slateBorder),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Target Bulanan:', style: TextStyle(fontSize: 10, color: Color(0xFF64748B))),
                      const SizedBox(height: 2),
                      FittedBox(
                        child: Text(
                          Formatters.formatRupiah(targetProfit),
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, fontFamily: 'monospace'),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppColors.emeraldLight,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: const Color(0xFFA7F3D0)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Realisasi Saat Ini:', style: TextStyle(fontSize: 10, color: Color(0xFF065F46))),
                      const SizedBox(height: 2),
                      FittedBox(
                        child: Text(
                          Formatters.formatRupiah(realizedProfit),
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, fontFamily: 'monospace', color: Color(0xFF064E3B)),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Pencapaian: $progressPct%', style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold)),
              Text('Sisa: ${Formatters.formatRupiah(remainingTarget)}', style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontFamily: 'monospace')),
            ],
          ),
          const SizedBox(height: 6),
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: LinearProgressIndicator(
              value: (progressPct / 100).clamp(0.0, 1.0),
              minHeight: 12,
              backgroundColor: const Color(0xFFE2E8F0),
              valueColor: AlwaysStoppedAnimation<Color>(
                progressPct >= 100 ? Colors.green : (progressPct >= 50 ? ThemeConfig.primary : Colors.orange),
              ),
            ),
          ),
          const SizedBox(height: 12),
          Center(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
              decoration: BoxDecoration(
                color: progressPct >= 100 ? const Color(0xFFD1FAE5) : const Color(0xFFF1F5F9),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: progressPct >= 100 ? const Color(0xFF6EE7B7) : const Color(0xFFCBD5E1)),
              ),
              child: Text(
                progressPct >= 100 ? '🎉 TARGET BULANAN TERCAPAI' : 'Menuju target profit bulan berjalan',
                style: TextStyle(
                  fontSize: 10.5,
                  fontWeight: FontWeight.bold,
                  color: progressPct >= 100 ? const Color(0xFF065F46) : const Color(0xFF475569),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDailyTrendCard() {
    final d = _dashboardData ?? {};
    final rawDaily = d['dailySales'];
    final Map<dynamic, dynamic> dailySales = (rawDaily is Map) ? rawDaily : {};

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.slateBorder),
        boxShadow: const [BoxShadow(color: Color(0x05000000), blurRadius: 4)],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: const [
              Icon(Icons.show_chart, size: 16, color: ThemeConfig.primary),
              SizedBox(width: 6),
              Text('Tren Penjualan Harian', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            ],
          ),
          const Divider(height: 16),
          if (dailySales.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 24),
              child: Center(
                child: Text('Belum ada transaksi penjualan pada periode ini', style: TextStyle(fontSize: 11, color: Colors.grey)),
              ),
            )
          else ...[
            ...dailySales.entries.take(7).map((e) {
              final day = e.key.toString();
              final rev = Formatters.parseDouble(e.value);
              return Padding(
                padding: const EdgeInsets.symmetric(vertical: 3),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(day, style: const TextStyle(fontSize: 11, color: Color(0xFF475569))),
                    Text(Formatters.formatRupiah(rev), style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, fontFamily: 'monospace')),
                  ],
                ),
              );
            }),
          ],
        ],
      ),
    );
  }

  Widget _buildTopProductsTable() {
    final d = _dashboardData ?? {};
    final topProducts = (d['topProducts'] as List?) ?? [];

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.slateBorder),
        boxShadow: const [BoxShadow(color: Color(0x05000000), blurRadius: 4)],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Header Bar matching Web `#133e1c`
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: const BoxDecoration(
              color: Color(0xFF133E1C),
              borderRadius: BorderRadius.vertical(top: Radius.circular(13)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: const [
                    Icon(Icons.check_circle_outline, size: 16, color: Colors.white70),
                    SizedBox(width: 6),
                    Text(
                      'URUTAN PRODUK TERLARIS PERIODE INI',
                      style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 11.5, letterSpacing: 0.3),
                    ),
                  ],
                ),
                InkWell(
                  onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ProductsScreen())),
                  child: const Text('Lihat Semua >', style: TextStyle(color: Color(0xFFA7F3D0), fontSize: 11, fontWeight: FontWeight.bold)),
                ),
              ],
            ),
          ),

          // Table
          if (topProducts.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 30),
              child: Center(
                child: Text('Belum ada riwayat transaksi penjualan fisik pada periode ini.', style: TextStyle(fontSize: 12, color: Colors.grey)),
              ),
            )
          else
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: DataTable(
                headingRowHeight: 38,
                dataRowMinHeight: 40,
                dataRowMaxHeight: 48,
                headingRowColor: WidgetStateProperty.all(const Color(0xFFF8FAFC)),
                columns: const [
                  DataColumn(label: Text('NO', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold))),
                  DataColumn(label: Text('KODE BARANG', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold))),
                  DataColumn(label: Text('NAMA PRODUK', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold))),
                  DataColumn(label: Text('JENIS', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold))),
                  DataColumn(label: Text('MEREK', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold))),
                  DataColumn(label: Text('HARGA JUAL', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold))),
                  DataColumn(label: Text('JUMLAH TERJUAL', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold))),
                ],
                rows: topProducts.asMap().entries.map((entry) {
                  final idx = entry.key + 1;
                  final tp = entry.value;
                  final prod = tp['product'] ?? {};
                  final itemCode = prod['item_code'] ?? prod['code'] ?? '-';
                  final name = prod['name'] ?? '-';
                  final type = prod['type'] ?? '-';
                  final brand = prod['brand'] ?? '-';
                  final price = Formatters.parseDouble(prod['retail_price'] ?? prod['selling_price']);
                  final totalSold = Formatters.parseInt(tp['total_sold']);

                  return DataRow(
                    cells: [
                      DataCell(Text('$idx', style: const TextStyle(fontSize: 11, color: Colors.grey))),
                      DataCell(Text(itemCode, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ThemeConfig.primary, fontFamily: 'monospace'))),
                      DataCell(Text(name, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600))),
                      DataCell(
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(4)),
                          child: Text(type, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
                        ),
                      ),
                      DataCell(Text(brand, style: const TextStyle(fontSize: 11))),
                      DataCell(Text(Formatters.formatRupiah(price), style: const TextStyle(fontSize: 11, fontFamily: 'monospace'))),
                      DataCell(
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: AppColors.emeraldLight,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: const Color(0xFFA7F3D0)),
                          ),
                          child: Text('$totalSold pcs', style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF065F46))),
                        ),
                      ),
                    ],
                  );
                }).toList(),
              ),
            ),
        ],
      ),
    );
  }

  // ===========================================================================
  // APP DRAWER (All 18 Modules Access)
  // ===========================================================================

  Widget _buildAppDrawer() {
    return Drawer(
      child: SafeArea(
        bottom: true,
        child: Column(
          children: [
            UserAccountsDrawerHeader(
              decoration: const BoxDecoration(color: ThemeConfig.primary),
              currentAccountPicture: const CircleAvatar(
                backgroundColor: Colors.white,
                child: Icon(Icons.store, color: ThemeConfig.primary, size: 36),
              ),
              accountName: Text(
                _userData?['name'] ?? 'User Kasir',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              accountEmail: Text(_userData?['email'] ?? 'user@pos.moonbyte.my.id'),
            ),
            Expanded(
              child: ListView(
                padding: EdgeInsets.zero,
                children: [
                  _drawerItem(Icons.point_of_sale, 'Kasir Eceran (Retail)', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const PosScreen(saleType: 'retail')));
                  }),
                  _drawerItem(Icons.storefront, 'Kasir Grosir', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const PosScreen(saleType: 'grosir')));
                  }),
                  _drawerItem(Icons.phone_android, 'Produk Multi / Pulsa', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const DigitalScreen()));
                  }),
                  const Divider(),
                  _drawerItem(Icons.storefront, 'Master Cabang / Toko', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const OutletsScreen()));
                  }),
                  _drawerItem(Icons.inventory_2, 'Master Data Produk', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const ProductsScreen()));
                  }),
                  _drawerItem(Icons.people, 'Master Pelanggan', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const CustomersScreen()));
                  }),
                  _drawerItem(Icons.local_shipping, 'Master Supplier', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const SuppliersScreen()));
                  }),
                  _drawerItem(Icons.account_tree, 'Bagan Akun (COA)', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const AccountsScreen()));
                  }),
                  const Divider(),
                  _drawerItem(Icons.shopping_bag, 'Pembelian & Hutang', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const PurchasesScreen()));
                  }),
                  _drawerItem(Icons.receipt, 'Piutang Pelanggan', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const ReceivablesScreen()));
                  }),
                  _drawerItem(Icons.assignment_return, 'Retur Penjualan', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const ReturnsScreen()));
                  }),
                  _drawerItem(Icons.tune, 'Penyesuaian Stok (Opname)', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const InventoryScreen()));
                  }),
                  _drawerItem(Icons.attach_money, 'Kas Masuk & Keluar', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const CashScreen()));
                  }),
                  _drawerItem(Icons.swap_horiz, 'Transfer Antar Akun', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const TransferScreen()));
                  }),
                  const Divider(),
                  _drawerItem(Icons.bar_chart, 'Laporan Keuangan', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const ReportsScreen()));
                  }),
                  _drawerItem(Icons.manage_accounts, 'Manajemen Pengguna', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const UsersScreen()));
                  }),
                  _drawerItem(Icons.settings, 'Pengaturan & Printer', () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const SettingsScreen()));
                  }),
                  const Divider(),
                  // ── LOGOUT ────────────────────────────────────────
                  ListTile(
                    leading: const Icon(Icons.logout_rounded, color: Colors.red, size: 20),
                    title: const Text(
                      'Keluar / Logout',
                      style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.red),
                    ),
                    dense: true,
                    onTap: () async {
                      final confirmed = await showDialog<bool>(
                        context: context,
                        builder: (ctx) => AlertDialog(
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                          title: const Row(
                            children: [
                              Icon(Icons.logout_rounded, color: Colors.red, size: 22),
                              SizedBox(width: 8),
                              Text('Konfirmasi Logout'),
                            ],
                          ),
                          content: const Text(
                            'Apakah Anda yakin ingin keluar dari aplikasi? Semua sesi akan dihapus.',
                          ),
                          actions: [
                            TextButton(
                              onPressed: () => Navigator.pop(ctx, false),
                              child: const Text('Batal'),
                            ),
                            ElevatedButton(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.red,
                                foregroundColor: Colors.white,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                              onPressed: () => Navigator.pop(ctx, true),
                              child: const Text('Ya, Keluar'),
                            ),
                          ],
                        ),
                      );
                      if (confirmed == true && context.mounted) {
                        await ApiService.logout();
                        if (context.mounted) {
                          Navigator.pushNamedAndRemoveUntil(context, '/login', (route) => false);
                        }
                      }
                    },
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _drawerItem(IconData icon, String title, VoidCallback onTap) {
    return ListTile(
      leading: Icon(icon, color: ThemeConfig.primary, size: 20),
      title: Text(title, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
      dense: true,
      onTap: onTap,
    );
  }
}
