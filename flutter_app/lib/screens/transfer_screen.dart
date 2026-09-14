import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/theme_config.dart';

class TransferScreen extends StatefulWidget {
  const TransferScreen({super.key});

  @override
  State<TransferScreen> createState() => _TransferScreenState();
}

class _TransferScreenState extends State<TransferScreen> {
  List<dynamic> _transfers = [];
  Map<String, dynamic>? _user;
  bool _isLoading = true;

  final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  // Form Controllers
  final _bankNameController = TextEditingController();
  final _accountNumberController = TextEditingController();
  final _accountHolderController = TextEditingController();
  final _amountController = TextEditingController();
  final _notesController = TextEditingController();

  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _loadTransfers();
  }

  void _loadTransfers() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final user = await ApiService.getUser();
      final res = await ApiService.getTransfers();

      if (mounted) {
        setState(() {
          _user = user;
          if (res['success'] == true) {
            _transfers = res['data']['data'] ?? [];
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  void _submitTransferRequest() async {
    if (_bankNameController.text.isEmpty ||
        _accountNumberController.text.isEmpty ||
        _accountHolderController.text.isEmpty ||
        _amountController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Harap isi semua kolom wajib!'), backgroundColor: Colors.red),
      );
      return;
    }

    final double? amount = double.tryParse(_amountController.text.replaceAll('.', '').replaceAll(',', ''));
    if (amount == null || amount < 1000) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nominal transfer minimal Rp 1.000'), backgroundColor: Colors.red),
      );
      return;
    }

    setState(() {
      _isSubmitting = true;
    });

    try {
      final res = await ApiService.requestTransfer(
        bankName: _bankNameController.text.trim(),
        accountNumber: _accountNumberController.text.trim(),
        accountHolder: _accountHolderController.text.trim(),
        amount: amount,
        notes: _notesController.text.trim(),
      );

      setState(() {
        _isSubmitting = false;
      });

      if (res['success'] == true) {
        if (!mounted) return;
        Navigator.pop(context); // Close modal

        // Clear Form
        _bankNameController.clear();
        _accountNumberController.clear();
        _accountHolderController.clear();
        _amountController.clear();
        _notesController.clear();

        // Show Native Notification
        NotificationService.showNotification(
          id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
          title: '🔔 Pengajuan Transfer Terkirim!',
          body: 'Pengajuan transfer Rp ${currencyFormatter.format(amount)} telah dikirim ke admin.',
        );

        _loadTransfers();

        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Pengajuan transfer berhasil dikirim!'),
            backgroundColor: ThemeConfig.primary,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res['message'] ?? 'Gagal mengirim pengajuan'), backgroundColor: Colors.red),
        );
      }
    } catch (e) {
      setState(() {
        _isSubmitting = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Koneksi gagal. Coba lagi.'), backgroundColor: Colors.red),
      );
    }
  }

  void _openRequestModal() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
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
              return SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Form Ajukan Transfer',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close),
                          onPressed: () => Navigator.pop(context),
                        )
                      ],
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: _bankNameController,
                      decoration: const InputDecoration(
                        labelText: 'Nama Bank / E-Wallet',
                        hintText: 'BCA, Mandiri, BRI, DANA',
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _accountNumberController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(
                        labelText: 'Nomor Rekening',
                        hintText: '1234567890',
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _accountHolderController,
                      decoration: const InputDecoration(
                        labelText: 'Nama Pemilik Rekening',
                        hintText: 'AHMAD FADLI',
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _amountController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(
                        labelText: 'Nominal Transfer (Rp)',
                        hintText: '100000',
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _notesController,
                      decoration: const InputDecoration(
                        labelText: 'Catatan Opsional',
                        hintText: 'Transfer modal kasir',
                      ),
                    ),
                    const SizedBox(height: 20),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: _isSubmitting ? null : _submitTransferRequest,
                        child: _isSubmitting
                            ? const SizedBox(
                                height: 20,
                                width: 20,
                                child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                              )
                            : const Text('Kirim Pengajuan Transfer'),
                      ),
                    )
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
    return Scaffold(
      appBar: AppBar(
        title: const Text('Transfer Antar Agen & Bank'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadTransfers,
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openRequestModal,
        backgroundColor: ThemeConfig.primary,
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text('Ajukan Transfer', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
          : RefreshIndicator(
              onRefresh: () async => _loadTransfers(),
              child: _transfers.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.compare_arrows_rounded, size: 64, color: Colors.grey.shade300),
                          const SizedBox(height: 12),
                          const Text(
                            'Belum ada pengajuan transfer',
                            style: TextStyle(fontSize: 14, color: ThemeConfig.textMuted),
                          ),
                          const SizedBox(height: 16),
                          ElevatedButton(
                            onPressed: _openRequestModal,
                            child: const Text('Ajukan Transfer Baru'),
                          )
                        ],
                      ),
                    )
                  : ListView.separated(
                      padding: const EdgeInsets.all(16),
                      itemCount: _transfers.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 10),
                      itemBuilder: (context, index) {
                        final item = _transfers[index];
                        final String ref = item['reference_no'] ?? '-';
                        final String bank = item['bank_name'] ?? '-';
                        final String accNum = item['account_number'] ?? '-';
                        final String holder = item['account_holder'] ?? '-';
                        final double amount = (item['amount'] ?? 0).toDouble();
                        final String status = item['status'] ?? 'pending';

                        Color statusColor = Colors.amber.shade700;
                        if (status == 'approved') statusColor = Colors.green.shade700;
                        if (status == 'rejected') statusColor = Colors.red.shade700;

                        return Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: Colors.grey.shade300),
                            boxShadow: [
                              BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 8),
                            ],
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    ref,
                                    style: const TextStyle(
                                      fontSize: 11,
                                      fontWeight: FontWeight.bold,
                                      color: ThemeConfig.textMuted,
                                    ),
                                  ),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                    decoration: BoxDecoration(
                                      color: statusColor.withOpacity(0.1),
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Text(
                                      status.toUpperCase(),
                                      style: TextStyle(
                                        fontSize: 10,
                                        fontWeight: FontWeight.bold,
                                        color: statusColor,
                                      ),
                                    ),
                                  )
                                ],
                              ),
                              const Divider(height: 16),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        '$bank - $accNum',
                                        style: const TextStyle(
                                          fontSize: 14,
                                          fontWeight: FontWeight.bold,
                                          color: ThemeConfig.textDark,
                                        ),
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        'a/n $holder',
                                        style: const TextStyle(
                                          fontSize: 12,
                                          color: ThemeConfig.textMuted,
                                        ),
                                      ),
                                    ],
                                  ),
                                  Text(
                                    currencyFormatter.format(amount),
                                    style: TextStyle(
                                      fontSize: 15,
                                      fontWeight: FontWeight.w900,
                                      color: statusColor,
                                    ),
                                  )
                                ],
                              ),
                              if (item['notes'] != null && item['notes'].toString().isNotEmpty) ...[
                                const SizedBox(height: 8),
                                Text(
                                  'Catatan: ${item['notes']}',
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontStyle: FontStyle.italic,
                                    color: Colors.grey.shade600,
                                  ),
                                ),
                              ],
                            ],
                          ),
                        );
                      },
                    ),
            ),
    );
  }
}
