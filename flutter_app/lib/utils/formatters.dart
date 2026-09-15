import 'package:intl/intl.dart';

class Formatters {
  static final currency = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  static double parseDouble(dynamic val) {
    if (val == null) return 0.0;
    if (val is double) return val;
    if (val is int) return val.toDouble();
    if (val is num) return val.toDouble();
    try {
      final clean = val.toString().replaceAll(',', '.').replaceAll(RegExp(r'[^0-9.-]'), '');
      return double.tryParse(clean) ?? 0.0;
    } catch (_) {
      return 0.0;
    }
  }

  static int parseInt(dynamic val) {
    if (val == null) return 0;
    if (val is int) return val;
    if (val is num) return val.toInt();
    try {
      final d = parseDouble(val);
      return d.toInt();
    } catch (_) {
      return 0;
    }
  }

  static String formatRupiah(dynamic val) {
    return currency.format(parseDouble(val));
  }
}
