import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';
import '../utils/formatters.dart';

class PrinterService {
  static const String keyPaperSize = 'printer_paper_size';
  static const String keyAutoPrint = 'printer_auto_print';
  static const String keyHeaderNote = 'printer_header_note';
  static const String keyFooterNote = 'printer_footer_note';

  static Future<String> getPreferredPaperSize() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(keyPaperSize) ?? '58mm';
  }

  static Future<void> setPreferredPaperSize(String size) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(keyPaperSize, size);
  }

  static Future<bool> isAutoPrintEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(keyAutoPrint) ?? false;
  }

  static Future<void> setAutoPrintEnabled(bool enabled) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(keyAutoPrint, enabled);
  }

  static Uint8List? _cachedLogoBytes;

  /// Clear in-memory cached logo bytes (e.g. after uploading a new logo)
  static void clearCachedLogo() {
    _cachedLogoBytes = null;
  }

  /// Resolve store settings with fallback to ApiService.getSettings()
  static Future<Map<String, dynamic>> _resolveStoreSetting(Map<String, dynamic>? storeSetting) async {
    final map = <String, dynamic>{};
    if (storeSetting != null && storeSetting.isNotEmpty) {
      map.addAll(storeSetting);
    }

    final hasLogo = map['logo_url'] != null && map['logo_url'].toString().trim().isNotEmpty;
    final hasValidName = (map['store_name'] != null && map['store_name'].toString().trim().isNotEmpty) ||
        (map['name'] != null && map['name'].toString().trim().isNotEmpty);

    // If logo_url or store name is missing, always query settings from server
    if (!hasValidName || !hasLogo) {
      try {
        final res = await ApiService.getSettings();
        if (res['success'] == true && res['setting'] is Map) {
          final fetched = Map<String, dynamic>.from(res['setting']);
          for (final entry in fetched.entries) {
            if (map[entry.key] == null || map[entry.key].toString().trim().isEmpty) {
              map[entry.key] = entry.value;
            }
          }
          if (fetched['logo_url'] != null && fetched['logo_url'].toString().trim().isNotEmpty) {
            map['logo_url'] = fetched['logo_url'];
          }
        }
      } catch (e) {
        debugPrint('Error resolving store settings: $e');
      }
    }

    return map;
  }

  /// Download logo bytes or return null with caching and relative-path resolution
  static Future<Uint8List?> _fetchLogoBytes(String? logoUrl) async {
    if (_cachedLogoBytes != null) return _cachedLogoBytes;
    if (logoUrl == null || logoUrl.trim().isEmpty) return null;

    try {
      String resolvedUrl = logoUrl.trim();
      // Handle relative paths from backend
      if (!resolvedUrl.startsWith('http://') && !resolvedUrl.startsWith('https://')) {
        final base = ApiService.baseUrl.replaceAll(RegExp(r'/api/?$'), '');
        final cleanPath = resolvedUrl.startsWith('/') ? resolvedUrl.substring(1) : resolvedUrl;
        resolvedUrl = '$base/$cleanPath';
      }

      final uri = Uri.parse(resolvedUrl);
      final res = await http.get(uri).timeout(const Duration(seconds: 5));
      if (res.statusCode == 200 && res.bodyBytes.isNotEmpty) {
        _cachedLogoBytes = res.bodyBytes;
        return res.bodyBytes;
      }
    } catch (e) {
      debugPrint('Error fetching store logo bytes: $e');
    }
    return null;
  }

  /// Build Store Logo matching Pengaturan Struk
  static pw.Widget _buildPdfLogo(Uint8List? logoBytes) {
    if (logoBytes != null && logoBytes.isNotEmpty) {
      return pw.Container(
        height: 48,
        margin: const pw.EdgeInsets.only(bottom: 3),
        alignment: pw.Alignment.center,
        child: pw.Image(
          pw.MemoryImage(logoBytes),
          height: 48,
          fit: pw.BoxFit.contain,
        ),
      );
    }

    // Clean lightweight store badge (no solid dark green blob)
    return pw.Container(
      width: 34,
      height: 34,
      decoration: pw.BoxDecoration(
        shape: pw.BoxShape.circle,
        color: const PdfColor(0.93, 0.95, 0.96),
        border: pw.Border.all(color: const PdfColor(0.75, 0.82, 0.86), width: 0.8),
      ),
      child: pw.Center(
        child: pw.Text(
          'POS',
          style: pw.TextStyle(
            fontSize: 8,
            fontWeight: pw.FontWeight.bold,
            color: const PdfColor(0.15, 0.25, 0.35),
          ),
        ),
      ),
    );
  }

  /// Print Thermal Receipt directly inside the app using Printing plugin
  static Future<void> printReceipt({
    required Map<String, dynamic> sale,
    Map<String, dynamic>? storeSetting,
    String? paperSize,
  }) async {
    final size = paperSize ?? await getPreferredPaperSize();
    final resolvedSetting = await _resolveStoreSetting(storeSetting);

    final double rollWidth = size == '80mm' ? (80 * PdfPageFormat.mm) : (58 * PdfPageFormat.mm);
    final pageFormat = PdfPageFormat(rollWidth, double.infinity, marginAll: 3 * PdfPageFormat.mm);

    final pdfBytes = await generateReceiptPdf(
      sale: sale,
      storeSetting: resolvedSetting,
      paperSize: size,
    );

    final invoiceNo = sale['invoice_number'] ?? sale['invoice_no'] ?? 'Struk';
    await Printing.layoutPdf(
      name: 'Receipt-$invoiceNo',
      format: pageFormat,
      onLayout: (PdfPageFormat _) async => pdfBytes,
    );
  }

  /// Print Thermal Receipt for Digital / Pulsa / PLN Sales
  static Future<void> printDigitalReceipt({
    required Map<String, dynamic> digitalSale,
    Map<String, dynamic>? storeSetting,
    String? paperSize,
  }) async {
    final size = paperSize ?? await getPreferredPaperSize();
    final resolvedSetting = await _resolveStoreSetting(storeSetting);

    final double rollWidth = size == '80mm' ? (80 * PdfPageFormat.mm) : (58 * PdfPageFormat.mm);
    final pageFormat = PdfPageFormat(rollWidth, double.infinity, marginAll: 3 * PdfPageFormat.mm);

    final pdfBytes = await generateDigitalReceiptPdf(
      digitalSale: digitalSale,
      storeSetting: resolvedSetting,
      paperSize: size,
    );

    final trxNo = digitalSale['transaction_number'] ?? digitalSale['invoice_number'] ?? 'Pulsa';
    await Printing.layoutPdf(
      name: 'Receipt-$trxNo',
      format: pageFormat,
      onLayout: (PdfPageFormat _) async => pdfBytes,
    );
  }

  /// Print Official A4 Sales Invoice
  static Future<void> printInvoice({
    required Map<String, dynamic> sale,
    Map<String, dynamic>? storeSetting,
  }) async {
    final resolvedSetting = await _resolveStoreSetting(storeSetting);
    final pdfBytes = await generateInvoicePdf(sale: sale, storeSetting: resolvedSetting);
    final invoiceNo = sale['invoice_number'] ?? sale['invoice_no'] ?? 'Faktur';
    await Printing.layoutPdf(
      name: 'Faktur-$invoiceNo',
      format: PdfPageFormat.a4,
      onLayout: (PdfPageFormat format) async => pdfBytes,
    );
  }

  /// Generate Receipt PDF bytes for 58mm or 80mm roll paper
  static Future<Uint8List> generateReceiptPdf({
    required Map<String, dynamic> sale,
    Map<String, dynamic>? storeSetting,
    String paperSize = '58mm',
  }) async {
    final pdf = pw.Document();

    final double rollWidth = paperSize == '80mm' ? (80 * PdfPageFormat.mm) : (58 * PdfPageFormat.mm);
    final pageFormat = PdfPageFormat(rollWidth, double.infinity, marginAll: 3 * PdfPageFormat.mm);

    final outlet = (sale['outlet'] is Map) ? sale['outlet'] : null;
    final storeName = (storeSetting?['store_name'] != null && storeSetting!['store_name'].toString().isNotEmpty)
        ? storeSetting['store_name'].toString()
        : ((storeSetting?['name'] != null && storeSetting!['name'].toString().isNotEmpty)
            ? storeSetting['name'].toString()
            : ((outlet?['name'] != null && outlet!['name'].toString().isNotEmpty)
                ? outlet['name'].toString()
                : 'ELEPHANT CELL GROUP'));

    final storeAddress = (storeSetting?['address'] != null && storeSetting!['address'].toString().isNotEmpty)
        ? storeSetting['address'].toString()
        : (outlet?['address']?.toString() ?? '');

    final storePhone = (storeSetting?['phone'] != null && storeSetting!['phone'].toString().isNotEmpty)
        ? storeSetting['phone'].toString()
        : (outlet?['phone']?.toString() ?? '');

    final receiptFooter = (storeSetting?['receipt_footer'] != null && storeSetting!['receipt_footer'].toString().isNotEmpty)
        ? storeSetting['receipt_footer'].toString()
        : 'Terima kasih telah berbelanja!\nBarang yang sudah dibeli tidak dapat ditukar/dikembalikan.';

    // Fetch logo if present
    final logoUrl = storeSetting?['logo_url']?.toString();
    final logoBytes = await _fetchLogoBytes(logoUrl);

    final invoiceNo = sale['invoice_number'] ?? sale['invoice_no'] ?? '-';
    final dateStr = sale['date'] != null ? sale['date'].toString() : DateFormat('dd/MM/yyyy HH:mm').format(DateTime.now());
    final cashierName = sale['user'] != null && sale['user'] is Map
        ? (sale['user']['name'] ?? 'Kasir')
        : (sale['cashier_name'] ?? 'Kasir');
    final customerName = sale['customer'] != null && sale['customer'] is Map
        ? (sale['customer']['name'] ?? 'Umum')
        : (sale['customer_name'] ?? 'Umum');

    final items = (sale['items'] is List) ? (sale['items'] as List) : [];
    final subtotal = Formatters.parseDouble(sale['subtotal']);
    final discount = Formatters.parseDouble(sale['discount']);
    final grandTotal = Formatters.parseDouble(sale['grand_total'] ?? sale['total']);
    final paidAmount = Formatters.parseDouble(sale['paid_amount']);
    final changeAmount = Formatters.parseDouble(sale['change_amount']);
    final paymentMethod = (sale['payment_method'] ?? 'CASH').toString().toUpperCase();

    pdf.addPage(
      pw.Page(
        pageFormat: pageFormat,
        build: (pw.Context ctx) {
          return pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.stretch,
            children: [
              // Logo Avatar matching preview
              pw.Center(
                child: _buildPdfLogo(logoBytes),
              ),
              pw.SizedBox(height: 4),

              // Store Name
              pw.Center(
                child: pw.Text(
                  storeName,
                  style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 10),
                  textAlign: pw.TextAlign.center,
                ),
              ),
              if (storeAddress.isNotEmpty)
                pw.Center(
                  child: pw.Padding(
                    padding: const pw.EdgeInsets.only(top: 1),
                    child: pw.Text(
                      storeAddress,
                      style: const pw.TextStyle(fontSize: 7, color: PdfColors.grey700),
                      textAlign: pw.TextAlign.center,
                    ),
                  ),
                ),
              if (storePhone.isNotEmpty)
                pw.Center(
                  child: pw.Padding(
                    padding: const pw.EdgeInsets.only(top: 1),
                    child: pw.Text(
                      'Telp: $storePhone',
                      style: const pw.TextStyle(fontSize: 7, color: PdfColors.grey700),
                      textAlign: pw.TextAlign.center,
                    ),
                  ),
                ),
              pw.SizedBox(height: 3),
              pw.Divider(thickness: 0.5, color: PdfColors.grey300),

              // Meta rows matching preview
              _receiptRow('No. Nota', invoiceNo),
              _receiptRow('Tanggal', dateStr),
              _receiptRow('Kasir', cashierName),
              if (customerName.toUpperCase() != 'UMUM')
                _receiptRow('Pelanggan', customerName),
              pw.Divider(thickness: 0.5, color: PdfColors.grey300),

              // Item List matching preview
              ...items.map((it) {
                final prodName = it['product'] != null && it['product'] is Map
                    ? (it['product']['name'] ?? 'Item')
                    : (it['product_name'] ?? 'Item');
                final qty = Formatters.parseDouble(it['qty']);
                final price = Formatters.parseDouble(it['price'] ?? it['selling_price']);
                final sub = Formatters.parseDouble(it['subtotal'] ?? (qty * price));

                return pw.Padding(
                  padding: const pw.EdgeInsets.symmetric(vertical: 1),
                  child: pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.start,
                    children: [
                      pw.Text(
                        prodName,
                        style: const pw.TextStyle(fontSize: 7.5),
                      ),
                      pw.Row(
                        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                        children: [
                          pw.Text(
                            '  ${qty.toStringAsFixed(0)} x ${Formatters.formatRupiah(price)}',
                            style: const pw.TextStyle(fontSize: 7, color: PdfColors.grey700),
                          ),
                          pw.Text(
                            Formatters.formatRupiah(sub),
                            style: const pw.TextStyle(fontSize: 7.5),
                          ),
                        ],
                      ),
                    ],
                  ),
                );
              }),

              pw.Divider(thickness: 0.5, color: PdfColors.grey300),

              // Totals Section matching preview
              _receiptRow('Subtotal', Formatters.formatRupiah(subtotal > 0 ? subtotal : (grandTotal + discount))),
              _receiptRow('Diskon', discount > 0 ? '- ${Formatters.formatRupiah(discount)}' : 'Rp 0'),
              _receiptRow('Total', Formatters.formatRupiah(grandTotal), bold: true),
              _receiptRow('Bayar ($paymentMethod)', Formatters.formatRupiah(paidAmount > 0 ? paidAmount : grandTotal)),
              _receiptRow('Kembali', Formatters.formatRupiah(changeAmount >= 0 ? changeAmount : 0)),
              if (grandTotal > paidAmount && (paidAmount > 0 || paymentMethod.contains('PIUTANG')))
                _receiptRow('Sisa Piutang', Formatters.formatRupiah(grandTotal - paidAmount), bold: true),

              pw.Divider(thickness: 0.5, color: PdfColors.grey300),
              pw.SizedBox(height: 2),

              // Footer Notes matching preview
              pw.Center(
                child: pw.Text(
                  receiptFooter,
                  style: pw.TextStyle(fontSize: 6.5, fontStyle: pw.FontStyle.italic, color: PdfColors.grey700),
                  textAlign: pw.TextAlign.center,
                ),
              ),
              pw.SizedBox(height: 2),
              pw.Center(
                child: pw.Text(
                  '--- * ---',
                  style: const pw.TextStyle(fontSize: 6.5, color: PdfColors.grey400),
                  textAlign: pw.TextAlign.center,
                ),
              ),
              pw.SizedBox(height: 6),
            ],
          );
        },
      ),
    );

    return pdf.save();
  }
  /// Generate Receipt PDF bytes for Digital / Pulsa / PLN Sales (58mm/80mm)
  static Future<Uint8List> generateDigitalReceiptPdf({
    required Map<String, dynamic> digitalSale,
    Map<String, dynamic>? storeSetting,
    String paperSize = '58mm',
  }) async {
    final pdf = pw.Document();

    final double rollWidth = paperSize == '80mm' ? (80 * PdfPageFormat.mm) : (58 * PdfPageFormat.mm);
    final pageFormat = PdfPageFormat(rollWidth, double.infinity, marginAll: 3 * PdfPageFormat.mm);

    final storeName = (storeSetting?['store_name'] != null && storeSetting!['store_name'].toString().isNotEmpty)
        ? storeSetting['store_name'].toString()
        : ((storeSetting?['name'] != null && storeSetting!['name'].toString().isNotEmpty)
            ? storeSetting['name'].toString()
            : 'ELEPHANT CELL GROUP');
    final storeAddress = storeSetting?['address']?.toString() ?? '';
    final storePhone = storeSetting?['phone']?.toString() ?? '';
    final receiptFooter = (storeSetting?['receipt_footer'] != null && storeSetting!['receipt_footer'].toString().isNotEmpty)
        ? storeSetting['receipt_footer'].toString()
        : 'Terima kasih telah berbelanja!\nBarang yang sudah dibeli tidak dapat ditukar/dikembalikan.';

    final logoUrl = storeSetting?['logo_url']?.toString();
    final logoBytes = await _fetchLogoBytes(logoUrl);

    final trxNo = digitalSale['transaction_number'] ?? digitalSale['invoice_number'] ?? 'PE-NOTA';

    String dateFormatted;
    if (digitalSale['date'] != null) {
      try {
        final parsed = DateTime.parse(digitalSale['date'].toString()).toLocal();
        dateFormatted = DateFormat('dd/MM/yyyy  HH:mm').format(parsed);
      } catch (_) {
        dateFormatted = digitalSale['date'].toString();
      }
    } else {
      dateFormatted = DateFormat('dd/MM/yyyy  HH:mm').format(DateTime.now());
    }

    final customerNo = digitalSale['customer_number'] ?? digitalSale['phone'] ?? '-';
    final productName = digitalSale['digital_product']?['name'] ?? digitalSale['product_name'] ?? 'Pulsa / Elektrik';
    final sellingPrice = Formatters.parseDouble(digitalSale['selling_price'] ?? digitalSale['total']);
    final status = (digitalSale['status'] ?? 'SUKSES').toString().toUpperCase();
    final notes = digitalSale['notes']?.toString() ?? '';

    pdf.addPage(
      pw.Page(
        pageFormat: pageFormat,
        build: (pw.Context ctx) {
          return pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.stretch,
            children: [
              // Store Logo Avatar matching preview
              pw.Center(
                child: _buildPdfLogo(logoBytes),
              ),
              pw.SizedBox(height: 4),

              // Store Header
              pw.Center(
                child: pw.Text(
                  storeName,
                  style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 10),
                  textAlign: pw.TextAlign.center,
                ),
              ),
              if (storeAddress.isNotEmpty)
                pw.Center(
                  child: pw.Padding(
                    padding: const pw.EdgeInsets.only(top: 1),
                    child: pw.Text(
                      storeAddress,
                      style: const pw.TextStyle(fontSize: 7, color: PdfColors.grey700),
                      textAlign: pw.TextAlign.center,
                    ),
                  ),
                ),
              if (storePhone.isNotEmpty)
                pw.Center(
                  child: pw.Padding(
                    padding: const pw.EdgeInsets.only(top: 1),
                    child: pw.Text(
                      'Telp: $storePhone',
                      style: const pw.TextStyle(fontSize: 7, color: PdfColors.grey700),
                      textAlign: pw.TextAlign.center,
                    ),
                  ),
                ),
              pw.SizedBox(height: 3),
              pw.Divider(thickness: 0.5, color: PdfColors.grey300),

              // Info
              _receiptRow('No. Trx', trxNo),
              _receiptRow('Tanggal', dateFormatted),
              _receiptRow('Kategori', 'Pulsa / PPOB'),
              pw.Divider(thickness: 0.5, color: PdfColors.grey300),

              // Product Detail
              _receiptRow('Produk', productName),
              _receiptRow('No. Tujuan', customerNo),
              if (notes.isNotEmpty) _receiptRow('SN / Ket', notes),
              pw.Divider(thickness: 0.5, color: PdfColors.grey300),

              // Total & Status matching preview
              _receiptRow('Total', Formatters.formatRupiah(sellingPrice), bold: true),
              _receiptRow('Bayar', 'Tunai'),
              _receiptRow('Status', status, bold: true, statusColor: PdfColors.green800),

              pw.Divider(thickness: 0.5, color: PdfColors.grey300),
              pw.SizedBox(height: 2),

              // Footer
              pw.Center(
                child: pw.Text(
                  receiptFooter,
                  style: pw.TextStyle(fontSize: 6.5, fontStyle: pw.FontStyle.italic, color: PdfColors.grey700),
                  textAlign: pw.TextAlign.center,
                ),
              ),
              pw.SizedBox(height: 2),
              pw.Center(
                child: pw.Text(
                  '--- * ---',
                  style: const pw.TextStyle(fontSize: 6.5, color: PdfColors.grey400),
                  textAlign: pw.TextAlign.center,
                ),
              ),
              pw.SizedBox(height: 6),
            ],
          );
        },
      ),
    );

    return pdf.save();
  }

  static pw.Widget _receiptRow(String label, String value, {bool bold = false, PdfColor? statusColor}) {
    return pw.Padding(
      padding: const pw.EdgeInsets.symmetric(vertical: 1.0),
      child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        children: [
          pw.Text(
            label,
            style: pw.TextStyle(
              fontSize: 7.5,
              fontWeight: bold ? pw.FontWeight.bold : pw.FontWeight.normal,
            ),
          ),
          pw.Text(
            value,
            style: pw.TextStyle(
              fontSize: 7.5,
              fontWeight: (bold || statusColor != null) ? pw.FontWeight.bold : pw.FontWeight.normal,
              color: statusColor,
            ),
          ),
        ],
      ),
    );
  }

  /// Generate A4 Official Sales Invoice
  static Future<Uint8List> generateInvoicePdf({
    required Map<String, dynamic> sale,
    Map<String, dynamic>? storeSetting,
  }) async {
    final pdf = pw.Document();

    final storeName = storeSetting?['store_name'] ?? 'ELEPHANT CELL';
    final storeAddress = storeSetting?['address'] ?? 'Jl. Raya Utama No. 88';
    final storePhone = storeSetting?['phone'] ?? '0812-3456-7890';

    final invoiceNo = sale['invoice_number'] ?? sale['invoice_no'] ?? '-';
    final dateStr = sale['date'] != null ? sale['date'].toString() : DateFormat('dd MMMM yyyy').format(DateTime.now());
    final customerName = sale['customer'] != null && sale['customer'] is Map
        ? (sale['customer']['name'] ?? 'Pelanggan Umum')
        : (sale['customer_name'] ?? 'Pelanggan Umum');
    final customerPhone = sale['customer'] != null && sale['customer'] is Map
        ? (sale['customer']['phone'] ?? '-')
        : '-';

    final items = (sale['items'] is List) ? (sale['items'] as List) : [];
    final subtotal = Formatters.parseDouble(sale['subtotal']);
    final discount = Formatters.parseDouble(sale['discount']);
    final grandTotal = Formatters.parseDouble(sale['grand_total'] ?? sale['total']);
    final paidAmount = Formatters.parseDouble(sale['paid_amount']);
    final status = sale['status'] ?? (paidAmount >= grandTotal ? 'LUNAS' : 'BELUM LUNAS');

    pdf.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.all(24),
        build: (pw.Context ctx) {
          return pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.start,
            children: [
              // Header
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                crossAxisAlignment: pw.CrossAxisAlignment.start,
                children: [
                  pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.start,
                    children: [
                      pw.Text(storeName, style: pw.TextStyle(fontSize: 18, fontWeight: pw.FontWeight.bold, color: PdfColors.green900)),
                      pw.Text(storeAddress, style: const pw.TextStyle(fontSize: 9, color: PdfColors.grey700)),
                      pw.Text('Telp: $storePhone', style: const pw.TextStyle(fontSize: 9, color: PdfColors.grey700)),
                    ],
                  ),
                  pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.end,
                    children: [
                      pw.Text('FAKTUR PENJUALAN', style: pw.TextStyle(fontSize: 16, fontWeight: pw.FontWeight.bold, color: PdfColors.black)),
                      pw.Text('No: $invoiceNo', style: pw.TextStyle(fontSize: 10, fontWeight: pw.FontWeight.bold)),
                      pw.Text('Tanggal: $dateStr', style: const pw.TextStyle(fontSize: 9)),
                      pw.Container(
                        margin: const pw.EdgeInsets.only(top: 4),
                        padding: const pw.EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: pw.BoxDecoration(
                          color: status == 'LUNAS' ? PdfColors.green100 : PdfColors.red100,
                          borderRadius: pw.BorderRadius.circular(4),
                        ),
                        child: pw.Text(
                          status,
                          style: pw.TextStyle(fontSize: 9, fontWeight: pw.FontWeight.bold, color: status == 'LUNAS' ? PdfColors.green900 : PdfColors.red900),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              pw.Divider(thickness: 1.5, color: PdfColors.green900),
              pw.SizedBox(height: 8),

              // Customer Info
              pw.Container(
                padding: const pw.EdgeInsets.all(8),
                decoration: pw.BoxDecoration(color: PdfColors.grey100, borderRadius: pw.BorderRadius.circular(6)),
                child: pw.Row(
                  children: [
                    pw.Expanded(
                      child: pw.Column(
                        crossAxisAlignment: pw.CrossAxisAlignment.start,
                        children: [
                          pw.Text('KEPADA YTH:', style: pw.TextStyle(fontSize: 8, fontWeight: pw.FontWeight.bold, color: PdfColors.grey700)),
                          pw.Text(customerName, style: pw.TextStyle(fontSize: 11, fontWeight: pw.FontWeight.bold)),
                          pw.Text('No. HP: $customerPhone', style: const pw.TextStyle(fontSize: 9)),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              pw.SizedBox(height: 14),

              // Table
              pw.Table(
                border: pw.TableBorder.all(color: PdfColors.grey300, width: 0.5),
                columnWidths: {
                  0: const pw.FixedColumnWidth(28),
                  1: const pw.FlexColumnWidth(4),
                  2: const pw.FixedColumnWidth(45),
                  3: const pw.FixedColumnWidth(80),
                  4: const pw.FixedColumnWidth(85),
                },
                children: [
                  pw.TableRow(
                    decoration: const pw.BoxDecoration(color: PdfColors.green900),
                    children: [
                      _th('NO', align: pw.TextAlign.center),
                      _th('NAMA BARANG'),
                      _th('QTY', align: pw.TextAlign.center),
                      _th('HARGA SATUAN', align: pw.TextAlign.right),
                      _th('TOTAL', align: pw.TextAlign.right),
                    ],
                  ),
                  ...items.asMap().entries.map((entry) {
                    final idx = entry.key + 1;
                    final it = entry.value;
                    final prodName = it['product'] != null && it['product'] is Map
                        ? (it['product']['name'] ?? 'Item')
                        : (it['product_name'] ?? 'Item');
                    final qty = Formatters.parseDouble(it['qty']);
                    final price = Formatters.parseDouble(it['price'] ?? it['selling_price']);
                    final sub = Formatters.parseDouble(it['subtotal'] ?? (qty * price));

                    return pw.TableRow(
                      decoration: pw.BoxDecoration(color: idx % 2 == 0 ? PdfColors.grey50 : PdfColors.white),
                      children: [
                        _td(idx.toString(), align: pw.TextAlign.center),
                        _td(prodName),
                        _td(qty.toStringAsFixed(0), align: pw.TextAlign.center),
                        _td(Formatters.formatRupiah(price), align: pw.TextAlign.right),
                        _td(Formatters.formatRupiah(sub), align: pw.TextAlign.right),
                      ],
                    );
                  }),
                ],
              ),
              pw.SizedBox(height: 12),

              // Totals
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.end,
                children: [
                  pw.Container(
                    width: 230,
                    child: pw.Column(
                      children: [
                        _invRow('Subtotal:', Formatters.formatRupiah(subtotal > 0 ? subtotal : grandTotal)),
                        if (discount > 0) _invRow('Diskon:', '- ${Formatters.formatRupiah(discount)}'),
                        pw.Divider(color: PdfColors.grey400),
                        _invRow('TOTAL PEMBAYARAN:', Formatters.formatRupiah(grandTotal), isBold: true),
                        _invRow('Jumlah Dibayar:', Formatters.formatRupiah(paidAmount)),
                        if (grandTotal > paidAmount)
                          _invRow('SISA PIUTANG:', Formatters.formatRupiah(grandTotal - paidAmount), isBold: true, color: PdfColors.red900),
                      ],
                    ),
                  ),
                ],
              ),
              pw.Spacer(),

              // Signatures
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Column(
                    children: [
                      pw.Text('Tanda Terima Pelanggan,', style: const pw.TextStyle(fontSize: 9)),
                      pw.SizedBox(height: 45),
                      pw.Text('( _______________________ )', style: const pw.TextStyle(fontSize: 9)),
                    ],
                  ),
                  pw.Column(
                    children: [
                      pw.Text('Hormat Kami,', style: const pw.TextStyle(fontSize: 9)),
                      pw.SizedBox(height: 45),
                      pw.Text('( $storeName )', style: pw.TextStyle(fontSize: 9, fontWeight: pw.FontWeight.bold)),
                    ],
                  ),
                ],
              ),
              pw.SizedBox(height: 10),
            ],
          );
        },
      ),
    );

    return pdf.save();
  }

  static pw.Widget _th(String text, {pw.TextAlign align = pw.TextAlign.left}) {
    return pw.Padding(
      padding: const pw.EdgeInsets.symmetric(horizontal: 6, vertical: 5),
      child: pw.Text(
        text,
        style: pw.TextStyle(color: PdfColors.white, fontWeight: pw.FontWeight.bold, fontSize: 8.5),
        textAlign: align,
      ),
    );
  }

  static pw.Widget _td(String text, {pw.TextAlign align = pw.TextAlign.left}) {
    return pw.Padding(
      padding: const pw.EdgeInsets.symmetric(horizontal: 6, vertical: 4.5),
      child: pw.Text(text, style: const pw.TextStyle(fontSize: 8.5), textAlign: align),
    );
  }

  static pw.Widget _invRow(String label, String value, {bool isBold = false, PdfColor? color}) {
    return pw.Padding(
      padding: const pw.EdgeInsets.symmetric(vertical: 1.5),
      child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        children: [
          pw.Text(label, style: pw.TextStyle(fontSize: 9, fontWeight: isBold ? pw.FontWeight.bold : pw.FontWeight.normal, color: color)),
          pw.Text(value, style: pw.TextStyle(fontSize: 9, fontWeight: isBold ? pw.FontWeight.bold : pw.FontWeight.normal, color: color)),
        ],
      ),
    );
  }

  /// Test Print Action for Printer Settings Screen
  static Future<void> testPrintReceipt(BuildContext context, {Map<String, dynamic>? overrideSetting}) async {
    try {
      final now = DateTime.now();
      final dateFormatted = DateFormat('dd/MM/yyyy  HH:mm').format(now);

      final dummySale = {
        'invoice_number': 'INV-${now.year}${now.month.toString().padLeft(2, '0')}${now.day.toString().padLeft(2, '0')}-001',
        'date': dateFormatted,
        'cashier_name': 'Admin',
        'customer_name': 'Umum',
        'subtotal': 5250000,
        'discount': 0,
        'grand_total': 5250000,
        'paid_amount': 6000000,
        'change_amount': 750000,
        'payment_method': 'Tunai',
        'items': [
          {
            'product_name': 'Samsung A55 5G',
            'qty': 1,
            'price': 5200000,
            'subtotal': 5200000,
          },
          {
            'product_name': 'Tempered Glass',
            'qty': 2,
            'price': 25000,
            'subtotal': 50000,
          },
        ],
      };

      await printReceipt(
        sale: dummySale,
        storeSetting: overrideSetting,
      );
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal melakukan test print: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  /// Print Shift Closing / Rekap Shift Receipt
  static Future<void> printShiftReceipt({
    required Map<String, dynamic> shiftData,
    Map<String, dynamic>? storeSetting,
  }) async {
    final pdf = pw.Document();
    final paperSizeStr = await getPreferredPaperSize();
    final is80mm = paperSizeStr == '80mm';
    final pageFormat = is80mm ? PdfPageFormat.roll80 : PdfPageFormat.roll57;

    final resolvedSetting = await _resolveStoreSetting(storeSetting);
    final logoBytes = await _fetchLogoBytes(resolvedSetting['logo_url']?.toString());
    final logoImage = _buildPdfLogo(logoBytes);
    final fontRegular = await PdfGoogleFonts.robotoRegular();
    final fontBold = await PdfGoogleFonts.robotoBold();

    final storeName = resolvedSetting['store_name']?.toString() ?? 'ELEPHANT POS';
    final storeAddress = resolvedSetting['address']?.toString() ?? '';
    final storePhone = resolvedSetting['phone']?.toString() ?? '';

    final outletName = shiftData['outlet_name'] ?? shiftData['outlet']?['name'] ?? 'Pusat';
    final cashierName = shiftData['user_name'] ?? shiftData['user']?['name'] ?? 'Kasir';
    final startTime = shiftData['start_time_formatted'] ?? shiftData['start_time'] ?? '-';
    final endTime = shiftData['end_time_formatted'] ?? shiftData['end_time'] ?? '-';

    final totalDeposited = Formatters.parseDouble(shiftData['total_deposited'] ?? 0);
    final retailDeposited = Formatters.parseDouble(shiftData['cash_retail_deposited'] ?? 0);
    final retailRetained = Formatters.parseDouble(shiftData['cash_retail_retained'] ?? 400000);
    final multiDeposited = Formatters.parseDouble(shiftData['cash_multi_deposited'] ?? 0);
    final multiRetained = Formatters.parseDouble(shiftData['cash_multi_retained'] ?? 0);
    final transferDeposited = Formatters.parseDouble(shiftData['cash_transfer_deposited'] ?? 0);
    final transferRetained = Formatters.parseDouble(shiftData['cash_transfer_retained'] ?? 0);

    final cashSales = Formatters.parseDouble(shiftData['cash_sales'] ?? shiftData['summary']?['cash_sales'] ?? 0);
    final nonCashSales = Formatters.parseDouble(shiftData['non_cash_sales'] ?? shiftData['summary']?['non_cash_sales'] ?? 0);
    final receivableSales = Formatters.parseDouble(shiftData['receivable_sales'] ?? shiftData['summary']?['receivable_sales'] ?? 0);
    final digitalSales = Formatters.parseDouble(shiftData['digital_sales'] ?? shiftData['summary']?['total_digital_sales'] ?? 0);
    final transferCash = Formatters.parseDouble(shiftData['transfer_cash'] ?? shiftData['summary']?['total_transfer_cash'] ?? 0);
    final expenses = Formatters.parseDouble(shiftData['expenses'] ?? shiftData['summary']?['total_expense'] ?? 0);
    final trxCount = shiftData['transaction_count'] ?? shiftData['summary']?['total_transactions'] ?? 0;
    final notes = shiftData['notes']?.toString() ?? '';

    pdf.addPage(
      pw.Page(
        pageFormat: pageFormat,
        margin: const pw.EdgeInsets.symmetric(horizontal: 4, vertical: 8),
        theme: pw.ThemeData.withFont(base: fontRegular, bold: fontBold),
        build: (context) => pw.Column(
          crossAxisAlignment: pw.CrossAxisAlignment.center,
          children: [
            if (logoImage != null) pw.Container(height: 38, child: pw.Center(child: logoImage)),
            pw.Text(storeName, style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 11), textAlign: pw.TextAlign.center),
            if (storeAddress.isNotEmpty) pw.Text(storeAddress, style: const pw.TextStyle(fontSize: 8), textAlign: pw.TextAlign.center),
            if (storePhone.isNotEmpty) pw.Text('Telp: $storePhone', style: const pw.TextStyle(fontSize: 8), textAlign: pw.TextAlign.center),
            pw.Divider(thickness: 0.5, borderStyle: pw.BorderStyle.dashed),
            pw.Text('STRUK REKAP & TUTUP SHIFT', style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 9)),
            pw.SizedBox(height: 4),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('Outlet:', style: const pw.TextStyle(fontSize: 8)),
              pw.Text(outletName, style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 8)),
            ]),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('Kasir:', style: const pw.TextStyle(fontSize: 8)),
              pw.Text(cashierName, style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 8)),
            ]),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('Mulai:', style: const pw.TextStyle(fontSize: 8)),
              pw.Text(startTime, style: const pw.TextStyle(fontSize: 8)),
            ]),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('Selesai:', style: const pw.TextStyle(fontSize: 8)),
              pw.Text(endTime, style: const pw.TextStyle(fontSize: 8)),
            ]),
            pw.Divider(thickness: 0.5, borderStyle: pw.BorderStyle.dashed),
            pw.Align(alignment: pw.Alignment.centerLeft, child: pw.Text('OPERASIONAL SHIFT', style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 8))),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('Penjualan Tunai:', style: const pw.TextStyle(fontSize: 8)),
              pw.Text(Formatters.currency.format(cashSales), style: const pw.TextStyle(fontSize: 8)),
            ]),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('Non-Tunai (TF/QRIS):', style: const pw.TextStyle(fontSize: 8)),
              pw.Text(Formatters.currency.format(nonCashSales), style: const pw.TextStyle(fontSize: 8)),
            ]),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('Tempo (Piutang):', style: const pw.TextStyle(fontSize: 8)),
              pw.Text(Formatters.currency.format(receivableSales), style: const pw.TextStyle(fontSize: 8)),
            ]),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('Produk Multi:', style: const pw.TextStyle(fontSize: 8)),
              pw.Text(Formatters.currency.format(digitalSales), style: const pw.TextStyle(fontSize: 8)),
            ]),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('Transfer Masuk:', style: const pw.TextStyle(fontSize: 8)),
              pw.Text(Formatters.currency.format(transferCash), style: const pw.TextStyle(fontSize: 8)),
            ]),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('Kas Keluar (Beban):', style: const pw.TextStyle(fontSize: 8)),
              pw.Text('-${Formatters.currency.format(expenses)}', style: const pw.TextStyle(fontSize: 8)),
            ]),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('Total Transaksi:', style: const pw.TextStyle(fontSize: 8)),
              pw.Text('$trxCount Trx', style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 8)),
            ]),
            pw.Divider(thickness: 0.5, borderStyle: pw.BorderStyle.dashed),
            pw.Align(alignment: pw.Alignment.centerLeft, child: pw.Text('SETORAN 3 KAS & MODAL', style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 8))),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('Setor Retail:', style: const pw.TextStyle(fontSize: 8)),
              pw.Text(Formatters.currency.format(retailDeposited), style: const pw.TextStyle(fontSize: 8)),
            ]),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('Sisa Modal Retail:', style: const pw.TextStyle(fontSize: 8)),
              pw.Text(Formatters.currency.format(retailRetained), style: const pw.TextStyle(fontSize: 8)),
            ]),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('Setor Multi:', style: const pw.TextStyle(fontSize: 8)),
              pw.Text(Formatters.currency.format(multiDeposited), style: const pw.TextStyle(fontSize: 8)),
            ]),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('Setor Transfer:', style: const pw.TextStyle(fontSize: 8)),
              pw.Text(Formatters.currency.format(transferDeposited), style: const pw.TextStyle(fontSize: 8)),
            ]),
            pw.Divider(thickness: 0.8),
            pw.Row(mainAxisAlignment: pw.MainAxisAlignment.spaceBetween, children: [
              pw.Text('TOTAL DISETOR:', style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 9)),
              pw.Text(Formatters.currency.format(totalDeposited), style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 9)),
            ]),
            if (notes.isNotEmpty) ...[
              pw.SizedBox(height: 3),
              pw.Align(alignment: pw.Alignment.centerLeft, child: pw.Text('Catatan: $notes', style: const pw.TextStyle(fontSize: 7))),
            ],
            pw.SizedBox(height: 10),
            pw.Row(
              mainAxisAlignment: pw.MainAxisAlignment.spaceAround,
              children: [
                pw.Column(children: [
                  pw.Text('Diserahkan,', style: const pw.TextStyle(fontSize: 7)),
                  pw.SizedBox(height: 18),
                  pw.Text('($cashierName)', style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 7)),
                ]),
                pw.Column(children: [
                  pw.Text('Diterima,', style: const pw.TextStyle(fontSize: 7)),
                  pw.SizedBox(height: 18),
                  pw.Text('(Supervisor)', style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 7)),
                ]),
              ],
            ),
            pw.SizedBox(height: 6),
            pw.Text(DateFormat('dd/MM/yyyy HH:mm').format(DateTime.now()), style: const pw.TextStyle(fontSize: 6)),
          ],
        ),
      ),
    );

    await Printing.layoutPdf(
      onLayout: (PdfPageFormat format) async => pdf.save(),
      name: 'Rekap_Shift_${DateTime.now().millisecondsSinceEpoch}',
    );
  }
}
