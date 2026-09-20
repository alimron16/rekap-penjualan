import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../services/printer_service.dart';
import '../utils/formatters.dart';
import '../utils/theme_config.dart';

class ShiftScreen extends StatefulWidget {
  const ShiftScreen({super.key});

  @override
  State<ShiftScreen> createState() => _ShiftScreenState();
}

class _ShiftScreenState extends State<ShiftScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoading = true;
  bool _isLoadingHistory = false;
  bool _isSubmitting = false;

  Map<String, dynamic>? _shiftData;
  List<dynamic> _historyList = [];
  List<dynamic> _outlets = [];
  int? _selectedOutletId;
  bool _isAdmin = false;

  // Controllers for 3 Cash Types
  final _retailDepositController = TextEditingController();
  final _retailRetainedController = TextEditingController(text: '400000');
  final _multiDepositController = TextEditingController();
  final _multiRetainedController = TextEditingController(text: '0');
  final _transferDepositController = TextEditingController();
  final _transferRetainedController = TextEditingController(text: '0');
  final _notesController = TextEditingController();

  double _totalDeposit = 0.0;
  final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(() {
      if (_tabController.index == 1 && _historyList.isEmpty) {
        _loadHistory();
      }
    });

    _retailDepositController.addListener(_calculateTotal);
    _multiDepositController.addListener(_calculateTotal);
    _transferDepositController.addListener(_calculateTotal);

    _initData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _retailDepositController.dispose();
    _retailRetainedController.dispose();
    _multiDepositController.dispose();
    _multiRetainedController.dispose();
    _transferDepositController.dispose();
    _transferRetainedController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  void _calculateTotal() {
    final ret = double.tryParse(_retailDepositController.text.replaceAll('.', '').replaceAll(',', '')) ?? 0.0;
    final mlt = double.tryParse(_multiDepositController.text.replaceAll('.', '').replaceAll(',', '')) ?? 0.0;
    final trf = double.tryParse(_transferDepositController.text.replaceAll('.', '').replaceAll(',', '')) ?? 0.0;
    setState(() {
      _totalDeposit = ret + mlt + trf;
    });
  }

  Future<void> _initData() async {
    await _loadOutlets();
    await _loadShiftData();
  }

  Future<void> _loadOutlets() async {
    try {
      final res = await ApiService.getOutlets();
      if (res['success'] == true && res['data'] is List) {
        if (mounted) {
          setState(() {
            _outlets = res['data'];
          });
        }
      }
    } catch (_) {}
  }

  Future<void> _loadShiftData() async {
    if (!mounted) return;
    setState(() => _isLoading = true);
    try {
      final res = await ApiService.getShiftSummary(outletId: _selectedOutletId);
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _shiftData = res;
            final role = res['user']?['role']?.toString().toLowerCase() ?? '';
            _isAdmin = role == 'admin' || role == 'superadmin' || role == 'super_admin';

            // Auto-select outlet if not selected yet
            if (_selectedOutletId == null && res['outlet_id'] != null) {
              _selectedOutletId = res['outlet_id'];
            }

            // Auto-fill recommended deposits
            final recRetail = (res['recommended_deposit'] as num?)?.toDouble() ?? 0.0;
            _retailDepositController.text = recRetail > 0 ? recRetail.toInt().toString() : '0';

            final multiSales = (res['cash_multi']?['shift_sales'] as num?)?.toDouble() ?? 0.0;
            _multiDepositController.text = multiSales > 0 ? multiSales.toInt().toString() : '0';

            final trfBal = (res['cash_transfer']?['balance'] as num?)?.toDouble() ?? 0.0;
            _transferDepositController.text = trfBal > 0 ? trfBal.toInt().toString() : '0';

            _calculateTotal();
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _loadHistory() async {
    setState(() => _isLoadingHistory = true);
    try {
      final res = await ApiService.getShiftHistory(outletId: _selectedOutletId);
      if (mounted) {
        setState(() {
          if (res['success'] == true && res['data'] is List) {
            _historyList = res['data'];
          }
          _isLoadingHistory = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _isLoadingHistory = false);
    }
  }

  void _applyQuickPreset() {
    if (_shiftData == null) return;
    final recRetail = (_shiftData!['recommended_deposit'] as num?)?.toDouble() ?? 0.0;
    _retailDepositController.text = recRetail > 0 ? recRetail.toInt().toString() : '0';
    _retailRetainedController.text = '400000';

    final multiSales = (_shiftData!['cash_multi']?['shift_sales'] as num?)?.toDouble() ?? 0.0;
    _multiDepositController.text = multiSales > 0 ? multiSales.toInt().toString() : '0';
    _multiRetainedController.text = '0';

    final trfBal = (_shiftData!['cash_transfer']?['balance'] as num?)?.toDouble() ?? 0.0;
    _transferDepositController.text = trfBal > 0 ? trfBal.toInt().toString() : '0';
    _transferRetainedController.text = '0';

    _calculateTotal();

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Preset diterapkan: Modal wajib Rp 400.000 dijaga di laci.'),
        backgroundColor: ThemeConfig.primary,
        duration: Duration(seconds: 2),
      ),
    );
  }

  void _submitCloseShift() async {
    final retDep = double.tryParse(_retailDepositController.text.replaceAll('.', '').replaceAll(',', '')) ?? 0.0;
    final retRet = double.tryParse(_retailRetainedController.text.replaceAll('.', '').replaceAll(',', '')) ?? 400000.0;
    final mltDep = double.tryParse(_multiDepositController.text.replaceAll('.', '').replaceAll(',', '')) ?? 0.0;
    final mltRet = double.tryParse(_multiRetainedController.text.replaceAll('.', '').replaceAll(',', '')) ?? 0.0;
    final trfDep = double.tryParse(_transferDepositController.text.replaceAll('.', '').replaceAll(',', '')) ?? 0.0;
    final trfRet = double.tryParse(_transferRetainedController.text.replaceAll('.', '').replaceAll(',', '')) ?? 0.0;

    final total = retDep + mltDep + trfDep;

    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        title: Row(
          children: const [
            Icon(Icons.assignment_turned_in_rounded, color: Colors.green),
            SizedBox(width: 8),
            Text('Konfirmasi Ganti Shift', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Total uang tunai sebesar ${currencyFormatter.format(total)} akan disetor ke brankas/pusat.',
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.black87),
            ),
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: Colors.green.shade50,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: Colors.green.shade200),
              ),
              child: Column(
                children: [
                  _confirmRow('Setor Retail:', currencyFormatter.format(retDep)),
                  _confirmRow('Setor Multi:', currencyFormatter.format(mltDep)),
                  _confirmRow('Setor Transfer:', currencyFormatter.format(trfDep)),
                  const Divider(height: 12),
                  _confirmRow('Sisa Modal Retail:', currencyFormatter.format(retRet), isBold: true),
                ],
              ),
            ),
            const SizedBox(height: 10),
            const Text(
              'Perhatian: Setelah tutup shift, semua penghitung transaksi penjualan akan otomatis kembali ke 0 untuk shift kasir berikutnya.',
              style: TextStyle(fontSize: 11, color: Colors.blueGrey),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: ThemeConfig.primary),
            child: const Text('Ya, Setor & Ganti Shift'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() => _isSubmitting = true);

    try {
      final res = await ApiService.closeShift(
        cashRetailDeposit: retDep,
        cashRetailRetained: retRet,
        cashMultiDeposit: mltDep,
        cashMultiRetained: mltRet,
        cashTransferDeposit: trfDep,
        cashTransferRetained: trfRet,
        outletId: _selectedOutletId,
        notes: _notesController.text.trim().isNotEmpty ? _notesController.text.trim() : 'Ganti Shift Kasir',
      );

      setState(() => _isSubmitting = false);

      if (res['success'] == true) {
        NotificationService.showNotification(
          id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
          title: 'Ganti Shift & Setor Berhasil',
          body: 'Total ${currencyFormatter.format(total)} sukses disetor ke brankas. Shift baru aktif.',
        );

        if (!mounted) return;

        // Prompt Print Receipt
        final printNow = await showDialog<bool>(
          context: context,
          builder: (ctx) => AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
            title: const Text('Cetak Struk Tutup Shift?'),
            content: const Text('Struk rekap penyerahan uang kasir dapat langsung dicetak ke printer thermal.'),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Nanti Saja')),
              ElevatedButton.icon(
                onPressed: () => Navigator.pop(ctx, true),
                icon: const Icon(Icons.print, size: 16),
                label: const Text('Cetak Sekarang'),
                style: ElevatedButton.styleFrom(backgroundColor: ThemeConfig.primary),
              ),
            ],
          ),
        );

        if (printNow == true && res['shift_log'] != null) {
          try {
            await PrinterService.printShiftReceipt(shiftData: res['shift_log']);
          } catch (_) {}
        }

        _loadShiftData();
        _loadHistory();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res['message'] ?? 'Gagal tutup shift'), backgroundColor: Colors.red),
        );
      }
    } catch (e) {
      setState(() => _isSubmitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    }
  }

  Widget _confirmRow(String label, String value, {bool isBold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 1.5),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontSize: 11, color: Colors.grey.shade700)),
          Text(value, style: TextStyle(fontSize: 11, fontWeight: isBold ? FontWeight.bold : FontWeight.w600, color: Colors.black87)),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text(
          'Rekap Shift & Setor Penjualan',
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
        ),
        backgroundColor: ThemeConfig.primary,
        iconTheme: const IconThemeData(color: Colors.white),
        elevation: 1,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: Colors.white),
            onPressed: () {
              if (_tabController.index == 0) {
                _loadShiftData();
              } else {
                _loadHistory();
              }
            },
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.amber,
          indicatorWeight: 3,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
          tabs: const [
            Tab(icon: Icon(Icons.access_time_filled_rounded, size: 18), text: 'Shift Berjalan'),
            Tab(icon: Icon(Icons.history_rounded, size: 18), text: 'Histori Shift'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildActiveShiftTab(),
          _buildHistoryTab(),
        ],
      ),
    );
  }

  // ==========================================
  // TAB 1: SHIFT BERJALAN & SETOR
  // ==========================================
  Widget _buildActiveShiftTab() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator(color: ThemeConfig.primary));
    }

    final summary = _shiftData?['summary'] ?? {};
    final shiftNumber = _shiftData?['shift_number'] ?? 1;
    final startTimeFormatted = _shiftData?['start_time_formatted'] ?? _shiftData?['start_time'] ?? '-';
    final duration = _shiftData?['duration'] ?? '-';
    final cashierName = _shiftData?['user']?['name'] ?? 'Kasir';
    final storeName = _shiftData?['outlet_name'] ?? _shiftData?['user']?['store_name'] ?? 'Toko';

    final retailBal = (_shiftData?['cash_retail']?['balance'] as num?)?.toDouble() ?? 0.0;
    final retailRec = (_shiftData?['cash_retail']?['recommended_deposit'] as num?)?.toDouble() ?? 0.0;
    final multiSales = (_shiftData?['cash_multi']?['shift_sales'] as num?)?.toDouble() ?? 0.0;
    final multiProfit = (_shiftData?['cash_multi']?['shift_profit'] as num?)?.toDouble() ?? 0.0;
    final multiCount = _shiftData?['cash_multi']?['shift_count'] ?? 0;
    final trfBal = (_shiftData?['cash_transfer']?['balance'] as num?)?.toDouble() ?? 0.0;
    final trfIn = (_shiftData?['cash_transfer']?['shift_transfers_in'] as num?)?.toDouble() ?? 0.0;
    final trfFee = (_shiftData?['cash_transfer']?['shift_transfers_fee'] as num?)?.toDouble() ?? 0.0;

    return RefreshIndicator(
      onRefresh: _loadShiftData,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            
            // Outlet Selector (For Admin / Superadmin)
            if (_isAdmin && _outlets.isNotEmpty) ...[
              Container(
                margin: const EdgeInsets.only(bottom: 14),
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.grey.shade300),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.storefront_outlined, color: ThemeConfig.primary, size: 20),
                    const SizedBox(width: 8),
                    const Text('Pilih Cabang:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.black87)),
                    const SizedBox(width: 10),
                    Expanded(
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<int?>(
                          value: (_selectedOutletId == null || _outlets.any((o) => o['id'] == _selectedOutletId))
                              ? _selectedOutletId
                              : null,
                          isExpanded: true,
                          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: ThemeConfig.primary),
                          items: [
                            const DropdownMenuItem<int?>(value: null, child: Text('Semua Cabang / Pusat')),
                            ..._outlets.map((o) => DropdownMenuItem<int?>(
                                  value: o['id'],
                                  child: Text(o['name'] ?? 'Cabang ${o['id']}'),
                                )),
                          ],
                          onChanged: (val) {
                            setState(() {
                              _selectedOutletId = val;
                              _historyList = [];
                            });
                            _loadShiftData();
                          },
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],

            // 1. Shift Status Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.grey.shade200),
                boxShadow: [
                  BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 8, offset: const Offset(0, 2)),
                ],
              ),
              child: Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: ThemeConfig.primary.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.schedule_rounded, color: ThemeConfig.primary, size: 24),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                              decoration: BoxDecoration(
                                color: Colors.green.shade100,
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                'Shift #$shiftNumber Aktif',
                                style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.green.shade900),
                              ),
                            ),
                            const Spacer(),
                            Text(storeName, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.blueGrey)),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Mulai: $startTimeFormatted ($duration)',
                          style: const TextStyle(fontSize: 11, color: Colors.black87),
                        ),
                        Text(
                          'Petugas Kasir: $cashierName',
                          style: TextStyle(fontSize: 10, color: Colors.grey.shade600),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),

            // 2. 3 Kantong Kas Cards
            const Text(
              'Posisi Kas Fisik & Saldo (3 Kantong)',
              style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
            ),
            const SizedBox(height: 8),

            // Card 1: Cash Retail
            _buildCashCard(
              title: '1. Cash Retail (Laci Toko)',
              code: '1-1110',
              icon: '💵',
              balance: retailBal,
              bgColor: const Color(0xFFF0FDF4),
              borderColor: const Color(0xFFBBF7D0),
              textColor: const Color(0xFF166534),
              footer: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Modal Wajib: Rp 400.000', style: TextStyle(fontSize: 11, color: Colors.black54)),
                  Text('Rekomendasi Setor: ${currencyFormatter.format(retailRec)}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF15803D))),
                ],
              ),
            ),
            const SizedBox(height: 8),

            // Card 2: Cash Multi
            _buildCashCard(
              title: '2. Cash Multi (Pulsa & PPOB)',
              code: '1-1131',
              icon: '📱',
              balance: multiSales,
              balanceLabel: 'Penjualan Digital Shift Ini',
              bgColor: const Color(0xFFF0F9FF),
              borderColor: const Color(0xFFBAE6FD),
              textColor: const Color(0xFF075985),
              footer: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('$multiCount Trx Sukses', style: const TextStyle(fontSize: 11, color: Colors.black54)),
                  Text('Margin Laba: +${currencyFormatter.format(multiProfit)}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF0284C7))),
                ],
              ),
            ),
            const SizedBox(height: 8),

            // Card 3: Cash Transfer
            _buildCashCard(
              title: '3. Cash Transfer (Agen & Tarik)',
              code: '1-1111',
              icon: '🔁',
              balance: trfBal,
              bgColor: const Color(0xFFFAF5FF),
              borderColor: const Color(0xFFE9D5FF),
              textColor: const Color(0xFF6B21A8),
              footer: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('Transfer Masuk: ${currencyFormatter.format(trfIn)}', style: const TextStyle(fontSize: 11, color: Colors.black54)),
                  Text('Fee: +${currencyFormatter.format(trfFee)}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF7E22CE))),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // 3. Operational Metrics
            const Text(
              'Rincian Operasional Shift Ini',
              style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
            ),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.grey.shade200),
              ),
              child: Column(
                children: [
                  _buildMetricRow('Penjualan Tunai Retail', summary['cash_sales'] ?? 0, Icons.payments_outlined, Colors.green.shade700),
                  const Divider(height: 12),
                  _buildMetricRow('Non-Tunai (TF / QRIS)', summary['non_cash_sales'] ?? 0, Icons.qr_code_2_outlined, Colors.blue.shade700),
                  const Divider(height: 12),
                  _buildMetricRow('Penjualan Tempo (Piutang)', summary['receivable_sales'] ?? 0, Icons.receipt_long_outlined, Colors.orange.shade800),
                  const Divider(height: 12),
                  _buildMetricRow('Margin Laba Produk Multi', summary['total_digital_profit'] ?? 0, Icons.phone_android_outlined, Colors.teal.shade700, isProfit: true),
                  const Divider(height: 12),
                  _buildMetricRow('Fee Jasa Transfer Agen', summary['total_transfer_fee'] ?? 0, Icons.send_to_mobile_outlined, Colors.indigo.shade700, isProfit: true),
                  const Divider(height: 12),
                  _buildMetricRow('Biaya Kas Keluar (Beban)', summary['total_expense'] ?? 0, Icons.shopping_bag_outlined, Colors.red.shade700, isNegative: true),
                  const Divider(height: 16, thickness: 1.2),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Total Transaksi Nota:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                      Text('${summary['total_transactions'] ?? 0} Transaksi', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: ThemeConfig.primary)),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),

            // 4. Setor Uang & Ganti Shift Box
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: Colors.green.shade300, width: 1.5),
                boxShadow: [
                  BoxShadow(color: Colors.green.shade100.withOpacity(0.5), blurRadius: 10, offset: const Offset(0, 3)),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: const [
                          Icon(Icons.account_balance_wallet_rounded, color: ThemeConfig.primary, size: 20),
                          SizedBox(width: 8),
                          Text('Setor & Ganti Shift', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                        ],
                      ),
                      TextButton.icon(
                        onPressed: _applyQuickPreset,
                        icon: const Icon(Icons.bolt_rounded, size: 14, color: Colors.amber),
                        label: const Text('Preset Standar', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                        style: TextButton.styleFrom(padding: EdgeInsets.zero, visualDensity: VisualDensity.compact),
                      ),
                    ],
                  ),
                  const Text(
                    'Input nominal yang disetor untuk masing-masing kas. Uang disetor ke brankas pusat.',
                    style: TextStyle(fontSize: 11, color: Colors.blueGrey),
                  ),
                  const SizedBox(height: 14),

                  // 1. Retail
                  _buildDepositInput(
                    label: '💵 Setor Cash Retail (Rp):',
                    controller: _depositControllerSelect(1),
                    hint: 'Disetor ke brankas',
                  ),
                  const SizedBox(height: 10),

                  // 2. Multi
                  _buildDepositInput(
                    label: '📱 Setor Cash Multi (Rp):',
                    controller: _depositControllerSelect(2),
                    hint: 'Disetor ke brankas',
                  ),
                  const SizedBox(height: 10),

                  // 3. Transfer
                  _buildDepositInput(
                    label: '🔁 Setor Cash Transfer (Rp):',
                    controller: _depositControllerSelect(3),
                    hint: 'Disetor ke brankas',
                  ),
                  const SizedBox(height: 14),

                  // Total Setoran Box
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0F172A),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('TOTAL DISETOR:', style: TextStyle(color: Colors.white70, fontSize: 10, fontWeight: FontWeight.bold)),
                            Text(currencyFormatter.format(_totalDeposit), style: const TextStyle(color: Color(0xFF34D399), fontSize: 18, fontWeight: FontWeight.bold)),
                          ],
                        ),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: const [
                            Text('Shift di-reset ke 0', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                            Text('untuk kasir berikutnya', style: TextStyle(color: Colors.white54, fontSize: 9)),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Notes
                  TextField(
                    controller: _notesController,
                    style: const TextStyle(fontSize: 12),
                    decoration: InputDecoration(
                      labelText: 'Catatan Shift (Opsional)',
                      hintText: 'Contoh: Operasional shift lancar, kas klop.',
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                  const SizedBox(height: 14),

                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: ElevatedButton.icon(
                      onPressed: _isSubmitting ? null : _submitCloseShift,
                      icon: const Icon(Icons.check_circle_rounded, color: Colors.amber, size: 18),
                      label: _isSubmitting
                          ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : const Text('SETOR & GANTI SHIFT (RESET KE 0)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: ThemeConfig.primary,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
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

  TextEditingController _depositControllerSelect(int type) {
    if (type == 1) return _retailDepositController;
    if (type == 2) return _multiDepositController;
    return _transferDepositController;
  }

  Widget _buildDepositInput({required String label, required TextEditingController controller, required String hint}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Colors.black87)),
        const SizedBox(height: 4),
        TextField(
          controller: controller,
          keyboardType: TextInputType.number,
          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold),
          decoration: InputDecoration(
            prefixText: 'Rp ',
            hintText: hint,
            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
          ),
        ),
      ],
    );
  }

  Widget _buildCashCard({
    required String title,
    required String code,
    required String icon,
    required double balance,
    required Color bgColor,
    required Color borderColor,
    required Color textColor,
    required Widget footer,
    String balanceLabel = 'Saldo di Laci',
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: borderColor),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 6, offset: const Offset(0, 2)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Text(icon, style: const TextStyle(fontSize: 16)),
                  const SizedBox(width: 6),
                  Text(title, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.black87)),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(color: bgColor, borderRadius: BorderRadius.circular(6)),
                child: Text(code, style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: textColor)),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(balanceLabel, style: TextStyle(fontSize: 10, color: Colors.grey.shade600)),
          Text(currencyFormatter.format(balance), style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: textColor)),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(color: bgColor, borderRadius: BorderRadius.circular(10)),
            child: footer,
          ),
        ],
      ),
    );
  }

  Widget _buildMetricRow(String title, dynamic value, IconData icon, Color color, {bool isNegative = false, bool isProfit = false}) {
    final double amount = Formatters.parseDouble(value);
    return Row(
      children: [
        Icon(icon, size: 16, color: color),
        const SizedBox(width: 8),
        Expanded(child: Text(title, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600))),
        Text(
          (isNegative ? '- ' : (isProfit ? '+ ' : '')) + currencyFormatter.format(amount),
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.bold,
            color: isNegative ? Colors.red.shade700 : (isProfit ? Colors.green.shade700 : Colors.black87),
          ),
        ),
      ],
    );
  }

  // ==========================================
  // TAB 2: HISTORI SHIFT
  // ==========================================
  Widget _buildHistoryTab() {
    if (_isLoadingHistory) {
      return const Center(child: CircularProgressIndicator(color: ThemeConfig.primary));
    }

    return Column(
      children: [
        if (_isAdmin && _outlets.isNotEmpty)
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.grey.shade300),
              ),
              child: Row(
                children: [
                  const Icon(Icons.storefront_outlined, color: ThemeConfig.primary, size: 20),
                  const SizedBox(width: 8),
                  const Text('Filter Cabang:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.black87)),
                  const SizedBox(width: 10),
                  Expanded(
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<int?>(
                        value: (_selectedOutletId == null || _outlets.any((o) => o['id'] == _selectedOutletId))
                            ? _selectedOutletId
                            : null,
                        isExpanded: true,
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: ThemeConfig.primary),
                        items: [
                          const DropdownMenuItem<int?>(value: null, child: Text('Semua Cabang / Pusat')),
                          ..._outlets.map((o) => DropdownMenuItem<int?>(
                                value: o['id'],
                                child: Text(o['name'] ?? 'Cabang ${o['id']}'),
                              )),
                        ],
                        onChanged: (val) {
                          setState(() {
                            _selectedOutletId = val;
                          });
                          _loadHistory();
                        },
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        Expanded(
          child: _historyList.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.history_toggle_off_rounded, size: 54, color: Colors.grey.shade400),
                      const SizedBox(height: 12),
                      const Text('Belum ada histori tutup shift', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                      const SizedBox(height: 4),
                      Text('Riwayat pergantian shift akan tersimpan di sini.', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadHistory,
                  child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _historyList.length,
        separatorBuilder: (_, __) => const SizedBox(height: 12),
        itemBuilder: (context, idx) {
          final item = _historyList[idx] as Map<String, dynamic>;
          final outletName = item['outlet']?['name'] ?? 'Pusat';
          final cashier = item['user']?['name'] ?? 'Kasir';
          final endTimeStr = item['end_time'] != null ? DateFormat('dd/MM/yyyy HH:mm').format(DateTime.parse(item['end_time'])) : '-';
          final totalDep = Formatters.parseDouble(item['total_deposited'] ?? 0);
          final retDep = Formatters.parseDouble(item['cash_retail_deposited'] ?? 0);
          final mltDep = Formatters.parseDouble(item['cash_multi_deposited'] ?? 0);
          final trfDep = Formatters.parseDouble(item['cash_transfer_deposited'] ?? 0);
          final retRet = Formatters.parseDouble(item['cash_retail_retained'] ?? 0);

          return Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.grey.shade200),
              boxShadow: [
                BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 6, offset: const Offset(0, 2)),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(endTimeStr, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(color: Colors.blueGrey.shade100, borderRadius: BorderRadius.circular(6)),
                      child: Text(outletName, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.blueGrey)),
                    ),
                  ],
                ),
                const SizedBox(height: 2),
                Text('Kasir: $cashier', style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
                const Divider(height: 16),

                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Total Uang Disetor:', style: TextStyle(fontSize: 10, color: Colors.grey)),
                        Text(currencyFormatter.format(totalDep), style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: ThemeConfig.primary)),
                      ],
                    ),
                    ElevatedButton.icon(
                      onPressed: () => _printHistoryItem(item),
                      icon: const Icon(Icons.print, size: 14),
                      label: const Text('Cetak Struk', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.green.shade50,
                        foregroundColor: ThemeConfig.primary,
                        elevation: 0,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),

                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(color: Colors.blueGrey.shade50, borderRadius: BorderRadius.circular(8)),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _miniStat('Retail', currencyFormatter.format(retDep)),
                      _miniStat('Multi', currencyFormatter.format(mltDep)),
                      _miniStat('Transfer', currencyFormatter.format(trfDep)),
                      _miniStat('Sisa Modal', currencyFormatter.format(retRet)),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
      ),
    ),
  ),
],
);
}

  Widget _miniStat(String label, String value) {
    return Column(
      children: [
        Text(label, style: const TextStyle(fontSize: 9, color: Colors.grey)),
        Text(value, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.black87)),
      ],
    );
  }

  void _printHistoryItem(Map<String, dynamic> item) async {
    try {
      await PrinterService.printShiftReceipt(shiftData: item);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal mencetak struk: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }
}
