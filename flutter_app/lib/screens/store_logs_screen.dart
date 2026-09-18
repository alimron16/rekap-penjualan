import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../utils/formatters.dart';
import '../utils/theme_config.dart';

class StoreLogsScreen extends StatefulWidget {
  final int initialTabIndex;

  const StoreLogsScreen({super.key, this.initialTabIndex = 0});

  @override
  State<StoreLogsScreen> createState() => _StoreLogsScreenState();
}

class _StoreLogsScreenState extends State<StoreLogsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoading = true;

  List<dynamic> _withdrawals = [];
  List<dynamic> _stockIn = [];
  List<dynamic> _stockOut = [];
  List<dynamic> _returns = [];

  final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this, initialIndex: widget.initialTabIndex);
    _loadLogs();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _loadLogs() async {
    setState(() => _isLoading = true);
    try {
      final res = await ApiService.getUnifiedLogs();
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _withdrawals = res['withdrawals'] ?? [];
            _stockIn = res['stock_in'] ?? [];
            _stockOut = res['stock_out'] ?? [];
            _returns = res['returns'] ?? [];
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('Histori Operasional Toko', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: ThemeConfig.primary,
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadLogs),
        ],
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          indicatorColor: Colors.amber,
          indicatorWeight: 3,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
          tabs: const [
            Tab(icon: Icon(Icons.payments_outlined, size: 16), text: 'TARIK TUNAI'),
            Tab(icon: Icon(Icons.input, size: 16), text: 'BARANG MASUK'),
            Tab(icon: Icon(Icons.output, size: 16), text: 'BARANG KELUAR'),
            Tab(icon: Icon(Icons.assignment_return_outlined, size: 16), text: 'RETUR PENJUALAN'),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
          : TabBarView(
              controller: _tabController,
              children: [
                _buildLogList(_withdrawals, 'Tarik Tunai', Icons.payments, Colors.amber.shade800),
                _buildLogList(_stockIn, 'Barang Masuk (Kulakan)', Icons.input, Colors.green),
                _buildLogList(_stockOut, 'Barang Keluar (Penjualan)', Icons.output, Colors.blue),
                _buildLogList(_returns, 'Retur Penjualan', Icons.assignment_return, Colors.red),
              ],
            ),
    );
  }

  Widget _buildLogList(List<dynamic> items, String emptyLabel, IconData icon, Color badgeColor) {
    if (items.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 54, color: Colors.grey.shade400),
            const SizedBox(height: 12),
            Text('Belum ada data $emptyLabel.', style: TextStyle(fontSize: 13, color: Colors.grey.shade600, fontWeight: FontWeight.w500)),
          ],
        ),
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(14),
      itemCount: items.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, index) {
        final item = items[index];
        final amount = Formatters.parseDouble(item['amount']);
        final fee = Formatters.parseDouble(item['fee']);
        final title = item['title'] ?? '-';
        final number = item['number'] ?? '';
        final date = item['date'] ?? '';
        final badge = item['badge'] ?? '';

        return Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.grey.shade200),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: badgeColor.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, color: badgeColor, size: 18),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: badgeColor.withOpacity(0.15),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            badge,
                            style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: badgeColor),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(date, style: TextStyle(fontSize: 10, color: Colors.grey.shade500)),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    const SizedBox(height: 4),
                    if (number.isNotEmpty)
                      Text(number, style: const TextStyle(fontFamily: 'monospace', fontSize: 10, color: Colors.blueGrey)),
                    if (fee > 0) ...[
                      const SizedBox(height: 2),
                      Text('Admin Fee: ${currencyFormatter.format(fee)}', style: const TextStyle(fontSize: 10, color: Colors.green, fontWeight: FontWeight.w600)),
                    ],
                  ],
                ),
              ),
              Text(
                currencyFormatter.format(amount),
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
                  color: badgeColor,
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
