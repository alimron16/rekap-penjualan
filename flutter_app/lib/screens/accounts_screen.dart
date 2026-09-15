import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
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

  final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

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

  void _showAddAccountModal() {
    final codeController = TextEditingController();
    final nameController = TextEditingController();
    final balanceController = TextEditingController(text: '0');
    String group = 'AKTIVA';
    String type = 'D';
    bool isSubmitting = false;

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
                    Text('Tambah Akun Perkiraan (COA)', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: ThemeConfig.textDark)),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                  ],
                ),
                const SizedBox(height: 12),
                const Text('Kode Akun', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(controller: codeController, decoration: const InputDecoration(hintText: 'Contoh: 1-1115')),
                const SizedBox(height: 14),
                const Text('Nama Akun', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(controller: nameController, decoration: const InputDecoration(hintText: 'Contoh: Kas Toko Cabang')),
                const SizedBox(height: 14),
                const Text('Kelompok Akun', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  value: group,
                  decoration: const InputDecoration(),
                  items: _groups.where((g) => g != 'ALL').map((g) {
                    return DropdownMenuItem(value: g, child: Text(g, style: const TextStyle(fontSize: 13)));
                  }).toList(),
                  onChanged: (val) => setModalState(() => group = val!),
                ),
                const SizedBox(height: 14),
                const Text('Tipe Akun (Header / Detail)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  value: type,
                  decoration: const InputDecoration(),
                  items: const [
                    DropdownMenuItem(value: 'D', child: Text('Detail (D) - Dapat Ditransaksikan')),
                    DropdownMenuItem(value: 'H', child: Text('Header (H) - Induk Kelompok')),
                  ],
                  onChanged: (val) => setModalState(() => type = val!),
                ),
                const SizedBox(height: 14),
                const Text('Saldo Awal (Rp)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(controller: balanceController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: '0')),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    onPressed: isSubmitting
                        ? null
                        : () async {
                            if (codeController.text.trim().isEmpty || nameController.text.trim().isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Kode dan nama akun wajib diisi')));
                              return;
                            }
                            setModalState(() => isSubmitting = true);

                            final res = await ApiService.storeAccount({
                              'code': codeController.text.trim(),
                              'name': nameController.text.trim(),
                              'group': group,
                              'type': type,
                              'initial_balance': double.tryParse(balanceController.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0,
                            });

                            if (res['success'] == true) {
                              Navigator.pop(context);
                              _loadData();
                              NotificationService.showNotification(
                                id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                title: 'Akun Perkiraan Dibuat! 📑',
                                body: 'Akun "${codeController.text} - ${nameController.text}" berhasil ditambahkan ke COA.',
                              );
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Akun COA berhasil dibuat!'), backgroundColor: ThemeConfig.accent),
                              );
                            } else {
                              setModalState(() => isSubmitting = false);
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Gagal membuat akun'), backgroundColor: Colors.red),
                              );
                            }
                          },
                    child: isSubmitting
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text('Simpan Akun COA'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final filtered = _selectedGroupFilter == 'ALL'
        ? _accounts
        : _accounts.where((a) => (a['group'] ?? '').toString().toUpperCase() == _selectedGroupFilter).toList();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Bagan Akun (COA)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ThemeConfig.primary,
        onPressed: _showAddAccountModal,
        icon: const Icon(Icons.add_card, color: Colors.white),
        label: const Text('Akun Baru', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
      ),
      body: Column(
        children: [
          Container(
            height: 48,
            margin: const EdgeInsets.symmetric(vertical: 8),
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: _groups.length,
              itemBuilder: (ctx, i) {
                final g = _groups[i];
                final isSel = g == _selectedGroupFilter;
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: FilterChip(
                    label: Text(g, style: TextStyle(fontSize: 12, fontWeight: isSel ? FontWeight.bold : FontWeight.normal, color: isSel ? Colors.white : ThemeConfig.textDark)),
                    selected: isSel,
                    selectedColor: ThemeConfig.primary,
                    checkmarkColor: Colors.white,
                    onSelected: (_) => setState(() => _selectedGroupFilter = g),
                  ),
                );
              },
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
                        ? const Center(child: Text('Tidak ada data akun pada kelompok ini', style: TextStyle(color: Colors.grey)))
                        : RefreshIndicator(
                            onRefresh: () async => _loadData(),
                            child: ListView.builder(
                              padding: const EdgeInsets.only(left: 16, right: 16, bottom: 80),
                              itemCount: filtered.length,
                              itemBuilder: (ctx, i) {
                                final a = filtered[i];
                                final isHeader = (a['type'] ?? 'D').toString().toUpperCase() == 'H';
                                final balance = Formatters.parseDouble(a['current_balance'] ?? a['initial_balance']);

                                return Card(
                                  margin: const EdgeInsets.only(bottom: 10),
                                  color: isHeader ? Colors.grey.shade100 : Colors.white,
                                  elevation: isHeader ? 0.5 : 1.5,
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                                  child: Padding(
                                    padding: const EdgeInsets.all(14),
                                    child: Row(
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                          decoration: BoxDecoration(
                                            color: ThemeConfig.primary.withOpacity(0.1),
                                            borderRadius: BorderRadius.circular(8),
                                          ),
                                          child: Text(
                                            a['code'] ?? '',
                                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: ThemeConfig.primary),
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
                                                  fontSize: 14,
                                                  color: ThemeConfig.textDark,
                                                ),
                                              ),
                                              Text(
                                                '${a['group'] ?? ''} • ${isHeader ? 'Header' : 'Detail'}',
                                                style: TextStyle(color: ThemeConfig.textMuted, fontSize: 11),
                                              ),
                                            ],
                                          ),
                                        ),
                                        if (!isHeader)
                                          Text(
                                            currencyFormatter.format(balance),
                                            style: TextStyle(
                                              fontWeight: FontWeight.bold,
                                              fontSize: 13,
                                              color: balance >= 0 ? ThemeConfig.textDark : Colors.red,
                                            ),
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
}
