import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';
import 'package:shared_preferences/shared_preferences.dart';
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

  /// Print Thermal Receipt directly inside the app using Printing plugin
  static Future<void> printReceipt({
    required Map<String, dynamic> sale,
    Map<String, dynamic>? storeSetting,
    String? paperSize,
  }) async {
    final size = paperSize ?? await getPreferredPaperSize();
    final pdfBytes = await generateReceiptPdf(
      sale: sale,
      storeSetting: storeSetting,
      paperSize: size,
    );

    final invoiceNo = sale['invoice_number'] ?? sale['invoice_no'] ?? 'Struk';
    await Printing.layoutPdf(
      name: 'Receipt-$invoiceNo',
      onLayout: (PdfPageFormat format) async => pdfBytes,
    );
  }

  /// Print Official A4 Sales Invoice
  static Future<void> printInvoice({
    required Map<String, dynamic> sale,
    Map<String, dynamic>? storeSetting,
  }) async {
    final pdfBytes = await generateInvoicePdf(sale: sale, storeSetting: storeSetting);
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

    // 58mm = 58 * 72 / 25.4 = ~164 pt
    // 80mm = 80 * 72 / 25.4 = ~226 pt
    final double rollWidth = paperSize == '80mm' ? (80 * PdfPageFormat.mm) : (58 * PdfPageFormat.mm);
    final pageFormat = PdfPageFormat(rollWidth, double.infinity, marginAll: 4 * PdfPageFormat.mm);

    final outlet = (sale['outlet'] is Map) ? sale['outlet'] : null;
    final storeName = (outlet?['name'] != null && outlet!['name'].toString().isNotEmpty)
        ? outlet['name'].toString()
        : (storeSetting?['name'] ?? storeSetting?['store_name'] ?? 'ELEPHANT CELL GROUP');
    final storeAddress = (outlet?['address'] != null && outlet!['address'].toString().isNotEmpty)
        ? outlet['address'].toString()
        : (storeSetting?['address'] ?? '');
    final storePhone = (outlet?['phone'] != null && outlet!['phone'].toString().isNotEmpty)
        ? outlet['phone'].toString()
        : (storeSetting?['phone'] ?? '');
    final receiptFooter = (storeSetting?['receipt_footer'] != null && storeSetting!['receipt_footer'].toString().isNotEmpty)
        ? storeSetting['receipt_footer'].toString()
        : 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.';

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
    final paymentMethod = (sale['payment_method'] ?? 'cash').toString().toUpperCase();

    pdf.addPage(
      pw.Page(
        pageFormat: pageFormat,
        build: (pw.Context ctx) {
          return pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.stretch,
            children: [
              // Store Header
              pw.Center(
                child: pw.Text(
                  storeName,
                  style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 11),
                  textAlign: pw.TextAlign.center,
                ),
              ),
              if (storeAddress.isNotEmpty)
                pw.Center(
                  child: pw.Text(
                    storeAddress,
                    style: const pw.TextStyle(fontSize: 7.5),
                    textAlign: pw.TextAlign.center,
                  ),
                ),
              if (storePhone.isNotEmpty)
                pw.Center(
                  child: pw.Text(
                    'Telp/WA: $storePhone',
                    style: const pw.TextStyle(fontSize: 7.5),
                    textAlign: pw.TextAlign.center,
                  ),
                ),
              pw.SizedBox(height: 3),
              pw.Text('----------------------------------------------------', style: const pw.TextStyle(fontSize: 6.5)),

              // Info
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Text('No: $invoiceNo', style: const pw.TextStyle(fontSize: 7.5)),
                  pw.Text(dateStr, style: const pw.TextStyle(fontSize: 7.5)),
                ],
              ),
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Text('Kasir: $cashierName', style: const pw.TextStyle(fontSize: 7.5)),
                  pw.Text('Pelanggan: $customerName', style: const pw.TextStyle(fontSize: 7.5)),
                ],
              ),
              pw.Text('----------------------------------------------------', style: const pw.TextStyle(fontSize: 6.5)),

              // Items
              ...items.map((it) {
                final prodName = it['product'] != null && it['product'] is Map
                    ? (it['product']['name'] ?? 'Item')
                    : (it['product_name'] ?? 'Item');
                final qty = Formatters.parseDouble(it['qty']);
                final price = Formatters.parseDouble(it['price'] ?? it['selling_price']);
                final sub = Formatters.parseDouble(it['subtotal'] ?? (qty * price));

                return pw.Padding(
                  padding: const pw.EdgeInsets.symmetric(vertical: 1.5),
                  child: pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.start,
                    children: [
                      pw.Text(prodName, style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 8)),
                      pw.Row(
                        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                        children: [
                          pw.Text('${qty.toStringAsFixed(0)} x ${Formatters.formatRupiah(price)}', style: const pw.TextStyle(fontSize: 7.5)),
                          pw.Text(Formatters.formatRupiah(sub), style: const pw.TextStyle(fontSize: 7.5)),
                        ],
                      ),
                    ],
                  ),
                );
              }),

              pw.Text('----------------------------------------------------', style: const pw.TextStyle(fontSize: 6.5)),

              // Calculation
              _receiptRow('Subtotal:', Formatters.formatRupiah(subtotal > 0 ? subtotal : grandTotal)),
              if (discount > 0) _receiptRow('Diskon:', '- ${Formatters.formatRupiah(discount)}'),
              pw.SizedBox(height: 2),
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Text('TOTAL:', style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 9.5)),
                  pw.Text(Formatters.formatRupiah(grandTotal), style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 9.5)),
                ],
              ),
              pw.SizedBox(height: 2),
              _receiptRow('Metode:', paymentMethod),
              if (paidAmount > 0) _receiptRow('Bayar:', Formatters.formatRupiah(paidAmount)),
              if (changeAmount >= 0 && paidAmount > grandTotal) _receiptRow('Kembali:', Formatters.formatRupiah(changeAmount)),
              if (grandTotal > paidAmount && (paidAmount > 0 || paymentMethod.contains('PIUTANG')))
                _receiptRow('Sisa Piutang:', Formatters.formatRupiah(grandTotal - paidAmount)),

              pw.Text('----------------------------------------------------', style: const pw.TextStyle(fontSize: 6.5)),
              pw.SizedBox(height: 3),

              // Footer Notes
              pw.Center(
                child: pw.Text(
                  'TERIMA KASIH ATAS KUNJUNGAN ANDA',
                  style: pw.TextStyle(fontSize: 7.5, fontWeight: pw.FontWeight.bold),
                  textAlign: pw.TextAlign.center,
                ),
              ),
              pw.SizedBox(height: 1),
              pw.Center(
                child: pw.Text(
                  receiptFooter,
                  style: const pw.TextStyle(fontSize: 6.5),
                  textAlign: pw.TextAlign.center,
                ),
              ),
              pw.SizedBox(height: 10),
            ],
          );
        },
      ),
    );

    return pdf.save();
  }

  static pw.Widget _receiptRow(String label, String value) {
    return pw.Padding(
      padding: const pw.EdgeInsets.symmetric(vertical: 0.8),
      child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        children: [
          pw.Text(label, style: const pw.TextStyle(fontSize: 7.5)),
          pw.Text(value, style: const pw.TextStyle(fontSize: 7.5)),
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
  static Future<void> testPrintReceipt(BuildContext context) async {
    try {
      final dummySale = {
        'invoice_number': 'TEST-${DateTime.now().millisecondsSinceEpoch ~/ 1000}',
        'date': DateFormat('dd/MM/yyyy HH:mm').format(DateTime.now()),
        'cashier_name': 'Admin Demo',
        'customer_name': 'Pelanggan Uji Coba',
        'subtotal': 35000,
        'discount': 0,
        'grand_total': 35000,
        'paid_amount': 50000,
        'change_amount': 15000,
        'payment_method': 'cash',
        'items': [
          {
            'product_name': 'Paket Data Telkomsel 10GB',
            'qty': 1,
            'price': 25000,
            'subtotal': 25000,
          },
          {
            'product_name': 'Kabel Data Type C Fast Charging',
            'qty': 1,
            'price': 10000,
            'subtotal': 10000,
          },
        ],
      };

      await printReceipt(sale: dummySale);
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal melakukan test print: $e'), backgroundColor: Colors.red),
      );
    }
  }
}
