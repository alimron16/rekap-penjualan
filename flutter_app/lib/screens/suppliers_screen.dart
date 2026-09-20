import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/theme_config.dart';

class SuppliersScreen extends StatefulWidget {
  const SuppliersScreen({super.key});

  @override
  State<SuppliersScreen> createState() => _SuppliersScreenState();
}

class _SuppliersScreenState extends State<SuppliersScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _suppliers = [];
  List<dynamic> _filteredSuppliers = [];
  final _searchController = TextEditingController();

  // Outlet Scoping
  List<dynamic> _outlets = [];
  int? _selectedOutletId;
  String _selectedOutletName = 'Semua Toko (Global)';
  bool _isGlobal = true;
  bool _canFilterOutlet = false;

  @override
  void initState() {
    super.initState();
    _loadUser();
    _loadData();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _loadUser() async {
    final user = await ApiService.getUser();
    if (user != null && mounted) {
      setState(() {
        final role = (user['role'] ?? '').toString().toLowerCase();
        _canFilterOutlet = role == 'admin' || role == 'super_admin' || role == 'superadmin';
      });
    }
  }

  void _loadData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiService.getSuppliers(outletId: _selectedOutletId);
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _suppliers = res['data'] ?? [];
            if (res['outlets'] != null && res['outlets'] is List) {
              _outlets = res['outlets'];
            }
            _selectedOutletName = res['selected_outlet_name'] ?? (_selectedOutletId == null ? 'Semua Toko (Global)' : 'Toko Cabang');
            _isGlobal = res['is_global'] ?? (_selectedOutletId == null);
            _applyFilter();
          } else {
            _errorMessage = res['message'] ?? 'Gagal memuat supplier';
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
        _filteredSuppliers = _suppliers;
      } else {
        _filteredSuppliers = _suppliers.where((s) {
          final name = (s['name'] ?? '').toString().toLowerCase();
          final phone = (s['phone'] ?? '').toString().toLowerCase();
          final address = (s['address'] ?? '').toString().toLowerCase();
          final bank = (s['bank_name'] ?? '').toString().toLowerCase();
          final outletName = (s['outlet']?['name'] ?? '').toString().toLowerCase();
          return name.contains(query) || phone.contains(query) || address.contains(query) || bank.contains(query) || outletName.contains(query);
        }).toList();
      }
    });
  }

  void _showSupplierFormModal({dynamic supplier}) {
    final isEditing = supplier != null;
    final nameController = TextEditingController(text: supplier?['name'] ?? '');
    final phoneController = TextEditingController(text: supplier?['phone'] ?? '');
    final addressController = TextEditingController(text: supplier?['address'] ?? '');
    final bankNameController = TextEditingController(text: supplier?['bank_name'] ?? '');
    final accountNumController = TextEditingController(text: supplier?['account_number'] ?? '');
    final accountNameController = TextEditingController(text: supplier?['account_name'] ?? '');
    int? selectedModalOutletId = supplier?['outlet_id'];
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
                      isEditing ? 'Edit Supplier: ${supplier['name']}' : 'Tambah Mitra Supplier Baru',
                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                    ),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                  ],
                ),
                const Divider(height: 16),
                const Text('Nama Supplier / Distributor *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 4),
                TextField(controller: nameController, decoration: const InputDecoration(hintText: 'Nama supplier/grosir')),
                const SizedBox(height: 10),

                // Outlet selection in modal
                if (_canFilterOutlet && _outlets.isNotEmpty) ...[
                  const Text('Toko / Cabang', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                  const SizedBox(height: 4),
                  DropdownButtonFormField<int?>(
                    value: selectedModalOutletId,
                    decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8)),
                    items: [
                      const DropdownMenuItem<int?>(
                        value: null,
                        child: Text('Semua Toko / Global (Supplier Bersama)', style: TextStyle(fontSize: 12)),
                      ),
                      ..._outlets.map((ot) => DropdownMenuItem<int?>(
                        value: ot['id'] as int,
                        child: Text('${ot['name']} (${ot['code'] ?? 'CABANG'})', style: const TextStyle(fontSize: 12)),
                      )),
                    ],
                    onChanged: (val) => setModalState(() => selectedModalOutletId = val),
                  ),
                  const SizedBox(height: 10),
                ],

                const Text('Nomor Telepon / Sales', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 4),
                TextField(controller: phoneController, keyboardType: TextInputType.phone, decoration: const InputDecoration(hintText: '08...')),
                const SizedBox(height: 10),
                const Text('Alamat Gudang / Kantor', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 4),
                TextField(controller: addressController, decoration: const InputDecoration(hintText: 'Alamat')),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Nama Bank', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          TextField(controller: bankNameController, decoration: const InputDecoration(hintText: 'BCA / Mandiri')),
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
                const SizedBox(height: 10),
                const Text('Atas Nama Rekening', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 4),
                TextField(controller: accountNameController, decoration: const InputDecoration(hintText: 'Nama pemilik rekening')),
                const SizedBox(height: 18),
                SizedBox(
                  width: double.infinity,
                  height: 46,
                  child: ElevatedButton(
                    onPressed: isSubmitting
                        ? null
                        : () async {
                            if (nameController.text.trim().isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nama supplier wajib diisi')));
                              return;
                            }
                            setModalState(() => isSubmitting = true);

                            final payload = {
                              'name': nameController.text.trim(),
                              'phone': phoneController.text.trim(),
                              'address': addressController.text.trim(),
                              'bank_name': bankNameController.text.trim(),
                              'account_number': accountNumController.text.trim(),
                              'account_name': accountNameController.text.trim(),
                              'outlet_id': selectedModalOutletId,
                            };

                            final res = isEditing
                                ? await ApiService.updateSupplier(supplier['id'], payload)
                                : await ApiService.storeSupplier(payload);

                            if (res['success'] == true) {
                              Navigator.pop(context);
                              _loadData();
                              NotificationService.showNotification(
                                id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                title: isEditing ? 'Supplier Diperbarui' : 'Supplier Baru Disimpan',
                                body: 'Supplier "${nameController.text}" berhasil disimpan ke sistem.',
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
                        : Text(isEditing ? 'Simpan Perubahan' : 'Simpan Supplier Baru'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _confirmDeleteSupplier(dynamic supplier) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: const [
            Icon(Icons.warning_amber_rounded, color: Colors.red, size: 28),
            SizedBox(width: 8),
            Text('Hapus Supplier?', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Text('Yakin ingin menghapus mitra supplier [${supplier['name']}]?\n\nPerhatian: Supplier yang memiliki riwayat faktur pembelian tidak dapat dihapus.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () async {
              Navigator.pop(ctx);
              final res = await ApiService.deleteSupplier(supplier['id']);
              if (res['success'] == true) {
                _loadData();
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Supplier berhasil dihapus!'), backgroundColor: Colors.red),
                );
              } else {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Gagal menghapus supplier'), backgroundColor: Colors.red),
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
        title: const Text('Master Data Supplier', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        backgroundColor: ThemeConfig.primary,
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadData),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ThemeConfig.primary,
        onPressed: () => _showSupplierFormModal(),
        icon: const Icon(Icons.add_business, color: Colors.white),
        label: const Text('Supplier Baru', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
      ),
      body: Column(
        children: [
          // Admin Outlet Selector ChoiceChips
          if (_canFilterOutlet && _outlets.isNotEmpty) ...[
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              color: Colors.white,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: const [
                      Icon(Icons.storefront_outlined, size: 14, color: ThemeConfig.primary),
                      SizedBox(width: 4),
                      Text('PILIH TOKO / CABANG:', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ThemeConfig.textDark)),
                    ],
                  ),
                  const SizedBox(height: 6),
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        Padding(
                          padding: const EdgeInsets.only(right: 6),
                          child: ChoiceChip(
                            selected: _selectedOutletId == null,
                            label: const Text('Semua Toko (Global)'),
                            labelStyle: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              color: _selectedOutletId == null ? Colors.white : const Color(0xFF1E293B),
                            ),
                            selectedColor: ThemeConfig.primary,
                            backgroundColor: Colors.white,
                            showCheckmark: true,
                            checkmarkColor: Colors.white,
                            side: BorderSide(
                              color: _selectedOutletId == null ? ThemeConfig.primary : const Color(0xFFCBD5E1),
                              width: 1.2,
                            ),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                            onSelected: (val) {
                              if (_selectedOutletId != null) {
                                setState(() => _selectedOutletId = null);
                                _loadData();
                              }
                            },
                          ),
                        ),
                        ..._outlets.map((ot) {
                          final isSelected = _selectedOutletId == ot['id'];
                          return Padding(
                            padding: const EdgeInsets.only(right: 6),
                            child: ChoiceChip(
                              selected: isSelected,
                              label: Text(ot['name'] ?? 'Cabang'),
                              labelStyle: TextStyle(
                                fontSize: 11,
                                fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                                color: isSelected ? Colors.white : const Color(0xFF1E293B),
                              ),
                              selectedColor: ThemeConfig.primary,
                              backgroundColor: Colors.white,
                              showCheckmark: true,
                              checkmarkColor: Colors.white,
                              side: BorderSide(
                                color: isSelected ? ThemeConfig.primary : const Color(0xFFCBD5E1),
                                width: 1.2,
                              ),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                              onSelected: (val) {
                                if (_selectedOutletId != ot['id']) {
                                  setState(() => _selectedOutletId = ot['id']);
                                  _loadData();
                                }
                              },
                            ),
                          );
                        }),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],

          // Informative Status Banner
          Container(
            width: double.infinity,
            margin: const EdgeInsets.fromLTRB(12, 8, 12, 4),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: _isGlobal ? const Color(0xFFEFF6FF) : const Color(0xFFECFDF5),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: _isGlobal ? const Color(0xFFBFDBFE) : const Color(0xFFA7F3D0)),
            ),
            child: Row(
              children: [
                Icon(
                  _isGlobal ? Icons.public : Icons.store,
                  size: 20,
                  color: _isGlobal ? const Color(0xFF1D4ED8) : const Color(0xFF047857),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _isGlobal
                            ? 'MODE: DATA SUPPLIER GLOBAL (SEMUA TOKO)'
                            : 'MODE: DATA SUPPLIER - ${_selectedOutletName.toUpperCase()}',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: _isGlobal ? const Color(0xFF1D4ED8) : const Color(0xFF047857),
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        _isGlobal
                            ? 'Menampilkan konsolidasi seluruh mitra supplier kulakan semua cabang.'
                            : 'Menampilkan mitra supplier cabang & supplier global seluruh toko.',
                        style: TextStyle(
                          fontSize: 10,
                          color: _isGlobal ? const Color(0xFF1E40AF) : const Color(0xFF065F46),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // Search Header
          Container(
            padding: const EdgeInsets.all(12),
            color: Colors.transparent,
            child: TextField(
              controller: _searchController,
              onChanged: (_) => _applyFilter(),
              decoration: InputDecoration(
                hintText: 'Cari nama supplier, toko, no. telp, bank...',
                prefixIcon: const Icon(Icons.search, size: 20),
                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                filled: true,
                fillColor: Colors.white,
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
                    : _filteredSuppliers.isEmpty
                        ? const Center(child: Text('Belum ada data supplier', style: TextStyle(color: Colors.grey)))
                        : RefreshIndicator(
                            onRefresh: () async => _loadData(),
                            child: ListView.separated(
                              padding: const EdgeInsets.only(left: 12, right: 12, top: 4, bottom: 80),
                              itemCount: _filteredSuppliers.length,
                              separatorBuilder: (_, __) => const SizedBox(height: 8),
                              itemBuilder: (ctx, i) {
                                final s = _filteredSuppliers[i];
                                final outlet = s['outlet'];
                                final outletName = outlet != null ? outlet['name'] : null;

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
                                        backgroundColor: ThemeConfig.accent.withOpacity(0.12),
                                        foregroundColor: ThemeConfig.accent,
                                        child: const Icon(Icons.business),
                                      ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              s['name'] ?? '',
                                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: ThemeConfig.textDark),
                                            ),
                                            const SizedBox(height: 4),

                                            // Store Badge
                                            Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                              decoration: BoxDecoration(
                                                color: outlet != null ? const Color(0xFFECFDF5) : const Color(0xFFEFF6FF),
                                                borderRadius: BorderRadius.circular(4),
                                                border: Border.all(color: outlet != null ? const Color(0xFFA7F3D0) : const Color(0xFFBFDBFE)),
                                              ),
                                              child: Row(
                                                mainAxisSize: MainAxisSize.min,
                                                children: [
                                                  Icon(
                                                    outlet != null ? Icons.storefront : Icons.public,
                                                    size: 11,
                                                    color: outlet != null ? const Color(0xFF047857) : const Color(0xFF1D4ED8),
                                                  ),
                                                  const SizedBox(width: 3),
                                                  Text(
                                                    outlet != null ? 'Toko: $outletName' : 'Semua Toko (Global)',
                                                    style: TextStyle(
                                                      color: outlet != null ? const Color(0xFF047857) : const Color(0xFF1D4ED8),
                                                      fontWeight: FontWeight.bold,
                                                      fontSize: 10,
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),

                                            const SizedBox(height: 4),
                                            Row(
                                              children: [
                                                const Icon(Icons.phone_outlined, size: 12, color: ThemeConfig.textMuted),
                                                const SizedBox(width: 4),
                                                Text(
                                                  s['phone'] != null && s['phone'].toString().isNotEmpty ? s['phone'].toString() : 'Tidak ada telepon',
                                                  style: const TextStyle(color: ThemeConfig.textMuted, fontSize: 11),
                                                ),
                                              ],
                                            ),
                                            if (s['address'] != null && s['address'].toString().isNotEmpty) ...[
                                              const SizedBox(height: 2),
                                              Row(
                                                children: [
                                                  const Icon(Icons.place_outlined, size: 12, color: Colors.grey),
                                                  const SizedBox(width: 4),
                                                  Expanded(
                                                    child: Text(s['address'].toString(), style: const TextStyle(color: Colors.grey, fontSize: 11), maxLines: 1, overflow: TextOverflow.ellipsis),
                                                  ),
                                                ],
                                              ),
                                            ],
                                            if (s['bank_name'] != null && s['account_number'] != null) ...[
                                              const SizedBox(height: 2),
                                              Row(
                                                children: [
                                                  Icon(Icons.account_balance_outlined, size: 12, color: Colors.blueGrey.shade700),
                                                  const SizedBox(width: 4),
                                                  Expanded(
                                                    child: Text('${s['bank_name']}: ${s['account_number']} a/n ${s['account_name'] ?? '-'}', style: TextStyle(color: Colors.blueGrey.shade700, fontSize: 11), maxLines: 1, overflow: TextOverflow.ellipsis),
                                                  ),
                                                ],
                                              ),
                                            ],
                                          ],
                                        ),
                                      ),
                                      const SizedBox(width: 6),
                                      Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          IconButton(
                                            icon: const Icon(Icons.edit, size: 18, color: Colors.blue),
                                            onPressed: () => _showSupplierFormModal(supplier: s),
                                            tooltip: 'Edit Supplier',
                                          ),
                                          IconButton(
                                            icon: const Icon(Icons.delete, size: 18, color: Colors.red),
                                            onPressed: () => _confirmDeleteSupplier(s),
                                            tooltip: 'Hapus Supplier',
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
