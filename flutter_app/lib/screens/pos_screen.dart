import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/theme_config.dart';

class PosScreen extends StatefulWidget {
  final String saleType; // 'retail' or 'grosir'

  const PosScreen({super.key, required this.saleType});

  @override
  State<PosScreen> createState() => _PosScreenState();
}

class _PosScreenState extends State<PosScreen> {
  List<dynamic> _products = [];
  List<dynamic> _filteredProducts = [];
  List<dynamic> _customers = [];
  List<dynamic> _accounts = [];
  bool _isLoading = true;

  // Cart: Map of productId -> {product, qty, price}
  final Map<int, Map<String, dynamic>> _cart = {};

  final _searchController = TextEditingController();
  final _paidController = TextEditingController();
  final _discountController = TextEditingController(text: '0');
  final _notesController = TextEditingController();

  int? _selectedCustomerId;
  int? _selectedAccountId;
  String _paymentMethod = 'cash';
  bool _isCheckingOut = false;

  final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  @override
  void initState() {
    super.initState();
    _loadPosData();
  }

  void _loadPosData() async {
    setState(() => _isLoading = true);
    try {
      final res = await ApiService.getPosData();
      if (mounted && res['success'] == true) {
        setState(() {
          _products = res['products'] ?? [];
          _filteredProducts = _products;
          _customers = res['customers'] ?? [];
          _accounts = res['accounts'] ?? [];
          if (_accounts.isNotEmpty) {
            _selectedAccountId = _accounts[0]['id'];
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _filterProducts(String query) {
    if (query.isEmpty) {
      setState(() => _filteredProducts = _products);
      return;
    }
    final q = query.toLowerCase();
    setState(() {
      _filteredProducts = _products.where((p) {
        final name = (p['name'] ?? '').toString().toLowerCase();
        final code = (p['code'] ?? '').toString().toLowerCase();
        final barcode = (p['barcode'] ?? '').toString().toLowerCase();
        return name.contains(q) || code.contains(q) || barcode.contains(q);
      }).toList();
    });
  }

  void _addToCart(dynamic product) {
    final int id = product['id'];
    final double price = widget.saleType == 'grosir'
        ? (double.tryParse((product['selling_price_grosir'] ?? product['selling_price']).toString()) ?? 0)
        : (double.tryParse((product['selling_price'] ?? 0).toString()) ?? 0);

    setState(() {
      if (_cart.containsKey(id)) {
        _cart[id]!['qty'] += 1;
      } else {
        _cart[id] = {
          'product': product,
          'qty': 1.0,
          'price': price,
        };
      }
    });
  }

  void _updateCartQty(int id, double delta) {
    setState(() {
      if (!_cart.containsKey(id)) return;
      final newQty = _cart[id]!['qty'] + delta;
      if (newQty <= 0) {
        _cart.remove(id);
      } else {
        _cart[id]!['qty'] = newQty;
      }
    });
  }

  double get _subtotal {
    double sum = 0;
    _cart.forEach((_, item) {
      sum += (item['qty'] as double) * (item['price'] as double);
    });
    return sum;
  }

  double get _discount {
    return double.tryParse(_discountController.text.replaceAll('.', '').replaceAll(',', '')) ?? 0;
  }

  double get _grandTotal {
    final total = _subtotal - _discount;
    return total > 0 ? total : 0;
  }

  void _handleCheckout() async {
    if (_cart.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Keranjang belanja masih kosong!'), backgroundColor: Colors.red),
      );
      return;
    }

    final double paid = double.tryParse(_paidController.text.replaceAll('.', '').replaceAll(',', '')) ?? 0;
    if (_paymentMethod == 'cash' && paid < _grandTotal) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nominal pembayaran kurang!'), backgroundColor: Colors.red),
      );
      return;
    }

    setState(() => _isCheckingOut = true);

    final items = _cart.entries.map((entry) {
      return {
        'product_id': entry.key,
        'qty': entry.value['qty'],
        'price': entry.value['price'],
      };
    }).toList();

    try {
      final res = await ApiService.checkoutPos(
        saleType: widget.saleType,
        customerId: _selectedCustomerId,
        discount: _discount,
        paidAmount: paid > 0 ? paid : _grandTotal,
        paymentMethod: _paymentMethod,
        accountId: _selectedAccountId,
        notes: _notesController.text.trim(),
        items: items,
      );

      setState(() => _isCheckingOut = false);

      if (res['success'] == true) {
        final change = (paid > _grandTotal) ? (paid - _grandTotal) : 0;

        NotificationService.showNotification(
          id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
          title: '✅ Transaksi POS Selesai!',
          body: 'Penjualan senilai ${currencyFormatter.format(_grandTotal)} berhasil disimpan!',
        );

        if (!mounted) return;
        _showSuccessDialog(change.toDouble());
        setState(() {
          _cart.clear();
          _paidController.clear();
          _discountController.text = '0';
          _notesController.clear();
        });
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res['message'] ?? 'Gagal memproses transaksi'), backgroundColor: Colors.red),
        );
      }
    } catch (e) {
      setState(() => _isCheckingOut = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Koneksi gagal: $e'), backgroundColor: Colors.red),
      );
    }
  }

