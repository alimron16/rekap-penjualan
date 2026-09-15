import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/theme_config.dart';

class ProductsScreen extends StatefulWidget {
  final bool isMulti;
  const ProductsScreen({super.key, this.isMulti = false});

  @override
  State<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  List<dynamic> _products = [];
  bool _isLoading = true;
  final _searchController = TextEditingController();
  final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  @override
  void initState() {
    super.initState();
    _loadProducts();
  }

  void _loadProducts({String? search}) async {
    setState(() {
      _isLoading = true;
    });

    try {
      final res = widget.isMulti
          ? await ApiService.getMultiProducts()
          : await ApiService.getProducts(search: search);

      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _products = res['data'] ?? [];
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  void _showAddProductModal() {
    final codeController = TextEditingController(text: 'PRD-${DateTime.now().millisecondsSinceEpoch.toString().substring(7)}');
    final nameController = TextEditingController();
    final barcodeController = TextEditingController();
    final buyPriceController = TextEditingController(text: '0');
    final sellPriceController = TextEditingController(text: '0');
    final grosirPriceController = TextEditingController(text: '0');
    final stockController = TextEditingController(text: '0');
    final unitController = TextEditingController(text: 'PCS');
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
                    Text('Tambah Produk Baru', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: ThemeConfig.textDark)),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Kode Barang', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                          const SizedBox(height: 6),
                          TextField(controller: codeController, decoration: const InputDecoration(hintText: 'Kode')),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Barcode (Opsional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                          const SizedBox(height: 6),
                          TextField(controller: barcodeController, decoration: const InputDecoration(hintText: 'Scan/Ketik')),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                const Text('Nama Barang', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextField(controller: nameController, decoration: const InputDecoration(hintText: 'Nama lengkap barang')),
                const SizedBox(height: 14),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Harga Beli (Rp)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                          const SizedBox(height: 6),
                          TextField(controller: buyPriceController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: '0')),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Harga Jual Ritel (Rp)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                          const SizedBox(height: 6),
                          TextField(controller: sellPriceController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: '0')),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Harga Grosir (Rp)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                          const SizedBox(height: 6),
                          TextField(controller: grosirPriceController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: '0')),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Stok Awal & Satuan', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                          const SizedBox(height: 6),
                          Row(
                            children: [
                              Expanded(
                                flex: 2,
                                child: TextField(controller: stockController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: '0')),
                              ),
                              const SizedBox(width: 6),
                              Expanded(
                                flex: 1,
                                child: TextField(controller: unitController, decoration: const InputDecoration(hintText: 'PCS')),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    onPressed: isSubmitting
                        ? null
                        : () async {
                            if (nameController.text.trim().isEmpty || sellPriceController.text.trim().isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nama dan harga jual wajib diisi')));
                              return;
                            }
                            setModalState(() => isSubmitting = true);

                            final res = await ApiService.storeProduct({
                              'code': codeController.text.trim(),
                              'name': nameController.text.trim(),
                              'barcode': barcodeController.text.trim().isEmpty ? null : barcodeController.text.trim(),
                              'buy_price': double.tryParse(buyPriceController.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0,
                              'selling_price': double.tryParse(sellPriceController.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0,
                              'selling_price_grosir': double.tryParse(grosirPriceController.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0,
                              'stock': double.tryParse(stockController.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0,
                              'unit': unitController.text.trim(),
                              'status': 'Masih Dijual',
                            });

                            if (res['success'] == true) {
                              Navigator.pop(context);
                              _loadProducts();
                              NotificationService.showNotification(
                                id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                title: 'Produk Baru Disimpan! 🛍️',
                                body: 'Item "${nameController.text}" berhasil ditambahkan ke katalog.',
                              );
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Produk berhasil ditambahkan!'), backgroundColor: ThemeConfig.accent),
                              );
                            } else {
                              setModalState(() => isSubmitting = false);
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Gagal menyimpan produk'), backgroundColor: Colors.red),
                              );
                            }
                          },
                    child: isSubmitting
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text('Simpan Produk Baru'),
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
        title: Text(widget.isMulti ? 'Master Produk Multi' : 'Master Katalog Barang', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
      ),
      floatingActionButton: widget.isMulti
          ? null
          : FloatingActionButton.extended(
              backgroundColor: ThemeConfig.primary,
              onPressed: _showAddProductModal,
              icon: const Icon(Icons.add_circle, color: Colors.white),
              label: const Text('Tambah Produk', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
            ),
      body: Column(
        children: [
          // Search Input
          Container(
            padding: const EdgeInsets.all(16),
            color: Colors.white,
            child: TextField(
              controller: _searchController,
              onChanged: (val) {
                _loadProducts(search: val);
              },
              decoration: InputDecoration(
                hintText: 'Cari nama barang, barcode, merek...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () {
                          _searchController.clear();
                          _loadProducts();
                        },
                      )
                    : null,
              ),
            ),
          ),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
                : _products.isEmpty
                    ? const Center(
                        child: Text(
                          'Tidak ada barang ditemukan',
                          style: TextStyle(color: ThemeConfig.textMuted),
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: () async => _loadProducts(),
                        child: ListView.separated(
                          padding: const EdgeInsets.only(left: 16, right: 16, top: 16, bottom: 80),
                          itemCount: _products.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 8),
                          itemBuilder: (context, index) {
                            final item = _products[index];
                            final name = item['name'] ?? '-';
                            final code = item['code'] ?? '-';
                            final price = (item['selling_price'] ?? item['retail_price'] ?? item['price'] ?? 0).toDouble();
                            final grosirPrice = (item['selling_price_grosir'] ?? 0).toDouble();
                            final stock = item['stock'] ?? item['current_stock'] ?? 0;

                            return Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: Colors.grey.shade300),
                              ),
                              child: Row(
                                children: [
                                  Container(
                                    width: 44,
                                    height: 44,
                                    decoration: BoxDecoration(
                                      color: ThemeConfig.primary.withOpacity(0.08),
                                      borderRadius: BorderRadius.circular(10),
                                    ),
                                    child: const Icon(Icons.inventory_2_outlined, color: ThemeConfig.primary),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          name,
                                          style: const TextStyle(
                                            fontSize: 13,
                                            fontWeight: FontWeight.bold,
                                            color: ThemeConfig.textDark,
                                          ),
                                        ),
                                        const SizedBox(height: 2),
                                        Text(
                                          'Kode: $code • Stok: $stock ${item['unit'] ?? ''}',
                                          style: const TextStyle(
                                            fontSize: 11,
                                            color: ThemeConfig.textMuted,
                                          ),
                                        ),
                                        if (grosirPrice > 0)
                                          Text(
                                            'Grosir: ${currencyFormatter.format(grosirPrice)}',
                                            style: const TextStyle(fontSize: 11, color: Colors.teal, fontWeight: FontWeight.w600),
                                          ),
                                      ],
                                    ),
                                  ),
                                  Text(
                                    currencyFormatter.format(price),
                                    style: const TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.bold,
                                      color: ThemeConfig.primary,
                                    ),
                                  )
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
