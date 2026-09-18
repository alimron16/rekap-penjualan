import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/formatters.dart';
import '../utils/theme_config.dart';

class AccountsScreen extends StatefulWidget {
  const AccountsScreen({super.key});

  @override
  State<AccountsScreen> createState() => _AccountsScreenState();
}

class _AccountsScreenState extends State<AccountsScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _accounts = [];
  String _selectedGroupFilter = 'ALL';
  final _searchController = TextEditingController();

  final List<String> _groups = [
    'ALL',
    'AKTIVA',
    'KEWAJIBAN',
    'MODAL',
    'PENDAPATAN',
    'HPP',
    'BIAYA',
    'PENDAPATAN LAIN',
    'BIAYA LAIN',
  ];

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
      final res = await ApiService.getAccounts();
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _accounts = res['data'] ?? [];
          } else {
            _errorMessage = res['message'] ?? 'Gagal memuat bagan akun';
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

  void _showAccountFormModal({dynamic account}) {
    final isEditing = account != null;
    final codeController = TextEditingController(text: account?['code'] ?? '');
    final nameController = TextEditingController(text: account?['name'] ?? '');
    final balanceController = TextEditingController(
      text: account != null ? Formatters.parseDouble(account['initial_balance']).toStringAsFixed(0) : '0',
    );
    String group = account?['group'] ?? 'AKTIVA';
    String type = account?['type'] ?? 'D';
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
                      isEditing ? 'Edit Akun: ${account['name']}' : 'Tambah Akun Rekening Baru',
                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                    ),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                  ],
                ),
                const Divider(height: 16),
                Row(
                  children: [
                    Expanded(
                      flex: 2,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Kode Akun', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          TextField(controller: codeController, decoration: const InputDecoration(hintText: '1101')),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      flex: 3,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Golongan / Kelompok', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          DropdownButtonFormField<String>(
                            value: group,
                            isDense: true,
                            decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 8)),
                            items: _groups.where((g) => g != 'ALL').map((g) {
                              return DropdownMenuItem(value: g, child: Text(g, style: const TextStyle(fontSize: 11)));
                            }).toList(),
                            onChanged: (val) => setModalState(() => group = val ?? 'AKTIVA'),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                const Text('Nama Akun Rekening', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 4),
                TextField(controller: nameController, decoration: const InputDecoration(hintText: 'Nama akun')),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Tipe Akun', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          DropdownButtonFormField<String>(
                            value: type,
                            decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8)),
                            items: const [
                              DropdownMenuItem(value: 'D', child: Text('Detail (D)')),
                              DropdownMenuItem(value: 'H', child: Text('Header (H)')),
                              DropdownMenuItem(value: 'K', child: Text('Kas / Bank (K)')),
                            ],
                            onChanged: (val) => setModalState(() => type = val ?? 'D'),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Saldo Awal (Rp)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          TextField(controller: balanceController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: '0')),
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
                            if (codeController.text.trim().isEmpty || nameController.text.trim().isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Kode dan nama akun wajib diisi')));
                              return;
                            }
                            setModalState(() => isSubmitting = true);

                            final payload = {
                              'code': codeController.text.trim().toUpperCase(),
                              'name': nameController.text.trim().toUpperCase(),
                              'group': group,
                              'type': type,
                              'initial_balance': Formatters.parseDouble(balanceController.text.replaceAll(RegExp(r'[^0-9.]'), '')),
                            };

                            final res = isEditing
                                ? await ApiService.updateAccount(account['id'], payload)
                                : await ApiService.storeAccount(payload);

                            if (res['success'] == true) {
                              Navigator.pop(context);
                              _loadData();
                              NotificationService.showNotification(
                                id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                title: isEditing ? 'Akun Diperbarui' : 'Akun Baru Disimpan',
                                body: 'Akun "${nameController.text}" berhasil disimpan.',
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
                        : Text(isEditing ? 'Simpan Perubahan Akun' : 'Simpan Akun Baru'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _confirmDeleteAccount(dynamic account) {
    if (account['is_system_locked'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Akun sistem terkunci dan tidak dapat dihapus!'), backgroundColor: Colors.orange),
      );
      return;
    }

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: const [
            Icon(Icons.warning_amber_rounded, color: Colors.red, size: 28),
            SizedBox(width: 8),
            Text('Hapus Akun?', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Text('Yakin ingin menghapus akun rekening [${account['code']} ${account['name']}]?\n\nPerhatian: Akun yang sudah memiliki mutasi jurnal akuntansi tidak dapat dihapus.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () async {
              Navigator.pop(ctx);
              final res = await ApiService.deleteAccount(account['id']);
              if (res['success'] == true) {
                _loadData();
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Akun berhasil dihapus!'), backgroundColor: Colors.red),
                );
              } else {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Gagal menghapus akun'), backgroundColor: Colors.red),
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
    final query = _searchController.text.trim().toLowerCase();
    final filtered = _accounts.where((a) {
      final matchesGroup = _selectedGroupFilter == 'ALL' || (a['group'] ?? '') == _selectedGroupFilter;
      final code = (a['code'] ?? '').toString().toLowerCase();
      final name = (a['name'] ?? '').toString().toLowerCase();
      final matchesSearch = query.isEmpty || code.contains(query) || name.contains(query);
      return matchesGroup && matchesSearch;
    }).toList();

    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        title: const Text('Bagan Akun (COA)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        backgroundColor: ThemeConfig.primary,
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadData),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ThemeConfig.primary,
        onPressed: () => _showAccountFormModal(),
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text('Akun Baru', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
      ),
      body: Column(
        children: [
          // Search & Group Filter Header
          Container(
            padding: const EdgeInsets.all(12),
            color: Colors.white,
            child: Column(
              children: [
                TextField(
                  controller: _searchController,
                  onChanged: (_) => setState(() {}),
                  decoration: InputDecoration(
                    hintText: 'Cari kode akun atau nama rekening...',
                    prefixIcon: const Icon(Icons.search, size: 20),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    suffixIcon: _searchController.text.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear, size: 18),
                            onPressed: () {
                              _searchController.clear();
                              setState(() {});
                            },
                          )
                        : null,
                  ),
                ),
                const SizedBox(height: 8),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: _groups.map((g) {
                      final isSelected = _selectedGroupFilter == g;
                      return Padding(
                        padding: const EdgeInsets.only(right: 6),
                        child: ChoiceChip(
                          label: Text(g, style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: isSelected ? Colors.white : Colors.grey.shade700)),
                          selected: isSelected,
                          selectedColor: ThemeConfig.primary,
                          backgroundColor: Colors.grey.shade100,
                          onSelected: (_) => setState(() => _selectedGroupFilter = g),
                        ),
                      );
                    }).toList(),
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
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(_errorMessage!, style: const TextStyle(color: Colors.red)),
                            const SizedBox(height: 12),
                            ElevatedButton(onPressed: _loadData, child: const Text('Coba Lagi')),
                          ],
                        ),
                      )
                    : filtered.isEmpty
                        ? const Center(child: Text('Tidak ada akun pada kategori ini', style: TextStyle(color: Colors.grey)))
                        : RefreshIndicator(
                            onRefresh: () async => _loadData(),
                            child: ListView.separated(
                              padding: const EdgeInsets.only(left: 12, right: 12, top: 12, bottom: 80),
                              itemCount: filtered.length,
                              separatorBuilder: (_, __) => const SizedBox(height: 8),
                              itemBuilder: (ctx, i) {
                                final a = filtered[i];
                                final isHeader = (a['type'] ?? 'D').toString().toUpperCase() == 'H';
                                final balance = Formatters.parseDouble(a['current_balance'] ?? a['initial_balance']);
                                final isLocked = a['is_system_locked'] == true;

                                return Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    color: isHeader ? Colors.grey.shade100 : Colors.white,
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(color: Colors.grey.shade200),
                                    boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 4)],
                                  ),
                                  child: Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: ThemeConfig.primary.withOpacity(0.1),
                                          borderRadius: BorderRadius.circular(6),
                                        ),
                                        child: Text(
                                          a['code'] ?? '',
                                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, fontFamily: 'monospace', color: ThemeConfig.primary),
                                        ),
                                      ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              a['name'] ?? '',
                                              style: TextStyle(
                                                fontWeight: isHeader ? FontWeight.bold : FontWeight.w600,
                                                fontSize: 13,
                                                color: ThemeConfig.textDark,
                                              ),
                                            ),
                                            const SizedBox(height: 2),
                                            Text(
                                              '${a['group'] ?? ''} • ${isHeader ? 'Header' : 'Detail'}${isLocked ? ' • [Sistem]' : ''}',
                                              style: TextStyle(color: ThemeConfig.textMuted, fontSize: 11),
                                            ),
                                          ],
                                        ),
                                      ),
                                      Column(
                                        crossAxisAlignment: CrossAxisAlignment.end,
                                        children: [
                                          if (!isHeader)
                                            Text(
                                              Formatters.formatRupiah(balance),
                                              style: TextStyle(
                                                fontWeight: FontWeight.bold,
                                                fontSize: 12,
                                                fontFamily: 'monospace',
                                                color: balance >= 0 ? ThemeConfig.textDark : Colors.red,
                                              ),
                                            ),
                                          if (!isLocked)
                                            Row(
                                              mainAxisSize: MainAxisSize.min,
                                              children: [
                                                InkWell(
                                                  onTap: () => _showAccountFormModal(account: a),
                                                  child: const Padding(
                                                    padding: EdgeInsets.all(4),
                                                    child: Icon(Icons.edit, size: 16, color: Colors.blue),
                                                  ),
                                                ),
                                                const SizedBox(width: 4),
                                                InkWell(
                                                  onTap: () => _confirmDeleteAccount(a),
                                                  child: const Padding(
                                                    padding: EdgeInsets.all(4),
                                                    child: Icon(Icons.delete, size: 16, color: Colors.red),
                                                  ),
                                                ),
                                              ],
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