  void _showSuccessDialog(double change) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.check_circle, color: Colors.green, size: 64),
              const SizedBox(height: 14),
              const Text('Transaksi Sukses!', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              Text('Total: ${currencyFormatter.format(_grandTotal)}', style: const TextStyle(fontSize: 14)),
              if (change > 0) ...[
                const SizedBox(height: 6),
                Text('Kembalian: ${currencyFormatter.format(change)}',
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.green)),
              ],
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => Navigator.pop(context),
                  style: ElevatedButton.styleFrom(backgroundColor: ThemeConfig.primary),
                  child: const Text('Selesai / Transaksi Baru'),
                ),
              )
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final title = widget.saleType == 'grosir' ? 'Kasir Grosir' : 'Kasir Retail (Eceran)';

    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        title: Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: ThemeConfig.primary,
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadPosData),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
          : Column(
              children: [
                // Live Product Search Bar
                Container(
                  padding: const EdgeInsets.all(12),
                  color: Colors.white,
                  child: TextField(
                    controller: _searchController,
                    onChanged: _filterProducts,
                    decoration: InputDecoration(
                      hintText: 'Cari nama barang / barcode...',
                      prefixIcon: const Icon(Icons.search),
                      suffixIcon: _searchController.text.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear),
                              onPressed: () {
                                _searchController.clear();
                                _filterProducts('');
                              },
                            )
                          : null,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    ),
                  ),
                ),

                // Main Content: Product Catalog List
                Expanded(
                  child: _filteredProducts.isEmpty
                      ? const Center(child: Text('Barang tidak ditemukan'))
                      : ListView.separated(
                          padding: const EdgeInsets.all(12),
                          itemCount: _filteredProducts.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 8),
                          itemBuilder: (context, index) {
                            final p = _filteredProducts[index];
                            final int id = p['id'];
                            final String name = p['name'] ?? '-';
                            final String code = p['code'] ?? '';
                            final double stock = (p['stock'] ?? 0).toDouble();
                            final double price = widget.saleType == 'grosir'
                                ? (double.tryParse((p['selling_price_grosir'] ?? p['selling_price']).toString()) ?? 0)
                                : (double.tryParse((p['selling_price'] ?? 0).toString()) ?? 0);

                            final int inCartQty = (_cart[id]?['qty'] ?? 0).toInt();

                            return Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(
                                  color: inCartQty > 0 ? ThemeConfig.accent : Colors.grey.shade200,
                                  width: inCartQty > 0 ? 1.5 : 1,
                                ),
                              ),
                              child: Row(
                                children: [
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          name,
                                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                        const SizedBox(height: 2),
                                        Text(
                                          'Kode: $code • Stok: $stock',
                                          style: TextStyle(
                                            fontSize: 11,
                                            color: stock <= 3 ? Colors.red : Colors.grey.shade600,
                                            fontWeight: stock <= 3 ? FontWeight.bold : FontWeight.normal,
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          currencyFormatter.format(price),
                                          style: const TextStyle(
                                            fontWeight: FontWeight.bold,
                                            fontSize: 13,
                                            color: ThemeConfig.primary,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  if (inCartQty > 0) ...[
                                    IconButton(
                                      icon: const Icon(Icons.remove_circle_outline, color: Colors.red),
                                      onPressed: () => _updateCartQty(id, -1),
                                    ),
                                    Text('$inCartQty', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                                    IconButton(
                                      icon: const Icon(Icons.add_circle_outline, color: Colors.green),
                                      onPressed: () => _updateCartQty(id, 1),
                                    ),
                                  ] else
                                    ElevatedButton(
                                      onPressed: () => _addToCart(p),
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: ThemeConfig.primary,
                                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                                      ),
                                      child: const Text('+ Tambah', style: TextStyle(fontSize: 12)),
                                    ),
                                ],
                              ),
                            );
                          },
                        ),
                ),

                // Bottom Cart Summary Bar
                if (_cart.isNotEmpty)
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
                      boxShadow: [
                        BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 10, offset: const Offset(0, -4)),
                      ],
                    ),
                    child: SafeArea(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text('${_cart.length} Jenis Item', style: const TextStyle(color: Colors.grey, fontSize: 12)),
                              Text(
                                currencyFormatter.format(_grandTotal),
                                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: ThemeConfig.primary),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Expanded(
                                child: TextField(
                                  controller: _paidController,
                                  keyboardType: TextInputType.number,
                                  decoration: const InputDecoration(
                                    hintText: 'Uang Bayar',
                                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 10),
                              ElevatedButton(
                                onPressed: _isCheckingOut ? null : _handleCheckout,
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: ThemeConfig.accent,
                                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                                ),
                                child: _isCheckingOut
                                    ? const SizedBox(
                                        height: 18,
                                        width: 18,
                                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                                      )
                                    : const Row(
                                        children: [
                                          Icon(Icons.payment, size: 18),
                                          SizedBox(width: 6),
                                          Text('Bayar', style: TextStyle(fontWeight: FontWeight.bold)),
                                        ],
                                      ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
    );
  }
}
