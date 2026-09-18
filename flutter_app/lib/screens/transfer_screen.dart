import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:cached_network_image/cached_network_image.dart';
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
  List<dynamic> _outlets = [];
  int? _selectedOutletId;
  Map<String, dynamic>? _user;
  bool _isLoading = true;
  String? _errorMessage;

  final ImagePicker _picker = ImagePicker();

  // Form Controllers for Request
  final _bankNameController = TextEditingController();
  final _accountNumberController = TextEditingController();
  final _accountHolderController = TextEditingController();
  final _amountController = TextEditingController();
  final _adminFeeController = TextEditingController(text: '2500');
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
    _bankNameController.dispose();
    _accountNumberController.dispose();
    _accountHolderController.dispose();
    _amountController.dispose();
    _adminFeeController.dispose();
    _notesController.dispose();
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
      final res = await ApiService.getTransfers(outletId: _selectedOutletId);

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
            if (res['outlets'] != null && res['outlets'] is List) {
              _outlets = res['outlets'];
            }
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

    final double adminFee = Formatters.parseDouble(_adminFeeController.text);

    setState(() => _isSubmitting = true);

    try {
      final res = await ApiService.requestTransfer(
        bankName: _bankNameController.text.trim(),
        accountNumber: _accountNumberController.text.trim(),
        accountHolder: _accountHolderController.text.trim(),
        amount: amount,
        adminFee: adminFee,
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
        _adminFeeController.text = '2500';
        _notesController.clear();

        NotificationService.showNotification(
          id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
          title: 'Pengajuan Transfer Terkirim',
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
    File? proofFile;
    bool isProcessing = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) => Container(
          padding: EdgeInsets.only(
            top: 20,
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
                      style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                    ),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                  ],
                ),
                const SizedBox(height: 10),

                // Ringkasan Pengajuan
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF0FDF4),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFBBF7D0)),
                  ),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(transfer['reference_no'] ?? '-', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: ThemeConfig.primary)),
                          Text(
                            Formatters.formatRupiah(transfer['total_amount'] ?? transfer['amount']),
                            style: const TextStyle(fontWeight: FontWeight.w900, color: Color(0xFF15803D), fontSize: 15),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('${transfer['bank_name']} - ${transfer['account_number']}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                          Text('a.n ${transfer['account_holder']}', style: const TextStyle(fontSize: 11, color: Colors.grey)),
                        ],
                      ),
                      if (transfer['outlet'] != null || transfer['store_name'] != null) ...[
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            const Icon(Icons.storefront, size: 13, color: Colors.grey),
                            const SizedBox(width: 4),
                            Text(
                              transfer['outlet']?['name'] ?? transfer['store_name'] ?? '-',
                              style: const TextStyle(fontSize: 11, color: Colors.black87, fontWeight: FontWeight.w500),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                // Rekening Sumber
                const Text('Rekening Sumber Dana (Kas / Bank Pusat) *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 6),
                DropdownButtonFormField<int>(
                  value: selectedSourceId,
                  isExpanded: true,
                  decoration: const InputDecoration(
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  ),
                  items: _bankAccounts.map<DropdownMenuItem<int>>((acc) {
                    final balance = Formatters.parseDouble(acc['current_balance']);
                    return DropdownMenuItem<int>(
                      value: acc['id'],
                      child: Text('${acc['code']} - ${acc['name']} (${Formatters.formatRupiah(balance)})', style: const TextStyle(fontSize: 12)),
                    );
                  }).toList(),
                  onChanged: (val) => setModalState(() => selectedSourceId = val),
                ),
                const SizedBox(height: 16),

                // Upload Bukti Struk Transfer (Opsional)
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Foto Bukti Struk Transfer', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFF10B981).withOpacity(0.1),
                        borderRadius: BorderRadius.circular(4),
                        border: Border.all(color: Colors.green.shade200),
                      ),
                      child: const Text('OPSIONAL', style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Colors.green)),
                    ),
                  ],
                ),
                const SizedBox(height: 8),

                if (proofFile != null) ...[
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.green.shade300),
                      borderRadius: BorderRadius.circular(12),
                      color: Colors.green.shade50.withOpacity(0.3),
                    ),
                    child: Row(
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: Image.file(proofFile!, width: 60, height: 60, fit: BoxFit.cover),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('Foto Struk Terpilih', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                              Text(proofFile!.path.split('/').last, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 10, color: Colors.grey)),
                            ],
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.delete_outline, color: Colors.red),
                          onPressed: () => setModalState(() => proofFile = null),
                          tooltip: 'Hapus Foto',
                        ),
                      ],
                    ),
                  ),
                ] else ...[
                  Container(
                    padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey.shade300, style: BorderStyle.solid),
                      borderRadius: BorderRadius.circular(12),
                      color: Colors.grey.shade50,
                    ),
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            OutlinedButton.icon(
                              onPressed: () async {
                                final picked = await _picker.pickImage(source: ImageSource.camera, maxWidth: 1920, maxHeight: 1920, imageQuality: 80);
                                if (picked != null) setModalState(() => proofFile = File(picked.path));
                              },
                              icon: const Icon(Icons.camera_alt, size: 16),
                              label: const Text('Kamera', style: TextStyle(fontSize: 12)),
                            ),
                            const SizedBox(width: 12),
                            OutlinedButton.icon(
                              onPressed: () async {
                                final picked = await _picker.pickImage(source: ImageSource.gallery, maxWidth: 1920, maxHeight: 1920, imageQuality: 80);
                                if (picked != null) setModalState(() => proofFile = File(picked.path));
                              },
                              icon: const Icon(Icons.photo_library, size: 16),
                              label: const Text('Galeri', style: TextStyle(fontSize: 12)),
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        const Text(
                          'Boleh dikosongkan jika struk tidak tersedia (bisa disetujui langsung)',
                          textAlign: TextAlign.center,
                          style: TextStyle(fontSize: 10, color: Colors.grey),
                        ),
                      ],
                    ),
                  ),
                ],

                const SizedBox(height: 14),
                const Text('Catatan Tambahan (Opsional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 6),
                TextField(
                  controller: notesController,
                  decoration: const InputDecoration(
                    hintText: 'Contoh: Transfer sukses via m-BCA',
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  ),
                ),
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
                              proofImage: proofFile,
                            );

                            if (res['success'] == true) {
                              Navigator.pop(context);
                              _loadTransfers();
                              NotificationService.showNotification(
                                id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                title: 'Transfer Disetujui',
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
                decoration: const InputDecoration(hintText: 'Contoh: Rekening tujuan tidak valid'),
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
                          title: 'Pengajuan Transfer Ditolak',
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

  void _showProofLightbox(String imageUrl, dynamic item) {
    final ref = item['reference_no'] ?? '-';
    final bank = '${item['bank_name']} - ${item['account_number']}';
    final amount = Formatters.formatRupiah(item['amount']);
    final store = item['outlet']?['name'] ?? item['store_name'] ?? 'Kasir';

    final shareText = 'Bukti Transfer $ref\nCabang: $store\nTujuan: $bank\nNominal: $amount\nLink: $imageUrl';

    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(16),
        child: Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(20),
            boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.3), blurRadius: 20)],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Header
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 14, 12, 10),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Bukti Struk: $ref', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: ThemeConfig.textDark)),
                        Text('$store • $amount', style: const TextStyle(fontSize: 11, color: Colors.grey)),
                      ],
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(ctx),
                      visualDensity: VisualDensity.compact,
                    ),
                  ],
                ),
              ),
              const Divider(height: 1),

              // Image with Interactive Pinch Zoom
              ConstrainedBox(
                constraints: BoxConstraints(
                  maxHeight: MediaQuery.of(context).size.height * 0.55,
                ),
                child: Container(
                  color: Colors.black.withOpacity(0.04),
                  width: double.infinity,
                  child: InteractiveViewer(
                    panEnabled: true,
                    minScale: 0.8,
                    maxScale: 4.0,
                    child: CachedNetworkImage(
                      imageUrl: imageUrl,
                      fit: BoxFit.contain,
                      placeholder: (context, url) => const Center(
                        child: Padding(
                          padding: EdgeInsets.all(40.0),
                          child: CircularProgressIndicator(),
                        ),
                      ),
                      errorWidget: (context, url, error) => const Center(
                        child: Padding(
                          padding: EdgeInsets.all(40.0),
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.broken_image, size: 48, color: Colors.grey),
                              SizedBox(height: 8),
                              Text('Gagal memuat gambar', style: TextStyle(color: Colors.grey)),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              ),
              const Divider(height: 1),

              // Action Toolbar
              Padding(
                padding: const EdgeInsets.all(12),
                child: Row(
                  children: [
                    // WhatsApp Share
                    Expanded(
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF25D366),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 10),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        onPressed: () async {
                          final uri = Uri.parse('https://api.whatsapp.com/send?text=${Uri.encodeComponent(shareText)}');
                          if (await canLaunchUrl(uri)) {
                            await launchUrl(uri, mode: LaunchMode.externalApplication);
                          }
                        },
                        icon: const Icon(Icons.send_rounded, size: 16),
                        label: const Text('Kirim WA', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                      ),
                    ),
                    const SizedBox(width: 8),

                    // Download / Open in Browser
                    Expanded(
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 10),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        onPressed: () async {
                          final uri = Uri.parse(imageUrl);
                          if (await canLaunchUrl(uri)) {
                            await launchUrl(uri, mode: LaunchMode.externalApplication);
                          }
                        },
                        icon: const Icon(Icons.download, size: 16),
                        label: const Text('Unduh', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
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
                        labelText: 'Bank Tujuan / E-Wallet *',
                        hintText: 'BCA, MANDIRI, BRI, DANA',
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _accountNumberController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(
                        labelText: 'Nomor Rekening *',
                        hintText: '1234567890',
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _accountHolderController,
                      decoration: const InputDecoration(
                        labelText: 'Nama Pemilik Rekening *',
                        hintText: 'NAMA LENGKAP PEMILIK',
                      ),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          flex: 3,
                          child: TextField(
                            controller: _amountController,
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(
                              labelText: 'Nominal Transfer (Rp) *',
                              hintText: '100000',
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          flex: 2,
                          child: TextField(
                            controller: _adminFeeController,
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(
                              labelText: 'Biaya Admin',
                              hintText: '2500',
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _notesController,
                      decoration: const InputDecoration(
                        labelText: 'Catatan (Opsional)',
                        hintText: 'Pelanggan TF tunai',
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
      body: Column(
        children: [
          // Outlet Filter Bar (For Admin / Super Admin)
          if (_isAdmin && _outlets.isNotEmpty)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              color: Colors.white,
              child: Row(
                children: [
                  const Icon(Icons.storefront, size: 16, color: ThemeConfig.primary),
                  const SizedBox(width: 8),
                  const Text('Cabang:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: ThemeConfig.textDark)),
                  const SizedBox(width: 8),
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
                                _loadTransfers();
                              }
                            },
                          ),
                          const SizedBox(width: 6),
                          ..._outlets.map((ot) {
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
                                  _loadTransfers();
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
            ),

          Expanded(
            child: _isLoading
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
          ),
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
          final double adminFee = Formatters.parseDouble(item['admin_fee'] ?? 0);
          final String status = (item['status'] ?? 'pending').toString().toLowerCase();
          final String sender = item['user']?['name'] ?? 'Kasir Agen';
          final String store = item['outlet']?['name'] ?? item['store_name'] ?? '-';
          final String? proofUrl = item['proof_image_url'] ?? (item['proof_image'] != null ? '${ApiService.baseUrl.replaceAll('/api', '')}/storage/${item['proof_image']}' : null);

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
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            ref,
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'monospace',
                              color: ThemeConfig.primary,
                            ),
                          ),
                          Text(
                            '$store • Oleh $sender',
                            style: const TextStyle(fontSize: 10, color: ThemeConfig.textMuted),
                          ),
                        ],
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
                          if (adminFee > 0)
                            Text(
                              'Admin: ${Formatters.formatRupiah(adminFee)}',
                              style: const TextStyle(fontSize: 10, color: Colors.grey),
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

                // Bukti Struk Transfer Section
                const SizedBox(height: 10),
                if (proofUrl != null && proofUrl.isNotEmpty) ...[
                  InkWell(
                    onTap: () => _showProofLightbox(proofUrl, item),
                    borderRadius: BorderRadius.circular(8),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF0FDF4),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: const Color(0xFFBBF7D0)),
                      ),
                      child: Row(
                        children: [
                          ClipRRect(
                            borderRadius: BorderRadius.circular(4),
                            child: CachedNetworkImage(
                              imageUrl: proofUrl,
                              width: 32,
                              height: 32,
                              fit: BoxFit.cover,
                              placeholder: (_, __) => Container(width: 32, height: 32, color: Colors.grey.shade200),
                              errorWidget: (_, __, ___) => const Icon(Icons.receipt, size: 20, color: Colors.green),
                            ),
                          ),
                          const SizedBox(width: 8),
                          const Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Ada Bukti Struk Transfer', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF15803D))),
                                Text('Ketuk untuk zoom & bagikan', style: TextStyle(fontSize: 9, color: Colors.grey)),
                              ],
                            ),
                          ),
                          const Icon(Icons.zoom_in, size: 18, color: Color(0xFF15803D)),
                        ],
                      ),
                    ),
                  ),
                ] else ...[
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade100,
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: const Text(
                      'Tanpa lampiran bukti transfer',
                      style: TextStyle(fontSize: 10, color: Colors.grey, fontStyle: FontStyle.italic),
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
