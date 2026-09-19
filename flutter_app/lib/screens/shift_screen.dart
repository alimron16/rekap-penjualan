import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/formatters.dart';
import '../utils/theme_config.dart';

class ShiftScreen extends StatefulWidget {
  const ShiftScreen({super.key});

  @override
  State<ShiftScreen> createState() => _ShiftScreenState();
}

class _ShiftScreenState extends State<ShiftScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _shiftData;
  final _depositController = TextEditingController();
  final _notesController = TextEditingController();
  bool _isSubmitting = false;

  final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  @override
  void initState() {
    super.initState();
    _loadShiftData();
  }

  void _loadShiftData() async {
    setState(() => _isLoading = true);
    try {
      final res = await ApiService.getShiftSummary();
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _shiftData = res;
            final rec = (res['recommended_deposit'] as num?)?.toDouble() ?? 0.0;
            _depositController.text = rec > 0 ? rec.toInt().toString() : '0';
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _submitCloseShift() async {
    final double? amount = double.tryParse(_depositController.text.replaceAll('.', '').replaceAll(',', ''));
    if (amount == null || amount <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Masukkan nominal setoran yang valid!'), backgroundColor: Colors.red),
      );
      return;
    }

    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: const [
            Icon(Icons.assignment_turned_in_outlined, color: Colors.green),
            SizedBox(width: 8),
            Text('Konfirmasi Setoran', style: TextStyle(fontSize: 16)),
          ],
        ),
        content: Text(
          'Uang tunai sebesar ${currencyFormatter.format(amount)} akan disetorkan ke brankas/pusat.\n\nSisa modal awal Rp 400.000 akan tetap berada di laci kasir untuk kasir shift selanjutnya.\n\nLanjutkan proses setor?',
          style: const TextStyle(fontSize: 13),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: ThemeConfig.primary),
            child: const Text('Ya, Setor Uang'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() => _isSubmitting = true);

    try {
      final res = await ApiService.closeShift(
        depositAmount: amount,
        notes: _notesController.text.trim(),
      );

      setState(() => _isSubmitting = false);

      if (res['success'] == true) {
        NotificationService.showNotification(
          id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
          title: 'Setoran Shift Kasir Berhasil',
          body: 'Uang penjualan ${currencyFormatter.format(amount)} sukses disetor ke brankas/pusat.',
        );

        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res['message'] ?? 'Setor shift berhasil!'), backgroundColor: ThemeConfig.primary),
        );

        _loadShiftData();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res['message'] ?? 'Gagal setor shift'), backgroundColor: Colors.red),
        );
      }
    } catch (e) {
      setState(() => _isSubmitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final summary = _shiftData?['summary'] ?? {};
    final drawerBalance = (_shiftData?['cash_drawer_balance'] as num?)?.toDouble() ?? 0.0;
    final requiredReserve = (_shiftData?['required_reserve'] as num?)?.toDouble() ?? 400000.0;
    final recommendedDeposit = (_shiftData?['recommended_deposit'] as num?)?.toDouble() ?? 0.0;
    final cashierName = _shiftData?['user']?['name'] ?? 'Kasir';
    final storeName = _shiftData?['user']?['store_name'] ?? 'Toko';

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text(
          'Rekap Shift & Setor Penjualan',
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
        ),
        backgroundColor: ThemeConfig.primary,
        iconTheme: const IconThemeData(color: Colors.white),
        elevation: 1,
        actions: [
          IconButton(icon: const Icon(Icons.refresh, color: Colors.white), onPressed: _loadShiftData),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Store & Cashier Header
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: ThemeConfig.primary,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 10, offset: const Offset(0, 4)),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(storeName, style: const TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.w600)),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: Colors.white.withOpacity(0.15),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                'Shift Aktif: $cashierName',
                                style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text('Saldo Kas Laci (Cash Retail)', style: TextStyle(color: Colors.white70, fontSize: 11)),
                                const SizedBox(height: 2),
                                Text(
                                  currencyFormatter.format(drawerBalance),
                                  style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w900),
                                ),
                              ],
                            ),
                            if (_shiftData?['cash_transfer_balance'] != null)
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  const Text('Kas Transfer (Laci):', style: TextStyle(color: Colors.white60, fontSize: 10)),
                                  Text(
                                    currencyFormatter.format((_shiftData!['cash_transfer_balance'] as num).toDouble()),
                                    style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold),
                                  ),
                                ],
                              ),
                          ],
                        ),
                        const Divider(color: Colors.white24, height: 20),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text('Wajib Cadangan Modal:', style: TextStyle(color: Colors.white60, fontSize: 10)),
                                Text(currencyFormatter.format(requiredReserve), style: const TextStyle(color: Colors.amber, fontSize: 13, fontWeight: FontWeight.bold)),
                              ],
                            ),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                const Text('Rekomendasi Setoran:', style: TextStyle(color: Colors.white60, fontSize: 10)),
                                Text(currencyFormatter.format(recommendedDeposit), style: const TextStyle(color: Color(0xFFA7F3D0), fontSize: 13, fontWeight: FontWeight.bold)),
                              ],
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 18),

                  // Sales & Operational Breakdown
                  const Text('Rincian Operasional Shift Hari Ini', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: ThemeConfig.textDark)),
                  const SizedBox(height: 10),

                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.grey.shade200),
                    ),
                    child: Column(
                      children: [
                        _buildSummaryRow(
                          'Penjualan Tunai Retail',
                          summary['cash_sales'] ?? 0,
                          icon: Icons.payments_outlined,
                          color: Colors.green.shade700,
                        ),
                        const Divider(height: 16),
                        _buildSummaryRow(
                          'Penjualan Produk Multi (Pulsa / PLN)',
                          summary['total_digital_sales'] ?? 0,
                          icon: Icons.phone_android_outlined,
                          color: Colors.teal.shade700,
                          subtitle: 'Margin laba: ${currencyFormatter.format(summary['total_digital_profit'] ?? 0)} (${summary['digital_sales_count'] ?? 0} transaksi)',
                        ),
                        const Divider(height: 16),
                        _buildSummaryRow(
                          'Jasa Transfer Agen (Uang Masuk)',
                          summary['total_transfer_cash'] ?? 0,
                          icon: Icons.send_to_mobile_outlined,
                          color: Colors.indigo.shade700,
                          subtitle: 'Fee admin: ${currencyFormatter.format(summary['total_transfer_fee'] ?? 0)} (${summary['transfer_count'] ?? 0} transaksi)',
                        ),
                        const Divider(height: 16),
                        _buildSummaryRow(
                          'Penjualan Non-Tunai (TF / QRIS)',
                          summary['non_cash_sales'] ?? 0,
                          icon: Icons.qr_code_2_outlined,
                          color: Colors.blue.shade700,
                          subtitle: 'Masuk langsung ke rekening Bank Pusat',
                        ),
                        const Divider(height: 16),
                        _buildSummaryRow(
                          'Penjualan Tempo (Piutang)',
                          summary['receivable_sales'] ?? 0,
                          icon: Icons.receipt_long_outlined,
                          color: Colors.orange.shade800,
                        ),
                        const Divider(height: 16),
                        _buildSummaryRow(
                          'Tarik Tunai Pelanggan (Uang Keluar)',
                          summary['total_withdraw_cash'] ?? 0,
                          icon: Icons.local_atm_outlined,
                          color: Colors.amber.shade900,
                          isNegative: true,
                          subtitle: 'Fee admin masuk: ${currencyFormatter.format(summary['total_withdraw_fee'] ?? 0)}',
                        ),
                        const Divider(height: 16),
                        _buildSummaryRow(
                          'Biaya Kas Keluar (Makan, Sampah, dll)',
                          summary['total_expense'] ?? 0,
                          icon: Icons.shopping_bag_outlined,
                          color: Colors.red.shade700,
                          isNegative: true,
                        ),
                        const Divider(height: 20, thickness: 1.5),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text('Total Transaksi Nota', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            Text('${summary['total_transactions'] ?? 0} Transaksi', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: ThemeConfig.primary)),
                          ],
                        ),
                      ],
                    ),
                  ),

                  // Histori Transaksi Produk Multi Shift Ini
                  if (_shiftData?['digital_sales'] != null && (_shiftData!['digital_sales'] as List).isNotEmpty) ...[
                    const SizedBox(height: 18),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: const [
                            Icon(Icons.bolt_outlined, color: Colors.teal, size: 18),
                            SizedBox(width: 6),
                            Text(
                              'Histori Produk Multi Shift Ini',
                              style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                            ),
                          ],
                        ),
                        Text(
                          '${(_shiftData!['digital_sales'] as List).length} Transaksi',
                          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Colors.blueGrey),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: Colors.grey.shade200),
                      ),
                      child: ListView.separated(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: (_shiftData!['digital_sales'] as List).length,
                        separatorBuilder: (_, __) => const Divider(height: 1),
                        itemBuilder: (context, idx) {
                          final item = (_shiftData!['digital_sales'] as List)[idx];
                          final prodName = item['product_name'] ?? 'Pulsa / Elektrik';
                          final custNum = item['customer_number'] ?? '-';
                          final price = (item['selling_price'] as num?)?.toDouble() ?? 0.0;
                          final profit = (item['profit_margin'] as num?)?.toDouble() ?? 0.0;
                          final timeStr = item['date'] ?? '';

                          return ListTile(
                            dense: true,
                            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
                            leading: Container(
                              padding: const EdgeInsets.all(6),
                              decoration: BoxDecoration(
                                color: Colors.teal.shade50,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Icon(Icons.phone_android, color: Colors.teal.shade700, size: 18),
                            ),
                            title: Text(prodName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                            subtitle: Text('$custNum • $timeStr', style: const TextStyle(fontSize: 11, color: Colors.blueGrey)),
                            trailing: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                Text(currencyFormatter.format(price), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: ThemeConfig.textDark)),
                                Text('+${currencyFormatter.format(profit)}', style: TextStyle(fontSize: 10, color: Colors.green.shade700, fontWeight: FontWeight.w600)),
                              ],
                            ),
                          );
                        },
                      ),
                    ),
                  ],
                  const SizedBox(height: 20),

                  // Close Shift & Deposit Box
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.amber.shade300),
                      boxShadow: [
                        BoxShadow(color: Colors.amber.shade100.withOpacity(0.5), blurRadius: 8, offset: const Offset(0, 2)),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: const [
                            Icon(Icons.inventory, color: Colors.amber, size: 20),
                            SizedBox(width: 8),
                            Text('Setor Uang Penjualan & Sisakan Modal', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                          ],
                        ),
                        const SizedBox(height: 6),
                        const Text(
                          'Setor uang penjualan hari ini ke brankas/pusat. Sistem akan otomatis menjaga modal awal Rp 400.000 tetap di laci untuk shift berikutnya.',
                          style: TextStyle(fontSize: 11, color: Colors.blueGrey),
                        ),
                        const SizedBox(height: 14),

                        const Text('Nominal Uang Disetor (Rp)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                        const SizedBox(height: 6),
                        TextField(
                          controller: _depositController,
                          keyboardType: TextInputType.number,
                          decoration: const InputDecoration(
                            prefixText: 'Rp ',
                            hintText: '0',
                            contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                          ),
                        ),
                        const SizedBox(height: 12),

                        const Text('Catatan / Keterangan (Opsional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                        const SizedBox(height: 6),
                        TextField(
                          controller: _notesController,
                          decoration: const InputDecoration(
                            hintText: 'Contoh: Setoran uang shift 1 ke brankas',
                            contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                          ),
                        ),
                        const SizedBox(height: 16),

                        SizedBox(
                          width: double.infinity,
                          height: 46,
                          child: ElevatedButton(
                            onPressed: _isSubmitting ? null : _submitCloseShift,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: ThemeConfig.primary,
                              foregroundColor: Colors.white,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            child: _isSubmitting
                                ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                                : const Text('Setor Uang & Tutup Shift', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _buildSummaryRow(String title, dynamic value, {required IconData icon, required Color color, bool isNegative = false, String? subtitle}) {
    final double amount = Formatters.parseDouble(value);
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: color),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
              if (subtitle != null) ...[
                const SizedBox(height: 2),
                Text(subtitle, style: TextStyle(fontSize: 10, color: Colors.grey.shade600, fontStyle: FontStyle.italic)),
              ],
            ],
          ),
        ),
        Text(
          (isNegative ? '- ' : '') + currencyFormatter.format(amount),
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 13,
            color: isNegative ? Colors.red.shade700 : Colors.black87,
          ),
        ),
      ],
    );
  }
}
