import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/formatters.dart';
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
  List<String> _types = ['ALL'];
  String _selectedType = 'ALL';
  bool _isLoading = true;
  String? _errorMessage;

  // Cart: Map of productId -> {product, qty, price}
  final Map<int, Map<String, dynamic>> _cart = {};

  final _searchController = TextEditingController();
  final _paidController = TextEditingController();
  final _discountController = TextEditingController(text: '0');
  final _notesController = TextEditingController();

  int? _selectedCustomerId;
  int? _selectedAccountId;
  String _paymentMethod = 'cash'; // 'cash', 'piutang', 'transfer'
  bool _isCheckingOut = false;

  @override
  void initState() {
    super.initState();
    _loadPosData();
  }

  void _loadPosData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiService.getPosData();
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _products = res['products'] ?? [];
            _customers = res['customers'] ?? [];
            _accounts = res['accounts'] ?? [];

            // Extract unique types for category pills
            final Set<String> typesSet = {'ALL'};
            for (var p in _products) {
              final t = (p['type'] ?? '').toString().trim().toUpperCase();
              if (t.isNotEmpty) typesSet.add(t);
            }
            _types = typesSet.toList();

            _filterProducts(_searchController.text);

            if (_customers.isNotEmpty && _selectedCustomerId == null) {
              final umum = _customers.firstWhere(
                (c) => (c['name'] ?? '').toString().toUpperCase() == 'UMUM',
                orElse: () => _customers[0],
              );
              _selectedCustomerId = umum['id'];
            }

            if (_accounts.isNotEmpty && _selectedAccountId == null) {
              _selectedAccountId = _accounts[0]['id'];
            }
          } else {
            _errorMessage = res['message'] ?? 'Gagal memuat katalog barang';
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = 'Koneksi gagal: $e';
          _isLoading = false;
        });
      }
    }
  }

  void _filterProducts(String query) {
    final q = query.toLowerCase();
    setState(() {
      _filteredProducts = _products.where((p) {
        final name = (p['name'] ?? '').toString().toLowerCase();
        final code = (p['item_code'] ?? p['code'] ?? '').toString().toLowerCase();
        final brand = (p['brand'] ?? '').toString().toLowerCase();
        final type = (p['type'] ?? '').toString().toUpperCase();

        final matchesSearch = q.isEmpty || name.contains(q) || code.contains(q) || brand.contains(q);
        final matchesType = _selectedType == 'ALL' || type == _selectedType;

        return matchesSearch && matchesType;
      }).toList();
    });
  }

  void _selectType(String type) {
    setState(() {
      _selectedType = type;
      _filterProducts(_searchController.text);
    });
  }

  void _addToCart(dynamic product) {
    final int id = Formatters.parseInt(product['id']);
    if (id <= 0) return;

    final double price = widget.saleType == 'grosir'
        ? Formatters.parseDouble(product['wholesale_price'] ?? product['selling_price_grosir'] ?? product['retail_price'] ?? product['selling_price'])
        : Formatters.parseDouble(product['retail_price'] ?? product['selling_price']);

    setState(() {
      if (_cart.containsKey(id)) {
        _cart[id]!['qty'] = (_cart[id]!['qty'] as double) + 1.0;
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
      final currentQty = _cart[id]!['qty'] as double;
      final newQty = currentQty + delta;
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
      final qty = Formatters.parseDouble(item['qty']);
      final price = Formatters.parseDouble(item['price']);
      sum += qty * price;
    });
    return sum;
  }

  double get _discount {
    return Formatters.parseDouble(_discountController.text);
  }

  double get _grandTotal {
    final total = _subtotal - _discount;
    return total > 0 ? total : 0;
  }

  void _setQuickCash(double amount) {
    setState(() {
      _paidController.text = amount.toStringAsFixed(0);
    });
  }

  void _showCheckoutSheet() {
    if (_cart.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Keranjang kasir masih kosong!'), backgroundColor: Colors.red),
      );
      return;
    }

    if (_paidController.text.isEmpty || Formatters.parseDouble(_paidController.text) == 0) {
      _paidController.text = _grandTotal.toStringAsFixed(0);
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setSheetState) {
          final double paid = Formatters.parseDouble(_paidController.text);
          final double change = (paid > _grandTotal) ? (paid - _grandTotal) : 0;
          final double remaining = (_grandTotal > paid) ? (_grandTotal - paid) : 0;

          return Container(
            padding: EdgeInsets.only(
              top: 20,
              left: 20,
              right: 20,
              bottom: MediaQuery.of(context).viewInsets.bottom + 20,
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
                        widget.saleType == 'grosir' ? 'Pembayaran Kasir Grosir' : 'Pembayaran Kasir Retail',
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                      ),
                      IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                    ],
                  ),
                  const Divider(height: 16),

                  // Customer & Account Selection
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Pelanggan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                            const SizedBox(height: 4),
                            DropdownButtonFormField<int>(
                              value: _selectedCustomerId,
                              isDense: true,
                              decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8)),
                              items: _customers.map<DropdownMenuItem<int>>((c) {
                                return DropdownMenuItem<int>(
                                  value: c['id'],
                                  child: Text(c['name'] ?? 'Pelanggan', style: const TextStyle(fontSize: 12)),
                                );
                              }).toList(),
                              onChanged: (val) => setSheetState(() => _selectedCustomerId = val),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Masuk ke Kas/Bank', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                            const SizedBox(height: 4),
                            DropdownButtonFormField<int>(
                              value: _selectedAccountId,
                              isDense: true,
                              decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8)),
                              items: _accounts.map<DropdownMenuItem<int>>((a) {
                                return DropdownMenuItem<int>(
                                  value: a['id'],
                                  child: Text(a['name'] ?? 'Kas', style: const TextStyle(fontSize: 11), overflow: TextOverflow.ellipsis),
                                );
                              }).toList(),
                              onChanged: (val) => setSheetState(() => _selectedAccountId = val),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Method Selection (Tunai / Piutang / Transfer)
                  Row(
                    children: [
                      _buildMethodButton('Tunai', 'cash', setSheetState),
                      const SizedBox(width: 6),
                      _buildMethodButton('Piutang (Tempo)', 'piutang', setSheetState),
                      const SizedBox(width: 6),
                      _buildMethodButton('Transfer Bank', 'transfer', setSheetState),
                    ],
                  ),
                  const SizedBox(height: 14),

                  // Total Belanja Box
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.green.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.green.shade200),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('TOTAL TAGIHAN:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: ThemeConfig.primary)),
                        Text(
                          Formatters.formatRupiah(_grandTotal),
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: ThemeConfig.primary),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Quick Cash Preset Chips
                  const Text('Nominal Cepat (Quick Cash):', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 11, color: Colors.grey)),
                  const SizedBox(height: 6),
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        _buildQuickCashChip('Uang Pas', _grandTotal, setSheetState),
                        _buildQuickCashChip('10rb', 10000, setSheetState),
                        _buildQuickCashChip('20rb', 20000, setSheetState),
                        _buildQuickCashChip('50rb', 50000, setSheetState),
                        _buildQuickCashChip('100rb', 100000, setSheetState),
                        _buildQuickCashChip('200rb', 200000, setSheetState),
                        _buildQuickCashChip('500rb', 500000, setSheetState),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Uang Diterima Input
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Uang Diterima (Rp)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                            const SizedBox(height: 4),
                            TextField(
                              controller: _paidController,
                              keyboardType: TextInputType.number,
                              onChanged: (_) => setSheetState(() {}),
                              decoration: const InputDecoration(
                                hintText: '0',
                                contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
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
                            Text(
                              _paymentMethod == 'piutang' ? 'Sisa Piutang' : 'Kembalian',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                            ),
                            const SizedBox(height: 4),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                              decoration: BoxDecoration(
                                color: Colors.grey.shade100,
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(color: Colors.grey.shade300),
                              ),
                              child: Text(
                                _paymentMethod == 'piutang'
                                    ? Formatters.formatRupiah(remaining)
                                    : Formatters.formatRupiah(change),
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 14,
                                  color: _paymentMethod == 'piutang' ? Colors.red.shade700 : Colors.green.shade800,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Diskon & Catatan
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Diskon Potongan (Rp)', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600)),
                            const SizedBox(height: 4),
                            TextField(
                              controller: _discountController,
                              keyboardType: TextInputType.number,
                              onChanged: (_) {
                                setState(() {});
                                setSheetState(() {});
                              },
                              decoration: const InputDecoration(
                                hintText: '0',
                                contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8),
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
                            const Text('Catatan / Keterangan', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600)),
                            const SizedBox(height: 4),
                            TextField(
                              controller: _notesController,
                              decoration: const InputDecoration(
                                hintText: 'Opsional',
                                contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 18),

                  // Submit Checkout Button
                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: ElevatedButton.icon(
                      onPressed: _isCheckingOut
                          ? null
                          : () {
                              Navigator.pop(ctx);
                              _handleCheckout();
                            },
                      icon: const Icon(Icons.print, size: 20),
                      label: const Text('PROSES & SIMPAN NOTA (F9)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                      style: ElevatedButton.styleFrom(backgroundColor: ThemeConfig.accent),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildMethodButton(String label, String value, StateSetter setSheetState) {
    final isSelected = _paymentMethod == value;
    return Expanded(
      child: InkWell(
        onTap: () {
          setSheetState(() => _paymentMethod = value);
          setState(() => _paymentMethod = value);
        },
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 8),
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: isSelected ? ThemeConfig.primary : Colors.grey.shade100,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: isSelected ? ThemeConfig.primary : Colors.grey.shade300),
          ),
          child: Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: isSelected ? Colors.white : Colors.grey.shade700,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildQuickCashChip(String label, double amount, StateSetter setSheetState) {
    return Padding(
      padding: const EdgeInsets.only(right: 6),
      child: ActionChip(
        label: Text(label, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.grey.shade100,
        onPressed: () {
          _setQuickCash(amount);
          setSheetState(() {});
        },
      ),
    );
  }

  void _handleCheckout() async {
    if (_cart.isEmpty) return;

    final double paid = Formatters.parseDouble(_paidController.text);
    if (_paymentMethod == 'cash' && paid < _grandTotal) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nominal uang bayar masih kurang!'), backgroundColor: Colors.red),
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
        final saleData = res['sale'];

        NotificationService.showNotification(
          id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
          title: '✅ Transaksi Kasir Sukses! 🧾',
          body: 'Penjualan ${saleData?['invoice_number'] ?? ''} senilai ${Formatters.formatRupiah(_grandTotal)} berhasil disimpan!',
        );

        if (!mounted) return;
        _showSuccessDialog(change.toDouble(), saleData);
        setState(() {
          _cart.clear();
          _paidController.clear();
          _discountController.text = '0';
          _notesController.clear();
        });
        _loadPosData();
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

  void _openReceiptUrl(String path) async {
    final uri = Uri.parse('https://pos.moonbyte.my.id$path');
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      } else {
        await launchUrl(uri, mode: LaunchMode.platformDefault);
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Tidak dapat membuka nota: $e'), backgroundColor: Colors.red),
      );
    }
  }

  void _showThermalReceiptModal(dynamic saleData, double change, List<Map<String, dynamic>> itemsPurchased, double grandTotal) {
    final inv = saleData?['invoice_number'] ?? 'PR-NOTA';
    final date = DateTime.now();
    final formattedDate = "${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')} ${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}";

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: const EdgeInsets.all(20),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Preview Struk Thermal', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                ],
              ),
              const Divider(height: 12),
              // Thermal Paper Preview Box
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFFAF9F6),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: Colors.grey.shade300),
                  boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 6)],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    const Text('ELEPHANT CELL POS', style: TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.bold, fontSize: 15)),
                    const Text('REKAP & KASIR PENJUALAN', style: TextStyle(fontFamily: 'monospace', fontSize: 11)),
                    const Text('================================', style: TextStyle(fontFamily: 'monospace', fontSize: 11)),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('No: $inv', style: const TextStyle(fontFamily: 'monospace', fontSize: 10, fontWeight: FontWeight.bold)),
                        Text(formattedDate, style: const TextStyle(fontFamily: 'monospace', fontSize: 10)),
                      ],
                    ),
                    const Text('--------------------------------', style: TextStyle(fontFamily: 'monospace', fontSize: 11)),
                    ...itemsPurchased.map((it) {
                      final name = it['product']?['name'] ?? 'Item';
                      final qty = it['qty'] ?? 1;
                      final price = Formatters.parseDouble(it['price']);
                      final total = qty * price;
                      return Padding(
                        padding: const EdgeInsets.symmetric(vertical: 2),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(name, style: const TextStyle(fontFamily: 'monospace', fontSize: 11, fontWeight: FontWeight.bold)),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text('$qty x ${Formatters.formatRupiah(price)}', style: const TextStyle(fontFamily: 'monospace', fontSize: 10)),
                                Text(Formatters.formatRupiah(total), style: const TextStyle(fontFamily: 'monospace', fontSize: 10, fontWeight: FontWeight.bold)),
                              ],
                            ),
                          ],
                        ),
                      );
                    }),
                    const Text('--------------------------------', style: TextStyle(fontFamily: 'monospace', fontSize: 11)),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('TOTAL TAGIHAN:', style: TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.bold, fontSize: 11)),
                        Text(Formatters.formatRupiah(grandTotal), style: const TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.bold, fontSize: 12)),
                      ],
                    ),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Uang Diterima:', style: TextStyle(fontFamily: 'monospace', fontSize: 10)),
                        Text(Formatters.formatRupiah(grandTotal + change), style: const TextStyle(fontFamily: 'monospace', fontSize: 10)),
                      ],
                    ),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Kembalian:', style: TextStyle(fontFamily: 'monospace', fontSize: 10, fontWeight: FontWeight.bold)),
                        Text(Formatters.formatRupiah(change), style: const TextStyle(fontFamily: 'monospace', fontSize: 11, fontWeight: FontWeight.bold, color: Colors.green)),
                      ],
                    ),
                    const Text('================================', style: TextStyle(fontFamily: 'monospace', fontSize: 11)),
                    const Text('Terima Kasih Atas Kunjungan Anda!', style: TextStyle(fontFamily: 'monospace', fontSize: 10)),
                    const Text('Barang yang dibeli tidak dapat ditukar', style: TextStyle(fontFamily: 'monospace', fontSize: 9, color: Colors.grey)),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () {
                        Navigator.pop(ctx);
                        if (saleData?['id'] != null) {
                          _openReceiptUrl('/receipt/thermal/${saleData['id']}');
                        }
                      },
                      icon: const Icon(Icons.print, size: 16),
                      label: const Text('Cetak / Buka Web'),
                      style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 12)),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () {
                        Navigator.pop(ctx);
                        if (saleData?['id'] != null) {
                          _openReceiptUrl('/receipt/invoice/${saleData['id']}');
                        }
                      },
                      icon: const Icon(Icons.picture_as_pdf, size: 16),
                      label: const Text('Faktur A4'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: ThemeConfig.accent,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showSuccessDialog(double change, dynamic saleData) {
    final saleId = saleData?['id'];
    final itemsPurchased = _cart.values.toList();
    final grandTotal = _grandTotal;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.check_circle, color: Colors.green, size: 56),
              const SizedBox(height: 10),
              const Text('Transaksi Berhasil!', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 4),
              Text(
                'Nota: ${saleData?['invoice_number'] ?? 'PR-SUCCESS'}',
                style: const TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.bold, color: ThemeConfig.primary, fontSize: 13),
              ),
              const Divider(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Total Belanja:', style: TextStyle(fontSize: 13, color: Colors.grey)),
                  Text(Formatters.formatRupiah(grandTotal), style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                ],
              ),
              const SizedBox(height: 4),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Kembalian:', style: TextStyle(fontSize: 13, color: Colors.grey)),
                  Text(Formatters.formatRupiah(change), style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w900, color: Colors.green)),
                ],
              ),
              const SizedBox(height: 16),
              // Action Buttons: Print Thermal & Invoice
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () {
                        _showThermalReceiptModal(saleData, change, itemsPurchased, grandTotal);
                      },
                      icon: const Icon(Icons.receipt_long, size: 16, color: ThemeConfig.primary),
                      label: const Text('Struk Thermal', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: ThemeConfig.primary)),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 10),
                        side: BorderSide(color: ThemeConfig.primary.withOpacity(0.5)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () {
                        if (saleId != null) {
                          _openReceiptUrl('/receipt/invoice/$saleId');
                        }
                      },
                      icon: const Icon(Icons.print, size: 16, color: Colors.white),
                      label: const Text('Faktur A4', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: ThemeConfig.accent,
                        padding: const EdgeInsets.symmetric(vertical: 10),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
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
    final title = widget.saleType == 'grosir' ? 'Kasir Penjualan Grosir' : 'Kasir Penjualan Retail (Eceran)';

    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        title: Text(title, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
        backgroundColor: ThemeConfig.primary,
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadPosData),
        ],
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
                      ElevatedButton(onPressed: _loadPosData, child: const Text('Coba Lagi')),
                    ],
                  ),
                )
              : Column(
                  children: [
                    // Search & Category Filter Section
                    Container(
                      padding: const EdgeInsets.all(12),
                      color: Colors.white,
                      child: Column(
                        children: [
                          TextField(
                            controller: _searchController,
                            onChanged: _filterProducts,
                            decoration: InputDecoration(
                              hintText: 'Cari nama barang, kode atau barcode...',
                              prefixIcon: const Icon(Icons.search, size: 20),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                              suffixIcon: _searchController.text.isNotEmpty
                                  ? IconButton(
                                      icon: const Icon(Icons.clear, size: 18),
                                      onPressed: () {
                                        _searchController.clear();
                                        _filterProducts('');
                                      },
                                    )
                                  : null,
                            ),
                          ),
                          if (_types.length > 1) ...[
                            const SizedBox(height: 8),
                            SingleChildScrollView(
                              scrollDirection: Axis.horizontal,
                              child: Row(
                                children: _types.map((type) {
                                  final isSelected = _selectedType == type;
                                  return Padding(
                                    padding: const EdgeInsets.only(right: 6),
                                    child: ChoiceChip(
                                      label: Text(
                                        type == 'ALL' ? 'SEMUA' : type,
                                        style: TextStyle(
                                          fontSize: 11,
                                          fontWeight: FontWeight.bold,
                                          color: isSelected ? Colors.white : Colors.grey.shade700,
                                        ),
                                      ),
                                      selected: isSelected,
                                      selectedColor: ThemeConfig.primary,
                                      backgroundColor: Colors.grey.shade100,
                                      onSelected: (_) => _selectType(type),
                                    ),
                                  );
                                }).toList(),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),

                    // Product Catalog List
                    Expanded(
                      child: _filteredProducts.isEmpty
                          ? Center(
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(Icons.search_off, size: 48, color: Colors.grey.shade400),
                                  const SizedBox(height: 8),
                                  const Text('Tidak ada produk sesuai filter', style: TextStyle(color: Colors.grey)),
                                ],
                              ),
                            )
                          : ListView.separated(
                              padding: const EdgeInsets.all(12),
                              itemCount: _filteredProducts.length,
                              separatorBuilder: (_, __) => const SizedBox(height: 8),
                              itemBuilder: (context, index) {
                                final p = _filteredProducts[index];
                                final int id = Formatters.parseInt(p['id']);
                                final String name = p['name'] ?? '-';
                                final String code = p['item_code'] ?? p['code'] ?? '';
                                final double stock = Formatters.parseDouble(p['stock']);
                                final double price = widget.saleType == 'grosir'
                                    ? Formatters.parseDouble(p['wholesale_price'] ?? p['selling_price_grosir'] ?? p['retail_price'] ?? p['selling_price'])
                                    : Formatters.parseDouble(p['retail_price'] ?? p['selling_price']);

                                final int inCartQty = _cart.containsKey(id)
                                    ? Formatters.parseInt(_cart[id]!['qty'])
                                    : 0;

                                return Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    color: Colors.white,
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(
                                      color: inCartQty > 0 ? ThemeConfig.accent : Colors.grey.shade200,
                                      width: inCartQty > 0 ? 1.5 : 1,
                                    ),
                                    boxShadow: [
                                      BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 4, offset: const Offset(0, 2))
                                    ],
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
                                              'Kode: $code • Stok: ${stock.toStringAsFixed(0)}',
                                              style: TextStyle(
                                                fontSize: 11,
                                                color: stock <= 3 ? Colors.red : Colors.grey.shade600,
                                                fontWeight: stock <= 3 ? FontWeight.bold : FontWeight.normal,
                                              ),
                                            ),
                                            const SizedBox(height: 4),
                                            Text(
                                              Formatters.formatRupiah(price),
                                              style: const TextStyle(
                                                fontWeight: FontWeight.w900,
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

                    // Bottom Cart Floating Action Bar
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
                          child: Row(
                            children: [
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text('${_cart.length} Item terpilih', style: const TextStyle(color: Colors.grey, fontSize: 11)),
                                  Text(
                                    Formatters.formatRupiah(_grandTotal),
                                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: ThemeConfig.primary),
                                  ),
                                ],
                              ),
                              const Spacer(),
                              ElevatedButton.icon(
                                onPressed: _showCheckoutSheet,
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: ThemeConfig.accent,
                                  padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                                ),
                                icon: const Icon(Icons.shopping_cart_checkout),
                                label: const Text('BAYAR (F9)', style: TextStyle(fontWeight: FontWeight.bold)),
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

