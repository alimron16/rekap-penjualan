import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/formatters.dart';
import '../utils/theme_config.dart';

class ReceivablesScreen extends StatefulWidget {
  const ReceivablesScreen({super.key});

  @override
  State<ReceivablesScreen> createState() => _ReceivablesScreenState();
}

class _ReceivablesScreenState extends State<ReceivablesScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoading = true;
  String? _errorMessage;

  List<dynamic> _unpaidSales = [];
  List<dynamic> _payments = [];
  List<dynamic> _accounts = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _loadData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiService.getReceivables();
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _unpaidSales = res['unpaid_sales'] ?? [];
            _payments = res['payments'] ?? [];
            _accounts = res['accounts'] ?? [];
          } else {
            _errorMessage = res['message'] ?? 'Gagal memuat data piutang';
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = 'Gagal terhubung ke server: $e';
          _isLoading = false;
        });
      }
    }
  }

  void _showPayModal(dynamic sale) {
    final grandTotal = Formatters.parseDouble(sale['grand_total']);
    final paidTotal = Formatters.parseDouble(sale['paid_amount']);
    final remainingDebt = sale['remaining_debt'] != null
        ? Formatters.parseDouble(sale['remaining_debt'])
        : (grandTotal - paidTotal);

    final amountController = TextEditingController(text: remainingDebt.toStringAsFixed(0));
    final notesController = TextEditingController();
    int? selectedAccountId = _accounts.isNotEmpty ? _accounts.first['id'] : null;
    DateTime selectedDate = DateTime.now();
    bool isSubmitting = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) => Container(
          padding: EdgeInsets.only(
            top: 24,
            left: 20,
            right: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 24,
          ),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Pelunasan Piutang',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: ThemeConfig.primary.withOpacity(0.08),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            sale['customer']?['name'] ?? 'Pelanggan Umum',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                          ),
                          Text('Faktur: ${sale['invoice_no'] ?? '-'}', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                        ],
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          const Text('Sisa Piutang', style: TextStyle(fontSize: 11, color: Colors.grey)),
                          Text(
                            Formatters.formatRupiah(remainingDebt),
                            style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.red, fontSize: 14),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                const Text('Jumlah Pembayaran (Rp)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(
                  controller: amountController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(hintText: 'Masukkan nominal'),
                ),
                const SizedBox(height: 14),
                const Text('Akun Kas / Penerimaan', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<int>(
                  value: selectedAccountId,
                  decoration: const InputDecoration(),
                  items: _accounts.map<DropdownMenuItem<int>>((acc) {
                    return DropdownMenuItem<int>(
                      value: acc['id'],
                      child: Text('${acc['code']} - ${acc['name']}', style: const TextStyle(fontSize: 13)),
                    );
                  }).toList(),
                  onChanged: (val) => setModalState(() => selectedAccountId = val),
                ),
                const SizedBox(height: 14),
                const Text('Catatan (Opsional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(
                  controller: notesController,
                  decoration: const InputDecoration(hintText: 'Contoh: Transfer BCA atau Tunai'),
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    onPressed: isSubmitting
                        ? null
                        : () async {
                            final amt = Formatters.parseDouble(amountController.text);
                            if (amt <= 0) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Nominal harus lebih dari 0')),
                              );
                              return;
                            }
                            if (selectedAccountId == null) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Pilih akun kas penerimaan')),
                              );
                              return;
                            }

                            setModalState(() => isSubmitting = true);

                            try {
                              final res = await ApiService.payReceivable(
                                date: DateFormat('yyyy-MM-dd').format(selectedDate),
                                saleId: sale['id'],
                                customerId: sale['customer_id'] ?? 1,
                                amount: amt,
                                accountId: selectedAccountId!,
                                notes: notesController.text,
                              );

                              if (res['success'] == true) {
                                Navigator.pop(context);
                                _loadData();
                                NotificationService.showNotification(
                                  id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                  title: 'Pelunasan Piutang Berhasil! 💰',
                                  body: 'Pembayaran sebesar ${Formatters.formatRupiah(amt)} untuk ${sale['customer']?['name'] ?? 'Pelanggan'} berhasil dicatat.',
                                );
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text(res['message'] ?? 'Pembayaran berhasil disimpan!'),
                                    backgroundColor: ThemeConfig.accent,
                                  ),
                                );
                              } else {
                                setModalState(() => isSubmitting = false);
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text(res['message'] ?? 'Gagal memproses pembayaran'),
                                    backgroundColor: Colors.red,
                                  ),
                                );
                              }
                            } catch (e) {
                              setModalState(() => isSubmitting = false);
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
                              );
                            }
                          },
                    child: isSubmitting
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text('Simpan Pembayaran'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Piutang Usaha', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: ThemeConfig.accent,
          indicatorWeight: 3,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: [
            Tab(text: 'Belum Lunas (${_unpaidSales.length})'),
            Tab(text: 'Riwayat Pelunasan (${_payments.length})'),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
          : _errorMessage != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(_errorMessage!, style: const TextStyle(color: Colors.red)),
                      const SizedBox(height: 12),
                      ElevatedButton(onPressed: _loadData, child: const Text('Coba Lagi')),
                    ],
                  ),
                )
              : TabBarView(
                  controller: _tabController,
                  children: [
                    _buildUnpaidList(),
                    _buildPaymentHistoryList(),
                  ],
                ),
    );
  }

  Widget _buildUnpaidList() {
    if (_unpaidSales.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.check_circle_outline, size: 64, color: Colors.green),
            SizedBox(height: 12),
            Text('Semua piutang telah lunas!', style: TextStyle(fontSize: 16, color: Colors.grey)),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () async => _loadData(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _unpaidSales.length,
        itemBuilder: (ctx, i) {
          final s = _unpaidSales[i];
          final total = Formatters.parseDouble(s['grand_total']);
          final paid = Formatters.parseDouble(s['paid_amount']);
          final remaining = total - paid;

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            elevation: 1.5,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          s['customer']?['name'] ?? 'Pelanggan Umum',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: ThemeConfig.textDark),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.red.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Text(
                          'BELUM LUNAS',
                          style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 11),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'No. Faktur: ${s['invoice_no'] ?? '-'} • Tanggal: ${s['date'] ?? s['sale_date'] ?? '-'}',
                    style: const TextStyle(color: ThemeConfig.textMuted, fontSize: 12),
                  ),
                  const Divider(height: 24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Total Tagihan', style: TextStyle(fontSize: 12, color: ThemeConfig.textMuted)),
                          Text(Formatters.formatRupiah(total), style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                        ],
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          const Text('Sisa Piutang', style: TextStyle(fontSize: 12, color: Colors.red)),
                          Text(
                            Formatters.formatRupiah(remaining),
                            style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.red, fontSize: 15),
                          ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  SizedBox(
                    width: double.infinity,
                    height: 40,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(backgroundColor: ThemeConfig.primary),
                      onPressed: () => _showPayModal(s),
                      icon: const Icon(Icons.payment, size: 16),
                      label: const Text('Bayar / Lunasi Piutang', style: TextStyle(fontSize: 13)),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildPaymentHistoryList() {
    if (_payments.isEmpty) {
      return const Center(
        child: Text('Belum ada riwayat pelunasan piutang', style: TextStyle(color: Colors.grey)),
      );
    }

    return RefreshIndicator(
      onRefresh: () async => _loadData(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _payments.length,
        itemBuilder: (ctx, i) {
          final p = _payments[i];
          final amt = Formatters.parseDouble(p['amount']);

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            elevation: 1,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Row(
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: ThemeConfig.accent.withOpacity(0.12),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.arrow_downward, color: ThemeConfig.accent),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          p['customer']?['name'] ?? 'Pelanggan',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: ThemeConfig.textDark),
                        ),
                        Text(
                          '${p['date'] ?? '-'} • Kas: ${p['account']?['name'] ?? '-'}',
                          style: const TextStyle(color: ThemeConfig.textMuted, fontSize: 12),
                        ),
                        if (p['notes'] != null && p['notes'].toString().isNotEmpty)
                          Text('Catatan: ${p['notes']}', style: const TextStyle(fontSize: 11, fontStyle: FontStyle.italic)),
                      ],
                    ),
                  ),
                  Text(
                    '+${Formatters.formatRupiah(amt)}',
                    style: const TextStyle(fontWeight: FontWeight.bold, color: ThemeConfig.accent, fontSize: 14),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
