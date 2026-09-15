import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../services/api_service.dart';
import '../services/printer_service.dart';
import '../utils/theme_config.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  bool _isLoading = true;
  bool _isSaving = false;
  String? _errorMessage;
  Map<String, dynamic>? _setting;
  List<dynamic> _closingHistory = [];

  // Printer settings
  String _selectedPaperSize = '58mm';
  bool _autoPrintEnabled = false;

  // Form controllers
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameCtrl;
  late TextEditingController _addressCtrl;
  late TextEditingController _phoneCtrl;
  late TextEditingController _footerCtrl;

  // Logo
  File? _pickedLogo;
  bool _isUploadingLogo = false;

  @override
  void initState() {
    super.initState();
    _nameCtrl = TextEditingController();
    _addressCtrl = TextEditingController();
    _phoneCtrl = TextEditingController();
    _footerCtrl = TextEditingController();
    _loadData();
    _loadPrinterSettings();
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _addressCtrl.dispose();
    _phoneCtrl.dispose();
    _footerCtrl.dispose();
    super.dispose();
  }

  void _loadPrinterSettings() async {
    final size = await PrinterService.getPreferredPaperSize();
    final auto = await PrinterService.isAutoPrintEnabled();
    if (mounted) {
      setState(() {
        _selectedPaperSize = size;
        _autoPrintEnabled = auto;
      });
    }
  }

  void _loadData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiService.getSettings();
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _setting = res['setting'];
            _closingHistory = res['closing_history'] ?? [];
            // Populate text controllers
            _nameCtrl.text = _setting?['store_name'] ?? _setting?['name'] ?? '';
            _addressCtrl.text = _setting?['address'] ?? '';
            _phoneCtrl.text = _setting?['phone'] ?? '';
            _footerCtrl.text = _setting?['receipt_footer'] ?? 'Terima kasih telah berbelanja!\nBarang yang sudah dibeli tidak dapat ditukar/dikembalikan.';
          } else {
            _errorMessage = res['message'] ?? 'Gagal memuat pengaturan';
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

  Future<void> _saveSettings() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSaving = true);
    try {
      final res = await ApiService.updateSettings({
        'store_name': _nameCtrl.text.trim(),
        'address': _addressCtrl.text.trim(),
        'phone': _phoneCtrl.text.trim(),
        'receipt_footer': _footerCtrl.text.trim(),
      });

      if (mounted) {
        if (res['success'] == true) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('✅ Pengaturan toko berhasil disimpan!'),
              backgroundColor: ThemeConfig.primary,
            ),
          );
          // Refresh data
          _loadData();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(res['message'] ?? 'Gagal menyimpan pengaturan'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  Future<void> _pickAndUploadLogo() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
    if (picked == null) return;

    setState(() {
      _pickedLogo = File(picked.path);
      _isUploadingLogo = true;
    });

    try {
      final res = await ApiService.uploadSettingsLogo(picked.path);
      if (mounted) {
        if (res['success'] == true) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('✅ Logo toko berhasil diperbarui!'),
              backgroundColor: ThemeConfig.primary,
            ),
          );
          _loadData();
        } else {
          setState(() => _pickedLogo = null);
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(res['message'] ?? 'Gagal mengunggah logo'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } finally {
      if (mounted) setState(() => _isUploadingLogo = false);
    }
  }

  void _showReceiptPreview() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => DraggableScrollableSheet(
        initialChildSize: 0.85,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        builder: (_, controller) => Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: Column(
            children: [
              // Handle bar
              Container(
                margin: const EdgeInsets.only(top: 12, bottom: 4),
                width: 44,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                child: Row(
                  children: [
                    const Icon(Icons.receipt_long, color: ThemeConfig.primary),
                    const SizedBox(width: 8),
                    const Text(
                      'Preview Struk',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    const Spacer(),
                    TextButton.icon(
                      onPressed: () => Navigator.pop(context),
                      icon: const Icon(Icons.close, size: 18),
                      label: const Text('Tutup'),
                    ),
                  ],
                ),
              ),
              const Divider(height: 1),
              Expanded(
                child: ListView(
                  controller: controller,
                  padding: const EdgeInsets.all(16),
                  children: [
                    _buildReceiptPreviewCard(),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildReceiptPreviewCard() {
    final storeName = _nameCtrl.text.isNotEmpty ? _nameCtrl.text : 'ELEPHANT CELL';
    final address = _addressCtrl.text.isNotEmpty ? _addressCtrl.text : 'Jl. Raya Utama';
    final phone = _phoneCtrl.text.isNotEmpty ? _phoneCtrl.text : '0812-3456-7890';
    final footer = _footerCtrl.text.isNotEmpty
        ? _footerCtrl.text
        : 'Terima kasih telah berbelanja!';

    return Center(
      child: Container(
        width: 300,
        decoration: BoxDecoration(
          color: Colors.white,
          border: Border.all(color: Colors.grey.shade300),
          borderRadius: BorderRadius.circular(8),
          boxShadow: const [BoxShadow(color: Color(0x1A000000), blurRadius: 8)],
        ),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            // Logo
            if (_pickedLogo != null)
              ClipRRect(
                borderRadius: BorderRadius.circular(6),
                child: Image.file(_pickedLogo!, height: 60, fit: BoxFit.contain),
              )
            else if (_setting?['logo_url'] != null && (_setting!['logo_url'] as String).isNotEmpty)
              Image.network(_setting!['logo_url'], height: 60, fit: BoxFit.contain,
                  errorBuilder: (_, __, ___) => const Icon(Icons.store, size: 40))
            else
              Container(
                width: 60, height: 60,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: ThemeConfig.primary.withOpacity(0.1),
                ),
                child: const Icon(Icons.store, color: ThemeConfig.primary, size: 32),
              ),
            const SizedBox(height: 8),
            Text(storeName,
                style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 14),
                textAlign: TextAlign.center),
            Text(address,
                style: const TextStyle(fontSize: 10, color: Colors.grey),
                textAlign: TextAlign.center),
            Text('Telp: $phone',
                style: const TextStyle(fontSize: 10, color: Colors.grey),
                textAlign: TextAlign.center),
            const Divider(height: 16),
            _receiptRow('No. Nota', 'INV-20260916-001'),
            _receiptRow('Tanggal', '16/09/2026  08:30'),
            _receiptRow('Kasir', 'Admin'),
            const Divider(height: 12),
            _receiptItem('Samsung A55 5G', 1, 5200000),
            _receiptItem('Tempered Glass', 2, 25000),
            const Divider(height: 12),
            _receiptRow('Subtotal', 'Rp 5.250.000'),
            _receiptRow('Diskon', 'Rp 0'),
            _receiptRow('Total', 'Rp 5.250.000', bold: true),
            _receiptRow('Bayar (Tunai)', 'Rp 6.000.000'),
            _receiptRow('Kembali', 'Rp 750.000'),
            const Divider(height: 16),
            Text(
              footer,
              style: const TextStyle(fontSize: 9, color: Colors.grey, fontStyle: FontStyle.italic),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 4),
            Text(
              '--- * ---',
              style: TextStyle(fontSize: 9, color: Colors.grey.shade400),
            ),
          ],
        ),
      ),
    );
  }

  Widget _receiptRow(String label, String value, {bool bold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 1.5),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontSize: 10, fontWeight: bold ? FontWeight.bold : FontWeight.normal)),
          Text(value, style: TextStyle(fontSize: 10, fontWeight: bold ? FontWeight.bold : FontWeight.normal)),
        ],
      ),
    );
  }

  Widget _receiptItem(String name, int qty, double price) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 1.5),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(name, style: const TextStyle(fontSize: 10)),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('  $qty x Rp ${(price).toStringAsFixed(0).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.')}',
                  style: const TextStyle(fontSize: 9, color: Colors.grey)),
              Text('Rp ${(qty * price).toStringAsFixed(0).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.')}',
                  style: const TextStyle(fontSize: 10)),
            ],
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).padding.bottom;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Pengaturan Sistem',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        actions: [
          IconButton(
            icon: const Icon(Icons.remove_red_eye_outlined),
            tooltip: 'Preview Struk',
            onPressed: _showReceiptPreview,
          ),
        ],
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
              : Form(
                  key: _formKey,
                  child: RefreshIndicator(
                    onRefresh: () async => _loadData(),
                    child: ListView(
                      padding: EdgeInsets.only(
                        left: 16, right: 16, top: 16, bottom: bottomInset + 36,
                      ),
                      children: [
                        // ── 1. PRINTER & KERTAS ─────────────────────────────
                        _buildSectionCard(
                          icon: Icons.print_outlined,
                          title: 'Printer & Struk Kasir',
                          children: [
                            const Text('Ukuran Kertas Default:',
                                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                            const SizedBox(height: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12),
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(color: Colors.grey.shade300),
                              ),
                              child: DropdownButtonHideUnderline(
                                child: DropdownButton<String>(
                                  isExpanded: true,
                                  value: _selectedPaperSize,
                                  items: const [
                                    DropdownMenuItem(
                                      value: '58mm',
                                      child: Text('Thermal 58mm (Bluetooth Portabel)'),
                                    ),
                                    DropdownMenuItem(
                                      value: '80mm',
                                      child: Text('Thermal 80mm (Mesin Kasir POS)'),
                                    ),
                                    DropdownMenuItem(
                                      value: 'A4',
                                      child: Text('Kertas HVS A4 (Faktur Resmi)'),
                                    ),
                                  ],
                                  onChanged: (val) async {
                                    if (val != null) {
                                      setState(() => _selectedPaperSize = val);
                                      await PrinterService.setPreferredPaperSize(val);
                                      if (context.mounted) {
                                        ScaffoldMessenger.of(context).showSnackBar(
                                          SnackBar(
                                            content: Text('Ukuran kertas diubah ke: $val'),
                                            backgroundColor: ThemeConfig.primary,
                                            duration: const Duration(seconds: 2),
                                          ),
                                        );
                                      }
                                    }
                                  },
                                ),
                              ),
                            ),
                            const SizedBox(height: 12),
                            SwitchListTile(
                              contentPadding: EdgeInsets.zero,
                              title: const Text('Cetak Otomatis Setelah Bayar',
                                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                              subtitle: const Text('Langsung buka dialog cetak saat checkout',
                                  style: TextStyle(fontSize: 11, color: Colors.grey)),
                              value: _autoPrintEnabled,
                              activeColor: ThemeConfig.primary,
                              onChanged: (val) async {
                                setState(() => _autoPrintEnabled = val);
                                await PrinterService.setAutoPrintEnabled(val);
                              },
                            ),
                            SizedBox(
                              width: double.infinity,
                              height: 44,
                              child: ElevatedButton.icon(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: ThemeConfig.primary,
                                  shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(10)),
                                ),
                                icon: const Icon(Icons.print, size: 18, color: Colors.white),
                                label: const Text('Uji Cetak Struk (Test Print)',
                                    style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
                                onPressed: () => PrinterService.testPrintReceipt(context),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),

                        // ── 2. PROFIL TOKO ───────────────────────────────────
                        _buildSectionCard(
                          icon: Icons.storefront,
                          title: 'Profil Toko & Struk',
                          actions: [
                            TextButton.icon(
                              onPressed: _showReceiptPreview,
                              icon: const Icon(Icons.visibility, size: 16),
                              label: const Text('Preview'),
                            ),
                          ],
                          children: [
                            // Logo picker
                            Center(
                              child: GestureDetector(
                                onTap: _pickAndUploadLogo,
                                child: Stack(
                                  children: [
                                    Container(
                                      width: 90,
                                      height: 90,
                                      decoration: BoxDecoration(
                                        shape: BoxShape.circle,
                                        color: ThemeConfig.primary.withOpacity(0.08),
                                        border: Border.all(color: ThemeConfig.primary.withOpacity(0.3), width: 2),
                                      ),
                                      child: _isUploadingLogo
                                          ? const Center(child: CircularProgressIndicator(strokeWidth: 2))
                                          : _pickedLogo != null
                                              ? ClipOval(child: Image.file(_pickedLogo!, fit: BoxFit.cover))
                                              : (_setting?['logo_url'] != null &&
                                                      (_setting!['logo_url'] as String).isNotEmpty)
                                                  ? ClipOval(
                                                      child: Image.network(
                                                        _setting!['logo_url'],
                                                        fit: BoxFit.cover,
                                                        errorBuilder: (_, __, ___) =>
                                                            const Icon(Icons.store, size: 36, color: ThemeConfig.primary),
                                                      ),
                                                    )
                                                  : const Icon(Icons.store, size: 36, color: ThemeConfig.primary),
                                    ),
                                    Positioned(
                                      right: 0,
                                      bottom: 0,
                                      child: Container(
                                        padding: const EdgeInsets.all(6),
                                        decoration: const BoxDecoration(
                                          color: ThemeConfig.primary,
                                          shape: BoxShape.circle,
                                        ),
                                        child: const Icon(Icons.camera_alt, size: 14, color: Colors.white),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                            const SizedBox(height: 4),
                            const Center(
                              child: Text(
                                'Ketuk untuk ganti logo toko',
                                style: TextStyle(fontSize: 11, color: Colors.grey),
                              ),
                            ),
                            const SizedBox(height: 16),

                            _buildTextField(
                              controller: _nameCtrl,
                              label: 'Nama Toko / Usaha',
                              icon: Icons.store_mall_directory_outlined,
                              hint: 'Contoh: ELEPHANT CELL',
                            ),
                            const SizedBox(height: 12),
                            _buildTextField(
                              controller: _addressCtrl,
                              label: 'Alamat Lengkap',
                              icon: Icons.location_on_outlined,
                              hint: 'Contoh: Jl. Raya Utama No. 88, Bekasi',
                              maxLines: 2,
                            ),
                            const SizedBox(height: 12),
                            _buildTextField(
                              controller: _phoneCtrl,
                              label: 'Telepon / WhatsApp',
                              icon: Icons.phone_outlined,
                              hint: 'Contoh: 0812-3456-7890',
                              keyboardType: TextInputType.phone,
                            ),
                            const SizedBox(height: 12),
                            _buildTextField(
                              controller: _footerCtrl,
                              label: 'Catatan Bawah Struk (Footer)',
                              icon: Icons.notes_outlined,
                              hint: 'Contoh: Terima kasih!\nBarang tidak dapat ditukar.',
                              maxLines: 3,
                            ),
                            const SizedBox(height: 16),
                            SizedBox(
                              width: double.infinity,
                              height: 48,
                              child: ElevatedButton.icon(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: ThemeConfig.primary,
                                  shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(12)),
                                ),
                                icon: _isSaving
                                    ? const SizedBox(
                                        width: 18, height: 18,
                                        child: CircularProgressIndicator(
                                            strokeWidth: 2, color: Colors.white))
                                    : const Icon(Icons.save_outlined, color: Colors.white),
                                label: Text(
                                  _isSaving ? 'Menyimpan...' : 'Simpan Pengaturan Toko',
                                  style: const TextStyle(
                                      fontWeight: FontWeight.bold, color: Colors.white),
                                ),
                                onPressed: _isSaving ? null : _saveSettings,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),

                        // ── 3. RIWAYAT TUTUP BUKU ────────────────────────────
                        _buildSectionCard(
                          icon: Icons.event_available,
                          title: 'Riwayat Tutup Buku Tahunan',
                          children: [
                            if (_closingHistory.isEmpty)
                              const Padding(
                                padding: EdgeInsets.symmetric(vertical: 8),
                                child: Text('Belum ada riwayat tutup buku tahunan.',
                                    style: TextStyle(color: Colors.grey)),
                              )
                            else
                              ..._closingHistory.map((c) => ListTile(
                                    contentPadding: EdgeInsets.zero,
                                    leading: const CircleAvatar(
                                      backgroundColor: ThemeConfig.accent,
                                      child: Icon(Icons.lock, color: Colors.white, size: 18),
                                    ),
                                    title: Text('Tahun Buku: ${c['year'] ?? '-'}',
                                        style: const TextStyle(
                                            fontWeight: FontWeight.bold, fontSize: 14)),
                                    subtitle: Text(
                                        'Ditutup pada: ${c['closed_at'] ?? c['created_at'] ?? '-'}',
                                        style: const TextStyle(fontSize: 12)),
                                  )),
                          ],
                        ),
                        const SizedBox(height: 16),

                        // ── 4. INFORMASI SERVER ──────────────────────────────
                        _buildSectionCard(
                          icon: Icons.cloud_done,
                          title: 'Informasi Server & Versi',
                          children: [
                            _buildReadOnlyRow('Server Backend', 'pos.moonbyte.my.id'),
                            _buildReadOnlyRow('Status Koneksi', 'Terhubung (HTTPS Online)'),
                            _buildReadOnlyRow('Versi Aplikasi', 'v2.3.0 (FCM + Native Printing)'),
                            _buildReadOnlyRow('Firebase Project', 'elephant-pos-c6210'),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
    );
  }

  Widget _buildSectionCard({
    required IconData icon,
    required String title,
    required List<Widget> children,
    List<Widget>? actions,
  }) {
    return Card(
      elevation: 1.5,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, color: ThemeConfig.primary),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    title,
                    style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                        color: ThemeConfig.textDark),
                  ),
                ),
                if (actions != null) ...actions,
              ],
            ),
            const Divider(height: 24),
            ...children,
          ],
        ),
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    String? hint,
    int maxLines = 1,
    TextInputType keyboardType = TextInputType.text,
  }) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      keyboardType: keyboardType,
      style: const TextStyle(fontSize: 13),
      decoration: InputDecoration(
        labelText: label,
        hintText: hint,
        hintStyle: const TextStyle(fontSize: 12, color: Colors.grey),
        prefixIcon: Icon(icon, size: 18, color: ThemeConfig.primary),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: ThemeConfig.primary, width: 1.5),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      ),
    );
  }

  Widget _buildReadOnlyRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(label,
                style: TextStyle(color: ThemeConfig.textMuted, fontSize: 13)),
          ),
          Expanded(
            child: Text(value,
                style: TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                    color: ThemeConfig.textDark)),
          ),
        ],
      ),
    );
  }
}
