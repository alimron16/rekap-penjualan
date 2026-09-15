import 'package:flutter/material.dart';
import '../services/api_service.dart';
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

  @override
  void initState() {
    super.initState();
    _loadData();
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
                    padding: const EdgeInsets.all(16),
                    children: [
                      // Store Profile Card
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

                      // Yearly Closing Card
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

                      // System Info Card
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
                              _buildSettingRow('Versi Aplikasi', 'v2.1.0 (Full Pure Native)'),
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
