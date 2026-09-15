import 'package:flutter/material.dart';
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
  String? _errorMessage;
  Map<String, dynamic>? _setting;
  List<dynamic> _closingHistory = [];

  // Printer settings
  String _selectedPaperSize = '58mm';
  bool _autoPrintEnabled = false;

  @override
  void initState() {
    super.initState();
    _loadData();
    _loadPrinterSettings();
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

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).padding.bottom;

    return Scaffold(
      appBar: AppBar(title: const Text('Pengaturan Sistem', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18))),
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
              : RefreshIndicator(
                  onRefresh: () async => _loadData(),
                  child: ListView(
                    padding: EdgeInsets.only(
                      left: 16,
                      right: 16,
                      top: 16,
                      bottom: bottomInset + 36, // Safe bottom padding
                    ),
                    children: [
                      // 1. PENGATURAN PRINTER & STRUK KASIR
                      Card(
                        elevation: 1.5,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  const Icon(Icons.print_outlined, color: ThemeConfig.primary),
                                  const SizedBox(width: 8),
                                  Text(
                                    'Pengaturan Printer & Struk Kasir',
                                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: ThemeConfig.textDark),
                                  ),
                                ],
                              ),
                              const Divider(height: 24),
                              const Text('Ukuran Kertas Default:', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
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
                                        child: Text('Thermal 58mm (Roll Kecil Portabel / Bluetooth)'),
                                      ),
                                      DropdownMenuItem(
                                        value: '80mm',
                                        child: Text('Thermal 80mm (Roll Standar Mesin Kasir POS)'),
                                      ),
                                      DropdownMenuItem(
                                        value: 'A4',
                                        child: Text('Kertas HVS A4 (Format Faktur Penjualan Resmi)'),
                                      ),
                                    ],
                                    onChanged: (val) async {
                                      if (val != null) {
                                        setState(() => _selectedPaperSize = val);
                                        await PrinterService.setPreferredPaperSize(val);
                                        ScaffoldMessenger.of(context).showSnackBar(
                                          SnackBar(
                                            content: Text('Ukuran kertas diubah ke: $val'),
                                            backgroundColor: ThemeConfig.primary,
                                            duration: const Duration(seconds: 2),
                                          ),
                                        );
                                      }
                                    },
                                  ),
                                ),
                              ),
                              const SizedBox(height: 16),
                              SwitchListTile(
                                contentPadding: EdgeInsets.zero,
                                title: const Text('Cetak Otomatis Setelah Bayar', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                                subtitle: const Text('Langsung buka dialog cetak saat checkout berhasil', style: TextStyle(fontSize: 11, color: Colors.grey)),
                                value: _autoPrintEnabled,
                                activeColor: ThemeConfig.primary,
                                onChanged: (val) async {
                                  setState(() => _autoPrintEnabled = val);
                                  await PrinterService.setAutoPrintEnabled(val);
                                },
                              ),
                              const SizedBox(height: 12),
                              SizedBox(
                                width: double.infinity,
                                height: 44,
                                child: ElevatedButton.icon(
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: ThemeConfig.primary,
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                  ),
                                  icon: const Icon(Icons.receipt_long, size: 18, color: Colors.white),
                                  label: const Text('Uji Cetak Struk (Test Print)', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
                                  onPressed: () => PrinterService.testPrintReceipt(context),
                                ),
                              ),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  const Icon(Icons.info_outline, size: 14, color: Colors.grey),
                                  const SizedBox(width: 6),
                                  Expanded(
                                    child: Text(
                                      'Mendukung Printer Thermal Bluetooth, USB OTG, Wi-Fi, dan Print Service bawaan Android.',
                                      style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),

                      // 2. STORE PROFILE CARD
                      Card(
                        elevation: 1.5,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  const Icon(Icons.storefront, color: ThemeConfig.primary),
                                  const SizedBox(width: 8),
                                  Text(
                                    'Profil Toko & Usaha',
                                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: ThemeConfig.textDark),
                                  ),
                                ],
                              ),
                              const Divider(height: 24),
                              _buildSettingRow('Nama Toko', _setting?['store_name'] ?? 'ELEPHANT CELL'),
                              _buildSettingRow('Alamat', _setting?['address'] ?? 'Jl. Raya Utama No. 88'),
                              _buildSettingRow('Kontak / WhatsApp', _setting?['phone'] ?? '0812-3456-7890'),
                              _buildSettingRow('Catatan Struk', _setting?['receipt_footer'] ?? 'Barang yang sudah dibeli tidak dapat ditukar.'),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),

                      // 3. YEARLY CLOSING CARD
                      Card(
                        elevation: 1.5,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  const Icon(Icons.event_available, color: ThemeConfig.primary),
                                  const SizedBox(width: 8),
                                  Text(
                                    'Riwayat Tutup Buku Tahunan',
                                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: ThemeConfig.textDark),
                                  ),
                                ],
                              ),
                              const Divider(height: 24),
                              if (_closingHistory.isEmpty)
                                const Padding(
                                  padding: EdgeInsets.symmetric(vertical: 8),
                                  child: Text('Belum ada riwayat tutup buku tahunan.', style: TextStyle(color: Colors.grey)),
                                )
                              else
                                ..._closingHistory.map((c) {
                                  return ListTile(
                                    contentPadding: EdgeInsets.zero,
                                    leading: const CircleAvatar(
                                      backgroundColor: ThemeConfig.accent,
                                      child: Icon(Icons.lock, color: Colors.white, size: 18),
                                    ),
                                    title: Text('Tahun Buku: ${c['year'] ?? '-'}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                                    subtitle: Text('Ditutup pada: ${c['closed_at'] ?? c['created_at'] ?? '-'}', style: const TextStyle(fontSize: 12)),
                                  );
                                }),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),

                      // 4. SYSTEM INFO CARD
                      Card(
                        elevation: 1.5,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  const Icon(Icons.cloud_done, color: ThemeConfig.primary),
                                  const SizedBox(width: 8),
                                  Text(
                                    'Informasi Server & Versi',
                                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: ThemeConfig.textDark),
                                  ),
                                ],
                              ),
                              const Divider(height: 24),
                              _buildSettingRow('Server Backend', 'pos.moonbyte.my.id'),
                              _buildSettingRow('Status Koneksi', 'Terhubung (HTTPS Online)'),
                              _buildSettingRow('Versi Aplikasi', 'v2.2.0 (Pure Native & Direct Printing)'),
                              _buildSettingRow('Arsitektur', 'Flutter Native Engine (Zero WebView)'),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildSettingRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(label, style: TextStyle(color: ThemeConfig.textMuted, fontSize: 13)),
          ),
          Expanded(
            child: Text(value, style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: ThemeConfig.textDark)),
          ),
        ],
      ),
    );
  }
}
