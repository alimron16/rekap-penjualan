import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/theme_config.dart';

class PurchasesScreen extends StatefulWidget {
  const PurchasesScreen({super.key});

  @override
  State<PurchasesScreen> createState() => _PurchasesScreenState();
}

class _PurchasesScreenState extends State<PurchasesScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoading = true;
  String? _errorMessage;

  List<dynamic> _purchases = [];
  List<dynamic> _debts = [];
  List<dynamic> _suppliers = [];
  List<dynamic> _products = [];
  List<dynamic> _accounts = [];

  final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _loadData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiService.getPurchases();
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _purchases = res['purchases'] ?? [];
            _debts = res['debts'] ?? [];
            _suppliers = res['suppliers'] ?? [];
            _products = res['products'] ?? [];
            _accounts = res['accounts'] ?? [];
          } else {
            _errorMessage = res['message'] ?? 'Gagal memuat data pembelian';
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

  void _showNewPurchaseModal() {
    int? selectedSupplierId = _suppliers.isNotEmpty ? _suppliers.first['id'] : null;
    int? selectedAccountId = _accounts.isNotEmpty ? _accounts.first['id'] : null;
    String paymentMethod = 'cash';
    final paidAmountController = TextEditingController();
    final notesController = TextEditingController();

    List<Map<String, dynamic>> cartItems = [];
    if (_products.isNotEmpty) {
      final firstProd = _products.first;
      cartItems.add({
        'product_id': firstProd['id'],
        'qty': 1.0,
        'buy_price': (firstProd['buy_price'] ?? 0).toDouble(),
      });
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) {
          double totalAmount = cartItems.fold(0, (sum, it) => sum + ((it['qty'] ?? 0) * (it['buy_price'] ?? 0)));

          return Container(
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
                        'Faktur Pembelian Baru',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                      ),
                      IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                    ],
                  ),
                  const SizedBox(height: 12),
                  const Text('Supplier / Pemasok', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                  const SizedBox(height: 6),
                  DropdownButtonFormField<int>(
                    value: selectedSupplierId,
                    isExpanded: true,
                    decoration: const InputDecoration(),
                    items: _suppliers.map<DropdownMenuItem<int>>((s) {
                      return DropdownMenuItem<int>(
                        value: s['id'],
                        child: Text(s['name'] ?? '', style: const TextStyle(fontSize: 13)),
                      );
                    }).toList(),
                    onChanged: (val) => setModalState(() => selectedSupplierId = val),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Item Barang', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                      TextButton.icon(
                        icon: const Icon(Icons.add, size: 16),
                        label: const Text('Tambah Item', style: TextStyle(fontSize: 12)),
                        onPressed: () {
                          if (_products.isNotEmpty) {
                            setModalState(() {
                              cartItems.add({
                                'product_id': _products.first['id'],
                                'qty': 1.0,
                                'buy_price': (_products.first['buy_price'] ?? 0).toDouble(),
                              });
                            });
                          }
                        },
                      ),
                    ],
                  ),
                  ...cartItems.asMap().entries.map((entry) {
                    final index = entry.key;
                    final item = entry.value;

                    return Container(
                      margin: const EdgeInsets.only(bottom: 10),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.grey.shade50,
                        border: Border.all(color: Colors.grey.shade200),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: DropdownButtonFormField<int>(
                                  value: item['product_id'],
                                  isExpanded: true,
                                  decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8)),
                                  items: _products.map<DropdownMenuItem<int>>((p) {
                                    return DropdownMenuItem<int>(
                                      value: p['id'],
                                      child: Text(p['name'] ?? '', style: const TextStyle(fontSize: 12)),
                                    );
                                  }).toList(),
                                  onChanged: (val) {
                                    setModalState(() {
                                      item['product_id'] = val;
                                      final prod = _products.firstWhere((p) => p['id'] == val, orElse: () => null);
                                      if (prod != null) {
                                        item['buy_price'] = (prod['buy_price'] ?? 0).toDouble();
                                      }
                                    });
                                  },
                                ),
                              ),
                              if (cartItems.length > 1)
                                IconButton(
                                  icon: const Icon(Icons.delete_outline, color: Colors.red, size: 20),
                                  onPressed: () => setModalState(() => cartItems.removeAt(index)),
                                ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Expanded(
                                flex: 1,
                                child: TextFormField(
                                  initialValue: item['qty'].toString(),
                                  keyboardType: TextInputType.number,
                                  decoration: const InputDecoration(
                                    labelText: 'Qty',
                                    contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                  ),
                                  onChanged: (v) => setModalState(() {
                                    item['qty'] = double.tryParse(v) ?? 1.0;
                                  }),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                flex: 2,
                                child: TextFormField(
                                  initialValue: item['buy_price'].toStringAsFixed(0),
                                  keyboardType: TextInputType.number,
                                  decoration: const InputDecoration(
                                    labelText: 'Harga Beli (Satuan)',
                                    contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                  ),
                                  onChanged: (v) => setModalState(() {
                                    item['buy_price'] = double.tryParse(v) ?? 0.0;
                                  }),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    );
                  }),
                  const SizedBox(height: 10),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: ThemeConfig.primary.withOpacity(0.06),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Total Pembelian:', style: TextStyle(fontWeight: FontWeight.bold)),
                        Text(
                          currencyFormatter.format(totalAmount),
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: ThemeConfig.primary),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),
                  const Text('Metode Pembayaran', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      Expanded(
                        child: ChoiceChip(
                          label: const Center(child: Text('Tunai / Lunas')),
                          selected: paymentMethod == 'cash',
                          selectedColor: ThemeConfig.accent.withOpacity(0.2),
                          onSelected: (val) => setModalState(() => paymentMethod = 'cash'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: ChoiceChip(
                          label: const Center(child: Text('Tempo / Hutang')),
                          selected: paymentMethod == 'credit',
                          selectedColor: Colors.orange.withOpacity(0.2),
                          onSelected: (val) => setModalState(() => paymentMethod = 'credit'),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  if (paymentMethod == 'cash') ...[
                    const Text('Akun Kas / Pembayaran', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
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
                  ] else ...[
                    const Text('Uang Muka / DP Dibayar (Rp)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                    const SizedBox(height: 6),
                    TextField(
                      controller: paidAmountController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(hintText: '0 jika tanpa DP'),
                    ),
                  ],
                  const SizedBox(height: 14),
                  const Text('Catatan Faktur (Opsional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                  const SizedBox(height: 6),
                  TextField(controller: notesController, decoration: const InputDecoration(hintText: 'Nomor nota supplier dsb.')),
                  const SizedBox(height: 20),
                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: ElevatedButton(
                      onPressed: () async {
                        if (selectedSupplierId == null) {
                          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Pilih supplier')));
                          return;
                        }
                        if (cartItems.isEmpty) {
                          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Tambahkan minimal 1 item')));
                          return;
                        }

                        final paidAmt = paymentMethod == 'cash'
                            ? totalAmount
                            : (double.tryParse(paidAmountController.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0);

                        final res = await ApiService.storePurchase(
                          date: DateFormat('yyyy-MM-dd').format(DateTime.now()),
                          supplierId: selectedSupplierId!,
                          paymentMethod: paymentMethod,
                          accountId: selectedAccountId,
                          paidAmount: paidAmt,
                          notes: notesController.text,
                          items: cartItems,
                        );

                        if (res['success'] == true) {
                          Navigator.pop(context);
                          _loadData();
                          NotificationService.showNotification(
                            id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                            title: 'Faktur Pembelian Disimpan! 📦',
                            body: 'Pembelian barang dari supplier sebesar ${currencyFormatter.format(totalAmount)} berhasil dicatat.',
                          );
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text(res['message'] ?? 'Faktur pembelian berhasil!'), backgroundColor: ThemeConfig.accent),
                          );
                        } else {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text(res['message'] ?? 'Gagal menyimpan faktur'), backgroundColor: Colors.red),
                          );
                        }
                      },
                      child: const Text('Simpan Faktur Pembelian'),
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

  void _showPayDebtModal(dynamic debt) {
    final remainingDebt = (debt['remaining_debt'] ?? (debt['grand_total'] - (debt['paid_amount'] ?? 0))).toDouble();
    final amountController = TextEditingController(text: remainingDebt.toStringAsFixed(0));
    final notesController = TextEditingController();
    int? selectedAccountId = _accounts.isNotEmpty ? _accounts.first['id'] : null;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
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
                  Text('Bayar Hutang Supplier', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: ThemeConfig.textDark)),
                  IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                ],
              ),
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.orange.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(debt['supplier']?['name'] ?? 'Supplier', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                        Text('Faktur: ${debt['invoice_no'] ?? '-'}', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                      ],
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        const Text('Sisa Hutang', style: TextStyle(fontSize: 11, color: Colors.grey)),
                        Text(currencyFormatter.format(remainingDebt), style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.red, fontSize: 14)),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              const Text('Jumlah Bayar (Rp)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
              const SizedBox(height: 6),
              TextField(controller: amountController, keyboardType: TextInputType.number, decoration: const InputDecoration(hintText: 'Nominal bayar')),
              const SizedBox(height: 14),
              const Text('Akun Kas / Pembayaran', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
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
                onChanged: (val) => selectedAccountId = val,
              ),
              const SizedBox(height: 14),
              const Text('Catatan (Opsional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
              const SizedBox(height: 6),
              TextField(controller: notesController, decoration: const InputDecoration(hintText: 'Contoh: Transfer Bank BCA')),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton(
                  onPressed: () async {
                    final amt = double.tryParse(amountController.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0;
                    if (amt <= 0 || selectedAccountId == null) {
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Isi nominal dan pilih akun kas')));
                      return;
                    }

                    final res = await ApiService.payDebt(
                      date: DateFormat('yyyy-MM-dd').format(DateTime.now()),
                      purchaseId: debt['id'],
                      supplierId: debt['supplier_id'],
                      amount: amt,
                      accountId: selectedAccountId!,
                      notes: notesController.text,
                    );

                    if (res['success'] == true) {
                      Navigator.pop(context);
                      _loadData();
                      NotificationService.showNotification(
                        id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                        title: 'Pembayaran Hutang Berhasil! 💸',
                        body: 'Hutang supplier sebesar ${currencyFormatter.format(amt)} berhasil dibayarkan.',
                      );
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text(res['message'] ?? 'Pembayaran hutang berhasil!'), backgroundColor: ThemeConfig.accent),
                      );
                    } else {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text(res['message'] ?? 'Gagal membayar hutang'), backgroundColor: Colors.red),
                      );
                    }
                  },
                  child: const Text('Simpan Pembayaran Hutang'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Pembelian & Hutang', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: ThemeConfig.accent,
          indicatorWeight: 3,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: [
            Tab(text: 'Daftar Pembelian (${_purchases.length})'),
            Tab(text: 'Hutang Supplier (${_debts.length})'),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: ThemeConfig.primary,
        onPressed: _showNewPurchaseModal,
        icon: const Icon(Icons.add_shopping_cart, color: Colors.white),
        label: const Text('Faktur Baru', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
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
              : TabBarView(
                  controller: _tabController,
                  children: [
                    _buildPurchasesList(),
                    _buildDebtsList(),
                  ],
                ),
    );
  }

  Widget _buildPurchasesList() {
    if (_purchases.isEmpty) {
      return const Center(child: Text('Belum ada riwayat pembelian barang', style: TextStyle(color: Colors.grey)));
    }

    return RefreshIndicator(
      onRefresh: () async => _loadData(),
      child: ListView.builder(
        padding: const EdgeInsets.only(left: 16, right: 16, top: 16, bottom: 80),
        itemCount: _purchases.length,
        itemBuilder: (ctx, i) {
          final p = _purchases[i];
          final total = (p['grand_total'] ?? 0).toDouble();

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
                          p['supplier']?['name'] ?? 'Supplier',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: ThemeConfig.textDark),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: p['status'] == 'LUNAS' ? ThemeConfig.accent.withOpacity(0.12) : Colors.red.withOpacity(0.12),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          p['status'] ?? 'LUNAS',
                          style: TextStyle(
                            color: p['status'] == 'LUNAS' ? ThemeConfig.accent : Colors.red,
                            fontWeight: FontWeight.bold,
                            fontSize: 11,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'No: ${p['invoice_no'] ?? '-'} • Tgl: ${p['date'] ?? '-'}',
                    style: TextStyle(color: ThemeConfig.textMuted, fontSize: 12),
                  ),
                  const Divider(height: 20),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('${(p['items'] as List?)?.length ?? 0} Jenis Item', style: TextStyle(fontSize: 12, color: ThemeConfig.textMuted)),
                      Text(currencyFormatter.format(total), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: ThemeConfig.primary)),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildDebtsList() {
    if (_debts.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.check_circle_outline, size: 64, color: Colors.green),
            SizedBox(height: 12),
            Text('Semua hutang supplier telah lunas!', style: TextStyle(fontSize: 16, color: Colors.grey)),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () async => _loadData(),
      child: ListView.builder(
        padding: const EdgeInsets.only(left: 16, right: 16, top: 16, bottom: 80),
        itemCount: _debts.length,
        itemBuilder: (ctx, i) {
          final d = _debts[i];
          final total = (d['grand_total'] ?? 0).toDouble();
          final paid = (d['paid_amount'] ?? 0).toDouble();
          final remaining = total - paid;

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
                          d['supplier']?['name'] ?? 'Supplier',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: ThemeConfig.textDark),
                        ),
                      ),
                      Text(
                        'Sisa: ${currencyFormatter.format(remaining)}',
                        style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.red, fontSize: 13),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text('Faktur: ${d['invoice_no'] ?? '-'} • Tgl: ${d['date'] ?? '-'}', style: TextStyle(color: ThemeConfig.textMuted, fontSize: 12)),
                  const SizedBox(height: 14),
                  SizedBox(
                    width: double.infinity,
                    height: 38,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(backgroundColor: ThemeConfig.primary),
                      onPressed: () => _showPayDebtModal(d),
                      icon: const Icon(Icons.payment, size: 16),
                      label: const Text('Bayar Hutang Supplier', style: TextStyle(fontSize: 12)),
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
}
