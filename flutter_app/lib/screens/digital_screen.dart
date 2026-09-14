import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
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
  double _saldoMulti = 0;
  bool _isLoading = true;

  final _phoneController = TextEditingController();
  final _sellingPriceController = TextEditingController();
  final _notesController = TextEditingController();

  int? _selectedProductId;
  int? _selectedDepositAccountId;
  int? _selectedCashAccountId;
  bool _isSubmitting = false;

  final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  @override
  void initState() {
    super.initState();
    _loadDigitalData();
  }

  void _loadDigitalData() async {
    setState(() => _isLoading = true);
    try {
      final res = await ApiService.getDigitalData();
      if (mounted && res['success'] == true) {
        setState(() {
          _products = res['products'] ?? [];
          _depositAccounts = res['deposit_accounts'] ?? [];
          _cashAccounts = res['cash_accounts'] ?? [];
          _saldoMulti = (res['saldo_multi'] ?? 0).toDouble();

          if (_products.isNotEmpty) {
            _selectedProductId = _products[0]['id'];
            _sellingPriceController.text = (_products[0]['selling_price'] ?? 0).toString();
          }
          if (_depositAccounts.isNotEmpty) _selectedDepositAccountId = _depositAccounts[0]['id'];
          if (_cashAccounts.isNotEmpty) _selectedCashAccountId = _cashAccounts[0]['id'];

          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _submitTransaction() async {
    if (_phoneController.text.isEmpty || _selectedProductId == null || _selectedDepositAccountId == null || _selectedCashAccountId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Harap lengkapi nomor tujuan dan produk!'), backgroundColor: Colors.red),
      );
      return;
    }

    final double price = double.tryParse(_sellingPriceController.text.replaceAll('.', '').replaceAll(',', '')) ?? 0;

    setState(() => _isSubmitting = true);

    try {
      final res = await ApiService.checkoutDigital(
        digitalProductId: _selectedProductId!,
        customerNumber: _phoneController.text.trim(),
        depositAccountId: _selectedDepositAccountId!,
        cashAccountId: _selectedCashAccountId!,
        sellingPrice: price,
        notes: _notesController.text.trim(),
      );

      setState(() => _isSubmitting = false);

      if (res['success'] == true) {
        NotificationService.showNotification(
          id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
          title: '⚡ Transaksi Pulsa / PLN Berhasil',
          body: 'Transaksi ${_phoneController.text} senilai ${currencyFormatter.format(price)} sukses.',
        );

        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Transaksi pulsa/listrik berhasil!'), backgroundColor: ThemeConfig.primary),
        );
        _phoneController.clear();
        _notesController.clear();
        _loadDigitalData();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res['message'] ?? 'Gagal memproses transaksi'), backgroundColor: Colors.red),
        );
      }
    } catch (e) {
      setState(() => _isSubmitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        title: const Text('Produk Elektrik & Pulsa', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: ThemeConfig.primary,
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadDigitalData),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Saldo Multi Card
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(colors: [Colors.teal, Color(0xFF0F766E)]),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Sisa Saldo Deposit Server', style: TextStyle(color: Colors.white70, fontSize: 11)),
                            SizedBox(height: 4),
                            Text('SALDO MULTI ELEKTRIK', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
                          ],
                        ),
                        Text(
                          currencyFormatter.format(_saldoMulti),
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 16),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Form Transaksi
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
                        const Text('Nomor Tujuan / No Meter PLN', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                        const SizedBox(height: 6),
                        TextField(
                          controller: _phoneController,
                          keyboardType: TextInputType.phone,
                          decoration: const InputDecoration(hintText: '081234567890 / 14123456789'),
                        ),
                        const SizedBox(height: 14),

                        const Text('Pilih Produk Elektrik', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                        const SizedBox(height: 6),
                        DropdownButtonFormField<int>(
                          value: _selectedProductId,
                          isExpanded: true,
                          items: _products.map<DropdownMenuItem<int>>((p) {
                            return DropdownMenuItem<int>(
                              value: p['id'],
                              child: Text('${p['name']} (${currencyFormatter.format(p['selling_price'] ?? 0)})', style: const TextStyle(fontSize: 12)),
                            );
                          }).toList(),
                          onChanged: (val) {
                            setState(() {
                              _selectedProductId = val;
                              final prod = _products.firstWhere((p) => p['id'] == val, orElse: () => null);
                              if (prod != null) {
                                _sellingPriceController.text = (prod['selling_price'] ?? 0).toString();
                              }
                            });
                          },
                        ),
                        const SizedBox(height: 14),

                        const Text('Harga Jual ke Pelanggan (Rp)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                        const SizedBox(height: 6),
                        TextField(
                          controller: _sellingPriceController,
                          keyboardType: TextInputType.number,
                          decoration: const InputDecoration(hintText: 'Nominal harga jual'),
                        ),
                        const SizedBox(height: 20),

                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: _isSubmitting ? null : _submitTransaction,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: ThemeConfig.primary,
                              padding: const EdgeInsets.symmetric(vertical: 14),
                            ),
                            child: _isSubmitting
                                ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                                : const Text('Proses Transaksi Sekarang', style: TextStyle(fontWeight: FontWeight.bold)),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}
