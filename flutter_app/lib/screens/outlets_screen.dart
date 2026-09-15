import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../utils/theme_config.dart';

class OutletsScreen extends StatefulWidget {
  const OutletsScreen({super.key});

  @override
  State<OutletsScreen> createState() => _OutletsScreenState();
}

class _OutletsScreenState extends State<OutletsScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _outlets = [];
  String _searchQuery = '';
  final TextEditingController _searchController = TextEditingController();

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
      final res = await ApiService.getOutlets();
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _outlets = res['data'] ?? [];
          } else {
            _errorMessage = res['message'] ?? 'Gagal memuat cabang toko';
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

  List<dynamic> get _filteredOutlets {
    if (_searchQuery.isEmpty) return _outlets;
    final q = _searchQuery.toLowerCase();
    return _outlets.where((o) {
      final code = (o['code'] ?? '').toString().toLowerCase();
      final name = (o['name'] ?? '').toString().toLowerCase();
      final address = (o['address'] ?? '').toString().toLowerCase();
      return code.contains(q) || name.contains(q) || address.contains(q);
    }).toList();
  }

  void _showAddEditModal([Map<String, dynamic>? outlet]) {
    final isEdit = outlet != null;
    final codeCtrl = TextEditingController(text: outlet?['code'] ?? '');
    final nameCtrl = TextEditingController(text: outlet?['name'] ?? '');
    final addressCtrl = TextEditingController(text: outlet?['address'] ?? '');
    final phoneCtrl = TextEditingController(text: outlet?['phone'] ?? '');
    String status = outlet?['status'] ?? 'active';
    bool isSaving = false;

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
                    Text(
                      isEdit ? 'Edit Cabang Toko' : 'Tambah Cabang Toko Baru',
                      style: const TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                    ),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                  ],
                ),
                const Divider(height: 16),
                const Text('Kode Cabang (Singkatan Unik) *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 6),
                TextField(
                  controller: codeCtrl,
                  textCapitalization: TextCapitalization.characters,
                  decoration: const InputDecoration(hintText: 'Contoh: CAB-A / TMB-01'),
                ),
                const SizedBox(height: 14),
                const Text('Nama Toko Cabang *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 6),
                TextField(
                  controller: nameCtrl,
                  decoration: const InputDecoration(hintText: 'Contoh: Toko Cabang Tambun'),
                ),
                const SizedBox(height: 14),
                const Text('Alamat Lengkap Toko', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 6),
                TextField(
                  controller: addressCtrl,
                  maxLines: 2,
                  decoration: const InputDecoration(hintText: 'Jl. Raya Tambun No. 12...'),
                ),
                const SizedBox(height: 14),
                const Text('Nomor Telepon / WhatsApp', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 6),
                TextField(
                  controller: phoneCtrl,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(hintText: '08...'),
                ),
                const SizedBox(height: 14),
                const Text('Status Operasional', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  value: status,
                  decoration: const InputDecoration(),
                  items: const [
                    DropdownMenuItem(value: 'active', child: Text('Aktif Beroperasi')),
                    DropdownMenuItem(value: 'inactive', child: Text('Nonaktif / Tutup')),
                  ],
                  onChanged: (val) => setModalState(() => status = val!),
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    onPressed: isSaving
                        ? null
                        : () async {
                            if (codeCtrl.text.trim().isEmpty || nameCtrl.text.trim().isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Kode dan nama cabang wajib diisi!'), backgroundColor: Colors.orange),
                              );
                              return;
                            }

                            setModalState(() => isSaving = true);
                            final payload = {
                              'code': codeCtrl.text.trim().toUpperCase(),
                              'name': nameCtrl.text.trim(),
                              'address': addressCtrl.text.trim(),
                              'phone': phoneCtrl.text.trim(),
                              'status': status,
                            };

                            final res = isEdit
                                ? await ApiService.updateOutlet(
                                    outlet['id'],
                                    code: codeCtrl.text.trim().toUpperCase(),
                                    name: nameCtrl.text.trim(),
                                    address: addressCtrl.text.trim(),
                                    phone: phoneCtrl.text.trim(),
                                    status: status,
                                  )
                                : await ApiService.storeOutlet(
                                    code: codeCtrl.text.trim().toUpperCase(),
                                    name: nameCtrl.text.trim(),
                                    address: addressCtrl.text.trim(),
                                    phone: phoneCtrl.text.trim(),
                                    status: status,
                                  );

                            if (mounted) {
                              setModalState(() => isSaving = false);
                              if (res['success'] == true) {
                                Navigator.pop(context);
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(content: Text(res['message'] ?? 'Berhasil disimpan'), backgroundColor: ThemeConfig.primary),
                                );
                                _loadData();
                              } else {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(content: Text(res['message'] ?? 'Gagal menyimpan'), backgroundColor: Colors.red),
                                );
                              }
                            }
                          },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: ThemeConfig.primary,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    child: isSaving
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : Text(isEdit ? 'Simpan Perubahan' : 'Tambah Cabang Toko', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _toggleStatus(Map<String, dynamic> outlet) async {
    final res = await ApiService.toggleOutletStatus(outlet['id']);
    if (mounted) {
      if (res['success'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res['message'] ?? 'Status berhasil diubah'), backgroundColor: ThemeConfig.primary),
        );
        _loadData();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res['message'] ?? 'Gagal mengubah status'), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _confirmDelete(Map<String, dynamic> outlet) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.warning_amber_rounded, color: Colors.red),
            SizedBox(width: 8),
            Text('Hapus Cabang Toko'),
          ],
        ),
        content: Text('Apakah Anda yakin ingin menghapus cabang [${outlet['name']}]?\n\nPerhatian: Cabang yang sudah memiliki riwayat transaksi atau kasir terhubung tidak dapat dihapus.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () async {
              Navigator.pop(ctx);
              final res = await ApiService.deleteOutlet(outlet['id']);
              if (mounted) {
                if (res['success'] == true) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text(res['message'] ?? 'Cabang berhasil dihapus'), backgroundColor: ThemeConfig.primary),
                  );
                  _loadData();
                } else {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text(res['message'] ?? 'Gagal menghapus cabang'), backgroundColor: Colors.red),
                  );
                }
              }
            },
            child: const Text('Hapus', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: ThemeConfig.primary,
        title: const Text('Master Cabang / Toko', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadData),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showAddEditModal(),
        backgroundColor: ThemeConfig.primary,
        icon: const Icon(Icons.add_business_rounded, color: Colors.white),
        label: const Text('Tambah Cabang', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
      ),
      body: Column(
        children: [
          // Search & Info Bar
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            color: Colors.white,
            child: Column(
              children: [
                TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    hintText: 'Cari kode, nama, atau alamat cabang...',
                    prefixIcon: const Icon(Icons.search, size: 20),
                    suffixIcon: _searchQuery.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear, size: 18),
                            onPressed: () {
                              _searchController.clear();
                              setState(() => _searchQuery = '');
                            },
                          )
                        : null,
                    isDense: true,
                    contentPadding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                  ),
                  onChanged: (val) => setState(() => _searchQuery = val.trim()),
                ),
              ],
            ),
          ),
          const Divider(height: 1),

          // List Body
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
                : _errorMessage != null
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(_errorMessage!, style: const TextStyle(color: Colors.red)),
                            const SizedBox(height: 8),
                            ElevatedButton(onPressed: _loadData, child: const Text('Coba Lagi')),
                          ],
                        ),
                      )
                    : _filteredOutlets.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.storefront_outlined, size: 64, color: Colors.grey.shade400),
                                const SizedBox(height: 12),
                                const Text('Belum ada data cabang toko', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.bold)),
                                const SizedBox(height: 4),
                                const Text('Ketuk tombol Tambah Cabang di bawah untuk membuat cabang baru.', style: TextStyle(color: Colors.grey, fontSize: 12), textAlign: TextAlign.center),
                              ],
                            ),
                          )
                        : RefreshIndicator(
                            onRefresh: () async => _loadData(),
                            child: ListView.separated(
                              padding: const EdgeInsets.all(14),
                              itemCount: _filteredOutlets.length,
                              separatorBuilder: (_, __) => const SizedBox(height: 10),
                              itemBuilder: (ctx, i) {
                                final o = _filteredOutlets[i];
                                final isActive = (o['status'] ?? 'active') == 'active';

                                return Container(
                                  decoration: BoxDecoration(
                                    color: Colors.white,
                                    borderRadius: BorderRadius.circular(14),
                                    border: Border.all(color: Colors.grey.shade200),
                                    boxShadow: [
                                      BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 4, offset: const Offset(0, 2)),
                                    ],
                                  ),
                                  padding: const EdgeInsets.all(14),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          Row(
                                            children: [
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                                decoration: BoxDecoration(
                                                  color: ThemeConfig.primary.withOpacity(0.12),
                                                  borderRadius: BorderRadius.circular(6),
                                                ),
                                                child: Text(
                                                  o['code'] ?? '-',
                                                  style: const TextStyle(fontWeight: FontWeight.bold, color: ThemeConfig.primary, fontSize: 11, fontFamily: 'monospace'),
                                                ),
                                              ),
                                              const SizedBox(width: 8),
                                              Text(
                                                o['name'] ?? '-',
                                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: ThemeConfig.textDark),
                                              ),
                                            ],
                                          ),
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                            decoration: BoxDecoration(
                                              color: isActive ? Colors.green.shade50 : Colors.red.shade50,
                                              borderRadius: BorderRadius.circular(12),
                                              border: Border.all(color: isActive ? Colors.green.shade300 : Colors.red.shade300),
                                            ),
                                            child: Text(
                                              isActive ? 'Aktif' : 'Nonaktif',
                                              style: TextStyle(
                                                fontSize: 10,
                                                fontWeight: FontWeight.bold,
                                                color: isActive ? Colors.green.shade800 : Colors.red.shade800,
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                      if (o['address'] != null && o['address'].toString().isNotEmpty) ...[
                                        const SizedBox(height: 6),
                                        Row(
                                          children: [
                                            const Icon(Icons.location_on_outlined, size: 14, color: Colors.grey),
                                            const SizedBox(width: 4),
                                            Expanded(
                                              child: Text(
                                                o['address'].toString(),
                                                style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ],
                                      if (o['phone'] != null && o['phone'].toString().isNotEmpty) ...[
                                        const SizedBox(height: 4),
                                        Row(
                                          children: [
                                            const Icon(Icons.phone_outlined, size: 14, color: Colors.grey),
                                            const SizedBox(width: 4),
                                            Text(
                                              o['phone'].toString(),
                                              style: const TextStyle(fontSize: 12, color: Color(0xFF64748B), fontFamily: 'monospace'),
                                            ),
                                          ],
                                        ),
                                      ],
                                      const Divider(height: 16),
                                      Row(
                                        mainAxisAlignment: MainAxisAlignment.end,
                                        children: [
                                          OutlinedButton.icon(
                                            style: OutlinedButton.styleFrom(
                                              visualDensity: VisualDensity.compact,
                                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                              side: BorderSide(color: isActive ? Colors.orange.shade300 : Colors.green.shade300),
                                            ),
                                            onPressed: () => _toggleStatus(o),
                                            icon: Icon(isActive ? Icons.block : Icons.check_circle_outline, size: 14, color: isActive ? Colors.orange.shade800 : Colors.green.shade800),
                                            label: Text(isActive ? 'Nonaktifkan' : 'Aktifkan', style: TextStyle(fontSize: 11, color: isActive ? Colors.orange.shade800 : Colors.green.shade800)),
                                          ),
                                          const SizedBox(width: 8),
                                          OutlinedButton.icon(
                                            style: OutlinedButton.styleFrom(
                                              visualDensity: VisualDensity.compact,
                                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                            ),
                                            onPressed: () => _showAddEditModal(o),
                                            icon: const Icon(Icons.edit_outlined, size: 14),
                                            label: const Text('Edit', style: TextStyle(fontSize: 11)),
                                          ),
                                          const SizedBox(width: 8),
                                          IconButton(
                                            icon: const Icon(Icons.delete_outline, size: 18, color: Colors.red),
                                            visualDensity: VisualDensity.compact,
                                            tooltip: 'Hapus Cabang',
                                            onPressed: () => _confirmDelete(o),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                );
                              },
                            ),
                          ),
          ),
        ],
      ),
    );
  }
}
