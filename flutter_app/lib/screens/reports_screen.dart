import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../utils/theme_config.dart';

class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key});

  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> {
  Map<String, dynamic>? _reports;
  bool _isLoading = true;

  final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  @override
  void initState() {
    super.initState();
    _loadReports();
  }

  void _loadReports() async {
    setState(() => _isLoading = true);
    try {
      final res = await ApiService.getReports();
      if (mounted && res['success'] == true) {
        setState(() {
          _reports = res;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final profitLoss = _reports?['profit_loss'];
    final balanceSheet = _reports?['balance_sheet'];

    final double netProfit = double.tryParse((profitLoss?['net_profit'] ?? 0).toString()) ?? 0;
    final double totalAssets = double.tryParse((balanceSheet?['total_assets'] ?? 0).toString()) ?? 0;

    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        title: const Text('Laporan Keuangan', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: ThemeConfig.primary,
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadReports),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Laba Rugi Card
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 10)],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Row(
                          children: [
                            Icon(Icons.show_chart, color: Colors.green),
                            SizedBox(width: 8),
                            Text('Ringkasan Laba Rugi', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                          ],
                        ),
                        const Divider(height: 20),
                        _buildRow('Total Pendapatan Penjualan', profitLoss?['total_revenue'] ?? 0),
                        _buildRow('Harga Pokok Penjualan (HPP)', profitLoss?['total_cogs'] ?? 0),
                        _buildRow('Total Beban Operasional', profitLoss?['total_expenses'] ?? 0),
                        const Divider(height: 20),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text('Laba Bersih (Net Profit)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            Text(
                              currencyFormatter.format(netProfit),
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 15,
                                color: netProfit >= 0 ? Colors.green : Colors.red,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Neraca Card
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 10)],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Row(
                          children: [
                            Icon(Icons.balance, color: Colors.blue),
                            SizedBox(width: 8),
                            Text('Posisi Neraca Keuangan', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                          ],
                        ),
                        const Divider(height: 20),
                        _buildRow('Total Aktiva (Kas, Bank & Persediaan)', totalAssets),
                        _buildRow('Total Kewajiban / Hutang', balanceSheet?['total_liabilities'] ?? 0),
                        _buildRow('Total Modal & Ekuitas', balanceSheet?['total_equity'] ?? 0),
                      ],
                    ),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _buildRow(String label, dynamic value) {
    final double val = double.tryParse(value.toString()) ?? 0;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontSize: 12, color: Colors.grey.shade700)),
          Text(currencyFormatter.format(val), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}
