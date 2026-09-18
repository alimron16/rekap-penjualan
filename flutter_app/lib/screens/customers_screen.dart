import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/theme_config.dart';

class CustomersScreen extends StatefulWidget {
  const CustomersScreen({super.key});

  @override
  State<CustomersScreen> createState() => _CustomersScreenState();
}

class _CustomersScreenState extends State<CustomersScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _customers = [];
  List<dynamic> _filteredCustomers = [];
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _loadData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiService.getCustomers();
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _customers = res['data'] ?? [];
            _applyFilter();
          } else {
            _errorMessage = res['message'] ?? 'Gagal memuat pelanggan';
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

  void _applyFilter() {
    final query = _searchController.text.trim().toLowerCase();
    setState(() {
      if (query.isEmpty) {
        _filteredCustomers = _customers;
      } else {
        _filteredCustomers = _customers.where((c) {
          final name = (c['name'] ?? '').toString().toLowerCase();
          final phone = (c['phone'] ?? '').toString().toLowerCase();
          final address = (c['address'] ?? '').toString().toLowerCase();
          return name.contains(query) || phone.contains(query) || address.contains(query);
        }).toList();
      }
    });
  }

  void _showCustomerFormModal({dynamic customer}) {
    final isEditing = customer != null;
    final nameController = TextEditingController(text: customer?['name'] ?? '');
    final phoneController = TextEditingController(text: customer?['phone'] ?? '');
    final addressController = TextEditingController(text: customer?['address'] ?? '');
    final bankNameController = TextEditingController(text: customer?['bank_name'] ?? '');
    final accountNumController = TextEditingController(text: customer?['account_number'] ?? '');
    String status = customer?['status'] ?? 'Aktif';
    bool isSubmitting = false;

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
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
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
                      isEditing ? 'Edit Pelanggan: ${customer['name']}' : 'Tambah Pelanggan Baru',
                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                    ),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                  ],
                ),
                const Divider(height: 16),
                const Text('Nama Lengkap', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 4),
                TextField(controller: nameController, decoration: const InputDecoration(hintText: 'Nama pelanggan')),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('No. HP / WhatsApp', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          TextField(controller: phoneController, keyboardType: TextInputType.phone, decoration: const InputDecoration(hintText: '08...')),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Status', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          DropdownButtonFormField<String>(
                            value: status,
                            decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8)),
                            items: const [
                              DropdownMenuItem(value: 'Aktif', child: Text('Aktif')),
                              DropdownMenuItem(value: 'Nonaktif', child: Text('Nonaktif')),
                            ],
                            onChanged: (val) => setModalState(() => status = val ?? 'Aktif'),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                const Text('Alamat Lengkap', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 4),
                TextField(controller: addressController, decoration: const InputDecoration(hintText: 'Alamat tempat tinggal/toko')),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Nama Bank', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          TextField(controller: bankNameController, decoration: const InputDecoration(hintText: 'BCA / BRI')),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('No. Rekening', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          TextField(controller: accountNumController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: 'Nomor rek')),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 18),
                SizedBox(
                  width: double.infinity,
                  height: 46,
                  child: ElevatedButton(
                    onPressed: isSubmitting
                        ? null
                        : () async {
                            if (nameController.text.trim().isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nama pelanggan wajib diisi')));
                              return;
                            }
                            setModalState(() => isSubmitting = true);

                            final payload = {
                              'name': nameController.text.trim(),
                              'phone': phoneController.text.trim(),
                              'address': addressController.text.trim(),
                              'bank_name': bankNameController.text.trim(),
                              'account_number': accountNumController.text.trim(),
                              'status': status,
                            };

                            final res = isEditing
                                ? await ApiService.updateCustomer(customer['id'], payload)
                                : await ApiService.storeCustomer(payload);

                            if (res['success'] == true) {
                              Navigator.pop(context);
                              _loadData();
                              NotificationService.showNotification(
                                id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                title: isEditing ? 'Pelanggan Diperbarui' : 'Pelanggan Baru Disimpan',
                                body: 'Pelanggan "${nameController.text}" berhasil disimpan ke sistem.',
                              );
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Berhasil disimpan!'), backgroundColor: ThemeConfig.accent),
                              );
                            } else {
                              setModalState(() => isSubmitting = false);
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Gagal menyimpan'), backgroundColor: Colors.red),
                              );
                            }
                          },
                    child: isSubmitting
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : Text(isEditing ? 'Simpan Perubahan' : 'Simpan Pelanggan Baru'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _confirmDeleteCustomer(dynamic customer) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: const [
            Icon(Icons.warning_amber_rounded, color: Colors.red, size: 28),
            SizedBox(width: 8),
            Text('Hapus Pelanggan?', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Text('Yakin ingin menghapus pelanggan [${customer['name']}]?\n\nPerhatian: Pelanggan yang memiliki riwayat transaksi/piutang tidak dapat dihapus, ganti status menjadi "Nonaktif".'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () async {
              Navigator.pop(ctx);
              final res = await ApiService.deleteCustomer(customer['id']);
              if (res['success'] == true) {
                _loadData();
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Pelanggan berhasil dihapus!'), backgroundColor: Colors.red),
                );
              } else {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Gagal menghapus pelanggan'), backgroundColor: Colors.red),
                );
              }
            },
            child: const Text('Hapus Sekarang'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        title: const Text('Master Pelanggan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        backgroundColor: ThemeConfig.primary,
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadData),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ThemeConfig.primary,
        onPressed: () => _showCustomerFormModal(),
        icon: const Icon(Icons.person_add, color: Colors.white),
        label: const Text('Pelanggan Baru', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
      ),
      body: Column(
        children: [
          // Search Header
          Container(
            padding: const EdgeInsets.all(12),
            color: Colors.white,
            child: TextField(
              controller: _searchController,
              onChanged: (_) => _applyFilter(),
              decoration: InputDecoration(
                hintText: 'Cari nama, no. HP, alamat pelanggan...',
                prefixIcon: const Icon(Icons.search, size: 20),
                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear, size: 18),
                        onPressed: () {
                          _searchController.clear();
                          _applyFilter();
                        },
                      )
                    : null,
              ),
            ),
          ),
          Expanded(
            child: _isLoading
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
                    : _filteredCustomers.isEmpty
                        ? const Center(child: Text('Belum ada data pelanggan', style: TextStyle(color: Colors.grey)))
                        : RefreshIndicator(
                            onRefresh: () async => _loadData(),
                            child: ListView.separated(
                              padding: const EdgeInsets.only(left: 12, right: 12, top: 12, bottom: 80),
                              itemCount: _filteredCustomers.length,
                              separatorBuilder: (_, __) => const SizedBox(height: 8),
                              itemBuilder: (ctx, i) {
                                final c = _filteredCustomers[i];
                                final isAktif = (c['status'] ?? 'Aktif') == 'Aktif';

                                return Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    color: Colors.white,
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(color: Colors.grey.shade200),
                                    boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 4)],
                                  ),
                                  child: Row(
                                    crossAxisAlignment: CrossAxisAlignment.center,
                                    children: [
                                      CircleAvatar(
                                        backgroundColor: ThemeConfig.primary.withOpacity(0.1),
                                        foregroundColor: ThemeConfig.primary,
                                        child: const Icon(Icons.person),
                                      ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Row(
                                              children: [
                                                Expanded(
                                                  child: Text(
                                                    c['name'] ?? '',
                                                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: ThemeConfig.textDark),
                                                    overflow: TextOverflow.ellipsis,
                                                  ),
                                                ),
                                                Container(
                                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                  decoration: BoxDecoration(
                                                    color: isAktif ? Colors.green.shade50 : Colors.red.shade50,
                                                    borderRadius: BorderRadius.circular(6),
                                                    border: Border.all(color: isAktif ? Colors.green.shade300 : Colors.red.shade300),
                                                  ),
                                                  child: Text(
                                                    c['status'] ?? 'Aktif',
                                                    style: TextStyle(
                                                      color: isAktif ? Colors.green.shade800 : Colors.red.shade800,
                                                      fontWeight: FontWeight.bold,
                                                      fontSize: 10,
                                                    ),
                                                  ),
                                                ),
                                              ],
                                            ),
                                            const SizedBox(height: 3),
                                            Text(
                                              c['phone'] != null && c['phone'].toString().isNotEmpty ? 'Telp: ${c['phone']}' : 'Tidak ada telepon',
                                              style: TextStyle(color: ThemeConfig.textMuted, fontSize: 11),
                                            ),
                                            if (c['address'] != null && c['address'].toString().isNotEmpty)
                                              Text('Alamat: ${c['address']}', style: const TextStyle(color: Colors.grey, fontSize: 11)),
                                            if (c['bank_name'] != null && c['account_number'] != null)
                                              Text('Bank: ${c['bank_name']} - ${c['account_number']}', style: TextStyle(color: Colors.blueGrey.shade700, fontSize: 11)),
                                          ],
                                        ),
                                      ),
                                      const SizedBox(width: 6),
                                      Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          IconButton(
                                            icon: const Icon(Icons.edit, size: 18, color: Colors.blue),
                                            onPressed: () => _showCustomerFormModal(customer: c),
                                            tooltip: 'Edit Pelanggan',
                                          ),
                                          IconButton(
                                            icon: const Icon(Icons.delete, size: 18, color: Colors.red),
                                            onPressed: () => _confirmDeleteCustomer(c),
                                            tooltip: 'Hapus Pelanggan',
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
