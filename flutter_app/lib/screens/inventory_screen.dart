import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/theme_config.dart';

class InventoryScreen extends StatefulWidget {
  const InventoryScreen({super.key});

  @override
  State<InventoryScreen> createState() => _InventoryScreenState();
}

class _InventoryScreenState extends State<InventoryScreen> {
  bool _isLoading = true;
  String? _errorMessage;

  List<dynamic> _adjustments = [];
  List<dynamic> _products = [];

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
      final res = await ApiService.getInventoryAdjustments();
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _adjustments = res['adjustments'] ?? [];
            _products = res['products'] ?? [];
          } else {
            _errorMessage = res['message'] ?? 'Gagal memuat data penyesuaian stok';
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

  void _showAddAdjustmentModal() {
    int? selectedProductId = _products.isNotEmpty ? _products.first['id'] : null;
    String type = 'IN';
    final qtyController = TextEditingController(text: '1');
    final notesController = TextEditingController();
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
                    Text(
                      'Penyesuaian Stok (Opname)',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                    ),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                  ],
                ),
                const SizedBox(height: 12),
                const Text('Pilih Produk', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<int>(
                  value: selectedProductId,
                  isExpanded: true,
                  decoration: const InputDecoration(),
                  items: _products.map<DropdownMenuItem<int>>((p) {
                    return DropdownMenuItem<int>(
                      value: p['id'],
                      child: Text('${p['name']} (Sisa: ${p['stock'] ?? 0})', style: const TextStyle(fontSize: 13)),
                    );
                  }).toList(),
                  onChanged: (val) => setModalState(() => selectedProductId = val),
                ),
                const SizedBox(height: 14),
                const Text('Jenis Penyesuaian', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                Row(
                  children: [
                    Expanded(
                      child: ChoiceChip(
                        avatar: const Icon(Icons.add, size: 16, color: Colors.green),
                        label: const Center(child: Text('Masuk (+)')),
                        selected: type == 'IN',
                        selectedColor: Colors.green.withOpacity(0.2),
                        onSelected: (val) => setModalState(() => type = 'IN'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: ChoiceChip(
                        avatar: const Icon(Icons.remove, size: 16, color: Colors.red),
                        label: const Center(child: Text('Keluar (-)')),
                        selected: type == 'OUT',
                        selectedColor: Colors.red.withOpacity(0.2),
                        onSelected: (val) => setModalState(() => type = 'OUT'),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                const Text('Jumlah Qty', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(
                  controller: qtyController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(hintText: 'Jumlah barang'),
                ),
                const SizedBox(height: 14),
                const Text('Alasan / Catatan Penyesuaian', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(
                  controller: notesController,
                  decoration: const InputDecoration(hintText: 'Contoh: Stok opname bulanan, barang rusak, selisih display'),
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
                              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Pilih produk')));
                              return;
                            }
                            final qty = double.tryParse(qtyController.text) ?? 0;
                            if (qty <= 0) {
                              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Qty harus lebih dari 0')));
                              return;
                            }

                            setModalState(() => isSubmitting = true);

                            try {
                              final res = await ApiService.storeInventoryAdjustment(
                                productId: selectedProductId!,
                                qty: qty,
                                type: type,
                                notes: notesController.text,
                              );

                              if (res['success'] == true) {
                                Navigator.pop(context);
                                _loadData();
                                NotificationService.showNotification(
                                  id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                  title: 'Penyesuaian Stok Berhasil',
                                  body: 'Penyesuaian stok (${type == 'IN' ? '+' : '-'}$qty) berhasil disimpan.',
                                );
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(content: Text(res['message'] ?? 'Penyesuaian stok berhasil!'), backgroundColor: ThemeConfig.accent),
                                );
                              } else {
                                setModalState(() => isSubmitting = false);
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(content: Text(res['message'] ?? 'Gagal menyesuaikan stok'), backgroundColor: Colors.red),
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
                        : const Text('Simpan Penyesuaian'),
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
        title: const Text('Penyesuaian Stok', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ThemeConfig.primary,
        onPressed: _showAddAdjustmentModal,
        icon: const Icon(Icons.tune, color: Colors.white),
        label: const Text('Sesuaikan Stok', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
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
              : _adjustments.isEmpty
                  ? const Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.inventory_outlined, size: 64, color: Colors.grey),
                          SizedBox(height: 12),
                          Text('Belum ada riwayat penyesuaian stok', style: TextStyle(fontSize: 16, color: Colors.grey)),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: () async => _loadData(),
                      child: ListView.builder(
                        padding: const EdgeInsets.only(left: 16, right: 16, top: 16, bottom: 80),
                        itemCount: _adjustments.length,
                        itemBuilder: (ctx, i) {
                          final a = _adjustments[i];
                          final isAdd = (a['type'] ?? 'IN') == 'IN';
                          final qty = a['qty'] ?? 0;

                          return Card(
                            margin: const EdgeInsets.only(bottom: 12),
                            elevation: 1.5,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                            child: Padding(
                              padding: const EdgeInsets.all(16),
                              child: Row(
                                children: [
                                  Container(
                                    width: 44,
                                    height: 44,
                                    decoration: BoxDecoration(
                                      color: isAdd ? Colors.green.withOpacity(0.12) : Colors.red.withOpacity(0.12),
                                      shape: BoxShape.circle,
                                    ),
                                    child: Icon(
                                      isAdd ? Icons.add_circle_outline : Icons.remove_circle_outline,
                                      color: isAdd ? Colors.green : Colors.red,
                                    ),
                                  ),
                                  const SizedBox(width: 14),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          a['product']?['name'] ?? 'Produk',
                                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: ThemeConfig.textDark),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          '${isAdd ? 'Masuk' : 'Keluar'} • ${a['date'] ?? a['created_at']?.toString().substring(0, 10) ?? '-'}',
                                          style: TextStyle(color: ThemeConfig.textMuted, fontSize: 12),
                                        ),
                                        if (a['notes'] != null && a['notes'].toString().isNotEmpty) ...[
                                          const SizedBox(height: 2),
                                          Text(a['notes'], style: const TextStyle(fontSize: 11, fontStyle: FontStyle.italic, color: Colors.grey)),
                                        ],
                                      ],
                                    ),
                                  ),
                                  Text(
                                    '${isAdd ? '+' : '-'}$qty',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 16,
                                      color: isAdd ? Colors.green : Colors.red,
                                    ),
                                  ),
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
