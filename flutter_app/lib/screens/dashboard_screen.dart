import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/theme_config.dart';
import 'pos_screen.dart';
import 'digital_screen.dart';
import 'cash_screen.dart';
import 'reports_screen.dart';
import 'transfer_screen.dart';
import 'products_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Map<String, dynamic>? _userData;
  Map<String, dynamic>? _stats;
  List<dynamic> _recentTransfers = [];
  bool _isLoading = true;

  final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  @override
  void initState() {
    super.initState();
    NotificationService.requestPermission();
    _loadDashboardData();
  }

  void _loadDashboardData() async {
    setState(() => _isLoading = true);
    try {
      final user = await ApiService.getUser();
      final data = await ApiService.getDashboard();

      if (mounted) {
        setState(() {
          _userData = user;
          if (data['success'] == true) {
            _stats = data['stats'];
            _recentTransfers = data['recent_transfers'] ?? [];

            final pendingCount = _stats?['pending_transfers'] ?? 0;
            if (pendingCount > 0 && (_userData?['role'] == 'admin' || _userData?['role'] == 'superadmin')) {
              NotificationService.showNotification(
                id: 101,
                title: '⚠️ Pengajuan Transfer Baru Menunggu',
                body: 'Ada $pendingCount pengajuan transfer agen yang butuh persetujuan Anda.',
              );
            }
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _handleLogout() async {
    await ApiService.logout();
    if (!mounted) return;
    Navigator.pushReplacementNamed(context, '/login');
  }

  void _triggerTestNotification() async {
    await NotificationService.showNotification(
      id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
      title: '🐘 ELEPHANT POS - Notifikasi Aktif!',
      body: 'Notifikasi status bar native Android berfungsi 100% sempurna dengan suara & getar!',
    );

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('🔔 Notifikasi berhasil dikirim ke Status Bar HP!'),
          backgroundColor: ThemeConfig.primary,
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final userName = _userData?['name'] ?? 'Pengguna';
    final storeName = _userData?['store_name'] ?? 'Elephant Cell';
    final role = (_userData?['role'] ?? 'user').toString().toUpperCase();

    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        backgroundColor: ThemeConfig.primary,
        elevation: 0,
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(8)),
              child: Image.asset(
                'assets/images/logo.png',
                width: 24,
                height: 24,
                errorBuilder: (_, __, ___) => const Icon(Icons.store, size: 24, color: ThemeConfig.primary),
              ),
            ),
            const SizedBox(width: 10),
            const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('ELEPHANT CELL', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, letterSpacing: 0.5)),
                Text('Sistem POS & Akuntansi Mandiri', style: TextStyle(fontSize: 10, color: Colors.white70)),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadDashboardData, tooltip: 'Segarkan'),
          IconButton(icon: const Icon(Icons.logout), onPressed: _handleLogout, tooltip: 'Keluar'),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
          : RefreshIndicator(
              onRefresh: () async => _loadDashboardData(),
              color: ThemeConfig.primary,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Header Profil Card
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [ThemeConfig.primary, ThemeConfig.primaryLight],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(color: ThemeConfig.primary.withOpacity(0.3), blurRadius: 10, offset: const Offset(0, 4))
                        ],
                      ),
                      child: Row(
                        children: [
                          CircleAvatar(
                            radius: 24,
                            backgroundColor: ThemeConfig.accent,
                            child: Text(
                              userName.isNotEmpty ? userName[0].toUpperCase() : 'U',
                              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white),
                            ),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(userName, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white)),
                                const SizedBox(height: 2),
                                Text('$storeName • $role', style: const TextStyle(fontSize: 11, color: Colors.white70)),
                              ],
                            ),
                          ),
                          ElevatedButton.icon(
                            onPressed: _triggerTestNotification,
                            icon: const Icon(Icons.notifications_active, size: 16),
                            label: const Text('Tes Notif', style: TextStyle(fontSize: 11)),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.amber.shade700,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                            ),
                          )
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Ringkasan Statistik
                    Row(
                      children: [
                        Expanded(
                          child: _buildSummaryCard(
                            title: 'Penjualan Hari Ini',
                            value: currencyFormatter.format(_stats?['sales_today'] ?? 0),
                            icon: Icons.payments_outlined,
                            color: Colors.green,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _buildSummaryCard(
                            title: 'Transaksi Hari Ini',
                            value: '${_stats?['trx_today'] ?? 0} Transaksi',
                            icon: Icons.receipt_long_outlined,
                            color: Colors.blue,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),

                    // ==========================================
                    // 1. MENU PENJUALAN & KASIR (100% NATIVE)
                    // ==========================================
                    _buildSectionTitle('1. Penjualan & Kasir', Icons.shopping_cart_outlined),
                    const SizedBox(height: 10),
                    GridView.count(
                      crossAxisCount: 2,
                      crossAxisSpacing: 10,
                      mainAxisSpacing: 10,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      childAspectRatio: 2.1,
                      children: [
                        _buildMenuCard('Kasir Eceran (Retail)', 'POS ritel harian', Icons.point_of_sale, Colors.green, () {
                          Navigator.push(context, MaterialPageRoute(builder: (_) => const PosScreen(saleType: 'retail')));
                        }),
                        _buildMenuCard('Kasir Grosir', 'Transaksi grosir', Icons.storefront, Colors.teal, () {
                          Navigator.push(context, MaterialPageRoute(builder: (_) => const PosScreen(saleType: 'grosir')));
                        }),
                        _buildMenuCard('Produk Elektrik', 'Pulsa, Data & PLN', Icons.bolt, Colors.amber.shade800, () {
                          Navigator.push(context, MaterialPageRoute(builder: (_) => const DigitalScreen()));
                        }),
                        _buildMenuCard('Master Barang / Item', 'Katalog & stok item', Icons.inventory_2_outlined, Colors.blue.shade700, () {
                          Navigator.push(context, MaterialPageRoute(builder: (_) => const ProductsScreen()));
                        }),
                      ],
                    ),
                    const SizedBox(height: 24),

                    // ==========================================
                    // 2. TRANSFER AGEN & BANK (100% NATIVE)
                    // ==========================================
                    _buildSectionTitle('2. Transfer Agen & Bank', Icons.compare_arrows_rounded),
                    const SizedBox(height: 10),
                    GridView.count(
                      crossAxisCount: 2,
                      crossAxisSpacing: 10,
                      mainAxisSpacing: 10,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      childAspectRatio: 2.1,
                      children: [
                        _buildMenuCard('Transfer Agen & Bank', 'Pengajuan transfer', Icons.swap_horiz_rounded, Colors.green.shade800, () {
                          Navigator.push(context, MaterialPageRoute(builder: (_) => const TransferScreen()));
                        }),
                      ],
                    ),
                    const SizedBox(height: 24),

                    // ==========================================
                    // 3. AKUNTANSI, KAS & LAPORAN (100% NATIVE)
                    // ==========================================
                    _buildSectionTitle('3. Akuntansi & Laporan', Icons.account_balance_outlined),
                    const SizedBox(height: 10),
                    GridView.count(
                      crossAxisCount: 2,
                      crossAxisSpacing: 10,
                      mainAxisSpacing: 10,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      childAspectRatio: 2.1,
                      children: [
                        _buildMenuCard('Kas Masuk', 'Penerimaan tunai', Icons.arrow_downward_rounded, Colors.green.shade700, () {
                          Navigator.push(context, MaterialPageRoute(builder: (_) => const CashScreen(initialType: 'in')));
                        }),
                        _buildMenuCard('Kas Keluar', 'Pengeluaran beban', Icons.arrow_upward_rounded, Colors.red.shade600, () {
                          Navigator.push(context, MaterialPageRoute(builder: (_) => const CashScreen(initialType: 'out')));
                        }),
                        _buildMenuCard('Laporan Keuangan', 'Laba rugi & neraca', Icons.analytics_outlined, Colors.purple.shade800, () {
                          Navigator.push(context, MaterialPageRoute(builder: (_) => const ReportsScreen()));
                        }),
                      ],
                    ),
                    const SizedBox(height: 30),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildSectionTitle(String title, IconData icon) {
    return Row(
      children: [
        Icon(icon, size: 18, color: ThemeConfig.primary),
        const SizedBox(width: 8),
        Text(title, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: ThemeConfig.textDark)),
      ],
    );
  }

  Widget _buildSummaryCard({
    required String title,
    required String value,
    required IconData icon,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.grey.shade200),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 10, offset: const Offset(0, 4)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 16, color: color),
              const SizedBox(width: 6),
              Expanded(
                child: Text(title, style: TextStyle(fontSize: 11, color: Colors.grey.shade600, fontWeight: FontWeight.w500), maxLines: 1, overflow: TextOverflow.ellipsis),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(value, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: ThemeConfig.textDark)),
        ],
      ),
    );
  }

  Widget _buildMenuCard(String title, String subtitle, IconData icon, Color color, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.grey.shade200),
          boxShadow: [
            BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 6, offset: const Offset(0, 2)),
          ],
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(color: color.withOpacity(0.12), borderRadius: BorderRadius.circular(10)),
              child: Icon(icon, color: color, size: 20),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(title, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: ThemeConfig.textDark), maxLines: 1, overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 2),
                  Text(subtitle, style: TextStyle(fontSize: 10, color: Colors.grey.shade500), maxLines: 1, overflow: TextOverflow.ellipsis),
                ],
              ),
            ),
            Icon(Icons.chevron_right, size: 16, color: Colors.grey.shade400),
          ],
        ),
      ),
    );
  }
}
