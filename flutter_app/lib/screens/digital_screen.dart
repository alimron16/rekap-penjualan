import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../services/printer_service.dart';
import '../utils/formatters.dart';
import '../utils/theme_config.dart';

class DigitalScreen extends StatefulWidget {
  const DigitalScreen({super.key});

  @override
  State<DigitalScreen> createState() => _DigitalScreenState();
}

class _DigitalScreenState extends State<DigitalScreen> {
  List<dynamic> _products = [];
  List<dynamic> _depositAccounts = [];
  List<dynamic> _cashAccounts = [];
  Map<String, dynamic>? _storeSetting;
  double _saldoMulti = 0;
  bool _isLoading = true;
  String? _errorMessage;

  final _phoneController = TextEditingController();
  final _sellingPriceController = TextEditingController();
  final _notesController = TextEditingController();

  int? _selectedProductId;
  int? _selectedDepositAccountId;
  int? _selectedCashAccountId;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _loadDigitalData();
  }

  void _loadDigitalData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await ApiService.getDigitalData();
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _products = res['products'] ?? [];
            _depositAccounts = res['deposit_accounts'] ?? [];
            _cashAccounts = res['cash_accounts'] ?? [];
            _saldoMulti = Formatters.parseDouble(res['saldo_multi']);
            _storeSetting = res['setting'];

            if (_products.isNotEmpty && _selectedProductId == null) {
              _selectedProductId = _products[0]['id'];
              _sellingPriceController.text = Formatters.parseDouble(_products[0]['selling_price']).toStringAsFixed(0);
            }
            if (_depositAccounts.isNotEmpty && _selectedDepositAccountId == null) {
              _selectedDepositAccountId = _depositAccounts[0]['id'];
            }
            if (_cashAccounts.isNotEmpty && _selectedCashAccountId == null) {
              _selectedCashAccountId = _cashAccounts[0]['id'];
            }
          } else {
            _errorMessage = res['message'] ?? 'Gagal memuat produk elektrik';
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

  void _submitTransaction() async {
    if (_phoneController.text.trim().isEmpty || _selectedProductId == null || _selectedDepositAccountId == null || _selectedCashAccountId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Harap lengkapi nomor tujuan dan produk!'), backgroundColor: Colors.red),
      );
      return;
    }

    final double price = Formatters.parseDouble(_sellingPriceController.text);
    if (price <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Harga jual harus lebih dari 0'), backgroundColor: Colors.red),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final selectedProd = _products.firstWhere((p) => p['id'] == _selectedProductId, orElse: () => null);
      final double hpp = selectedProd != null ? Formatters.parseDouble(selectedProd['hpp']) : 0;

      final res = await ApiService.checkoutDigital(
        digitalProductId: _selectedProductId!,
        customerNumber: _phoneController.text.trim(),
        depositAccountId: _selectedDepositAccountId!,
        cashAccountId: _selectedCashAccountId!,
        sellingPrice: price,
        hpp: hpp > 0 ? hpp : null,
        notes: _notesController.text.trim(),
      );

      setState(() => _isSubmitting = false);

      if (res['success'] == true) {
        final digitalSaleData = res['digital_sale'] ?? {
          'transaction_number': 'PE-${DateTime.now().millisecondsSinceEpoch}',
          'customer_number': _phoneController.text.trim(),
          'selling_price': price,
          'digital_product': selectedProd,
          'status': 'SUKSES',
          'notes': _notesController.text.trim(),
          'date': DateTime.now().toString(),
        };

        if (res['setting'] != null) {
          _storeSetting = res['setting'];
        }

        NotificationService.showNotification(
          id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
          title: '⚡ Transaksi Elektrik Berhasil! 🧾',
          body: 'Penjualan pulsa/data ke ${_phoneController.text} sebesar ${Formatters.formatRupiah(price)} sukses.',
        );

        if (!mounted) return;
        _showDigitalReceiptModal(digitalSaleData);

        _phoneController.clear();
        _notesController.clear();
        _loadDigitalData();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res['message'] ?? 'Transaksi gagal diproses'), backgroundColor: Colors.red),
        );
      }
    } catch (e) {
      setState(() => _isSubmitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    }
  }

  void _showDigitalReceiptModal(Map<String, dynamic> digitalSale) {
    final trxNo = digitalSale['transaction_number'] ?? 'PE-NOTA';
    final date = DateTime.now();
    final formattedDate = "${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}  ${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}";
    final storeDisplayName = _storeSetting?['name'] ?? _storeSetting?['store_name'] ?? 'ELEPHANT CELL GROUP';
    final storeAddress = _storeSetting?['address'] ?? '';
    final storePhone = _storeSetting?['phone'] ?? '';
    final productName = digitalSale['digital_product']?['name'] ?? 'Pulsa / Elektrik';
    final customerNo = digitalSale['customer_number'] ?? _phoneController.text;
    final sellingPrice = Formatters.parseDouble(digitalSale['selling_price']);
    final receiptFooterText = _storeSetting?['receipt_footer'] ?? 'Simpan struk ini sebagai bukti transaksi yang sah.';

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
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: const [
                      Icon(Icons.receipt_long, color: ThemeConfig.primary, size: 22),
                      SizedBox(width: 8),
                      Text('Preview Struk Pulsa', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    ],
                  ),
                  IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
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
                      Text(storeDisplayName, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 14), textAlign: TextAlign.center),
                      if (storeAddress.isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 2),
                          child: Text(storeAddress, style: const TextStyle(fontSize: 10, color: Colors.grey), textAlign: TextAlign.center),
                        ),
                      if (storePhone.isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 1),
                          child: Text('Telp: $storePhone', style: const TextStyle(fontSize: 10, color: Colors.grey), textAlign: TextAlign.center),
                        ),
                      const Divider(height: 16),
                      _receiptDigitalRow('No. Trx', trxNo),
                      _receiptDigitalRow('Tanggal', formattedDate),
                      _receiptDigitalRow('Kategori', 'Pulsa / PPOB'),
                      const Divider(height: 12),
                      _receiptDigitalRow('Produk', productName),
                      _receiptDigitalRow('No. Tujuan', customerNo),
                      if (digitalSale['notes'] != null && digitalSale['notes'].toString().isNotEmpty)
                        _receiptDigitalRow('SN / Ket', digitalSale['notes'].toString()),
                      const Divider(height: 12),
                      _receiptDigitalRow('Total', Formatters.formatRupiah(sellingPrice), bold: true),
                      _receiptDigitalRow('Bayar', 'Tunai'),
                      _receiptDigitalRow('Status', 'SUKSES', statusColor: Colors.green),
                      const Divider(height: 16),
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
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () async {
                    Navigator.pop(ctx);
                    await PrinterService.printDigitalReceipt(
                      digitalSale: digitalSale,
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
              const SizedBox(height: 8),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton(
                  onPressed: () => Navigator.pop(ctx),
                  child: const Text('Tutup'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _receiptDigitalRow(String label, String value, {bool bold = false, Color? statusColor}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 1.5),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontSize: 10, fontWeight: bold ? FontWeight.bold : FontWeight.normal)),
          Text(
            value,
            style: TextStyle(
              fontSize: 10,
              fontWeight: (bold || statusColor != null) ? FontWeight.bold : FontWeight.normal,
              color: statusColor,
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('Produk Elektrik & Pulsa', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: ThemeConfig.primary,
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadDigitalData),
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
                      ElevatedButton(onPressed: _loadDigitalData, child: const Text('Coba Lagi')),
                    ],
                  ),
                )
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Saldo Multi Card
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [Color(0xFF0F766E), Color(0xFF14B8A6)],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                          borderRadius: BorderRadius.circular(16),
                          boxShadow: [
                            BoxShadow(color: const Color(0xFF0F766E).withOpacity(0.3), blurRadius: 10, offset: const Offset(0, 4))
                          ],
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Saldo Deposit Server Multi', style: TextStyle(color: Colors.white70, fontSize: 12)),
                                SizedBox(height: 4),
                                Text('Ready to Topup', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
                              ],
                            ),
                            Text(
                              Formatters.formatRupiah(_saldoMulti),
                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 18),
                            )
                          ],
                        ),
                      ),
                      const SizedBox(height: 20),

                      // Form Input
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: Colors.grey.shade200),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Nomor Tujuan / No. Meter PLN', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            const SizedBox(height: 6),
                            TextField(
                              controller: _phoneController,
                              keyboardType: TextInputType.phone,
                              decoration: const InputDecoration(
                                hintText: '08xxxxxxxxxx atau No. Meter',
                                prefixIcon: Icon(Icons.phone_android),
                              ),
                            ),
                            const SizedBox(height: 16),
                            const Text('Pilih Produk Elektrik', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            const SizedBox(height: 6),
                            DropdownButtonFormField<int>(
                              value: _selectedProductId,
                              isExpanded: true,
                              decoration: const InputDecoration(),
                              items: _products.map<DropdownMenuItem<int>>((p) {
                                final price = Formatters.parseDouble(p['selling_price']);
                                return DropdownMenuItem<int>(
                                  value: p['id'],
                                  child: Text('${p['name']} (${Formatters.formatRupiah(price)})', style: const TextStyle(fontSize: 13)),
                                );
                              }).toList(),
                              onChanged: (val) {
                                setState(() {
                                  _selectedProductId = val;
                                  final prod = _products.firstWhere((p) => p['id'] == val, orElse: () => null);
                                  if (prod != null) {
                                    _sellingPriceController.text = Formatters.parseDouble(prod['selling_price']).toStringAsFixed(0);
                                  }
                                });
                              },
                            ),
                            const SizedBox(height: 16),
                            const Text('Harga Jual ke Pelanggan (Rp)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            const SizedBox(height: 6),
                            TextField(
                              controller: _sellingPriceController,
                              keyboardType: TextInputType.number,
                              decoration: const InputDecoration(hintText: 'Harga jual'),
                            ),
                            const SizedBox(height: 16),
                            const Text('Potong Saldo Dari (Akun Deposit)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            const SizedBox(height: 6),
                            DropdownButtonFormField<int>(
                              value: _selectedDepositAccountId,
                              isExpanded: true,
                              decoration: const InputDecoration(),
                              items: _depositAccounts.map<DropdownMenuItem<int>>((acc) {
                                final balance = Formatters.parseDouble(acc['current_balance']);
                                return DropdownMenuItem<int>(
                                  value: acc['id'],
                                  child: Text('${acc['code']} - ${acc['name']} (${Formatters.formatRupiah(balance)})', style: const TextStyle(fontSize: 12)),
                                );
                              }).toList(),
                              onChanged: (val) => setState(() => _selectedDepositAccountId = val),
                            ),
                            const SizedBox(height: 16),
                            const Text('Terima Pembayaran Tunai Ke', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            const SizedBox(height: 6),
                            DropdownButtonFormField<int>(
                              value: _selectedCashAccountId,
                              isExpanded: true,
                              decoration: const InputDecoration(),
                              items: _cashAccounts.map<DropdownMenuItem<int>>((acc) {
                                return DropdownMenuItem<int>(
                                  value: acc['id'],
                                  child: Text('${acc['code']} - ${acc['name']}', style: const TextStyle(fontSize: 12)),
                                );
                              }).toList(),
                              onChanged: (val) => setState(() => _selectedCashAccountId = val),
                            ),
                            const SizedBox(height: 16),
                            const Text('Catatan (Opsional)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            const SizedBox(height: 6),
                            TextField(
                              controller: _notesController,
                              decoration: const InputDecoration(hintText: 'Keterangan transaksi'),
                            ),
                            const SizedBox(height: 24),
                            SizedBox(
                              width: double.infinity,
                              height: 48,
                              child: ElevatedButton.icon(
                                onPressed: _isSubmitting ? null : _submitTransaction,
                                style: ElevatedButton.styleFrom(backgroundColor: ThemeConfig.primary),
                                icon: const Icon(Icons.flash_on),
                                label: _isSubmitting
                                    ? const SizedBox(
                                        height: 20,
                                        width: 20,
                                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                                      )
                                    : const Text('Proses Transaksi Elektrik', style: TextStyle(fontWeight: FontWeight.bold)),
                              ),
                            )
                          ],
                        ),
                      )
                    ],
                  ),
                ),
    );
  }
}
