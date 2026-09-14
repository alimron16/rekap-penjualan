import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/theme_config.dart';

class CashScreen extends StatefulWidget {
  final String initialType; // 'in' or 'out'

  const CashScreen({super.key, this.initialType = 'in'});

  @override
  State<CashScreen> createState() => _CashScreenState();
}

class _CashScreenState extends State<CashScreen> {
  List<dynamic> _transactions = [];
  List<dynamic> _accounts = [];
  bool _isLoading = true;
  late String _currentType;

  final _amountController = TextEditingController();
  final _descController = TextEditingController();
  int? _sourceAccountId;
  int? _oppositeAccountId;
  bool _isSubmitting = false;

  final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  @override
  void initState() {
    super.initState();
    _currentType = widget.initialType;
    _loadData();
  }

  void _loadData() async {
    setState(() => _isLoading = true);
    try {
      final accRes = await ApiService.getAccounts();
      final trxRes = await ApiService.getCashTransactions(type: _currentType);

      if (mounted) {
        setState(() {
          _accounts = accRes['data'] ?? [];
          _transactions = trxRes['data'] ?? [];
          if (_accounts.length >= 2) {
            _sourceAccountId = _accounts[0]['id'];
            _oppositeAccountId = _accounts[1]['id'];
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _submitCashTransaction() async {
    final double? amount = double.tryParse(_amountController.text.replaceAll('.', '').replaceAll(',', ''));
    if (amount == null || amount <= 0 || _descController.text.isEmpty || _sourceAccountId == null || _oppositeAccountId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Harap lengkapi semua kolom!'), backgroundColor: Colors.red),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final res = await ApiService.storeCashTransaction(
        type: _currentType,
        accountId: _sourceAccountId!,
        oppositeAccountId: _oppositeAccountId!,
        amount: amount,
        description: _descController.text.trim(),
      );

      setState(() => _isSubmitting = false);

      if (res['success'] == true) {
        if (!mounted) return;
        Navigator.pop(context);

        _amountController.clear();
        _descController.clear();

        final label = _currentType == 'in' ? 'Kas Masuk' : 'Kas Keluar';
        NotificationService.showNotification(
          id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
          title: '💰 $label Berhasil Dicatat',
          body: '$label senilai ${currencyFormatter.format(amount)} berhasil disimpan.',
        );

        _loadData();

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('$label berhasil disimpan!'), backgroundColor: ThemeConfig.primary),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res['message'] ?? 'Gagal menyimpan transaksi'), backgroundColor: Colors.red),
        );
      }
    } catch (e) {
      setState(() => _isSubmitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    }
  }

  void _openAddModal() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (context) {
        return Padding(
          padding: EdgeInsets.only(
            left: 20,
            right: 20,
            top: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: StatefulBuilder(
            builder: (context, setModalState) {
              final label = _currentType == 'in' ? 'Kas Masuk' : 'Kas Keluar';

              return SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Catat $label Baru', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 16),
                    TextField(
                      controller: _amountController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(labelText: 'Nominal (Rp)', hintText: '50000'),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _descController,
                      decoration: const InputDecoration(labelText: 'Keterangan Transaksi', hintText: 'Beban operasional / penerimaan'),
                    ),
                    const SizedBox(height: 20),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: _isSubmitting ? null : _submitCashTransaction,
                        style: ElevatedButton.styleFrom(backgroundColor: ThemeConfig.primary),
                        child: _isSubmitting
                            ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : Text('Simpan $label', style: const TextStyle(fontWeight: FontWeight.bold)),
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final title = _currentType == 'in' ? 'Kas Masuk' : 'Kas Keluar';

    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        title: Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: ThemeConfig.primary,
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadData),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openAddModal,
        backgroundColor: ThemeConfig.primary,
        icon: const Icon(Icons.add, color: Colors.white),
        label: Text('Tambah $title', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
          : _transactions.isEmpty
              ? Center(child: Text('Belum ada data riwayat $title'))
              : ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: _transactions.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (context, index) {
                    final t = _transactions[index];
                    final amount = double.tryParse(t['amount'].toString()) ?? 0;
                    final desc = t['description'] ?? '-';
                    final date = t['transaction_date'] ?? '-';

                    return Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.shade200),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(desc, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                const SizedBox(height: 2),
                                Text(date, style: TextStyle(fontSize: 11, color: Colors.grey.shade500)),
                              ],
                            ),
                          ),
                          Text(
                            currencyFormatter.format(amount),
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 14,
                              color: _currentType == 'in' ? Colors.green : Colors.red,
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
    );
  }
}
