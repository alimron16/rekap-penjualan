import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/theme_config.dart';

class CashScreen extends StatefulWidget {
  final String initialType; // 'in' or 'out'

  const CashScreen({super.key, this.initialType = 'out'});

  @override
  State<CashScreen> createState() => _CashScreenState();
}

class _CashScreenState extends State<CashScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<dynamic> _transactions = [];
  List<dynamic> _cashAccounts = [];
  List<dynamic> _expenseAccounts = [];
  List<dynamic> _incomeAccounts = [];
  bool _isLoading = true;

  final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  // Quick Expense Presets for Toko / Kasir
  final List<Map<String, dynamic>> _quickExpenses = [
    {'title': 'Uang Makan Kasir', 'code': '6-2300', 'desc': 'Uang makan kasir / karyawan toko', 'icon': Icons.restaurant},
    {'title': 'Sampah & Kebersihan', 'code': '6-2400', 'desc': 'Iuran sampah & kebersihan toko', 'icon': Icons.cleaning_services},
    {'title': 'Plastik / ATK Toko', 'code': '6-2200', 'desc': 'Beli kantong plastik kresek & perlengkapan', 'icon': Icons.shopping_bag_outlined},
    {'title': 'Listrik / Token PLN', 'code': '6-2100', 'desc': 'Beli token listrik toko', 'icon': Icons.bolt},
    {'title': 'Bensin / Operasional', 'code': '6-2500', 'desc': 'Operasional kurir / toko', 'icon': Icons.two_wheeler},
    {'title': 'Lain-lain', 'code': '6-2300', 'desc': 'Biaya operasional lainnya', 'icon': Icons.more_horiz},
  ];

  DateTime? _startDate;
  DateTime? _endDate;
  final dateFormatter = DateFormat('yyyy-MM-dd');
  final displayDateFormatter = DateFormat('d MMM yyyy');

  @override
  void initState() {
    super.initState();
    _tabController = TabController(
      length: 2,
      vsync: this,
      initialIndex: widget.initialType == 'in' ? 0 : 1,
    );
    _tabController.addListener(() {
      if (!_tabController.indexIsChanging) {
        _loadData();
      }
    });
    _loadData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  String get _currentType => _tabController.index == 0 ? 'in' : 'out';

  void _loadData() async {
    setState(() => _isLoading = true);
    try {
      final s = _startDate != null ? dateFormatter.format(_startDate!) : null;
      final e = _endDate != null ? dateFormatter.format(_endDate!) : null;

      final res = await ApiService.getCashTransactions(
        type: _currentType,
        startDate: s,
        endDate: e,
      );
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _transactions = res['data'] ?? [];
            _cashAccounts = res['cash_accounts'] ?? [];
            _expenseAccounts = res['expense_accounts'] ?? [];
            _incomeAccounts = res['income_accounts'] ?? [];
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _selectDateRange() async {
    final now = DateTime.now();
    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2023),
      lastDate: DateTime(now.year + 1),
      initialDateRange: _startDate != null && _endDate != null
          ? DateTimeRange(start: _startDate!, end: _endDate!)
          : DateTimeRange(start: now, end: now),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: ThemeConfig.primary,
              onPrimary: Colors.white,
              onSurface: Colors.black87,
            ),
          ),
          child: child!,
        );
      },
    );

    if (picked != null) {
      setState(() {
        _startDate = picked.start;
        _endDate = picked.end;
      });
      _loadData();
    }
  }

  void _clearDateFilter() {
    setState(() {
      _startDate = null;
      _endDate = null;
    });
    _loadData();
  }

  void _openAddModal({String? defaultExpenseTitle, String? defaultExpenseDesc}) {
    final amountController = TextEditingController();
    final descController = TextEditingController(text: defaultExpenseDesc ?? defaultExpenseTitle ?? '');
    
    // Default source account: Laci Retail (1-1110)
    int? sourceId;
    if (_cashAccounts.isNotEmpty) {
      final defaultCash = _cashAccounts.firstWhere(
        (a) => (a['code'] == '1-1110' || a['code'] == '1-1111'),
        orElse: () => _cashAccounts[0],
      );
      sourceId = defaultCash['id'];
    }

    // Default opposite account
    int? oppositeId;
    if (_currentType == 'out' && _expenseAccounts.isNotEmpty) {
      final defaultExp = _expenseAccounts.firstWhere(
        (a) => a['code'] == '6-2300', // Beban Operasional Lainnya
        orElse: () => _expenseAccounts[0],
      );
      oppositeId = defaultExp['id'];
    } else if (_currentType == 'in' && _incomeAccounts.isNotEmpty) {
      final defaultInc = _incomeAccounts.firstWhere(
        (a) => a['code'] == '3-1000' || a['code'] == '4-2000', // Modal Usaha / Pendapatan Lain
        orElse: () => _incomeAccounts[0],
      );
      oppositeId = defaultInc['id'];
    }

    bool isSubmitting = false;
    final isOut = _currentType == 'out';
    final label = isOut ? 'Kas Keluar' : 'Kas Masuk';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) {
        return Padding(
          padding: EdgeInsets.only(
            left: 20,
            right: 20,
            top: 20,
            bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
          ),
          child: StatefulBuilder(
            builder: (context, setModalState) {
              return SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            Icon(
                              isOut ? Icons.arrow_circle_up_outlined : Icons.arrow_circle_down_outlined,
                              color: isOut ? Colors.red.shade700 : Colors.green.shade700,
                              size: 24,
                            ),
                            const SizedBox(width: 8),
                            Text('Catat $label Baru', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                          ],
                        ),
                        IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                      ],
                    ),
                    const Divider(height: 16),

                    // Quick Chips for Kas Keluar
                    if (isOut) ...[
                      const Text('Pilihan Cepat Pengeluaran:', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Colors.blueGrey)),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: _quickExpenses.map((qe) {
                          final isSelected = descController.text.contains(qe['title']);
                          return ActionChip(
                            avatar: Icon(qe['icon'] as IconData, size: 14, color: isSelected ? Colors.white : Colors.red.shade700),
                            label: Text(qe['title'] as String, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: isSelected ? Colors.white : Colors.black87)),
                            backgroundColor: isSelected ? Colors.red.shade700 : Colors.red.shade50,
                            side: BorderSide(color: isSelected ? Colors.red.shade700 : Colors.red.shade200),
                            onPressed: () {
                              setModalState(() {
                                descController.text = qe['desc'] as String;
                              });
                            },
                          );
                        }).toList(),
                      ),
                      const SizedBox(height: 14),
                    ],

                    // Akun Kas Sumber
                    Text(
                      isOut ? 'Ambil Uang Dari (Kas Sumber) *' : 'Uang Disimpan Ke (Kas Penampung) *',
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12),
                    ),
                    const SizedBox(height: 6),
                    DropdownButtonFormField<int>(
                      value: sourceId,
                      isExpanded: true,
                      decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10)),
                      items: _cashAccounts.map<DropdownMenuItem<int>>((a) {
                        final code = a['code'] ?? '';
                        final name = a['name'] ?? '';
                        return DropdownMenuItem<int>(
                          value: a['id'] as int,
                          child: Text('$code - $name', style: const TextStyle(fontSize: 12)),
                        );
                      }).toList(),
                      onChanged: (val) => setModalState(() => sourceId = val),
                    ),
                    const SizedBox(height: 14),

                    // Kategori Akun Lawan
                    Text(
                      isOut ? 'Kategori Beban / Pengeluaran *' : 'Kategori Sumber Penerimaan / Modal *',
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12),
                    ),
                    const SizedBox(height: 6),
                    DropdownButtonFormField<int>(
                      value: oppositeId,
                      isExpanded: true,
                      decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10)),
                      items: (isOut ? _expenseAccounts : _incomeAccounts).map<DropdownMenuItem<int>>((a) {
                        final code = a['code'] ?? '';
                        final name = a['name'] ?? '';
                        return DropdownMenuItem<int>(
                          value: a['id'] as int,
                          child: Text('$code - $name', style: const TextStyle(fontSize: 12), overflow: TextOverflow.ellipsis),
                        );
                      }).toList(),
                      onChanged: (val) => setModalState(() => oppositeId = val),
                    ),
                    const SizedBox(height: 14),

                    // Nominal
                    const Text('Nominal (Rp) *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                    const SizedBox(height: 6),
                    TextField(
                      controller: amountController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(
                        hintText: 'Contoh: 15000',
                        prefixText: 'Rp ',
                        contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      ),
                    ),
                    const SizedBox(height: 14),

                    // Keterangan Transaksi
                    const Text('Keterangan / Catatan *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                    const SizedBox(height: 6),
                    TextField(
                      controller: descController,
                      decoration: InputDecoration(
                        hintText: isOut ? 'Contoh: Uang makan siang kasir toko' : 'Contoh: Tambah saldo modal toko',
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Submit Button
                    SizedBox(
                      width: double.infinity,
                      height: 46,
                      child: ElevatedButton(
                        onPressed: isSubmitting
                            ? null
                            : () async {
                                final double? amount = double.tryParse(amountController.text.replaceAll('.', '').replaceAll(',', ''));
                                if (amount == null || amount <= 0 || descController.text.trim().isEmpty || sourceId == null) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(content: Text('Harap lengkapi semua kolom dan nominal valid!'), backgroundColor: Colors.red),
                                  );
                                  return;
                                }

                                setModalState(() => isSubmitting = true);

                                try {
                                  final res = await ApiService.storeCashTransaction(
                                    type: _currentType,
                                    accountId: sourceId!,
                                    oppositeAccountId: oppositeId ?? sourceId!,
                                    amount: amount,
                                    description: descController.text.trim(),
                                  );

                                  if (res['success'] == true) {
                                    if (!mounted) return;
                                    Navigator.pop(ctx);
                                    _loadData();

                                    NotificationService.showNotification(
                                      id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                                      title: '💰 $label Berhasil Disimpan',
                                      body: '$label senilai ${currencyFormatter.format(amount)} (${descController.text}) sukses dibukukan.',
                                    );

                                    ScaffoldMessenger.of(context).showSnackBar(
                                      SnackBar(
                                        content: Text('$label berhasil disimpan!'),
                                        backgroundColor: isOut ? Colors.orange.shade800 : ThemeConfig.primary,
                                      ),
                                    );
                                  } else {
                                    setModalState(() => isSubmitting = false);
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      SnackBar(content: Text(res['message'] ?? 'Gagal menyimpan transaksi'), backgroundColor: Colors.red),
                                    );
                                  }
                                } catch (e) {
                                  setModalState(() => isSubmitting = false);
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
                                  );
                                }
                              },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: isOut ? Colors.red.shade700 : ThemeConfig.primary,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        child: isSubmitting
                            ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : Text('Simpan $label', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final isOut = _currentType == 'out';
    final totalAmount = _transactions.fold<double>(0.0, (acc, item) => acc + (double.tryParse(item['amount'].toString()) ?? 0.0));
    final hasDateFilter = _startDate != null && _endDate != null;

    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        title: const Text(
          'Kas Masuk & Kas Keluar',
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
        ),
        backgroundColor: ThemeConfig.primary,
        iconTheme: const IconThemeData(color: Colors.white),
        elevation: 1,
        actions: [
          IconButton(
            icon: Icon(hasDateFilter ? Icons.filter_alt : Icons.filter_alt_outlined, color: hasDateFilter ? Colors.amberAccent : Colors.white),
            tooltip: 'Filter Tanggal',
            onPressed: _selectDateRange,
          ),
          IconButton(
            icon: const Icon(Icons.refresh, color: Colors.white),
            tooltip: 'Muat Ulang',
            onPressed: _loadData,
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(48),
          child: Container(
            color: const Color(0xFF0F3216),
            child: TabBar(
              controller: _tabController,
              indicatorColor: Colors.amberAccent,
              indicatorWeight: 3,
              labelColor: Colors.white,
              unselectedLabelColor: Colors.white70,
              labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
              unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
              tabs: const [
                Tab(
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.arrow_circle_down_outlined, size: 18),
                      SizedBox(width: 8),
                      Text('KAS MASUK'),
                    ],
                  ),
                ),
                Tab(
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.arrow_circle_up_outlined, size: 18),
                      SizedBox(width: 8),
                      Text('KAS KELUAR'),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openAddModal(),
        backgroundColor: isOut ? Colors.red.shade700 : ThemeConfig.primary,
        icon: const Icon(Icons.add, color: Colors.white),
        label: Text(
          isOut ? 'Tambah Kas Keluar' : 'Tambah Kas Masuk',
          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
          : Column(
              children: [
                // Filter indicator banner if active
                if (hasDateFilter)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                    color: Colors.amber.shade50,
                    child: Row(
                      children: [
                        const Icon(Icons.date_range, size: 16, color: Colors.amber),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Periode: ${displayDateFormatter.format(_startDate!)} - ${displayDateFormatter.format(_endDate!)}',
                            style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.amber.shade900),
                          ),
                        ),
                        InkWell(
                          onTap: _clearDateFilter,
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: Colors.amber.shade200,
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: const Text('Reset', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.black87)),
                          ),
                        ),
                      ],
                    ),
                  ),
                // Header Summary Card
                Container(
                  width: double.infinity,
                  margin: const EdgeInsets.all(14),
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: Colors.grey.shade200),
                    boxShadow: [
                      BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 8, offset: const Offset(0, 2)),
                    ],
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: isOut ? Colors.red.shade50 : Colors.green.shade50,
                          shape: BoxShape.circle,
                        ),
                        child: Icon(
                          isOut ? Icons.trending_down : Icons.trending_up,
                          color: isOut ? Colors.red.shade700 : Colors.green.shade700,
                          size: 24,
                        ),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isOut ? 'Total Pengeluaran Kas Tercatat' : 'Total Kas Masuk Tercatat',
                              style: TextStyle(fontSize: 11, color: Colors.blueGrey.shade600, fontWeight: FontWeight.w600),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              currencyFormatter.format(totalAmount),
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.w900,
                                color: isOut ? Colors.red.shade700 : Colors.green.shade800,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),

                // Transactions List
                Expanded(
                  child: _transactions.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                isOut ? Icons.receipt_long_outlined : Icons.account_balance_wallet_outlined,
                                size: 54,
                                color: Colors.grey.shade400,
                              ),
                              const SizedBox(height: 10),
                              Text(
                                isOut ? 'Belum ada riwayat Kas Keluar.' : 'Belum ada riwayat Kas Masuk.',
                                style: TextStyle(fontSize: 13, color: Colors.grey.shade600, fontWeight: FontWeight.w500),
                              ),
                              const SizedBox(height: 6),
                              Text(
                                isOut
                                    ? 'Tekan tombol "Tambah Kas Keluar" untuk catat uang makan, sampah, dll.'
                                    : 'Tekan tombol "Tambah Kas Masuk" untuk catat saldo modal atau penerimaan.',
                                textAlign: TextAlign.center,
                                style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
                              ),
                            ],
                          ),
                        )
                      : ListView.separated(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                          itemCount: _transactions.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 8),
                          itemBuilder: (context, index) {
                            final t = _transactions[index];
                            final amount = double.tryParse(t['amount'].toString()) ?? 0;
                            final desc = t['description'] ?? '-';
                            final date = t['transaction_date'] ?? '-';
                            final number = t['transaction_number'] ?? '';
                            final debitAcc = t['debit_account'];
                            final creditAcc = t['credit_account'];

                            return Container(
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(14),
                                border: Border.all(color: Colors.grey.shade200),
                              ),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Container(
                                    padding: const EdgeInsets.all(8),
                                    decoration: BoxDecoration(
                                      color: isOut ? Colors.red.shade50 : Colors.green.shade50,
                                      borderRadius: BorderRadius.circular(10),
                                    ),
                                    child: Icon(
                                      isOut ? Icons.arrow_upward : Icons.arrow_downward,
                                      size: 16,
                                      color: isOut ? Colors.red.shade700 : Colors.green.shade700,
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(desc, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                        const SizedBox(height: 4),
                                        if (number.isNotEmpty)
                                          Text(number, style: const TextStyle(fontFamily: 'monospace', fontSize: 10, color: Colors.blueGrey)),
                                        const SizedBox(height: 2),
                                        if (isOut && debitAcc != null)
                                          Text('Pos: ${debitAcc['name']}', style: TextStyle(fontSize: 10, color: Colors.grey.shade600))
                                        else if (!isOut && creditAcc != null)
                                          Text('Sumber: ${creditAcc['name']}', style: TextStyle(fontSize: 10, color: Colors.grey.shade600)),
                                        const SizedBox(height: 2),
                                        Text(date, style: TextStyle(fontSize: 10, color: Colors.grey.shade500)),
                                      ],
                                    ),
                                  ),
                                  Text(
                                    (isOut ? '- ' : '+ ') + currencyFormatter.format(amount),
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 14,
                                      color: isOut ? Colors.red.shade700 : Colors.green.shade700,
                                    ),
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                ),
              ],
            ),
    );
  }
}
