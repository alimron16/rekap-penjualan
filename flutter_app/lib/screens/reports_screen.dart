import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';
import '../utils/formatters.dart';
import '../utils/theme_config.dart';


class ReportsScreen extends StatefulWidget {
  final int initialTabIndex;
  const ReportsScreen({super.key, this.initialTabIndex = 0});

  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  String _startDate = DateFormat('yyyy-MM-01').format(DateTime.now());
  String _endDate = DateFormat('yyyy-MM-dd').format(DateTime.now());

  // Data maps
  Map<String, dynamic>? _plData;
  Map<String, dynamic>? _bsData;
  Map<String, dynamic>? _salesData;
  Map<String, dynamic>? _purchasesData;
  Map<String, dynamic>? _cashData;
  Map<String, dynamic>? _debtsData;

  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 6, vsync: this, initialIndex: widget.initialTabIndex);
    _tabController.addListener(() {
      if (!_tabController.indexIsChanging) {
        _loadCurrentTabData();
      }
    });
    _loadCurrentTabData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _loadCurrentTabData() async {
    setState(() => _isLoading = true);
    final idx = _tabController.index;

    try {
      if (idx == 0) {
        final res = await ApiService.getProfitLossReport(startDate: _startDate, endDate: _endDate);
        if (mounted && res['success'] == true) _plData = res['data'];
      } else if (idx == 1) {
        final res = await ApiService.getBalanceSheetReport(asOfDate: _endDate);
        if (mounted && res['success'] == true) _bsData = res['data'];
      } else if (idx == 2) {
        final res = await ApiService.getSalesReport(startDate: _startDate, endDate: _endDate);
        if (mounted && res['success'] == true) _salesData = res;
      } else if (idx == 3) {
        final res = await ApiService.getPurchasesReport(startDate: _startDate, endDate: _endDate);
        if (mounted && res['success'] == true) _purchasesData = res;
      } else if (idx == 4) {
        final res = await ApiService.getCashReport(startDate: _startDate, endDate: _endDate);
        if (mounted && res['success'] == true) _cashData = res;
      } else if (idx == 5) {
        final res = await ApiService.getDebtsReceivablesReport();
        if (mounted && res['success'] == true) _debtsData = res;
      }
    } catch (_) {}

    if (mounted) setState(() => _isLoading = false);
  }

  void _pickDateRange() async {
    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime(2030),
      initialDateRange: DateTimeRange(
        start: DateTime.parse(_startDate),
        end: DateTime.parse(_endDate),
      ),
    );

    if (picked != null) {
      setState(() {
        _startDate = DateFormat('yyyy-MM-dd').format(picked.start);
        _endDate = DateFormat('yyyy-MM-dd').format(picked.end);
      });
      _loadCurrentTabData();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        title: const Text('Laporan & Analitik Keuangan', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
        backgroundColor: ThemeConfig.primary,
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          indicatorColor: ThemeConfig.accent,
          indicatorWeight: 3,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
          tabs: const [
            Tab(text: 'Laba Rugi'),
            Tab(text: 'Neraca'),
            Tab(text: 'Penjualan'),
            Tab(text: 'Pembelian'),
            Tab(text: 'Kas & Bank'),
            Tab(text: 'Hutang / Piutang'),
          ],
        ),
        actions: [
          IconButton(icon: const Icon(Icons.date_range), onPressed: _pickDateRange, tooltip: 'Pilih Periode Tanggal'),
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadCurrentTabData, tooltip: 'Segarkan Data'),
        ],
      ),
      body: Column(
        children: [
          // Filter Period Bar
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            color: Colors.white,
            child: Row(
              children: [
                const Icon(Icons.calendar_month, size: 16, color: ThemeConfig.primary),
                const SizedBox(width: 8),
                Text(
                  'Periode: $_startDate s/d $_endDate',
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: ThemeConfig.textDark),
                ),
                const Spacer(),
                InkWell(
                  onTap: _pickDateRange,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: ThemeConfig.primary.withOpacity(0.08),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: const Text('Ubah Tanggal', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ThemeConfig.primary)),
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
                      _buildProfitLossTab(),
                      _buildBalanceSheetTab(),
                      _buildSalesTab(),
                      _buildPurchasesTab(),
                      _buildCashTab(),
                      _buildDebtsReceivablesTab(),
                    ],
                  ),
          ),
        ],
      ),
    );
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

  // 1. LABA RUGI TAB
  Widget _buildProfitLossTab() {
    try {
      if (_plData == null) {
        return Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.analytics_outlined, size: 48, color: Colors.grey),
              const SizedBox(height: 12),
              const Text('Data Laba Rugi belum dimuat', style: TextStyle(fontSize: 13, color: Colors.grey)),
              const SizedBox(height: 12),
              ElevatedButton.icon(
                onPressed: _loadCurrentTabData,
                icon: const Icon(Icons.refresh, size: 16),
                label: const Text('Muat Ulang'),
              ),
            ],
          ),
        );
      }

      final netProfit = Formatters.parseDouble(_plData?['net_profit']);
      final revenues = _plData?['revenues'] is Map ? _plData!['revenues'] as Map<String, dynamic> : {};
      final hpp = _plData?['hpp'] is Map ? _plData!['hpp'] as Map<String, dynamic> : {};
      final expenses = _plData?['expenses'] is Map ? _plData!['expenses'] as Map<String, dynamic> : {};
      
      Map<String, dynamic> breakdown = {};
      if (expenses['breakdown'] is Map) {
        breakdown = Map<String, dynamic>.from(expenses['breakdown']);
      }

      return SingleChildScrollView(
        padding: const EdgeInsets.all(12),
        child: Column(
          children: [
            _buildCard(
              title: 'PENDAPATAN USAHA (REVENUE)',
              icon: Icons.trending_up,
              color: Colors.green,
              child: Column(
                children: [
                  _buildRow('Penjualan Retail (Eceran)', revenues['retail']),
                  _buildRow('Penjualan Grosir (Partai)', revenues['grosir']),
                  _buildRow('Produk Multi / Elektrik', revenues['multi']),
                  _buildRow('Jasa Transfer Agen & Bank', revenues['jasa_transfer']),
                  const Divider(height: 16),
                  _buildRow('TOTAL OMZET BRUTO', revenues['total'], isBold: true),
                ],
              ),
            ),
            const SizedBox(height: 12),
            _buildCard(
              title: 'HARGA POKOK PENJUALAN (HPP)',
              icon: Icons.inventory_2_outlined,
              color: Colors.amber.shade800,
              child: Column(
                children: [
                  _buildRow('HPP Retail', hpp['retail']),
                  _buildRow('HPP Grosir', hpp['grosir']),
                  _buildRow('HPP Multi', hpp['multi']),
                  const Divider(height: 16),
                  _buildRow('TOTAL HPP MODAL', hpp['total'], isBold: true),
                ],
              ),
            ),
            const SizedBox(height: 12),
            _buildCard(
              title: 'LABA KOTOR (GROSS PROFIT)',
              icon: Icons.account_balance_wallet,
              color: Colors.teal,
              child: _buildRow('Laba Kotor Penjualan', _plData?['gross_profit'], isBold: true),
            ),
            const SizedBox(height: 12),
            _buildCard(
              title: 'BIAYA OPERASIONAL (EXPENSES)',
              icon: Icons.money_off,
              color: Colors.red,
              child: Column(
                children: [
                  if (breakdown.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 8),
                      child: Text('Tidak ada beban operasional pada periode ini', style: TextStyle(fontSize: 11, color: Colors.grey)),
                    )
                  else
                    ...breakdown.entries.map((e) => _buildRow(e.key, e.value)),
                  const Divider(height: 16),
                  _buildRow('TOTAL BEBAN BIAYA', expenses['total'], isBold: true),
                ],
              ),
            ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: netProfit >= 0 ? Colors.green.shade700 : Colors.red.shade700,
                borderRadius: BorderRadius.circular(14),
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 8)],
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('LABA BERSIH (NET PROFIT)', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
                  Text(
                    Formatters.formatRupiah(netProfit),
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 18),
                  ),
                ],
              ),
            ),
          ],
        ),
      );
    } catch (e) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline, size: 48, color: Colors.red),
              const SizedBox(height: 12),
              const Text('Terjadi kendala saat menampilkan laporan Laba Rugi', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              const SizedBox(height: 6),
              Text('$e', style: const TextStyle(fontSize: 11, color: Colors.grey), textAlign: TextAlign.center),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: _loadCurrentTabData,
                icon: const Icon(Icons.refresh),
                label: const Text('Coba Lagi'),
              ),
            ],
          ),
        ),
      );
    }
  }


  // 2. NERACA TAB
  Widget _buildBalanceSheetTab() {
    if (_bsData == null) return const Center(child: Text('Data tidak tersedia'));
    final aktiva = _bsData?['aktiva'] ?? {};
    final kewajiban = _bsData?['kewajiban'] ?? {};
    final modal = _bsData?['modal'] ?? {};

    return SingleChildScrollView(
      padding: const EdgeInsets.all(12),
      child: Column(
        children: [
          _buildCard(
            title: '1. AKTIVA / ASET LANCAR',
            icon: Icons.account_balance,
            color: Colors.blue.shade800,
            child: Column(
              children: [
                ...((aktiva['accounts'] as List?) ?? []).map((a) => _buildRow('${a['code']} ${a['name']}', a['balance'])),
                const Divider(height: 16),
                _buildRow('TOTAL AKTIVA', aktiva['total'], isBold: true),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _buildCard(
            title: '2. KEWAJIBAN / HUTANG TEMPO',
            icon: Icons.receipt_long,
            color: Colors.red.shade700,
            child: Column(
              children: [
                ...((kewajiban['accounts'] as List?) ?? []).map((a) => _buildRow('${a['code']} ${a['name']}', a['balance'])),
                const Divider(height: 16),
                _buildRow('TOTAL KEWAJIBAN', kewajiban['total'], isBold: true),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _buildCard(
            title: '3. MODAL & EKUITAS PEMILIK',
            icon: Icons.pie_chart,
            color: Colors.purple.shade700,
            child: Column(
              children: [
                ...((modal['accounts'] as List?) ?? []).map((a) => _buildRow('${a['code']} ${a['name']}', a['balance'])),
                const Divider(height: 16),
                _buildRow('TOTAL MODAL', modal['total'], isBold: true),
              ],
            ),
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.blue.shade300),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('TOTAL KEWAJIBAN + MODAL', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                Text(
                  Formatters.formatRupiah(_bsData?['total_kewajiban_modal']),
                  style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 14, color: ThemeConfig.primary),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // 3. SALES TAB
  Widget _buildSalesTab() {
    final list = (_salesData?['data'] as List?) ?? [];
    final summary = _salesData?['summary'] ?? {};

    return Column(
      children: [
        _buildSummaryBar(
          items: [
            {'label': 'Qty Terjual', 'val': '${Formatters.parseInt(summary['total_qty'])} Pcs'},
            {'label': 'Omzet Bruto', 'val': Formatters.formatRupiah(summary['total_subtotal'])},
            {'label': 'Uang Masuk', 'val': Formatters.formatRupiah(summary['total_paid'])},
            {'label': 'Sisa Piutang', 'val': Formatters.formatRupiah(summary['total_receivable'])},
          ],
        ),
        Expanded(
          child: list.isEmpty
              ? const Center(child: Text('Tidak ada histori transaksi penjualan'))
              : ListView.separated(
                  padding: const EdgeInsets.all(12),
                  itemCount: list.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (context, index) {
                    final item = list[index];
                    final date = (item['date'] ?? '').toString().substring(0, 10);
                    final inv = item['invoice_number'] ?? '-';
                    final cust = item['customer']?['name'] ?? 'UMUM';
                    final type = (item['sale_type'] ?? 'retail').toString().toUpperCase();
                    final total = Formatters.parseDouble(item['total']);
                    final paid = Formatters.parseDouble(item['paid_amount']);
                    final receivable = Formatters.parseDouble(item['remaining_receivable']);

                    return Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.shade200),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(inv, style: const TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.bold, fontSize: 12, color: ThemeConfig.primary)),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                decoration: BoxDecoration(
                                  color: type == 'GROSIR' ? Colors.indigo.shade50 : Colors.green.shade50,
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text(type, style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: type == 'GROSIR' ? Colors.indigo.shade900 : Colors.green.shade900)),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Text('Tanggal: $date • Pelanggan: $cust', style: const TextStyle(fontSize: 11, color: Colors.grey)),
                          const Divider(height: 12),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text('Total: ${Formatters.formatRupiah(total)}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                              Text('Dibayar: ${Formatters.formatRupiah(paid)}', style: const TextStyle(fontSize: 11, color: Colors.green, fontWeight: FontWeight.w600)),
                              if (receivable > 0)
                                Text('Piutang: ${Formatters.formatRupiah(receivable)}', style: const TextStyle(fontSize: 11, color: Colors.red, fontWeight: FontWeight.bold)),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.end,
                            children: [
                              OutlinedButton.icon(
                                onPressed: () => _openReceiptUrl('/receipt/thermal/${item['id']}'),
                                icon: const Icon(Icons.receipt_long, size: 14, color: ThemeConfig.primary),
                                label: const Text('Cetak Struk', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: ThemeConfig.primary)),
                                style: OutlinedButton.styleFrom(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                  side: BorderSide(color: ThemeConfig.primary.withOpacity(0.5)),
                                ),
                              ),
                              const SizedBox(width: 8),
                              ElevatedButton.icon(
                                onPressed: () => _openReceiptUrl('/receipt/invoice/${item['id']}'),
                                icon: const Icon(Icons.print, size: 14, color: Colors.white),
                                label: const Text('Cetak Faktur', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: ThemeConfig.accent,
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    );
                  },
                ),
        ),
      ],
    );
  }

  // 4. PURCHASES TAB
  Widget _buildPurchasesTab() {
    final list = (_purchasesData?['data'] as List?) ?? [];
    final summary = _purchasesData?['summary'] ?? {};

    return Column(
      children: [
        _buildSummaryBar(
          items: [
            {'label': 'Qty Beli', 'val': '${Formatters.parseInt(summary['total_qty'])} Pcs'},
            {'label': 'Total Beli', 'val': Formatters.formatRupiah(summary['total_subtotal'])},
            {'label': 'Sudah Dibayar', 'val': Formatters.formatRupiah(summary['total_paid'])},
            {'label': 'Sisa Hutang', 'val': Formatters.formatRupiah(summary['total_debt'])},
          ],
        ),
        Expanded(
          child: list.isEmpty
              ? const Center(child: Text('Tidak ada histori faktur pembelian'))
              : ListView.separated(
                  padding: const EdgeInsets.all(12),
                  itemCount: list.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (context, index) {
                    final item = list[index];
                    final date = (item['date'] ?? '').toString().substring(0, 10);
                    final inv = item['invoice_number'] ?? '-';
                    final supp = item['supplier']?['name'] ?? '-';
                    final total = Formatters.parseDouble(item['total']);
                    final paid = Formatters.parseDouble(item['paid_amount']);
                    final debt = Formatters.parseDouble(item['remaining_debt']);

                    return Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.shade200),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(inv, style: const TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.bold, fontSize: 12, color: ThemeConfig.primary)),
                              Text(date, style: const TextStyle(fontSize: 10, color: Colors.grey)),
                            ],
                          ),
                          const SizedBox(height: 2),
                          Text('Supplier: $supp', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                          const Divider(height: 12),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text('Total: ${Formatters.formatRupiah(total)}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                              Text('Dibayar: ${Formatters.formatRupiah(paid)}', style: const TextStyle(fontSize: 11, color: Colors.green, fontWeight: FontWeight.w600)),
                              if (debt > 0)
                                Text('Hutang: ${Formatters.formatRupiah(debt)}', style: const TextStyle(fontSize: 11, color: Colors.red, fontWeight: FontWeight.bold)),
                            ],
                          ),
                        ],
                      ),
                    );
                  },
                ),
        ),
      ],
    );
  }

  // 5. CASH & BANK TAB
  Widget _buildCashTab() {
    final kasMasuk = (_cashData?['kas_masuk'] as List?) ?? [];
    final kasKeluar = (_cashData?['kas_keluar'] as List?) ?? [];
    final summary = _cashData?['summary'] ?? {};

    return SingleChildScrollView(
      padding: const EdgeInsets.all(12),
      child: Column(
        children: [
          _buildSummaryBar(
            items: [
              {'label': 'Kas Masuk', 'val': Formatters.formatRupiah(summary['total_masuk'])},
              {'label': 'Kas Keluar', 'val': Formatters.formatRupiah(summary['total_keluar'])},
              {'label': 'Transfer', 'val': Formatters.formatRupiah(summary['total_transfer'])},
            ],
          ),
          const SizedBox(height: 12),
          _buildCard(
            title: '1. MUTASI KAS MASUK',
            icon: Icons.arrow_downward,
            color: Colors.green,
            child: Column(
              children: kasMasuk.isEmpty
                  ? [const Text('Tidak ada mutasi kas masuk', style: TextStyle(fontSize: 11, color: Colors.grey))]
                  : kasMasuk.take(15).map((km) {
                      return _buildRow('${km['transaction_number']} • ${km['notes'] ?? '-'}', km['amount']);
                    }).toList(),
            ),
          ),
          const SizedBox(height: 12),
          _buildCard(
            title: '2. MUTASI KAS KELUAR (BEBAN)',
            icon: Icons.arrow_upward,
            color: Colors.red,
            child: Column(
              children: kasKeluar.isEmpty
                  ? [const Text('Tidak ada mutasi kas keluar', style: TextStyle(fontSize: 11, color: Colors.grey))]
                  : kasKeluar.take(15).map((kk) {
                      return _buildRow('${kk['transaction_number']} • ${kk['notes'] ?? '-'}', kk['amount']);
                    }).toList(),
            ),
          ),
        ],
      ),
    );
  }

  // 6. DEBTS & RECEIVABLES TAB
  Widget _buildDebtsReceivablesTab() {
    final debts = (_debtsData?['debts'] as List?) ?? [];
    final receivables = (_debtsData?['receivables'] as List?) ?? [];

    return SingleChildScrollView(
      padding: const EdgeInsets.all(12),
      child: Column(
        children: [
          _buildCard(
            title: 'REKAP PIUTANG PELANGGAN',
            icon: Icons.credit_score,
            color: Colors.blue.shade800,
            child: Column(
              children: [
                _buildRow('TOTAL PIUTANG BELUM LUNAS', _debtsData?['total_receivables'], isBold: true),
                const Divider(height: 16),
                ...receivables.take(15).map((r) {
                  final cust = r['customer']?['name'] ?? 'UMUM';
                  return _buildRow('${r['invoice_number']} ($cust)', r['remaining_receivable']);
                }),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _buildCard(
            title: 'REKAP HUTANG KEPADA SUPPLIER',
            icon: Icons.money_off_csred_rounded,
            color: Colors.red.shade800,
            child: Column(
              children: [
                _buildRow('TOTAL HUTANG BELUM LUNAS', _debtsData?['total_debts'], isBold: true),
                const Divider(height: 16),
                ...debts.take(15).map((d) {
                  final supp = d['supplier']?['name'] ?? '-';
                  return _buildRow('${d['invoice_number']} ($supp)', d['remaining_debt']);
                }),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // REUSABLE WIDGETS
  Widget _buildCard({required String title, required IconData icon, required Color color, required Widget child}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.grey.shade200),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 4, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 16, color: color),
              const SizedBox(width: 8),
              Text(title, style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: color, letterSpacing: 0.3)),
            ],
          ),
          const Divider(height: 16),
          child,
        ],
      ),
    );
  }

  Widget _buildRow(String label, dynamic value, {bool isBold = false}) {
    final double val = Formatters.parseDouble(value);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Expanded(
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                fontWeight: isBold ? FontWeight.bold : FontWeight.normal,
                color: isBold ? ThemeConfig.textDark : Colors.grey.shade700,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
          Text(
            Formatters.formatRupiah(val),
            style: TextStyle(
              fontSize: 12,
              fontWeight: isBold ? FontWeight.w900 : FontWeight.w600,
              color: isBold ? ThemeConfig.primary : ThemeConfig.textDark,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryBar({required List<Map<String, String>> items}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      color: Colors.white,
      child: Row(
        children: items.map((it) {
          return Expanded(
            child: Container(
              margin: const EdgeInsets.symmetric(horizontal: 2),
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: Colors.grey.shade50,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.grey.shade200),
              ),
              child: Column(
                children: [
                  Text(it['label']!, style: const TextStyle(fontSize: 9, color: Colors.grey, fontWeight: FontWeight.bold), maxLines: 1),
                  const SizedBox(height: 2),
                  Text(it['val']!, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w900, color: ThemeConfig.primary), maxLines: 1, overflow: TextOverflow.ellipsis),
                ],
              ),
            ),
          );
        }).toList(),
      ),
    );
  }
}

