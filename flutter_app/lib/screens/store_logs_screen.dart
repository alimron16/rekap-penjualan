import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/printer_service.dart';
import '../utils/formatters.dart';
import '../utils/theme_config.dart';

class StoreLogsScreen extends StatefulWidget {
  final int initialTabIndex;

  const StoreLogsScreen({super.key, this.initialTabIndex = 0});

  @override
  State<StoreLogsScreen> createState() => _StoreLogsScreenState();
}

class _StoreLogsScreenState extends State<StoreLogsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoading = true;

  DateTime? _startDate;
  DateTime? _endDate;

  List<dynamic> _withdrawals = [];
  List<dynamic> _digitalSales = [];
  List<dynamic> _stockIn = [];
  List<dynamic> _stockOut = [];
  List<dynamic> _returns = [];

  final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);
  final dateFormatter = DateFormat('yyyy-MM-dd');
  final displayDateFormatter = DateFormat('d MMM yyyy');

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 5, vsync: this, initialIndex: widget.initialTabIndex);
    _loadLogs();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _loadLogs() async {
    setState(() => _isLoading = true);
    try {
      final s = _startDate != null ? dateFormatter.format(_startDate!) : null;
      final e = _endDate != null ? dateFormatter.format(_endDate!) : null;

      final res = await ApiService.getUnifiedLogs(startDate: s, endDate: e);
      if (mounted) {
        setState(() {
          if (res['success'] == true) {
            _withdrawals = res['withdrawals'] ?? [];
            _digitalSales = res['digital_sales'] ?? [];
            _stockIn = res['stock_in'] ?? [];
            _stockOut = res['stock_out'] ?? [];
            _returns = res['returns'] ?? [];
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
      _loadLogs();
    }
  }

  void _clearDateFilter() {
    setState(() {
      _startDate = null;
      _endDate = null;
    });
    _loadLogs();
  }

  @override
  Widget build(BuildContext context) {
    final hasDateFilter = _startDate != null && _endDate != null;

    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        title: const Text(
          'Histori Operasional Toko',
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
            onPressed: _loadLogs,
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(48),
          child: Container(
            color: const Color(0xFF0F3216),
            child: TabBar(
              controller: _tabController,
              isScrollable: true,
              tabAlignment: TabAlignment.start,
              indicatorColor: Colors.amberAccent,
              indicatorWeight: 3,
              labelColor: Colors.white,
              unselectedLabelColor: Colors.white70,
              labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
              unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12),
              tabs: const [
                Tab(
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.payments_outlined, size: 16),
                      SizedBox(width: 6),
                      Text('TARIK TUNAI'),
                    ],
                  ),
                ),
                Tab(
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.input_outlined, size: 16),
                      SizedBox(width: 6),
                      Text('BARANG MASUK'),
                    ],
                  ),
                ),
                Tab(
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.output_outlined, size: 16),
                      SizedBox(width: 6),
                      Text('BARANG KELUAR'),
                    ],
                  ),
                ),
                Tab(
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.assignment_return_outlined, size: 16),
                      SizedBox(width: 6),
                      Text('RETUR PENJUALAN'),
                    ],
                  ),
                ),
                Tab(
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.phone_android_outlined, size: 16),
                      SizedBox(width: 6),
                      Text('PRODUK MULTI'),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
      body: Column(
        children: [
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

          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: ThemeConfig.primary))
                : TabBarView(
                    controller: _tabController,
                    children: [
                      _buildLogList(_withdrawals, 'Tarik Tunai', Icons.payments_outlined, Colors.amber.shade800),
                      _buildLogList(_stockIn, 'Barang Masuk (Kulakan)', Icons.input_outlined, Colors.green.shade800),
                      _buildLogList(_stockOut, 'Barang Keluar (Penjualan)', Icons.output_outlined, Colors.blue.shade800),
                      _buildLogList(_returns, 'Retur Penjualan', Icons.assignment_return_outlined, Colors.red.shade800),
                      _buildDigitalLogList(_digitalSales),
                    ],
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildLogList(List<dynamic> items, String emptyLabel, IconData icon, Color badgeColor) {
    if (items.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 54, color: Colors.grey.shade400),
            const SizedBox(height: 12),
            Text('Belum ada data $emptyLabel.', style: TextStyle(fontSize: 13, color: Colors.grey.shade600, fontWeight: FontWeight.w500)),
          ],
        ),
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(14),
      itemCount: items.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, index) {
        final item = items[index];
        final amount = Formatters.parseDouble(item['amount']);
        final fee = Formatters.parseDouble(item['fee']);
        final title = item['title'] ?? '-';
        final number = item['number'] ?? '';
        final date = item['date'] ?? '';
        final badge = item['badge'] ?? '';

        return Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.grey.shade200),
            boxShadow: [
              BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 4, offset: const Offset(0, 2)),
            ],
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: badgeColor.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, color: badgeColor, size: 18),
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
                            color: badgeColor.withOpacity(0.15),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            badge,
                            style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: badgeColor),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(date, style: TextStyle(fontSize: 10, color: Colors.grey.shade500)),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    const SizedBox(height: 4),
                    if (number.isNotEmpty)
                      Text(number, style: const TextStyle(fontFamily: 'monospace', fontSize: 10, color: Colors.blueGrey)),
                    if (fee > 0) ...[
                      const SizedBox(height: 2),
                      Text('Admin Fee: ${currencyFormatter.format(fee)}', style: TextStyle(fontSize: 10, color: Colors.green.shade800, fontWeight: FontWeight.w600)),
                    ],
                  ],
                ),
              ),
              Text(
                currencyFormatter.format(amount),
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
                  color: badgeColor,
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildDigitalLogList(List<dynamic> items) {
    if (items.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.phone_android_outlined, size: 54, color: Colors.grey.shade400),
            const SizedBox(height: 12),
            Text(
              'Belum ada riwayat transaksi produk multi.',
              style: TextStyle(fontSize: 13, color: Colors.grey.shade600, fontWeight: FontWeight.w500),
            ),
          ],
        ),
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(14),
      itemCount: items.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, index) {
        final item = items[index];
        final sellingPrice = Formatters.parseDouble(item['selling_price'] ?? item['amount']);
        final profitMargin = Formatters.parseDouble(item['profit_margin']);
        final title = item['title'] ?? item['product_name'] ?? 'Pulsa / Paket Data';
        final customerNumber = item['customer_number'] ?? '';
        final number = item['number'] ?? item['transaction_number'] ?? '';
        final date = item['date'] ?? '';
        final status = (item['status'] ?? 'SUKSES').toString().toUpperCase();
        final isSuccess = status == 'SUKSES' || status == 'SUCCESS';

        const badgeColor = Color(0xFF0F766E);

        return Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.grey.shade200),
            boxShadow: [
              BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 4, offset: const Offset(0, 2)),
            ],
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: badgeColor.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Icons.phone_android_outlined, color: badgeColor, size: 18),
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
                            color: badgeColor.withOpacity(0.15),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: const Text(
                            'PRODUK MULTI',
                            style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: badgeColor),
                          ),
                        ),
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: isSuccess ? Colors.green.shade50 : Colors.red.shade50,
                            borderRadius: BorderRadius.circular(4),
                            border: Border.all(color: isSuccess ? Colors.green.shade300 : Colors.red.shade300, width: 0.5),
                          ),
                          child: Text(
                            status,
                            style: TextStyle(
                              fontSize: 9,
                              fontWeight: FontWeight.bold,
                              color: isSuccess ? Colors.green.shade800 : Colors.red.shade800,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            date,
                            style: TextStyle(fontSize: 10, color: Colors.grey.shade500),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    const SizedBox(height: 4),
                    if (customerNumber.isNotEmpty)
                      Text(
                        'Tujuan: $customerNumber',
                        style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 11, color: Colors.black87),
                      ),
                    if (number.isNotEmpty)
                      Text(number, style: const TextStyle(fontFamily: 'monospace', fontSize: 10, color: Colors.blueGrey)),
                    if (profitMargin > 0) ...[
                      const SizedBox(height: 2),
                      Text(
                        'Laba: ${currencyFormatter.format(profitMargin)}',
                        style: TextStyle(fontSize: 10, color: Colors.green.shade800, fontWeight: FontWeight.w600),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    currencyFormatter.format(sellingPrice),
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                      color: badgeColor,
                    ),
                  ),
                  const SizedBox(height: 8),
                  InkWell(
                    onTap: () async {
                      try {
                        await PrinterService.printDigitalReceipt(digitalSale: item);
                      } catch (e) {
                        if (context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text('Gagal mencetak struk: $e'), backgroundColor: Colors.red),
                          );
                        }
                      }
                    },
                    borderRadius: BorderRadius.circular(6),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.grey.shade100,
                        border: Border.all(color: Colors.grey.shade300),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: const [
                          Icon(Icons.print_outlined, size: 14, color: Colors.black87),
                          SizedBox(width: 4),
                          Text('Struk', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.black87)),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }
}
