import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/theme_config.dart';

class UsersScreen extends StatefulWidget {
  const UsersScreen({super.key});

  @override
  State<UsersScreen> createState() => _UsersScreenState();
}

class _UsersScreenState extends State<UsersScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _users = [];
  List<dynamic> _outlets = [];
  String _searchQuery = '';
  String _roleFilter = 'ALL';
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
      final res = await ApiService.getUsers();
      final outletRes = await ApiService.getOutlets();
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _users = res['data'] ?? [];
          } else {
            _errorMessage = res['message'] ?? 'Gagal memuat pengguna';
          }
          if (outletRes['success'] == true) {
            _outlets = outletRes['data'] ?? [];
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

  List<dynamic> get _filteredUsers {
    return _users.where((u) {
      final role = (u['role'] ?? '').toString().toLowerCase();
      if (_roleFilter != 'ALL' && role != _roleFilter.toLowerCase()) {
        return false;
      }
      if (_searchQuery.isEmpty) return true;
      final query = _searchQuery.toLowerCase();
      final name = (u['name'] ?? '').toString().toLowerCase();
      final email = (u['email'] ?? '').toString().toLowerCase();
      final phone = (u['phone'] ?? '').toString().toLowerCase();
      final store = (u['store_name'] ?? '').toString().toLowerCase();
      return name.contains(query) || email.contains(query) || phone.contains(query) || store.contains(query);
    }).toList();
  }

  void _showAddUserModal() {
    final nameController = TextEditingController();
    final emailController = TextEditingController();
    final passwordController = TextEditingController();
    final storeNameController = TextEditingController();
    final phoneController = TextEditingController();
    String role = 'toko';
    int? selectedOutletId;
    bool isSubmitting = false;

    final Map<String, bool> permissions = {
      'pos': true,
      'digital': true,
      'cash_withdrawal': true,
      'transfer': true,
      'master': false,
      'edit_stock': false,
      'multi_topup': false,
      'purchase': false,
      'accounting': false,
      'manage_modal': false,
      'view_final_balance': false,
      'reports': false,
      'settings': false,
      'users': false,
    };

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
                    Text('Tambah Pengguna Baru', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: ThemeConfig.textDark)),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                  ],
                ),
                const SizedBox(height: 12),
                const Text('Nama Lengkap', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(controller: nameController, decoration: const InputDecoration(hintText: 'Nama lengkap kasir/admin')),
                const SizedBox(height: 14),
                const Text('Email Login', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(controller: emailController, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(hintText: 'email@domain.com')),
                const SizedBox(height: 14),
                const Text('Kata Sandi (Password)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(controller: passwordController, obscureText: true, decoration: const InputDecoration(hintText: 'Minimal 6 karakter')),
                const SizedBox(height: 14),
                const Text('Peran (Role)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  value: role,
                  decoration: const InputDecoration(),
                  items: const [
                    DropdownMenuItem(value: 'toko', child: Text('Toko / Kasir')),
                    DropdownMenuItem(value: 'admin', child: Text('Administrator')),
                    DropdownMenuItem(value: 'super_admin', child: Text('Super Admin / Pemilik')),
                  ],
                  onChanged: (val) {
                    if (val == null) return;
                    setModalState(() {
                      role = val;
                      if (role == 'super_admin') {
                        permissions.updateAll((k, v) => true);
                      } else if (role == 'admin') {
                        permissions.updateAll((k, v) => k != 'users');
                      } else {
                        permissions.updateAll((k, v) => ['pos', 'digital', 'cash_withdrawal', 'transfer'].contains(k));
                      }
                    });
                  },
                ),
                const SizedBox(height: 14),
                const Text('Penugasan Cabang Toko / Outlet', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<int?>(
                  value: null,
                  decoration: const InputDecoration(
                    prefixIcon: Icon(Icons.storefront, size: 18),
                  ),
                  items: [
                    const DropdownMenuItem<int?>(
                      value: null,
                      child: Text('Kantor Pusat / Akses Semua Cabang'),
                    ),
                    ..._outlets.map((ot) => DropdownMenuItem<int?>(
                      value: ot['id'] as int?,
                      child: Text('${ot['code']} - ${ot['name']}'),
                    )),
                  ],
                  onChanged: (val) {
                    setModalState(() {
                      selectedOutletId = val;
                      if (val != null) {
                        final found = _outlets.firstWhere((o) => o['id'] == val, orElse: () => null);
                        if (found != null) {
                          storeNameController.text = found['name'] ?? '';
                        }
                      }
                    });
                  },
                ),
                const SizedBox(height: 14),
                const Text('Nama Toko / Keterangan (Opsional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(controller: storeNameController, decoration: const InputDecoration(hintText: 'Contoh: Elephant Cell Pusat')),
                const SizedBox(height: 14),
                const Text('Nomor HP (Opsional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(controller: phoneController, keyboardType: TextInputType.phone, decoration: const InputDecoration(hintText: '08...')),
                const SizedBox(height: 16),
                
                // Granular Permissions Checklist
                const Text('Pengaturan Hak Akses Fitur / Modul', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: ThemeConfig.primary)),
                const SizedBox(height: 4),
                const Text('Super Admin dapat mengatur hak akses tiap user secara fleksibel:', style: TextStyle(fontSize: 11, color: Colors.grey)),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade50,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.grey.shade200),
                  ),
                  child: Column(
                    children: [
                      _buildPermSwitch('Kasir POS (Retail & Grosir)', 'pos', permissions, setModalState),
                      _buildPermSwitch('Produk Multi / Elektrik', 'digital', permissions, setModalState),
                      _buildPermSwitch('Tarik Tunai Kasir', 'cash_withdrawal', permissions, setModalState),
                      _buildPermSwitch('Transfer Agen & Bank', 'transfer', permissions, setModalState),
                      _buildPermSwitch('Master Data (Barang, Pelanggan, Supplier)', 'master', permissions, setModalState),
                      _buildPermSwitch('Edit Angka Stok Fisik Barang', 'edit_stock', permissions, setModalState),
                      _buildPermSwitch('Top Up Saldo Multi Server', 'multi_topup', permissions, setModalState),
                      _buildPermSwitch('Menu Pembelian', 'purchase', permissions, setModalState),
                      _buildPermSwitch('Akuntansi & Bagan Akun (COA)', 'accounting', permissions, setModalState),
                      _buildPermSwitch('Kelola Modal Awal Kas', 'manage_modal', permissions, setModalState),
                      _buildPermSwitch('Lihat Saldo Akhir & Laba Bersih di Dashboard', 'view_final_balance', permissions, setModalState),
                      _buildPermSwitch('Laporan Keuangan', 'reports', permissions, setModalState),
                      _buildPermSwitch('Pengaturan Toko & Printer', 'settings', permissions, setModalState),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    onPressed: isSubmitting
                        ? null
                        : () async {
                            if (nameController.text.trim().isEmpty || emailController.text.trim().isEmpty || passwordController.text.trim().length < 6) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Lengkapi form dan pastikan password min 6 karakter')),
                              );
                              return;
                            }
                            setModalState(() => isSubmitting = true);

                            final res = await ApiService.storeUser({
                              'name': nameController.text.trim(),
                              'email': emailController.text.trim(),
                              'password': passwordController.text.trim(),
                              'role': role,
                              'outlet_id': selectedOutletId,
                              'store_name': storeNameController.text.trim(),
                              'phone': phoneController.text.trim(),
                              'permissions': permissions,
                            });

                            if (res['success'] == true) {
                              Navigator.pop(context);
                              _loadData();
                              NotificationService.showNotification(
                                id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                title: 'Pengguna Berhasil Ditambahkan',
                                body: 'Akun "${nameController.text}" ($role) berhasil didaftarkan.',
                              );
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Pengguna berhasil dibuat!'), backgroundColor: ThemeConfig.accent),
                              );
                            } else {
                              setModalState(() => isSubmitting = false);
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Gagal membuat pengguna'), backgroundColor: Colors.red),
                              );
                            }
                          },
                    child: isSubmitting
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text('Simpan Pengguna Baru'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _showEditUserModal(Map<String, dynamic> user) {
    final nameController = TextEditingController(text: user['name'] ?? '');
    final emailController = TextEditingController(text: user['email'] ?? '');
    final passwordController = TextEditingController();
    final storeNameController = TextEditingController(text: user['store_name'] ?? '');
    final phoneController = TextEditingController(text: user['phone'] ?? '');
    String role = user['role'] ?? 'toko';
    int? selectedOutletId = user['outlet_id'];
    bool isSubmitting = false;

    // Load existing permissions or defaults
    Map<String, dynamic> rawPerms = {};
    if (user['permissions'] is Map) {
      rawPerms = Map<String, dynamic>.from(user['permissions']);
    }

    final Map<String, bool> permissions = {
      'pos': rawPerms['pos'] ?? (role == 'super_admin' || role == 'admin' || role == 'toko'),
      'digital': rawPerms['digital'] ?? (role == 'super_admin' || role == 'admin' || role == 'toko'),
      'cash_withdrawal': rawPerms['cash_withdrawal'] ?? (role == 'super_admin' || role == 'admin' || role == 'toko'),
      'transfer': rawPerms['transfer'] ?? (role == 'super_admin' || role == 'admin' || role == 'toko'),
      'master': rawPerms['master'] ?? (role == 'super_admin' || role == 'admin'),
      'edit_stock': rawPerms['edit_stock'] ?? (role == 'super_admin' || role == 'admin'),
      'multi_topup': rawPerms['multi_topup'] ?? (role == 'super_admin' || role == 'admin'),
      'purchase': rawPerms['purchase'] ?? (role == 'super_admin' || role == 'admin'),
      'accounting': rawPerms['accounting'] ?? (role == 'super_admin' || role == 'admin'),
      'manage_modal': rawPerms['manage_modal'] ?? (role == 'super_admin' || role == 'admin'),
      'view_final_balance': rawPerms['view_final_balance'] ?? (role == 'super_admin' || role == 'admin'),
      'reports': rawPerms['reports'] ?? (role == 'super_admin' || role == 'admin'),
      'settings': rawPerms['settings'] ?? (role == 'super_admin' || role == 'admin'),
      'users': rawPerms['users'] ?? (role == 'super_admin'),
    };

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
                    Text('Edit Pengguna', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: ThemeConfig.textDark)),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                  ],
                ),
                const SizedBox(height: 12),
                const Text('Nama Lengkap', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(controller: nameController, decoration: const InputDecoration(hintText: 'Nama lengkap')),
                const SizedBox(height: 14),
                const Text('Email Login', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(controller: emailController, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(hintText: 'email@domain.com')),
                const SizedBox(height: 14),
                const Text('Kata Sandi Baru (Kosongkan jika tidak diubah)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(controller: passwordController, obscureText: true, decoration: const InputDecoration(hintText: 'Biarkan kosong untuk mempertahankan password')),
                const SizedBox(height: 14),
                const Text('Peran (Role)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  value: role,
                  decoration: const InputDecoration(),
                  items: const [
                    DropdownMenuItem(value: 'toko', child: Text('Toko / Kasir')),
                    DropdownMenuItem(value: 'admin', child: Text('Administrator')),
                    DropdownMenuItem(value: 'super_admin', child: Text('Super Admin / Pemilik')),
                  ],
                  onChanged: (val) {
                    if (val == null) return;
                    setModalState(() {
                      role = val;
                      if (role == 'super_admin') {
                        permissions.updateAll((k, v) => true);
                      } else if (role == 'admin') {
                        permissions.updateAll((k, v) => k != 'users');
                      } else {
                        permissions.updateAll((k, v) => ['pos', 'digital', 'cash_withdrawal', 'transfer'].contains(k));
                      }
                    });
                  },
                ),
                const SizedBox(height: 14),
                const Text('Penugasan Cabang Toko / Outlet', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<int?>(
                  value: selectedOutletId,
                  decoration: const InputDecoration(
                    prefixIcon: Icon(Icons.storefront, size: 18),
                  ),
                  items: [
                    const DropdownMenuItem<int?>(
                      value: null,
                      child: Text('Kantor Pusat / Akses Semua Cabang'),
                    ),
                    ..._outlets.map((ot) => DropdownMenuItem<int?>(
                      value: ot['id'] as int?,
                      child: Text('${ot['code']} - ${ot['name']}'),
                    )),
                  ],
                  onChanged: (val) {
                    setModalState(() {
                      selectedOutletId = val;
                      if (val != null) {
                        final found = _outlets.firstWhere((o) => o['id'] == val, orElse: () => null);
                        if (found != null) {
                          storeNameController.text = found['name'] ?? '';
                        }
                      }
                    });
                  },
                ),
                const SizedBox(height: 14),
                const Text('Nama Toko / Keterangan', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(controller: storeNameController, decoration: const InputDecoration(hintText: 'Nama toko/cabang')),
                const SizedBox(height: 14),
                const Text('Nomor HP', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(controller: phoneController, keyboardType: TextInputType.phone, decoration: const InputDecoration(hintText: '08...')),
                const SizedBox(height: 16),

                // Granular Permissions Checklist
                const Text('Pengaturan Hak Akses Fitur / Modul', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: ThemeConfig.primary)),
                const SizedBox(height: 4),
                const Text('Super Admin dapat mengatur hak akses tiap user secara fleksibel:', style: TextStyle(fontSize: 11, color: Colors.grey)),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade50,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.grey.shade200),
                  ),
                  child: Column(
                    children: [
                      _buildPermSwitch('Kasir POS (Retail & Grosir)', 'pos', permissions, setModalState),
                      _buildPermSwitch('Produk Multi / Elektrik', 'digital', permissions, setModalState),
                      _buildPermSwitch('Tarik Tunai Kasir', 'cash_withdrawal', permissions, setModalState),
                      _buildPermSwitch('Transfer Agen & Bank', 'transfer', permissions, setModalState),
                      _buildPermSwitch('Master Data (Barang, Pelanggan, Supplier)', 'master', permissions, setModalState),
                      _buildPermSwitch('Edit Angka Stok Fisik Barang', 'edit_stock', permissions, setModalState),
                      _buildPermSwitch('Top Up Saldo Multi Server', 'multi_topup', permissions, setModalState),
                      _buildPermSwitch('Menu Pembelian', 'purchase', permissions, setModalState),
                      _buildPermSwitch('Akuntansi & Bagan Akun (COA)', 'accounting', permissions, setModalState),
                      _buildPermSwitch('Kelola Modal Awal Kas', 'manage_modal', permissions, setModalState),
                      _buildPermSwitch('Lihat Saldo Akhir & Laba Bersih di Dashboard', 'view_final_balance', permissions, setModalState),
                      _buildPermSwitch('Laporan Keuangan', 'reports', permissions, setModalState),
                      _buildPermSwitch('Pengaturan Toko & Printer', 'settings', permissions, setModalState),
                      _buildPermSwitch('Kelola Pengguna Sistem', 'users', permissions, setModalState),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    onPressed: isSubmitting
                        ? null
                        : () async {
                            if (nameController.text.trim().isEmpty || emailController.text.trim().isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Nama dan email wajib diisi')),
                              );
                              return;
                            }
                            setModalState(() => isSubmitting = true);

                            final payload = <String, dynamic>{
                              'name': nameController.text.trim(),
                              'email': emailController.text.trim(),
                              'role': role,
                              'outlet_id': selectedOutletId,
                              'store_name': storeNameController.text.trim(),
                              'phone': phoneController.text.trim(),
                              'permissions': permissions,
                            };
                            if (passwordController.text.trim().isNotEmpty) {
                              payload['password'] = passwordController.text.trim();
                            }

                            final res = await ApiService.updateUser(user['id'], payload);

                            if (res['success'] == true) {
                              Navigator.pop(context);
                              _loadData();
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Pengguna berhasil diperbarui!'), backgroundColor: ThemeConfig.accent),
                              );
                            } else {
                              setModalState(() => isSubmitting = false);
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Gagal memperbarui pengguna'), backgroundColor: Colors.red),
                              );
                            }
                          },
                    child: isSubmitting
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text('Simpan Perubahan'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _confirmDeleteUser(Map<String, dynamic> user) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: const [
            Icon(Icons.warning_amber_rounded, color: Colors.red, size: 28),
            SizedBox(width: 8),
            Text('Hapus Pengguna?', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
          ],
        ),
        content: Text('Apakah Anda yakin ingin menghapus akun "${user['name']}" (${user['email']})? Tindakan ini tidak dapat dibatalkan.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Batal', style: TextStyle(color: Colors.grey)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () async {
              Navigator.pop(ctx);
              final res = await ApiService.deleteUser(user['id']);
              if (res['success'] == true) {
                _loadData();
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Pengguna berhasil dihapus!'), backgroundColor: Colors.red),
                );
              } else {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Gagal menghapus pengguna'), backgroundColor: Colors.red),
                );
              }
            },
            child: const Text('Hapus Akun', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  void _toggleUserStatus(Map<String, dynamic> user) async {
    final res = await ApiService.toggleUserStatus(user['id']);
    if (res['success'] == true) {
      _loadData();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Status pengguna berhasil diubah!'), backgroundColor: ThemeConfig.accent),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Gagal mengubah status pengguna'), backgroundColor: Colors.red),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final filtered = _filteredUsers;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Manajemen Pengguna', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Segarkan Data',
            onPressed: _loadData,
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ThemeConfig.primary,
        onPressed: _showAddUserModal,
        icon: const Icon(Icons.person_add_alt_1, color: Colors.white),
        label: const Text('Tambah User', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
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
              : Column(
                  children: [
                    // Search & Filter Box
                    Container(
                      padding: const EdgeInsets.all(12),
                      color: Colors.white,
                      child: Column(
                        children: [
                          TextField(
                            controller: _searchController,
                            decoration: InputDecoration(
                              hintText: 'Cari nama, email, hp, atau toko...',
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
                              filled: true,
                              fillColor: Colors.grey.shade100,
                              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                            ),
                            onChanged: (val) => setState(() => _searchQuery = val.trim()),
                          ),
                          const SizedBox(height: 8),
                          SingleChildScrollView(
                            scrollDirection: Axis.horizontal,
                            child: Row(
                              children: [
                                _filterChip('Semua Peran', 'ALL'),
                                const SizedBox(width: 8),
                                _filterChip('Super Admin', 'super_admin'),
                                const SizedBox(width: 8),
                                _filterChip('Admin', 'admin'),
                                const SizedBox(width: 8),
                                _filterChip('Toko / Kasir', 'toko'),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const Divider(height: 1),

                    // User List
                    Expanded(
                      child: RefreshIndicator(
                        onRefresh: () async => _loadData(),
                        child: filtered.isEmpty
                            ? Center(
                                child: Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: const [
                                    Icon(Icons.person_off_outlined, size: 56, color: Colors.grey),
                                    SizedBox(height: 12),
                                    Text('Tidak ada pengguna yang cocok', style: TextStyle(color: Colors.grey, fontSize: 15)),
                                  ],
                                ),
                              )
                            : ListView.builder(
                                padding: const EdgeInsets.only(left: 14, right: 14, top: 12, bottom: 85),
                                itemCount: filtered.length,
                                itemBuilder: (ctx, i) {
                                  final u = filtered[i];
                                  final role = (u['role'] ?? 'toko').toString();
                                  final isActive = u['is_active'] == true || u['is_active'] == 1 || u['is_active'] == '1';

                                  return Card(
                                    margin: const EdgeInsets.only(bottom: 12),
                                    elevation: 1.5,
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                                    child: Padding(
                                      padding: const EdgeInsets.all(14),
                                      child: Column(
                                        children: [
                                          Row(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              CircleAvatar(
                                                radius: 22,
                                                backgroundColor: role == 'super_admin'
                                                    ? Colors.amber.shade100
                                                    : role == 'admin'
                                                        ? Colors.blue.shade100
                                                        : Colors.green.shade100,
                                                foregroundColor: role == 'super_admin'
                                                    ? Colors.amber.shade900
                                                    : role == 'admin'
                                                        ? Colors.blue.shade900
                                                        : Colors.green.shade900,
                                                child: Icon(role == 'super_admin' ? Icons.stars : Icons.person, size: 24),
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
                                                            u['name'] ?? '',
                                                            style: TextStyle(
                                                              fontWeight: FontWeight.bold,
                                                              fontSize: 15,
                                                              color: ThemeConfig.textDark,
                                                            ),
                                                          ),
                                                        ),
                                                        Container(
                                                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                                                          decoration: BoxDecoration(
                                                            color: isActive ? Colors.green.shade50 : Colors.red.shade50,
                                                            borderRadius: BorderRadius.circular(6),
                                                            border: Border.all(color: isActive ? Colors.green.shade300 : Colors.red.shade300),
                                                          ),
                                                          child: Text(
                                                            isActive ? 'AKTIF' : 'NONAKTIF',
                                                            style: TextStyle(
                                                              fontSize: 10,
                                                              fontWeight: FontWeight.bold,
                                                              color: isActive ? Colors.green.shade800 : Colors.red.shade800,
                                                            ),
                                                          ),
                                                        ),
                                                      ],
                                                    ),
                                                    const SizedBox(height: 2),
                                                    Row(
                                                      children: [
                                                        const Icon(Icons.email_outlined, size: 13, color: Colors.grey),
                                                        const SizedBox(width: 4),
                                                        Expanded(
                                                          child: Text(
                                                            u['email'] ?? '',
                                                            style: TextStyle(color: ThemeConfig.textMuted, fontSize: 12),
                                                          ),
                                                        ),
                                                      ],
                                                    ),
                                                    if (u['phone'] != null && u['phone'].toString().isNotEmpty) ...[
                                                      const SizedBox(height: 2),
                                                      Row(
                                                        children: [
                                                          const Icon(Icons.phone_outlined, size: 13, color: Colors.grey),
                                                          const SizedBox(width: 4),
                                                          Text(u['phone'].toString(), style: const TextStyle(color: Colors.grey, fontSize: 11)),
                                                        ],
                                                      ),
                                                    ],
                                                    if (u['outlet'] != null && u['outlet'] is Map) ...[
                                                      const SizedBox(height: 2),
                                                      Row(
                                                        children: [
                                                          const Icon(Icons.storefront, size: 13, color: ThemeConfig.primary),
                                                          const SizedBox(width: 4),
                                                          Text(
                                                            'Cabang: ${u['outlet']['code']} - ${u['outlet']['name']}',
                                                            style: const TextStyle(color: ThemeConfig.primary, fontSize: 11, fontWeight: FontWeight.bold),
                                                          ),
                                                        ],
                                                      ),
                                                    ] else if (u['store_name'] != null && u['store_name'].toString().isNotEmpty) ...[
                                                      const SizedBox(height: 2),
                                                      Row(
                                                        children: [
                                                          const Icon(Icons.storefront_outlined, size: 13, color: Colors.grey),
                                                          const SizedBox(width: 4),
                                                          Text(u['store_name'].toString(), style: const TextStyle(color: Colors.grey, fontSize: 11)),
                                                        ],
                                                      ),
                                                    ],
                                                  ],
                                                ),
                                              ),
                                            ],
                                          ),
                                          const SizedBox(height: 10),
                                          const Divider(height: 1),
                                          const SizedBox(height: 8),
                                          Row(
                                            children: [
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                                decoration: BoxDecoration(
                                                  color: ThemeConfig.primary.withOpacity(0.08),
                                                  borderRadius: BorderRadius.circular(8),
                                                ),
                                                child: Text(
                                                  role.replaceAll('_', ' ').toUpperCase(),
                                                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 10, color: ThemeConfig.primary),
                                                ),
                                              ),
                                              const Spacer(),
                                              // Toggle Active
                                              TextButton.icon(
                                                style: TextButton.styleFrom(
                                                  visualDensity: VisualDensity.compact,
                                                  foregroundColor: isActive ? Colors.orange.shade800 : Colors.green.shade800,
                                                  padding: const EdgeInsets.symmetric(horizontal: 8),
                                                ),
                                                icon: Icon(isActive ? Icons.block : Icons.check_circle_outline, size: 16),
                                                label: Text(isActive ? 'Nonaktifkan' : 'Aktifkan', style: const TextStyle(fontSize: 12)),
                                                onPressed: () => _toggleUserStatus(u),
                                              ),
                                              const SizedBox(width: 4),
                                              // Edit
                                              IconButton(
                                                icon: const Icon(Icons.edit_outlined, size: 20, color: Colors.blue),
                                                tooltip: 'Edit Pengguna',
                                                constraints: const BoxConstraints(),
                                                padding: const EdgeInsets.all(6),
                                                onPressed: () => _showEditUserModal(u),
                                              ),
                                              const SizedBox(width: 4),
                                              // Delete
                                              IconButton(
                                                icon: const Icon(Icons.delete_outline, size: 20, color: Colors.red),
                                                tooltip: 'Hapus Pengguna',
                                                constraints: const BoxConstraints(),
                                                padding: const EdgeInsets.all(6),
                                                onPressed: () => _confirmDeleteUser(u),
                                              ),
                                            ],
                                          ),
                                        ],
                                      ),
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

  Widget _filterChip(String title, String roleVal) {
    final isSelected = _roleFilter == roleVal;
    return ChoiceChip(
      label: Text(title, style: TextStyle(fontSize: 12, fontWeight: isSelected ? FontWeight.bold : FontWeight.normal)),
      selected: isSelected,
      selectedColor: ThemeConfig.primary.withOpacity(0.15),
      onSelected: (val) {
        if (val) {
          setState(() => _roleFilter = roleVal);
        }
      },
    );
  }

  Widget _buildPermSwitch(String label, String key, Map<String, bool> perms, StateSetter setModalState) {
    final val = perms[key] ?? false;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        children: [
          Expanded(
            child: Text(
              label,
              style: TextStyle(fontSize: 12, fontWeight: val ? FontWeight.w600 : FontWeight.normal, color: val ? ThemeConfig.textDark : Colors.grey.shade600),
            ),
          ),
          Transform.scale(
            scale: 0.8,
            child: Switch(
              value: val,
              activeColor: ThemeConfig.primary,
              onChanged: (bool newVal) {
                setModalState(() {
                  perms[key] = newVal;
                });
              },
            ),
          ),
        ],
      ),
    );
  }
}
