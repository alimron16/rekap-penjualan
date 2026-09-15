import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/formatters.dart';
import '../utils/theme_config.dart';

class TransferScreen extends StatefulWidget {
  const TransferScreen({super.key});

  @override
  State<TransferScreen> createState() => _TransferScreenState();
}

class _TransferScreenState extends State<TransferScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<dynamic> _transfers = [];
  List<dynamic> _bankAccounts = [];
  Map<String, dynamic>? _user;
  bool _isLoading = true;
  String? _errorMessage;

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
    _tabController = TabController(length: 4, vsync: this);
    _loadInitialData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _loadInitialData() async {
    final user = await ApiService.getUser();
    if (mounted) setState(() => _user = user);
    _loadTransfers();
  }

  void _loadTransfers() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiService.getTransfers();

      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            final data = res['data'];
            if (data is List) {
              _transfers = data;
            } else if (data is Map && data['data'] is List) {
              _transfers = data['data'];
            } else {
              _transfers = [];
            }
            _bankAccounts = res['bank_accounts'] ?? [];
          } else {
            _errorMessage = res['message'] ?? 'Gagal memuat data transfer';
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

  bool get _isAdmin {
    final role = _user?['role']?.toString().toLowerCase();
    return role == 'admin' || role == 'super_admin' || role == 'superadmin';
  }

  void _submitTransferRequest() async {
    if (_bankNameController.text.trim().isEmpty ||
        _accountNumberController.text.trim().isEmpty ||
        _accountHolderController.text.trim().isEmpty ||
        _amountController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Harap isi semua kolom wajib!'), backgroundColor: Colors.red),
      );
      return;
    }

    final double amount = Formatters.parseDouble(_amountController.text);
    if (amount < 1000) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nominal transfer minimal Rp 1.000'), backgroundColor: Colors.red),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final res = await ApiService.requestTransfer(
        bankName: _bankNameController.text.trim(),
        accountNumber: _accountNumberController.text.trim(),
        accountHolder: _accountHolderController.text.trim(),
        amount: amount,
        notes: _notesController.text.trim(),
      );

      setState(() => _isSubmitting = false);

      if (res['success'] == true) {
        if (!mounted) return;
        Navigator.pop(context);

        _bankNameController.clear();
        _accountNumberController.clear();
        _accountHolderController.clear();
        _amountController.clear();
        _notesController.clear();

        NotificationService.showNotification(
          id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
          title: '🔔 Pengajuan Transfer Terkirim!',
          body: 'Pengajuan transfer sebesar ${Formatters.formatRupiah(amount)} telah dikirim ke Admin.',
        );

        _loadTransfers();

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res['message'] ?? 'Pengajuan transfer berhasil dikirim!'),
            backgroundColor: ThemeConfig.primary,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res['message'] ?? 'Gagal mengirim pengajuan'), backgroundColor: Colors.red),
        );
      }
    } catch (e) {
      setState(() => _isSubmitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    }
  }

  void _showApproveDialog(dynamic transfer) {
    int? selectedSourceId = _bankAccounts.isNotEmpty ? _bankAccounts.first['id'] : null;
    final notesController = TextEditingController(text: 'Transfer disetujui via mobile');
    bool isProcessing = false;

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
                      'Persetujuan Transfer (ACC)',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                    ),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                  ],
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.green.withOpacity(0.08),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(transfer['bank_name'] ?? 'BANK', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                          Text('${transfer['account_number']} (${transfer['account_holder']})', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                        ],
                      ),
                      Text(
                        Formatters.formatRupiah(transfer['amount']),
                        style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.green, fontSize: 15),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                const Text('Pilih Rekening Sumber Dana (Debet)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<int>(
                  value: selectedSourceId,
                  isExpanded: true,
                  decoration: const InputDecoration(),
                  items: _bankAccounts.map<DropdownMenuItem<int>>((acc) {
                    final balance = Formatters.parseDouble(acc['current_balance']);
                    return DropdownMenuItem<int>(
                      value: acc['id'],
                      child: Text('${acc['code']} - ${acc['name']} (${Formatters.formatRupiah(balance)})', style: const TextStyle(fontSize: 12)),
                    );
                  }).toList(),
                  onChanged: (val) => setModalState(() => selectedSourceId = val),
                ),
                const SizedBox(height: 14),
                const Text('Catatan Persetujuan', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(controller: notesController, decoration: const InputDecoration(hintText: 'Keterangan approval')),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(backgroundColor: ThemeConfig.accent),
                    onPressed: isProcessing
                        ? null
                        : () async {
                            if (selectedSourceId == null) {
                              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Pilih akun sumber dana')));
                              return;
                            }
                            setModalState(() => isProcessing = true);

                            final res = await ApiService.approveTransfer(
                              transfer['id'],
                              sourceAccountId: selectedSourceId!,
                              notes: notesController.text.trim(),
                            );

                            if (res['success'] == true) {
                              Navigator.pop(context);
                              _loadTransfers();
                              NotificationService.showNotification(
                                id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                title: '✅ Transfer Disetujui!',
                                body: 'Transfer ${transfer['reference_no']} senilai ${Formatters.formatRupiah(transfer['amount'])} berhasil disetujui.',
                              );
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Transfer berhasil disetujui!'), backgroundColor: ThemeConfig.accent),
                              );
                            } else {
                              setModalState(() => isProcessing = false);
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Gagal memproses approval'), backgroundColor: Colors.red),
                              );
                            }
                          },
                    child: isProcessing
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text('Setujui & Potong Saldo (ACC)'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _showRejectDialog(dynamic transfer) {
    final reasonController = TextEditingController();
    bool isProcessing = false;

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Text('Tolak Pengajuan Transfer', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.red)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Apakah Anda yakin ingin menolak pengajuan transfer ${transfer['reference_no']} senilai ${Formatters.formatRupiah(transfer['amount'])}?'),
              const SizedBox(height: 14),
              const Text('Alasan Penolakan:', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
              const SizedBox(height: 6),
              TextField(
                controller: reasonController,
                decoration: const InputDecoration(hintText: 'Contoh: Nomor rekening tidak cocok / saldo tidak mencukupi'),
              ),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: const Text('Batal')),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
              onPressed: isProcessing
                  ? null
                  : () async {
                      if (reasonController.text.trim().isEmpty) {
                        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Alasan penolakan wajib diisi')));
                        return;
                      }
                      setDialogState(() => isProcessing = true);

                      final res = await ApiService.rejectTransfer(transfer['id'], notes: reasonController.text.trim());

                      if (res['success'] == true) {
                        Navigator.pop(context);
                        _loadTransfers();
                        NotificationService.showNotification(
                          id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                          title: '❌ Pengajuan Transfer Ditolak',
                          body: 'Pengajuan transfer ${transfer['reference_no']} telah ditolak.',
                        );
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Pengajuan transfer telah ditolak.'), backgroundColor: Colors.red),
                        );
                      } else {
                        setDialogState(() => isProcessing = false);
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(content: Text(res['message'] ?? 'Gagal menolak transfer'), backgroundColor: Colors.red),
                        );
                      }
                    },
              child: isProcessing
                  ? const SizedBox(height: 16, width: 16, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                  : const Text('Tolak Transfer'),
            ),
          ],
        ),
      ),
    );
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
                          'Form Pengajuan Transfer',
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
                        labelText: 'Bank Tujuan / E-Wallet',
                        hintText: 'BCA, MANDIRI, BRI, DANA',
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
                        hintText: 'NAMA LENGKAP',
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
                        labelText: 'Catatan (Opsional)',
                        hintText: 'Transfer modal agen kasir',
                      ),
                    ),
                    const SizedBox(height: 20),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: _isSubmitting ? null : _submitTransferRequest,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: ThemeConfig.primary,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                        ),
                        child: _isSubmitting
                            ? const SizedBox(
                                height: 20,
                                width: 20,
                                child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                              )
                            : const Text('Kirim Pengajuan Transfer', style: TextStyle(fontWeight: FontWeight.bold)),
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
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('Transfer Antar Agen & Bank', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: ThemeConfig.primary,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadTransfers,
            tooltip: 'Segarkan',
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: ThemeConfig.accent,
          indicatorWeight: 3,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Semua'),
            Tab(text: 'Menunggu'),
            Tab(text: 'Disetujui'),
            Tab(text: 'Ditolak'),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openRequestModal,
        backgroundColor: ThemeConfig.primary,
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text('Ajukan Transfer', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
          : _errorMessage != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24.0),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.error_outline, size: 48, color: Colors.red),
                        const SizedBox(height: 12),
                        Text(_errorMessage!, textAlign: TextAlign.center, style: const TextStyle(color: Colors.red)),
                        const SizedBox(height: 16),
                        ElevatedButton(onPressed: _loadTransfers, child: const Text('Coba Lagi')),
                      ],
                    ),
                  ),
                )
              : TabBarView(
                  controller: _tabController,
                  children: [
                    _buildTransferList(null),
                    _buildTransferList('pending'),
                    _buildTransferList('approved'),
                    _buildTransferList('rejected'),
                  ],
                ),
    );
  }

  Widget _buildTransferList(String? filterStatus) {
    final list = filterStatus == null
        ? _transfers
        : _transfers.where((t) => (t['status'] ?? '').toString().toLowerCase() == filterStatus).toList();

    if (list.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.compare_arrows_rounded, size: 64, color: Colors.grey.shade300),
            const SizedBox(height: 12),
            Text(
              filterStatus == null ? 'Belum ada riwayat pengajuan transfer' : 'Tidak ada transfer dengan status $filterStatus',
              style: const TextStyle(fontSize: 14, color: ThemeConfig.textMuted),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () async => _loadTransfers(),
      color: ThemeConfig.primary,
      child: ListView.separated(
        padding: const EdgeInsets.only(left: 16, right: 16, top: 16, bottom: 80),
        itemCount: list.length,
        separatorBuilder: (_, __) => const SizedBox(height: 10),
        itemBuilder: (context, index) {
          final item = list[index];
          final String ref = item['reference_no'] ?? '-';
          final String bank = item['bank_name'] ?? '-';
          final String accNum = item['account_number'] ?? '-';
          final String holder = item['account_holder'] ?? '-';
          final double amount = Formatters.parseDouble(item['amount']);
          final String status = (item['status'] ?? 'pending').toString().toLowerCase();
          final String sender = item['user']?['name'] ?? 'Kasir Agen';

          Color statusColor = Colors.amber.shade800;
          if (status == 'approved') statusColor = Colors.green.shade700;
          if (status == 'rejected') statusColor = Colors.red.shade700;

          return Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: Colors.grey.shade200),
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
                      '$ref • Pengaju: $sender',
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
                    Expanded(
                      child: Column(
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
                    ),
                    Text(
                      Formatters.formatRupiah(amount),
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

                // Admin Action Buttons (ACC & REJECT) for pending transfers
                if (_isAdmin && status == 'pending') ...[
                  const Divider(height: 20),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          style: OutlinedButton.styleFrom(
                            foregroundColor: Colors.red,
                            side: const BorderSide(color: Colors.red),
                            padding: const EdgeInsets.symmetric(vertical: 8),
                          ),
                          onPressed: () => _showRejectDialog(item),
                          icon: const Icon(Icons.cancel_outlined, size: 16),
                          label: const Text('Tolak', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: ThemeConfig.accent,
                            padding: const EdgeInsets.symmetric(vertical: 8),
                          ),
                          onPressed: () => _showApproveDialog(item),
                          icon: const Icon(Icons.check_circle_outline, size: 16),
                          label: const Text('Setujui (ACC)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                        ),
                      ),
                    ],
                  ),
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}
