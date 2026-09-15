import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/formatters.dart';
import '../utils/theme_config.dart';

class ReturnsScreen extends StatefulWidget {
  const ReturnsScreen({super.key});

  @override
  State<ReturnsScreen> createState() => _ReturnsScreenState();
}

class _ReturnsScreenState extends State<ReturnsScreen> {
  bool _isLoading = true;
  String? _errorMessage;

  List<dynamic> _returns = [];
  List<dynamic> _products = [];
  List<dynamic> _accounts = [];
  List<dynamic> _customers = [];

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
      final res = await ApiService.getReturns();
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _returns = res['returns'] ?? [];
            _products = res['products'] ?? [];
            _accounts = res['accounts'] ?? [];
            _customers = res['customers'] ?? [];
          } else {
            _errorMessage = res['message'] ?? 'Gagal memuat data retur';
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

  void _showAddReturnModal() {
    int? selectedProductId = _products.isNotEmpty ? _products.first['id'] : null;
    int? selectedCustomerId;
    int? selectedAccountId = _accounts.isNotEmpty ? _accounts.first['id'] : null;
    final qtyController = TextEditingController(text: '1');
    final refundController = TextEditingController();
    final notesController = TextEditingController();
    bool isSubmitting = false;

    // Autofill refund amount based on product price
    if (selectedProductId != null) {
      final prod = _products.firstWhere((p) => p['id'] == selectedProductId, orElse: () => null);
      if (prod != null) {
        refundController.text = Formatters.parseDouble(prod['selling_price']).toStringAsFixed(0);
      }
    }

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
                    const Text(
                      'Catat Retur Penjualan',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                const Text('Pilih Produk yang Diretur', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<int>(
                  value: selectedProductId,
                  isExpanded: true,
                  decoration: const InputDecoration(),
                  items: _products.map<DropdownMenuItem<int>>((p) {
                    return DropdownMenuItem<int>(
                      value: p['id'],
                      child: Text('${p['name']} (${Formatters.formatRupiah(p['selling_price'])})', style: const TextStyle(fontSize: 13)),
                    );
                  }).toList(),
                  onChanged: (val) {
                    setModalState(() {
                      selectedProductId = val;
                      final prod = _products.firstWhere((p) => p['id'] == val, orElse: () => null);
                      if (prod != null) {
                        final q = Formatters.parseDouble(qtyController.text);
                        final price = Formatters.parseDouble(prod['selling_price']);
                        refundController.text = (price * (q > 0 ? q : 1)).toStringAsFixed(0);
                      }
                    });
                  },
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    Expanded(
                      flex: 1,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Jumlah Qty', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                          const SizedBox(height: 6),
                          TextField(
                            controller: qtyController,
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(hintText: '1'),
                            onChanged: (val) {
                              final q = Formatters.parseDouble(val);
                              final prod = _products.firstWhere((p) => p['id'] == selectedProductId, orElse: () => null);
                              if (prod != null) {
                                final price = Formatters.parseDouble(prod['selling_price']);
                                refundController.text = (price * q).toStringAsFixed(0);
                              }
                            },
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      flex: 2,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Total Refund (Rp)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                          const SizedBox(height: 6),
                          TextField(
                            controller: refundController,
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(hintText: '0'),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                const Text('Pelanggan (Opsional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<int>(
                  value: selectedCustomerId,
                  isExpanded: true,
                  decoration: const InputDecoration(hintText: 'Pelanggan Umum (Tanpa Akun)'),
                  items: [
                    const DropdownMenuItem<int>(value: null, child: Text('Pelanggan Umum')),
                    ..._customers.map<DropdownMenuItem<int>>((c) {
                      return DropdownMenuItem<int>(
                        value: c['id'],
                        child: Text(c['name'] ?? '', style: const TextStyle(fontSize: 13)),
                      );
                    }),
                  ],
                  onChanged: (val) => setModalState(() => selectedCustomerId = val),
                ),
                const SizedBox(height: 14),
                const Text('Akun Pengembalian Dana', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<int>(
                  value: selectedAccountId,
                  decoration: const InputDecoration(),
                  items: _accounts.map<DropdownMenuItem<int>>((acc) {
                    return DropdownMenuItem<int>(
                      value: acc['id'],
                      child: Text('${acc['code']} - ${acc['name']}', style: const TextStyle(fontSize: 13)),
                    );
                  }).toList(),
                  onChanged: (val) => setModalState(() => selectedAccountId = val),
                ),
                const SizedBox(height: 14),
                const Text('Alasan Retur / Catatan', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(
                  controller: notesController,
                  decoration: const InputDecoration(hintText: 'Contoh: Barang cacat / salah beli'),
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    onPressed: isSubmitting
                        ? null
                        : () async {
                            if (selectedProductId == null) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Pilih produk yang diretur')),
                              );
                              return;
                            }
                            final qty = Formatters.parseDouble(qtyController.text);
                            final refund = Formatters.parseDouble(refundController.text);
                            if (qty <= 0) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Jumlah qty harus lebih dari 0')),
                              );
                              return;
                            }
                            if (selectedAccountId == null) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Pilih akun kas pengembalian dana')),
                              );
                              return;
                            }

                            setModalState(() => isSubmitting = true);

                            try {
                              final res = await ApiService.storeReturn(
                                date: DateFormat('yyyy-MM-dd').format(DateTime.now()),
                                productId: selectedProductId!,
                                customerId: selectedCustomerId,
                                qty: qty,
                                refundAmount: refund,
                                accountId: selectedAccountId!,
                                notes: notesController.text,
                              );

                              if (res['success'] == true) {
                                Navigator.pop(context);
                                _loadData();
                                NotificationService.showNotification(
                                  id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                  title: 'Retur Berhasil Dicatat! 🔄',
                                  body: 'Retur barang senilai ${Formatters.formatRupiah(refund)} berhasil disimpan dan stok telah disesuaikan.',
                                );
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text(res['message'] ?? 'Retur berhasil dicatat!'),
                                    backgroundColor: ThemeConfig.accent,
                                  ),
                                );
                              } else {
                                setModalState(() => isSubmitting = false);
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text(res['message'] ?? 'Gagal mencatat retur'),
                                    backgroundColor: Colors.red,
                                  ),
                                );
                              }
                            } catch (e) {
                              setModalState(() => isSubmitting = false);
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
                              );
                            }
                          },
                    child: isSubmitting
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text('Simpan Retur Penjualan'),
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
    return Scaffold(
      appBar: AppBar(
        title: const Text('Retur Penjualan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ThemeConfig.primary,
        onPressed: _showAddReturnModal,
        icon: const Icon(Icons.assignment_return, color: Colors.white),
        label: const Text('Retur Baru', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
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
              : _returns.isEmpty
                  ? const Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.inventory_2_outlined, size: 64, color: Colors.grey),
                          SizedBox(height: 12),
                          Text('Belum ada riwayat retur barang', style: TextStyle(fontSize: 16, color: Colors.grey)),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: () async => _loadData(),
                      child: ListView.builder(
                        padding: const EdgeInsets.only(left: 16, right: 16, top: 16, bottom: 80),
                        itemCount: _returns.length,
                        itemBuilder: (ctx, i) {
                          final r = _returns[i];
                          final refund = Formatters.parseDouble(r['refund_amount']);

                          return Card(
                            margin: const EdgeInsets.only(bottom: 12),
                            elevation: 1.5,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                            child: Padding(
                              padding: const EdgeInsets.all(16),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Expanded(
                                        child: Text(
                                          r['product']?['name'] ?? 'Produk',
                                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: ThemeConfig.textDark),
                                        ),
                                      ),
                                      Text(
                                        'Qty: ${r['qty'] ?? 1}',
                                        style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.orange, fontSize: 13),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 6),
                                  Text(
                                    'Pelanggan: ${r['customer']?['name'] ?? 'Pelanggan Umum'} • Tgl: ${r['date'] ?? '-'}',
                                    style: const TextStyle(color: ThemeConfig.textMuted, fontSize: 12),
                                  ),
                                  const Divider(height: 20),
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Text(
                                        'Akun: ${r['account']?['name'] ?? '-'}',
                                        style: const TextStyle(fontSize: 12, color: ThemeConfig.textMuted),
                                      ),
                                      Text(
                                        '-${Formatters.formatRupiah(refund)}',
                                        style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.red, fontSize: 14),
                                      ),
                                    ],
                                  ),
                                  if (r['notes'] != null && r['notes'].toString().isNotEmpty) ...[
                                    const SizedBox(height: 6),
                                    Text('Catatan: ${r['notes']}', style: const TextStyle(fontSize: 11, fontStyle: FontStyle.italic, color: Colors.grey)),
                                  ],
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                    ),
    );
  }
}
