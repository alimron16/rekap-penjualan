import 'package:flutter/material.dart';
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

class _ProductsScreenState extends State<ProductsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  // Physical Items Data
  List<dynamic> _items = [];
  List<dynamic> _filteredItems = [];
  List<dynamic> _itemTypes = [];
  List<dynamic> _itemBrands = [];
  String _selectedItemType = 'ALL';
  bool _isLoadingItems = true;

  // Multi Products Data
  List<dynamic> _multiProducts = [];
  List<dynamic> _filteredMultiProducts = [];
  List<dynamic> _multiTrxTypes = [];
  List<dynamic> _multiCategories = [];
  String _selectedMultiTrxType = 'ALL';
  String _selectedMultiCategory = 'ALL';
  bool _isLoadingMulti = true;

  final _searchItemController = TextEditingController();
  final _searchMultiController = TextEditingController();

  // Outlet Scoping
  List<dynamic> _outlets = [];
  int? _selectedOutletId;
  String _selectedOutletName = 'Semua Toko (Global)';
  bool _isGlobal = true;

  Map<String, dynamic>? _currentUser;
  bool _canEditStock = true;
  bool _canEditPrice = true;
  bool _canFilterOutlet = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this, initialIndex: widget.isMulti ? 1 : 0);
    _tabController.addListener(() {
      if (!_tabController.indexIsChanging) {
        setState(() {});
      }
    });

    _loadUser();
    _loadItems();
    _loadMultiProducts();
  }

  void _loadUser() async {
    final user = await ApiService.getUser();
    if (user != null && mounted) {
      setState(() {
        _currentUser = user;
        final role = (user['role'] ?? '').toString().toLowerCase();
        final isAdminOrSuper = role == 'admin' || role == 'super_admin' || role == 'superadmin';
        _canFilterOutlet = isAdminOrSuper;
        if (isAdminOrSuper) {
          _canEditStock = true;
          _canEditPrice = true;
        } else {
          final perms = user['permissions'];
          if (perms is Map && perms.containsKey('edit_stock')) {
            _canEditStock = perms['edit_stock'] == true;
          } else {
            _canEditStock = false;
          }
          _canEditPrice = false; // Toko/FL tidak bisa ubah harga
        }
      });
    }
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchItemController.dispose();
    _searchMultiController.dispose();
    super.dispose();
  }

  // ==========================================
  // 1. PHYSICAL ITEMS (BARANG FISIK)
  // ==========================================
  void _loadItems({String? search}) async {
    setState(() => _isLoadingItems = true);
    try {
      final res = await ApiService.getProducts(search: search, outletId: _selectedOutletId);
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _items = res['data'] ?? [];
            _itemTypes = res['types'] ?? [];
            _itemBrands = res['brands'] ?? [];
            if (res['outlets'] != null && res['outlets'] is List) {
              _outlets = res['outlets'];
            }
            _selectedOutletName = res['selected_outlet_name'] ?? (_selectedOutletId == null ? 'Semua Toko (Global)' : 'Toko Cabang');
            _isGlobal = res['is_global'] ?? (_selectedOutletId == null);
            _applyItemFilter();
          }
          _isLoadingItems = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _isLoadingItems = false);
    }
  }

  void _applyItemFilter() {
    final query = _searchItemController.text.trim().toLowerCase();
    setState(() {
      _filteredItems = _items.where((item) {
        final name = (item['name'] ?? '').toString().toLowerCase();
        final code = (item['item_code'] ?? '').toString().toLowerCase();
        final brand = (item['brand'] ?? '').toString().toLowerCase();
        final type = (item['type'] ?? '').toString().toUpperCase();

        final matchesSearch = query.isEmpty || name.contains(query) || code.contains(query) || brand.contains(query);
        final matchesType = _selectedItemType == 'ALL' || type == _selectedItemType.toUpperCase();

        return matchesSearch && matchesType;
      }).toList();
    });
  }

  // ==========================================
  // 2. MULTI PRODUCTS (PRODUK ELEKTRIK)
  // ==========================================
  void _loadMultiProducts() async {
    setState(() => _isLoadingMulti = true);
    try {
      final res = await ApiService.getMultiProducts(
        search: _searchMultiController.text.trim(),
        trxType: _selectedMultiTrxType,
        category: _selectedMultiCategory,
        outletId: _selectedOutletId,
      );
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _multiProducts = res['data'] ?? [];
            _multiTrxTypes = res['trx_types'] ?? [];
            _multiCategories = res['categories'] ?? [];
            if (res['outlets'] != null && res['outlets'] is List && _outlets.isEmpty) {
              _outlets = res['outlets'];
            }
            _filteredMultiProducts = _multiProducts;
          }
          _isLoadingMulti = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _isLoadingMulti = false);
    }
  }

  void _applyMultiFilter() {
    final query = _searchMultiController.text.trim().toLowerCase();
    setState(() {
      _filteredMultiProducts = _multiProducts.where((p) {
        final name = (p['name'] ?? '').toString().toLowerCase();
        final code = (p['product_code'] ?? '').toString().toLowerCase();
        final trxType = (p['trx_type'] ?? '').toString().toUpperCase();
        final cat = (p['category'] ?? '').toString().toUpperCase();

        final matchesSearch = query.isEmpty || name.contains(query) || code.contains(query);
        final matchesType = _selectedMultiTrxType == 'ALL' || trxType == _selectedMultiTrxType.toUpperCase();
        final matchesCat = _selectedMultiCategory == 'ALL' || cat == _selectedMultiCategory.toUpperCase();

        return matchesSearch && matchesType && matchesCat;
      }).toList();
    });
  }

  // ==========================================
  // CRUD ACTIONS: MODALS & DIALOGS
  // ==========================================

  // --- A. BARANG FISIK MODAL (ADD / EDIT) ---
  void _showItemFormModal({dynamic item}) {
    final isEditing = item != null;
    final codeController = TextEditingController(text: item?['item_code'] ?? 'PRD-${DateTime.now().millisecondsSinceEpoch.toString().substring(7)}');
    final nameController = TextEditingController(text: item?['name'] ?? '');
    final typeController = TextEditingController(text: item?['type'] ?? 'VOCER');
    final brandController = TextEditingController(text: item?['brand'] ?? 'TELKOMSEL');
    final hppController = TextEditingController(text: item != null ? Formatters.parseDouble(item['hpp']).toStringAsFixed(0) : '0');
    final retailPriceController = TextEditingController(text: item != null ? Formatters.parseDouble(item['retail_price']).toStringAsFixed(0) : '0');
    final wholesalePriceController = TextEditingController(text: item != null ? Formatters.parseDouble(item['wholesale_price']).toStringAsFixed(0) : '0');
    final stockController = TextEditingController(text: item != null ? Formatters.parseDouble(item['stock']).toStringAsFixed(0) : '0');
    final minStockController = TextEditingController(text: item != null ? (item['min_stock'] ?? 5).toString() : '5');
    String status = item?['status'] ?? 'Masih Dijual';
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
                      isEditing ? 'Edit Barang: ${item['name']}' : 'Tambah Item Barang Baru',
                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                    ),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                  ],
                ),
                const Divider(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Kode Barang', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          TextField(controller: codeController, decoration: const InputDecoration(hintText: 'Kode')),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Jenis / Tipe', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          TextField(controller: typeController, decoration: const InputDecoration(hintText: 'VOCER/ACC/KABEL')),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                const Text('Nama Lengkap Barang', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 4),
                TextField(controller: nameController, decoration: const InputDecoration(hintText: 'Nama lengkap barang')),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Merek / Brand', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          TextField(controller: brandController, decoration: const InputDecoration(hintText: 'TELKOMSEL/ROBOT')),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Status Penjualan', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          DropdownButtonFormField<String>(
                            value: status,
                            decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8)),
                            items: const [
                              DropdownMenuItem(value: 'Masih Dijual', child: Text('Masih Dijual')),
                              DropdownMenuItem(value: 'Tidak Dijual', child: Text('Tidak Dijual')),
                            ],
                            onChanged: (val) => setModalState(() => status = val ?? 'Masih Dijual'),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                // Pesan kunci harga untuk non-admin
                if (!_canEditPrice)
                  Container(
                    margin: const EdgeInsets.only(bottom: 8),
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                    decoration: BoxDecoration(
                      color: Colors.amber.shade50,
                      border: Border.all(color: Colors.amber.shade200),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.lock_outline, size: 14, color: Colors.amber.shade700),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            'Harga hanya dapat diubah oleh Admin. Hubungi Admin untuk perubahan harga.',
                            style: TextStyle(fontSize: 11, color: Colors.amber.shade800, fontWeight: FontWeight.w500),
                          ),
                        ),
                      ],
                    ),
                  ),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Text('HPP (Modal)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                              if (!_canEditPrice) ...[
                                const SizedBox(width: 4),
                                const Icon(Icons.lock_outline, size: 12, color: Colors.grey),
                              ],
                            ],
                          ),
                          const SizedBox(height: 4),
                          TextField(
                            controller: hppController,
                            keyboardType: TextInputType.number,
                            enabled: _canEditPrice,
                            decoration: InputDecoration(
                              hintText: '0',
                              filled: !_canEditPrice,
                              fillColor: !_canEditPrice ? Colors.grey.shade100 : null,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Text('Harga Retail', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                              if (!_canEditPrice) ...[
                                const SizedBox(width: 4),
                                const Icon(Icons.lock_outline, size: 12, color: Colors.grey),
                              ],
                            ],
                          ),
                          const SizedBox(height: 4),
                          TextField(
                            controller: retailPriceController,
                            keyboardType: TextInputType.number,
                            enabled: _canEditPrice,
                            decoration: InputDecoration(
                              hintText: '0',
                              filled: !_canEditPrice,
                              fillColor: !_canEditPrice ? Colors.grey.shade100 : null,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Text('Harga Grosir', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                              if (!_canEditPrice) ...[
                                const SizedBox(width: 4),
                                const Icon(Icons.lock_outline, size: 12, color: Colors.grey),
                              ],
                            ],
                          ),
                          const SizedBox(height: 4),
                          TextField(
                            controller: wholesalePriceController,
                            keyboardType: TextInputType.number,
                            enabled: _canEditPrice,
                            decoration: InputDecoration(
                              hintText: '0',
                              filled: !_canEditPrice,
                              fillColor: !_canEditPrice ? Colors.grey.shade100 : null,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Text('Stok Sekarang', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                              if (!_canEditStock) ...[
                                const SizedBox(width: 4),
                                const Icon(Icons.lock_outline, size: 13, color: Colors.grey),
                              ],
                            ],
                          ),
                          const SizedBox(height: 4),
                          TextField(
                            controller: stockController,
                            keyboardType: TextInputType.number,
                            enabled: _canEditStock,
                            decoration: InputDecoration(
                              hintText: '0',
                              filled: !_canEditStock,
                              fillColor: !_canEditStock ? Colors.grey.shade100 : null,
                              helperText: !_canEditStock ? 'Hanya Admin yg dapat mengubah stok' : null,
                              helperStyle: const TextStyle(fontSize: 10, color: Colors.grey),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Batas Min. Stok', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          TextField(controller: minStockController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: '5')),
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
                            if (nameController.text.trim().isEmpty || retailPriceController.text.trim().isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nama barang dan harga jual wajib diisi')));
                              return;
                            }
                            setModalState(() => isSubmitting = true);

                            final payload = {
                              'item_code': codeController.text.trim(),
                              'name': nameController.text.trim(),
                              'type': typeController.text.trim().toUpperCase(),
                              'brand': brandController.text.trim().toUpperCase(),
                              'hpp': Formatters.parseDouble(hppController.text.replaceAll(RegExp(r'[^0-9.]'), '')),
                              'retail_price': Formatters.parseDouble(retailPriceController.text.replaceAll(RegExp(r'[^0-9.]'), '')),
                              'wholesale_price': Formatters.parseDouble(wholesalePriceController.text.replaceAll(RegExp(r'[^0-9.]'), '')),
                              'stock': Formatters.parseDouble(stockController.text.replaceAll(RegExp(r'[^0-9.]'), '')),
                              'min_stock': Formatters.parseInt(minStockController.text),
                              'status': status,
                            };

                            final res = isEditing
                                ? await ApiService.updateProduct(item['id'], payload)
                                : await ApiService.storeProduct(payload);

                            if (res['success'] == true) {
                              Navigator.pop(context);
                              _loadItems();
                              NotificationService.showNotification(
                                id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                title: isEditing ? 'Item Diperbarui' : 'Item Baru Disimpan',
                                body: 'Barang "${nameController.text}" berhasil disimpan ke sistem.',
                              );
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Berhasil disimpan!'), backgroundColor: ThemeConfig.accent),
                              );
                            } else {
                              setModalState(() => isSubmitting = false);
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Gagal menyimpan barang'), backgroundColor: Colors.red),
                              );
                            }
                          },
                    child: isSubmitting
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : Text(isEditing ? 'Simpan Perubahan Barang' : 'Tambah Item Barang'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _confirmDeleteItem(dynamic item) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: const [
            Icon(Icons.warning_amber_rounded, color: Colors.red, size: 28),
            SizedBox(width: 8),
            Text('Hapus Barang?', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Text('Yakin ingin menghapus item [${item['name']}]?\n\nPerhatian: Barang dengan riwayat transaksi tidak dapat dihapus, ganti status menjadi "Tidak Dijual" untuk menonaktifkan.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () async {
              Navigator.pop(ctx);
              final res = await ApiService.deleteProduct(item['id']);
              if (res['success'] == true) {
                _loadItems();
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Barang berhasil dihapus!'), backgroundColor: Colors.red),
                );
              } else {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Gagal menghapus barang'), backgroundColor: Colors.red),
                );
              }
            },
            child: const Text('Hapus Sekarang'),
          ),
        ],
      ),
    );
  }

  // --- B. PRODUK MULTI MODAL (ADD / EDIT) ---
  void _showMultiFormModal({dynamic product}) {
    final isEditing = product != null;
    final codeController = TextEditingController(text: product?['product_code'] ?? 'MLT-${DateTime.now().millisecondsSinceEpoch.toString().substring(7)}');
    final nameController = TextEditingController(text: product?['name'] ?? '');
    final trxTypeController = TextEditingController(text: product?['trx_type'] ?? 'PULSA');
    final categoryController = TextEditingController(text: product?['category'] ?? 'TELKOMSEL');
    final hppController = TextEditingController(text: product != null ? Formatters.parseDouble(product['hpp']).toStringAsFixed(0) : '0');
    final sellingPriceController = TextEditingController(text: product != null ? Formatters.parseDouble(product['selling_price']).toStringAsFixed(0) : '0');
    String status = product?['status'] ?? 'OPEN';
    int? outletId = isEditing ? product['outlet_id'] : _selectedOutletId;
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
                      isEditing ? 'Edit Produk Multi: ${product['name']}' : 'Tambah Produk Multi (Digital)',
                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                    ),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                  ],
                ),
                const Divider(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Kode Produk Server', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          TextField(controller: codeController, decoration: const InputDecoration(hintText: 'T10/PLN20')),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Jenis Transaksi', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          TextField(controller: trxTypeController, decoration: const InputDecoration(hintText: 'PULSA/DATA/PLN')),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                const Text('Nama Produk', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 4),
                TextField(controller: nameController, decoration: const InputDecoration(hintText: 'Telkomsel 10.000 / Token PLN 50K')),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Kategori / Operator', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          TextField(controller: categoryController, decoration: const InputDecoration(hintText: 'TELKOMSEL/INDOSAT')),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Status Produk', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          DropdownButtonFormField<String>(
                            value: status,
                            decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8)),
                            items: const [
                              DropdownMenuItem(value: 'OPEN', child: Text('OPEN (Aktif)')),
                              DropdownMenuItem(value: 'CLOSE', child: Text('CLOSE (Gangguan)')),
                            ],
                            onChanged: (val) => setModalState(() => status = val ?? 'OPEN'),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('HPP (Modal Server)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          TextField(controller: hppController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: '0')),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Harga Jual Konsumen', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          TextField(controller: sellingPriceController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: '0')),
                        ],
                      ),
                    ),
                  ],
                ),
                if (_canFilterOutlet && _outlets.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  const Text('Cabang / Toko Pemilik', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                  const SizedBox(height: 4),
                  DropdownButtonFormField<int?>(
                    value: outletId,
                    decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8)),
                    items: [
                      const DropdownMenuItem<int?>(value: null, child: Text('Semua Toko (Global)')),
                      ..._outlets.map<DropdownMenuItem<int?>>((ot) => DropdownMenuItem<int?>(
                        value: ot['id'],
                        child: Text(ot['name'] ?? 'Cabang'),
                      )),
                    ],
                    onChanged: (val) => setModalState(() => outletId = val),
                  ),
                ],
                const SizedBox(height: 18),
                SizedBox(
                  width: double.infinity,
                  height: 46,
                  child: ElevatedButton(
                    onPressed: isSubmitting
                        ? null
                        : () async {
                            if (nameController.text.trim().isEmpty || sellingPriceController.text.trim().isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nama dan harga jual wajib diisi')));
                              return;
                            }
                            setModalState(() => isSubmitting = true);

                            final payload = {
                              'product_code': codeController.text.trim(),
                              'name': nameController.text.trim(),
                              'trx_type': trxTypeController.text.trim().toUpperCase(),
                              'category': categoryController.text.trim().toUpperCase(),
                              'hpp': Formatters.parseDouble(hppController.text.replaceAll(RegExp(r'[^0-9.]'), '')),
                              'selling_price': Formatters.parseDouble(sellingPriceController.text.replaceAll(RegExp(r'[^0-9.]'), '')),
                              'status': status,
                              'outlet_id': outletId,
                            };

                            final res = isEditing
                                ? await ApiService.updateMultiProduct(product['id'], payload)
                                : await ApiService.storeMultiProduct(payload);

                            if (res['success'] == true) {
                              Navigator.pop(context);
                              _loadMultiProducts();
                              NotificationService.showNotification(
                                id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                title: isEditing ? 'Produk Multi Diperbarui' : 'Produk Multi Disimpan',
                                body: 'Produk "${nameController.text}" berhasil disimpan ke sistem.',
                              );
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Berhasil disimpan!'), backgroundColor: ThemeConfig.accent),
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
                        : Text(isEditing ? 'Simpan Perubahan Produk' : 'Tambah Produk Multi'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _confirmDeleteMulti(dynamic product) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: const [
            Icon(Icons.warning_amber_rounded, color: Colors.red, size: 28),
            SizedBox(width: 8),
            Text('Hapus Produk Multi?', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Text('Yakin ingin menghapus produk multi [${product['name']}]?\n\nPerhatian: Produk dengan riwayat transaksi tidak dapat dihapus, ganti status menjadi "CLOSE" untuk menonaktifkan.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () async {
              Navigator.pop(ctx);
              final res = await ApiService.deleteMultiProduct(product['id']);
              if (res['success'] == true) {
                _loadMultiProducts();
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Produk berhasil dihapus!'), backgroundColor: Colors.red),
                );
              } else {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(res['message'] ?? 'Gagal menghapus produk multi'), backgroundColor: Colors.red),
                );
              }
            },
            child: const Text('Hapus Sekarang'),
          ),
        ],
      ),
    );
  }

  // ==========================================
  // BUILD METHOD
  // ==========================================
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        title: const Text('Master Data Barang & Multi', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
        backgroundColor: ThemeConfig.primary,
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: ThemeConfig.accent,
          indicatorWeight: 3,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
          tabs: const [
            Tab(icon: Icon(Icons.inventory_2_outlined, size: 18), text: 'Stok Barang Fisik'),
            Tab(icon: Icon(Icons.flash_on, size: 18), text: 'Produk Multi (Digital)'),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () {
              if (_tabController.index == 0) {
                _loadItems();
              } else {
                _loadMultiProducts();
              }
            },
            tooltip: 'Segarkan Data',
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ThemeConfig.primary,
        onPressed: () {
          if (_tabController.index == 0) {
            _showItemFormModal();
          } else {
            _showMultiFormModal();
          }
        },
        icon: const Icon(Icons.add_circle, color: Colors.white),
        label: Text(
          _tabController.index == 0 ? 'Tambah Barang' : 'Tambah Multi',
          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildPhysicalItemsTab(),
          _buildMultiProductsTab(),
        ],
      ),
    );
  }

  Widget _buildOutletSelectorBar({required VoidCallback onOutletChanged}) {
    if (!_canFilterOutlet || _outlets.isEmpty) return const SizedBox.shrink();

    return Container(
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
              Text(
                'PILIH TOKO / CABANG:',
                style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
              ),
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
                        setState(() {
                          _selectedOutletId = null;
                          _selectedOutletName = 'Semua Toko (Global)';
                          _isGlobal = true;
                        });
                        onOutletChanged();
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
                          setState(() {
                            _selectedOutletId = ot['id'];
                            _selectedOutletName = ot['name'] ?? 'Cabang';
                            _isGlobal = false;
                          });
                          onOutletChanged();
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
    );
  }

  // ==========================================
  // VIEW: TAB 1 - STOK BARANG FISIK
  // ==========================================
  Widget _buildPhysicalItemsTab() {
    return Column(
      children: [
        // Admin Outlet Selector Bar
        _buildOutletSelectorBar(onOutletChanged: () {
          _loadItems();
          _loadMultiProducts();
        }),

        // Informational Banner (Keterangan Sumber Stok Aktif)
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
              Icon(_isGlobal ? Icons.public : Icons.store, size: 20, color: _isGlobal ? const Color(0xFF1D4ED8) : const Color(0xFF047857)),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _isGlobal
                          ? 'MODE: STOK GLOBAL (KONSOLIDASI SELURUH TOKO)'
                          : 'MODE: STOK CABANG - ${_selectedOutletName.toUpperCase()}',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: _isGlobal ? const Color(0xFF1E3A8A) : const Color(0xFF064E3B),
                      ),
                    ),
                    Text(
                      _isGlobal
                          ? 'Angka pada kolom STOK adalah total akumulasi fisik barang dari seluruh toko.'
                          : 'Angka pada kolom STOK adalah stok fisik aktual yang tersedia di toko $_selectedOutletName.',
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

        // Search & Filter Header
        Container(
          padding: const EdgeInsets.all(12),
          color: Colors.white,
          child: Column(
            children: [
              TextField(
                controller: _searchItemController,
                onChanged: (_) => _applyItemFilter(),
                decoration: InputDecoration(
                  hintText: 'Cari nama barang, barcode, merek...',
                  prefixIcon: const Icon(Icons.search, size: 20),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  suffixIcon: _searchItemController.text.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear, size: 18),
                          onPressed: () {
                            _searchItemController.clear();
                            _applyItemFilter();
                          },
                        )
                      : null,
                ),
              ),
              if (_itemTypes.isNotEmpty) ...[
                const SizedBox(height: 8),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      _buildChip('ALL', 'SEMUA', _selectedItemType, (t) {
                        setState(() => _selectedItemType = t);
                        _applyItemFilter();
                      }),
                      ..._itemTypes.map((t) => _buildChip(t.toString(), t.toString(), _selectedItemType, (type) {
                        setState(() => _selectedItemType = type);
                        _applyItemFilter();
                      })),
                    ],
                  ),
                ),
              ]
            ],
          ),
        ),

        // Summary Bar
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          color: Colors.grey.shade100,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Menampilkan: ${_filteredItems.length} Item', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ThemeConfig.textDark)),
              const Text('Geser tabel ke kanan untuk melihat semua kolom', style: TextStyle(fontSize: 10, color: Colors.grey)),
            ],
          ),
        ),

        // Data Table
        Expanded(
          child: _isLoadingItems
              ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
              : _filteredItems.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.inventory_2_outlined, size: 48, color: Colors.grey.shade400),
                          const SizedBox(height: 8),
                          const Text('Tidak ada item stok ditemukan', style: TextStyle(color: ThemeConfig.textMuted, fontSize: 13)),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: () async => _loadItems(),
                      child: SingleChildScrollView(
                        scrollDirection: Axis.vertical,
                        child: SingleChildScrollView(
                          scrollDirection: Axis.horizontal,
                          child: DataTable(
                            headingRowColor: MaterialStateProperty.all(const Color(0xFF0F3D24)),
                            headingTextStyle: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 11),
                            dataRowHeight: 48,
                            columns: [
                              const DataColumn(label: Text('NO')),
                              const DataColumn(label: Text('KODE BARANG')),
                              const DataColumn(label: Text('NAMA BARANG')),
                              const DataColumn(label: Text('JENIS')),
                              const DataColumn(label: Text('MEREK')),
                              DataColumn(label: Text(_isGlobal ? 'TOTAL STOK' : 'STOK TOKO')),
                              const DataColumn(label: Text('MIN')),
                              const DataColumn(label: Text('HPP (MODAL)')),
                              const DataColumn(label: Text('HARGA RETAIL')),
                              const DataColumn(label: Text('HARGA GROSIR')),
                              const DataColumn(label: Text('SUBTOTAL NILAI')),
                              const DataColumn(label: Text('STATUS')),
                              const DataColumn(label: Text('AKSI')),
                            ],
                            rows: List<DataRow>.generate(_filteredItems.length, (idx) {
                              final item = _filteredItems[idx];
                              final stock = Formatters.parseDouble(item['stock']);
                              final minStock = Formatters.parseInt(item['min_stock']);
                              final hpp = Formatters.parseDouble(item['hpp']);
                              final retail = Formatters.parseDouble(item['retail_price']);
                              final wholesale = Formatters.parseDouble(item['wholesale_price']);
                              final subtotal = stock * hpp;
                              final isLow = stock <= minStock;
                              final isAktif = item['status'] == 'Masih Dijual';

                              return DataRow(
                                color: MaterialStateProperty.resolveWith<Color?>((states) => idx % 2 == 0 ? Colors.white : const Color(0xFFF8FAFC)),
                                cells: [
                                  DataCell(Text('${idx + 1}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11))),
                                  DataCell(Text(item['item_code'] ?? '-', style: const TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.bold, color: ThemeConfig.primary, fontSize: 11))),
                                  DataCell(
                                    SizedBox(
                                      width: 180,
                                      child: Text(item['name'] ?? '-', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 11), overflow: TextOverflow.ellipsis),
                                    ),
                                  ),
                                  DataCell(
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                      decoration: BoxDecoration(color: Colors.blue.shade50, borderRadius: BorderRadius.circular(4)),
                                      child: Text(item['type'] ?? '-', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.blue.shade900)),
                                    ),
                                  ),
                                  DataCell(Text(item['brand'] ?? '-', style: const TextStyle(fontSize: 11))),
                                  DataCell(
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: isLow ? Colors.red.shade50 : Colors.green.shade50,
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Text(
                                        stock.toStringAsFixed(0),
                                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: isLow ? Colors.red.shade700 : Colors.green.shade800),
                                      ),
                                    ),
                                  ),
                                  DataCell(Text('$minStock', style: const TextStyle(fontSize: 11, color: Colors.grey))),
                                  DataCell(Text(Formatters.formatRupiah(hpp), style: const TextStyle(fontFamily: 'monospace', fontSize: 11))),
                                  DataCell(Text(Formatters.formatRupiah(retail), style: const TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.bold, fontSize: 11))),
                                  DataCell(Text(Formatters.formatRupiah(wholesale), style: TextStyle(fontFamily: 'monospace', fontSize: 11, color: Colors.teal.shade800, fontWeight: FontWeight.bold))),
                                  DataCell(Text(Formatters.formatRupiah(subtotal), style: const TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.bold, fontSize: 11, color: ThemeConfig.primary))),
                                  DataCell(
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: isAktif ? Colors.green.shade100 : Colors.red.shade100,
                                        borderRadius: BorderRadius.circular(4),
                                      ),
                                      child: Text(item['status'] ?? '-', style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: isAktif ? Colors.green.shade900 : Colors.red.shade900)),
                                    ),
                                  ),
                                  DataCell(
                                    Row(
                                      children: [
                                        IconButton(
                                          icon: const Icon(Icons.edit, size: 18, color: Colors.blue),
                                          onPressed: () => _showItemFormModal(item: item),
                                          tooltip: 'Edit Barang',
                                        ),
                                        IconButton(
                                          icon: const Icon(Icons.delete, size: 18, color: Colors.red),
                                          onPressed: () => _confirmDeleteItem(item),
                                          tooltip: 'Hapus Barang',
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              );
                            }),
                          ),
                        ),
                      ),
                    ),
        ),
      ],
    );
  }

  // ==========================================
  // VIEW: TAB 2 - PRODUK MULTI (DIGITAL)
  // EXACT 100% MATCHING USER SCREENSHOT TABLE:
  // [NO] [KODE PRODUK] [NAMA PRODUK] [JENIS TRX] [KATEGORI] [HPP (MODAL SERVER)] [HARGA JUAL] [MARGIN LABA] [STATUS] [AKSI]
  // ==========================================
  Widget _buildMultiProductsTab() {
    return Column(
      children: [
        // Admin Outlet Selector Bar
        _buildOutletSelectorBar(onOutletChanged: () {
          _loadItems();
          _loadMultiProducts();
        }),

        // Informational Banner (Keterangan Sumber Produk Multi Aktif)
        Container(
          width: double.infinity,
          margin: const EdgeInsets.fromLTRB(12, 8, 12, 4),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: _selectedOutletId == null ? const Color(0xFFEFF6FF) : const Color(0xFFECFDF5),
            borderRadius: BorderRadius.circular(8),
            border: Border.all(
              color: _selectedOutletId == null ? const Color(0xFFBFDBFE) : const Color(0xFFA7F3D0),
            ),
          ),
          child: Row(
            children: [
              Icon(
                _selectedOutletId == null ? Icons.public : Icons.store,
                size: 16,
                color: _selectedOutletId == null ? const Color(0xFF1D4ED8) : const Color(0xFF047857),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  _selectedOutletId == null
                      ? 'MODE: PRODUK MULTI GLOBAL (SEMUA TOKO) — Menampilkan seluruh produk digital.'
                      : 'MODE: PRODUK MULTI CABANG — ${_selectedOutletName.toUpperCase()} — Menampilkan produk multi toko ini & global.',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                    color: _selectedOutletId == null ? const Color(0xFF1E40AF) : const Color(0xFF065F46),
                  ),
                ),
              ),
            ],
          ),
        ),

        // Search & Filter Header
        Container(
          padding: const EdgeInsets.all(12),
          color: Colors.white,
          child: Column(
            children: [
              TextField(
                controller: _searchMultiController,
                onChanged: (_) => _applyMultiFilter(),
                decoration: InputDecoration(
                  hintText: 'Cari Kode atau Nama Produk Multi...',
                  prefixIcon: const Icon(Icons.search, size: 20),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  suffixIcon: _searchMultiController.text.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear, size: 18),
                          onPressed: () {
                            _searchMultiController.clear();
                            _applyMultiFilter();
                          },
                        )
                      : null,
                ),
              ),
              if (_multiTrxTypes.isNotEmpty) ...[
                const SizedBox(height: 8),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      _buildChip('ALL', 'SEMUA JENIS', _selectedMultiTrxType, (t) {
                        setState(() => _selectedMultiTrxType = t);
                        _applyMultiFilter();
                      }),
                      ..._multiTrxTypes.map((t) => _buildChip(t.toString(), t.toString(), _selectedMultiTrxType, (type) {
                        setState(() => _selectedMultiTrxType = type);
                        _applyMultiFilter();
                      })),
                    ],
                  ),
                ),
              ]
            ],
          ),
        ),

        // Summary Bar
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          color: Colors.grey.shade100,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Menampilkan: ${_filteredMultiProducts.length} Produk Multi', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ThemeConfig.textDark)),
              const Text('Geser ke kanan untuk melihat tabel lengkap', style: TextStyle(fontSize: 10, color: Colors.grey)),
            ],
          ),
        ),

        // Data Table Exactly Matching Screenshot
        Expanded(
          child: _isLoadingMulti
              ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
              : _filteredMultiProducts.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.category_outlined, size: 48, color: Colors.grey.shade400),
                          const SizedBox(height: 8),
                          const Text('Tidak ada produk multi ditemukan', style: TextStyle(color: ThemeConfig.textMuted, fontSize: 13)),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: () async => _loadMultiProducts(),
                      child: SingleChildScrollView(
                        scrollDirection: Axis.vertical,
                        child: SingleChildScrollView(
                          scrollDirection: Axis.horizontal,
                          child: DataTable(
                            headingRowColor: MaterialStateProperty.all(const Color(0xFF0F3D24)),
                            headingTextStyle: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 11),
                            dataRowHeight: 48,
                            columns: const [
                              DataColumn(label: Text('NO')),
                              DataColumn(label: Text('KODE PRODUK')),
                              DataColumn(label: Text('NAMA PRODUK')),
                              DataColumn(label: Text('CABANG / TOKO')),
                              DataColumn(label: Text('JENIS TRX')),
                              DataColumn(label: Text('KATEGORI')),
                              DataColumn(label: Text('HPP (MODAL SERVER)')),
                              DataColumn(label: Text('HARGA JUAL')),
                              DataColumn(label: Text('MARGIN LABA')),
                              DataColumn(label: Text('STATUS')),
                              DataColumn(label: Text('AKSI')),
                            ],
                            rows: List<DataRow>.generate(_filteredMultiProducts.length, (idx) {
                              final p = _filteredMultiProducts[idx];
                              final hpp = Formatters.parseDouble(p['hpp']);
                              final selling = Formatters.parseDouble(p['selling_price']);
                              final margin = selling - hpp;
                              final isOpen = (p['status'] ?? 'OPEN') == 'OPEN';

                              return DataRow(
                                color: MaterialStateProperty.resolveWith<Color?>((states) => idx % 2 == 0 ? Colors.white : const Color(0xFFF8FAFC)),
                                cells: [
                                  DataCell(Text('${idx + 1}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11))),
                                  DataCell(Text(p['product_code'] ?? '-', style: const TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.bold, color: ThemeConfig.primary, fontSize: 11))),
                                  DataCell(
                                    SizedBox(
                                      width: 200,
                                      child: Text(p['name'] ?? '-', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11), overflow: TextOverflow.ellipsis),
                                    ),
                                  ),
                                  DataCell(
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: p['outlet_id'] == null ? const Color(0xFFF1F5F9) : const Color(0xFFECFDF5),
                                        borderRadius: BorderRadius.circular(4),
                                        border: Border.all(
                                          color: p['outlet_id'] == null ? const Color(0xFFCBD5E1) : const Color(0xFFA7F3D0),
                                        ),
                                      ),
                                      child: Text(
                                        p['outlet'] != null ? (p['outlet']['name'] ?? 'Cabang') : (p['outlet_id'] == null ? 'Semua Cabang' : 'Toko #${p['outlet_id']}'),
                                        style: TextStyle(
                                          fontSize: 10,
                                          fontWeight: FontWeight.bold,
                                          color: p['outlet_id'] == null ? const Color(0xFF475569) : const Color(0xFF065F46),
                                        ),
                                      ),
                                    ),
                                  ),
                                  DataCell(
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                      decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(4)),
                                      child: Text(p['trx_type'] ?? '-', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: ThemeConfig.textDark)),
                                    ),
                                  ),
                                  DataCell(
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(4), border: Border.all(color: Colors.grey.shade300)),
                                      child: Text(p['category'] ?? '-', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.blueGrey.shade700)),
                                    ),
                                  ),
                                  DataCell(Text(Formatters.formatRupiah(hpp), style: const TextStyle(fontFamily: 'monospace', fontSize: 11))),
                                  DataCell(Text(Formatters.formatRupiah(selling), style: const TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.bold, fontSize: 11))),
                                  DataCell(
                                    Text(
                                      '+${Formatters.formatRupiah(margin)}',
                                      style: const TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.w900, color: Colors.green, fontSize: 11),
                                    ),
                                  ),
                                  DataCell(
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                      decoration: BoxDecoration(
                                        color: isOpen ? Colors.green.shade100 : Colors.red.shade100,
                                        borderRadius: BorderRadius.circular(20),
                                        border: Border.all(color: isOpen ? Colors.green.shade400 : Colors.red.shade400),
                                      ),
                                      child: Text(
                                        isOpen ? 'OPEN' : 'CLOSE',
                                        style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: isOpen ? Colors.green.shade900 : Colors.red.shade900),
                                      ),
                                    ),
                                  ),
                                  DataCell(
                                    Row(
                                      children: [
                                        IconButton(
                                          icon: const Icon(Icons.edit, size: 18, color: Colors.blue),
                                          onPressed: () => _showMultiFormModal(product: p),
                                          tooltip: 'Edit Produk Multi',
                                        ),
                                        IconButton(
                                          icon: const Icon(Icons.delete, size: 18, color: Colors.red),
                                          onPressed: () => _confirmDeleteMulti(p),
                                          tooltip: 'Hapus Produk Multi',
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              );
                            }),
                          ),
                        ),
                      ),
                    ),
        ),
      ],
    );
  }

  Widget _buildChip(String key, String label, String currentSelected, Function(String) onSelect) {
    final isSelected = currentSelected == key;
    return Padding(
      padding: const EdgeInsets.only(right: 6),
      child: ChoiceChip(
        label: Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: isSelected ? Colors.white : Colors.grey.shade700)),
        selected: isSelected,
        selectedColor: ThemeConfig.primary,
        backgroundColor: Colors.grey.shade100,
        onSelected: (_) => onSelect(key),
      ),
    );
  }
}
