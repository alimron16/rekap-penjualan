import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../services/printer_service.dart';
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
  Map<String, dynamic>? _storeSetting;
  Map<String, dynamic>? _outlet;

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

  // Outlet Scoping
  List<dynamic> _outlets = [];
  int? _selectedOutletId;
  String _selectedOutletName = 'Cabang';
  bool _canFilterOutlet = false;

  @override
  void initState() {
    super.initState();
    _loadUser();
    _loadPosData();
  }

  void _loadUser() async {
    final user = await ApiService.getUser();
    if (user != null && mounted) {
      setState(() {
        final role = (user['role'] ?? '').toString().toLowerCase();
        _canFilterOutlet = role == 'admin' || role == 'super_admin' || role == 'superadmin';
      });
    }
  }

  void _loadPosData({int? outletId}) async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final targetOutletId = outletId ?? _selectedOutletId;
      final res = await ApiService.getPosData(outletId: targetOutletId);
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _products = res['products'] ?? [];
            _customers = res['customers'] ?? [];
            _accounts = res['accounts'] ?? [];
            _storeSetting = res['setting'];
            _outlet = res['outlet'];
            if (res['outlets'] != null && res['outlets'] is List) {
              _outlets = res['outlets'];
            }
            _selectedOutletId = res['selected_outlet_id'] ?? targetOutletId;
            _selectedOutletName = res['selected_outlet_name'] ?? (_outlet?['name'] ?? 'Cabang');

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

    final double availableStock = Formatters.parseDouble(product['stock']);
    if (availableStock <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Stok produk [${product['name'] ?? 'Item'}] habis! (Sisa: 0)'),
          backgroundColor: Colors.red,
          duration: const Duration(seconds: 2),
        ),
      );
      return;
    }

    final double currentCartQty = _cart.containsKey(id) ? (_cart[id]!['qty'] as double) : 0.0;
    if (currentCartQty + 1.0 > availableStock) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Jumlah melebihi sisa stok! (Sisa: ${availableStock.toInt()} pcs)'),
          backgroundColor: Colors.orange.shade800,
          duration: const Duration(seconds: 2),
        ),
      );
      return;
    }

    final double price = widget.saleType == 'grosir'
        ? Formatters.parseDouble(product['wholesale_price'] ?? product['selling_price_grosir'] ?? product['retail_price'] ?? product['selling_price'])
        : Formatters.parseDouble(product['retail_price'] ?? product['selling_price']);

    setState(() {
      if (_cart.containsKey(id)) {
        _cart[id]!['qty'] = currentCartQty + 1.0;
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
      final product = _cart[id]!['product'];
      final availableStock = product != null ? Formatters.parseDouble(product['stock']) : 9999.0;

      if (delta > 0 && newQty > availableStock) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Stok tidak mencukupi! Sisa stok: ${availableStock.toInt()} pcs'),
            backgroundColor: Colors.orange.shade800,
            duration: const Duration(seconds: 2),
          ),
        );
        return;
      }

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
                                final String code = a['code'] ?? a['account_number'] ?? '';
                                final String name = a['name'] ?? 'Kas';
                                final String label = code.isNotEmpty ? '$code - $name' : name;
                                return DropdownMenuItem<int>(
                                  value: a['id'],
                                  child: Text(label, style: const TextStyle(fontSize: 11), overflow: TextOverflow.ellipsis),
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
                        _buildQuickCashChip('5rb', 5000, setSheetState),
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
        borderRadius: BorderRadius.circular(8),
        onTap: () {
          setSheetState(() => _paymentMethod = value);
          setState(() => _paymentMethod = value);
        },
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 9),
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: isSelected ? const Color(0xFF0F3D24) : Colors.white,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(
              color: isSelected ? const Color(0xFF0F3D24) : const Color(0xFFCBD5E1),
              width: isSelected ? 1.5 : 1.0,
            ),
            boxShadow: isSelected
                ? [
                    BoxShadow(
                      color: const Color(0xFF0F3D24).withOpacity(0.2),
                      blurRadius: 4,
                      offset: const Offset(0, 2),
                    )
                  ]
                : null,
          ),
          child: Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: isSelected ? Colors.white : const Color(0xFF334155),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildQuickCashChip(String label, double amount, StateSetter setSheetState) {
    final double currentPaid = Formatters.parseDouble(_paidController.text);
    final isUangPas = label == 'Uang Pas';
    final isSelected = (currentPaid == amount) || (isUangPas && currentPaid == _grandTotal && _grandTotal > 0);

    Color bgColor;
    Color borderColor;
    Color textColor;

    if (isSelected) {
      bgColor = isUangPas ? const Color(0xFF0F3D24) : const Color(0xFF0284C7);
      borderColor = isUangPas ? const Color(0xFF0F3D24) : const Color(0xFF0284C7);
      textColor = Colors.white;
    } else if (isUangPas) {
      bgColor = const Color(0xFFECFDF5);
      borderColor = const Color(0xFF059669);
      textColor = const Color(0xFF047857);
    } else {
      bgColor = Colors.white;
      borderColor = const Color(0xFFCBD5E1);
      textColor = const Color(0xFF1E293B);
    }

    return Padding(
      padding: const EdgeInsets.only(right: 6),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(20),
          onTap: () {
            _setQuickCash(amount);
            setSheetState(() {});
          },
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
            decoration: BoxDecoration(
              color: bgColor,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: borderColor, width: 1.2),
              boxShadow: isSelected
                  ? [
                      BoxShadow(
                        color: (isUangPas ? const Color(0xFF0F3D24) : const Color(0xFF0284C7)).withOpacity(0.25),
                        blurRadius: 4,
                        offset: const Offset(0, 2),
                      )
                    ]
                  : null,
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (isUangPas) ...[
                  Icon(
                    Icons.check_circle_outline,
                    size: 13,
                    color: isSelected ? Colors.white : const Color(0xFF059669),
                  ),
                  const SizedBox(width: 4),
                ],
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                    color: textColor,
                  ),
                ),
              ],
            ),
          ),
        ),
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
        outletId: _selectedOutletId,
        notes: _notesController.text.trim(),
        items: items,
      );

      setState(() => _isCheckingOut = false);

      if (res['success'] == true) {
        final change = (paid > _grandTotal) ? (paid - _grandTotal) : 0;
        final saleData = res['sale'];

        NotificationService.showNotification(
          id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
          title: 'Transaksi Kasir Berhasil',
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
    final formattedDate = "${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}  ${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}";
    final storeDisplayName = (_outlet?['name'] != null && _outlet!['name'].toString().isNotEmpty)
        ? _outlet!['name'].toString()
        : (_storeSetting?['name'] ?? _storeSetting?['store_name'] ?? 'ELEPHANT CELL GROUP');
    final storeAddress = (_outlet?['address'] != null && _outlet!['address'].toString().isNotEmpty)
        ? _outlet!['address'].toString()
        : (_storeSetting?['address'] ?? '');
    final storePhone = (_outlet?['phone'] != null && _outlet!['phone'].toString().isNotEmpty)
        ? _outlet!['phone'].toString()
        : (_storeSetting?['phone'] ?? '');
    final receiptFooterText = (_storeSetting?['receipt_footer'] != null && _storeSetting!['receipt_footer'].toString().isNotEmpty)
        ? _storeSetting!['receipt_footer'].toString()
        : 'Terima kasih telah berbelanja!\nBarang yang sudah dibeli tidak dapat ditukar/dikembalikan.';

    final cashierName = saleData?['user']?['name'] ?? saleData?['cashier_name'] ?? 'Admin';
    final customerName = _selectedCustomerId != null
        ? (_customers.firstWhere((c) => c['id'] == _selectedCustomerId, orElse: () => null)?['name'] ?? 'Umum')
        : 'Umum';

    // Calculate subtotal, discount, paid
    final double discount = _discount;
    final double subtotal = grandTotal + discount;
    final double paid = grandTotal + change;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: EdgeInsets.only(
          top: 16,
          left: 16,
          right: 16,
          bottom: MediaQuery.of(ctx).padding.bottom + 20,
        ),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Top Bar Handle & Title
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: const [
                      Icon(Icons.receipt_long, color: ThemeConfig.primary, size: 22),
                      SizedBox(width: 8),
                      Text('Preview Struk', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    ],
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(ctx),
                  ),
                ],
              ),
              const Divider(height: 8),
              const SizedBox(height: 8),

              // Receipt Card exactly matching Settings Preview
              Center(
                child: Container(
                  width: 320,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    border: Border.all(color: Colors.grey.shade300),
                    borderRadius: BorderRadius.circular(10),
                    boxShadow: const [BoxShadow(color: Color(0x1A000000), blurRadius: 8)],
                  ),
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 18),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      // Store Avatar / Icon
                      if (_storeSetting?['logo_url'] != null && (_storeSetting!['logo_url'] as String).isNotEmpty)
                        Image.network(
                          _storeSetting!['logo_url'],
                          height: 54,
                          fit: BoxFit.contain,
                          errorBuilder: (_, __, ___) => Container(
                            width: 50,
                            height: 50,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color: ThemeConfig.primary.withOpacity(0.1),
                            ),
                            child: const Icon(Icons.store, color: ThemeConfig.primary, size: 28),
                          ),
                        )
                      else
                        Container(
                          width: 52,
                          height: 52,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: ThemeConfig.primary.withOpacity(0.1),
                          ),
                          child: const Icon(Icons.store, color: ThemeConfig.primary, size: 28),
                        ),
                      const SizedBox(height: 8),
                      Text(
                        storeDisplayName,
                        style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 14),
                        textAlign: TextAlign.center,
                      ),
                      if (storeAddress.isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 2),
                          child: Text(
                            storeAddress,
                            style: const TextStyle(fontSize: 10, color: Colors.grey),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      if (storePhone.isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 1),
                          child: Text(
                            'Telp: $storePhone',
                            style: const TextStyle(fontSize: 10, color: Colors.grey),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      const Divider(height: 16),

                      // Meta Info Rows
                      _receiptPreviewRow('No. Nota', inv),
                      _receiptPreviewRow('Tanggal', formattedDate),
                      _receiptPreviewRow('Kasir', cashierName.toString()),
                      if (customerName.toString().toUpperCase() != 'UMUM')
                        _receiptPreviewRow('Pelanggan', customerName.toString()),
                      const Divider(height: 12),

                      // Items List
                      ...itemsPurchased.map((it) {
                        final name = it['product']?['name'] ?? it['product_name'] ?? 'Item';
                        final double qty = Formatters.parseDouble(it['qty'] ?? 1);
                        final double price = Formatters.parseDouble(it['price']);
                        final double itemTotal = qty * price;
                        return Padding(
                          padding: const EdgeInsets.symmetric(vertical: 2),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(name, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w500)),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    '  ${qty.toStringAsFixed(0)} x ${Formatters.formatRupiah(price)}',
                                    style: const TextStyle(fontSize: 9, color: Colors.grey),
                                  ),
                                  Text(
                                    Formatters.formatRupiah(itemTotal),
                                    style: const TextStyle(fontSize: 10),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        );
                      }),
                      const Divider(height: 12),

                      // Totals
                      _receiptPreviewRow('Subtotal', Formatters.formatRupiah(subtotal)),
                      if (discount > 0)
                        _receiptPreviewRow('Diskon', '- ${Formatters.formatRupiah(discount)}'),
                      _receiptPreviewRow('Total', Formatters.formatRupiah(grandTotal), bold: true),
                      _receiptPreviewRow('Bayar (${_paymentMethod.toUpperCase()})', Formatters.formatRupiah(paid)),
                      _receiptPreviewRow('Kembali', Formatters.formatRupiah(change)),
                      const Divider(height: 16),

                      // Footer Note & Mark
                      Text(
                        receiptFooterText,
                        style: const TextStyle(fontSize: 9, color: Colors.grey, fontStyle: FontStyle.italic),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 6),
                      Text(
                        '--- * ---',
                        style: TextStyle(fontSize: 9, color: Colors.grey.shade400),
                      ),
                    ],
                  ),
                ),
              ),

              const SizedBox(height: 18),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () async {
                    Navigator.pop(ctx);
                    final salePayload = {
                      'id': saleData?['id'],
                      'invoice_number': inv,
                      'date': formattedDate,
                      'cashier_name': cashierName,
                      'customer_name': customerName,
                      'subtotal': subtotal,
                      'discount': discount,
                      'grand_total': grandTotal,
                      'paid_amount': paid,
                      'change_amount': change,
                      'payment_method': _paymentMethod.toUpperCase(),
                      'items': itemsPurchased,
                      'outlet': _outlet,
                    };
                    await PrinterService.printReceipt(
                      sale: salePayload,
                      storeSetting: _storeSetting,
                    );
                  },
                  icon: const Icon(Icons.print, size: 18, color: Colors.white),
                  label: const Text('Cetak Struk Thermal (ESC/POS)', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 13)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: ThemeConfig.primary,
                    padding: const EdgeInsets.symmetric(vertical: 13),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _receiptPreviewRow(String label, String value, {bool bold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 1.5),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontSize: 10, fontWeight: bold ? FontWeight.bold : FontWeight.normal)),
          Text(value, style: TextStyle(fontSize: 10, fontWeight: bold ? FontWeight.bold : FontWeight.normal)),
        ],
      ),
    );
  }

  void _showSuccessDialog(double change, dynamic saleData) {
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
              // Action Button: Cetak Struk Saja (Faktur dihilangkan)
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () {
                    _showThermalReceiptModal(saleData, change, itemsPurchased, grandTotal);
                  },
                  icon: const Icon(Icons.receipt_long, size: 18, color: Colors.white),
                  label: const Text('Cetak / Lihat Struk Thermal', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: ThemeConfig.primary,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ),
              const SizedBox(height: 10),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton(
                  onPressed: () => Navigator.pop(context),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  child: const Text('Selesai / Transaksi Baru'),
                ),
              )
            ],
          ),
        );
      },
    );
  }

  void _showWithdrawModal() {
    final amountController = TextEditingController();
    final adminFeeController = TextEditingController(text: '0');
    final customerNameController = TextEditingController();
    final customerPhoneController = TextEditingController();
    final notesController = TextEditingController();
    // Filter valid cash/bank accounts
    final validAccounts = _accounts.where((a) => (a['type'] == 'D') && (a['code'] != null)).toList();
    final defaultAcc = validAccounts.firstWhere(
      (a) => (a['code'] == '1-1110' || a['code'] == '1-1111'),
      orElse: () => validAccounts.isNotEmpty ? validAccounts[0] : {'id': null},
    );
    int? sourceAccountId = defaultAcc['id'] as int?;
    bool isProcessing = false;

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
                    Row(
                      children: const [
                        Icon(Icons.payments_outlined, color: Colors.amber, size: 24),
                        SizedBox(width: 8),
                        Text('Tarik Tunai Kasir POS', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      ],
                    ),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                  ],
                ),
                const Divider(height: 12),
                const SizedBox(height: 8),
                const Text('Sumber Kas Pengambilan Uang Fisik', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 6),
                DropdownButtonFormField<int>(
                  value: sourceAccountId,
                  decoration: const InputDecoration(),
                  items: _accounts
                      .where((a) => (a['type'] == 'D') && (a['code'] != null))
                      .map<DropdownMenuItem<int>>((a) {
                    final String code = a['code'] ?? a['account_number'] ?? '';
                    final String name = a['name'] ?? '';
                    final String label = code.isNotEmpty ? '$code - $name' : name;
                    return DropdownMenuItem<int>(
                      value: a['id'] as int,
                      child: Text(label, style: const TextStyle(fontSize: 13)),
                    );
                  }).toList(),
                  onChanged: (val) => setModalState(() => sourceAccountId = val),
                ),
                const SizedBox(height: 14),
                const Text('Nominal Uang Tunai Ditarik (Rp)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 6),
                TextField(
                  controller: amountController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    hintText: 'Contoh: 100000',
                    prefixText: 'Rp ',
                  ),
                ),
                const SizedBox(height: 14),
                const Text('Biaya Admin / Jasa Transfer (Rp)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 6),
                TextField(
                  controller: adminFeeController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    hintText: '0',
                    prefixText: 'Rp ',
                  ),
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Nama Pelanggan (Opsional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 6),
                          TextField(controller: customerNameController, decoration: const InputDecoration(hintText: 'Nama')),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('No. HP / WA (Opsional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 6),
                          TextField(controller: customerPhoneController, keyboardType: TextInputType.phone, decoration: const InputDecoration(hintText: '08...')),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                const Text('Catatan Tambahan (Opsional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                const SizedBox(height: 6),
                TextField(controller: notesController, decoration: const InputDecoration(hintText: 'Keterangan transaksi')),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  height: 46,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.amber.shade800,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: isProcessing
                        ? null
                        : () async {
                            final double amount = Formatters.parseDouble(amountController.text.replaceAll(RegExp(r'[^0-9.]'), ''));
                            final double adminFee = Formatters.parseDouble(adminFeeController.text.replaceAll(RegExp(r'[^0-9.]'), ''));
                            if (sourceAccountId == null || amount <= 0) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Pilih sumber kas dan masukkan jumlah penarikan valid'), backgroundColor: Colors.red),
                              );
                              return;
                            }
                            setModalState(() => isProcessing = true);
                            final res = await ApiService.withdrawPos(
                              sourceAccountId: sourceAccountId!,
                              amount: amount,
                              adminFee: adminFee,
                              customerName: customerNameController.text.trim(),
                              customerPhone: customerPhoneController.text.trim(),
                              notes: notesController.text.trim(),
                            );
                            if (res['success'] == true) {
                              Navigator.pop(ctx);
                              _loadPosData();
                              NotificationService.showNotification(
                                id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                title: 'Tarik Tunai Berhasil',
                                body: 'Penarikan uang tunai ${Formatters.formatRupiah(amount)} sukses dibukukan.',
                              );
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Tarik tunai berhasil diproses!'), backgroundColor: ThemeConfig.accent),
                              );
                            } else {
                              setModalState(() => isProcessing = false);
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(res['message'] ?? 'Gagal memproses tarik tunai'), backgroundColor: Colors.red),
                              );
                            }
                          },
                    child: isProcessing
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text('Proses Penarikan Tunai', style: TextStyle(fontWeight: FontWeight.bold)),
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
    final title = widget.saleType == 'grosir' ? 'Kasir Penjualan Grosir' : 'Kasir Penjualan Retail (Eceran)';

    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        title: Text(title, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
        backgroundColor: ThemeConfig.primary,
        actions: [
          TextButton.icon(
            style: TextButton.styleFrom(
              foregroundColor: Colors.amber.shade200,
              padding: const EdgeInsets.symmetric(horizontal: 10),
            ),
            icon: const Icon(Icons.payments_outlined, size: 18),
            label: const Text('Tarik Tunai', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
            onPressed: _showWithdrawModal,
          ),
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
                    // Outlet Selector Bar for Admin / Super Admin
                    if (_canFilterOutlet && _outlets.isNotEmpty)
                      Container(
                        color: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                const Icon(Icons.storefront, size: 14, color: ThemeConfig.primary),
                                const SizedBox(width: 4),
                                const Text(
                                  'KASIR TOKO / CABANG AKTIF:',
                                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                                ),
                                const Spacer(),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFF0F3D24).withOpacity(0.08),
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(color: const Color(0xFF0F3D24).withOpacity(0.2)),
                                  ),
                                  child: Text(
                                    _selectedOutletName,
                                    style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF0F3D24)),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            SingleChildScrollView(
                              scrollDirection: Axis.horizontal,
                              child: Row(
                                children: _outlets.map((ot) {
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
                                      selectedColor: const Color(0xFF0F3D24),
                                      backgroundColor: Colors.white,
                                      showCheckmark: true,
                                      checkmarkColor: Colors.white,
                                      side: BorderSide(
                                        color: isSelected ? const Color(0xFF0F3D24) : const Color(0xFFCBD5E1),
                                        width: 1.2,
                                      ),
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                                      onSelected: (val) {
                                        if (_selectedOutletId != ot['id']) {
                                          setState(() {
                                            _selectedOutletId = ot['id'];
                                            _selectedOutletName = ot['name'] ?? 'Cabang';
                                            _cart.clear(); // Clear cart when switching store to avoid stock mismatch
                                          });
                                          _loadPosData(outletId: ot['id']);
                                        }
                                      },
                                    ),
                                  );
                                }).toList(),
                              ),
                            ),
                          ],
                        ),
                      )
                    else if (_selectedOutletName.isNotEmpty)
                      Container(
                        color: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                        child: Row(
                          children: [
                            const Icon(Icons.store, size: 14, color: ThemeConfig.primary),
                            const SizedBox(width: 6),
                            Text(
                              'Kasir: $_selectedOutletName',
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: ThemeConfig.primary),
                            ),
                          ],
                        ),
                      ),
                    const Divider(height: 1, thickness: 1, color: Color(0xFFE2E8F0)),

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
                                      ] else if (stock <= 0) ...[
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                          decoration: BoxDecoration(
                                            color: Colors.red.shade50,
                                            borderRadius: BorderRadius.circular(6),
                                            border: Border.all(color: Colors.red.shade200),
                                          ),
                                          child: Text(
                                            'Habis',
                                            style: TextStyle(
                                              fontSize: 11,
                                              fontWeight: FontWeight.bold,
                                              color: Colors.red.shade700,
                                            ),
                                          ),
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
                        padding: EdgeInsets.only(
                          left: 16,
                          right: 16,
                          top: 14,
                          bottom: MediaQuery.of(context).padding.bottom + 14,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
                          boxShadow: [
                            BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 10, offset: const Offset(0, -4)),
                          ],
                        ),
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
                  ],
                ),
    );
  }
}

