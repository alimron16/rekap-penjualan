import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/formatters.dart';
import '../utils/theme_config.dart';

class ProductsScreen extends StatefulWidget {
  final bool isMulti;
  const ProductsScreen({super.key, this.isMulti = false});

  @override
  State<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  List<dynamic> _products = [];
  List<dynamic> _filteredProducts = [];
  List<dynamic> _types = [];
  String _selectedType = 'ALL';
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
            _filteredProducts = _products;
            _types = res['types'] ?? [];
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

  void _filterByType(String type) {
    setState(() {
      _selectedType = type;
      if (type == 'ALL') {
        _filteredProducts = _products;
      } else {
        _filteredProducts = _products.where((p) {
          final t = (p['type'] ?? '').toString().toUpperCase();
          return t == type.toUpperCase();
        }).toList();
      }
    });
  }

  void _showAddProductModal() {
    final codeController = TextEditingController(text: 'PRD-${DateTime.now().millisecondsSinceEpoch.toString().substring(7)}');
    final nameController = TextEditingController();
    final typeController = TextEditingController(text: 'VOCER');
    final brandController = TextEditingController(text: 'TELKOMSEL');
    final hppController = TextEditingController(text: '0');
    final retailPriceController = TextEditingController(text: '0');
    final wholesalePriceController = TextEditingController(text: '0');
    final stockController = TextEditingController(text: '0');
    final minStockController = TextEditingController(text: '5');
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
                    Text('Tambah Item Produk Baru', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: ThemeConfig.textDark)),
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
                          const Text('Jenis / Tipe', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                          const SizedBox(height: 6),
                          TextField(controller: typeController, decoration: const InputDecoration(hintText: 'VOCER/ACC/KABEL')),
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
                          const Text('Merek / Brand', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                          const SizedBox(height: 6),
                          TextField(controller: brandController, decoration: const InputDecoration(hintText: 'TELKOMSEL/ROBOT')),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Harga Modal (HPP)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                          const SizedBox(height: 6),
                          TextField(controller: hppController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: '0')),
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
                          const Text('Harga Eceran (Rp)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                          const SizedBox(height: 6),
                          TextField(controller: retailPriceController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: '0')),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Harga Grosir (Rp)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                          const SizedBox(height: 6),
                          TextField(controller: wholesalePriceController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: '0')),
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
                          const Text('Stok Awal', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                          const SizedBox(height: 6),
                          TextField(controller: stockController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: '0')),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Min. Stok', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                          const SizedBox(height: 6),
                          TextField(controller: minStockController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: '5')),
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
                            if (nameController.text.trim().isEmpty || retailPriceController.text.trim().isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nama dan harga jual wajib diisi')));
                              return;
                            }
                            setModalState(() => isSubmitting = true);

                            final res = await ApiService.storeProduct({
                              'item_code': codeController.text.trim(),
                              'name': nameController.text.trim(),
                              'type': typeController.text.trim().toUpperCase(),
                              'brand': brandController.text.trim().toUpperCase(),
                              'hpp': Formatters.parseDouble(hppController.text.replaceAll(RegExp(r'[^0-9.]'), '')),
                              'retail_price': Formatters.parseDouble(retailPriceController.text.replaceAll(RegExp(r'[^0-9.]'), '')),
                              'wholesale_price': Formatters.parseDouble(wholesalePriceController.text.replaceAll(RegExp(r'[^0-9.]'), '')),
                              'stock': Formatters.parseDouble(stockController.text.replaceAll(RegExp(r'[^0-9.]'), '')),
                              'min_stock': Formatters.parseInt(minStockController.text),
                              'status': 'Masih Dijual',
                            });

                            if (res['success'] == true) {
                              Navigator.pop(context);
                              _loadProducts();
                              NotificationService.showNotification(
                                id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                title: 'Produk Baru Disimpan! 🛍️',
                                body: 'Item "${nameController.text}" berhasil ditambahkan ke katalog stok.',
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
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        backgroundColor: ThemeConfig.primary,
        title: Text(widget.isMulti ? 'Master Produk Multi (Digital)' : 'Master Data Stok Barang', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: () => _loadProducts()),
        ],
      ),
      floatingActionButton: widget.isMulti
          ? null
          : FloatingActionButton.extended(
              backgroundColor: ThemeConfig.primary,
              onPressed: _showAddProductModal,
              icon: const Icon(Icons.add_circle, color: Colors.white),
              label: const Text('Tambah Item', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
            ),
      body: Column(
        children: [
          // Search & Filter Box
          Container(
            padding: const EdgeInsets.all(12),
            color: Colors.white,
            child: Column(
              children: [
                TextField(
                  controller: _searchController,
                  onChanged: (val) => _loadProducts(search: val),
                  decoration: InputDecoration(
                    hintText: 'Cari nama barang, kode, merek...',
                    prefixIcon: const Icon(Icons.search, size: 20),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    suffixIcon: _searchController.text.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear, size: 18),
                            onPressed: () {
                              _searchController.clear();
                              _loadProducts();
                            },
                          )
                        : null,
                  ),
                ),
                if (!widget.isMulti && _types.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        _buildFilterChip('ALL', 'SEMUA'),
                        ..._types.map((t) => _buildFilterChip(t.toString(), t.toString())),
                      ],
                    ),
                  ),
                ]
              ],
            ),
          ),

          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
                : _filteredProducts.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.inventory_2_outlined, size: 48, color: Colors.grey.shade400),
                            const SizedBox(height: 12),
                            const Text('Tidak ada item stok ditemukan', style: TextStyle(color: ThemeConfig.textMuted, fontSize: 13)),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: () async => _loadProducts(),
                        child: ListView.separated(
                          padding: const EdgeInsets.all(12),
                          itemCount: _filteredProducts.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 8),
                          itemBuilder: (context, index) {
                            final item = _filteredProducts[index];
                            final name = item['name'] ?? '-';
                            final code = item['item_code'] ?? item['code'] ?? '-';
                            final type = item['type'] ?? 'FISIK';
                            final brand = item['brand'] ?? '-';
                            final retailPrice = Formatters.parseDouble(item['retail_price'] ?? item['selling_price']);
                            final wholesalePrice = Formatters.parseDouble(item['wholesale_price'] ?? item['selling_price_grosir']);
                            final stock = Formatters.parseDouble(item['stock'] ?? item['current_stock']);
                            final isOutOfStock = stock <= 0;

                            return Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: Colors.grey.shade200),
                                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 4, offset: const Offset(0, 2))],
                              ),
                              child: Row(
                                children: [
                                  Container(
                                    width: 44,
                                    height: 44,
                                    decoration: BoxDecoration(
                                      color: isOutOfStock ? Colors.red.shade50 : ThemeConfig.primary.withOpacity(0.08),
                                      borderRadius: BorderRadius.circular(10),
                                    ),
                                    child: Icon(
                                      Icons.inventory_2_outlined,
                                      color: isOutOfStock ? Colors.red : ThemeConfig.primary,
                                      size: 22,
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Row(
                                          children: [
                                            Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                              decoration: BoxDecoration(
                                                color: Colors.blue.shade50,
                                                borderRadius: BorderRadius.circular(4),
                                              ),
                                              child: Text(
                                                type.toString().toUpperCase(),
                                                style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Colors.blue.shade900),
                                              ),
                                            ),
                                            const SizedBox(width: 6),
                                            Text(
                                              code,
                                              style: const TextStyle(fontFamily: 'monospace', fontSize: 11, fontWeight: FontWeight.bold, color: ThemeConfig.primary),
                                            ),
                                          ],
                                        ),
                                        const SizedBox(height: 3),
                                        Text(
                                          name,
                                          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                                        ),
                                        const SizedBox(height: 2),
                                        Text(
                                          'Brand: $brand • Stok: ${stock.toStringAsFixed(0)}',
                                          style: TextStyle(
                                            fontSize: 11,
                                            fontWeight: FontWeight.w600,
                                            color: isOutOfStock ? Colors.red : ThemeConfig.textMuted,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.end,
                                    children: [
                                      Text(
                                        Formatters.formatRupiah(retailPrice),
                                        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w900, color: ThemeConfig.primary),
                                      ),
                                      if (wholesalePrice > 0 && wholesalePrice != retailPrice)
                                        Text(
                                          'Grosir: ${Formatters.formatRupiah(wholesalePrice)}',
                                          style: TextStyle(fontSize: 10, color: Colors.teal.shade800, fontWeight: FontWeight.bold),
                                        ),
                                    ],
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

  Widget _buildFilterChip(String key, String label) {
    final isSelected = _selectedType == key;
    return Padding(
      padding: const EdgeInsets.only(right: 6),
      child: ChoiceChip(
        label: Text(label, style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: isSelected ? Colors.white : Colors.grey.shade700)),
        selected: isSelected,
        selectedColor: ThemeConfig.primary,
        backgroundColor: Colors.grey.shade100,
        onSelected: (_) => _filterByType(key),
      ),
    );
  }
}

