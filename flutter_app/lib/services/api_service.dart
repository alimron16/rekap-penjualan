import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static const String baseUrl = 'https://pos.moonbyte.my.id/api';

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token');
  }

  static Future<Map<String, String>> _headers() async {
    final token = await getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  static Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: jsonEncode({'email': email, 'password': password}),
    );
    final data = jsonDecode(response.body);
    if (response.statusCode == 200 && data['success'] == true) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('auth_token', data['token']);
      await prefs.setString('user_data', jsonEncode(data['user']));
    }
    return data;
  }

  static Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('user_data');
  }

  static Future<Map<String, dynamic>?> getUser() async {
    final prefs = await SharedPreferences.getInstance();
    final str = prefs.getString('user_data');
    if (str != null) return jsonDecode(str);
    return null;
  }

  static Future<Map<String, dynamic>> getDashboard() async {
    final headers = await _headers();
    final res = await http.get(Uri.parse('$baseUrl/dashboard'), headers: headers);
    return jsonDecode(res.body);
  }

  // Master Data
  static Future<Map<String, dynamic>> getProducts({String? search}) async {
    final headers = await _headers();
    final url = search != null && search.isNotEmpty
        ? '$baseUrl/products?q=${Uri.encodeComponent(search)}'
        : '$baseUrl/products';
    final res = await http.get(Uri.parse(url), headers: headers);
    return jsonDecode(res.body);
  }

  static Future<Map<String, dynamic>> getCustomers() async {
    final headers = await _headers();
    final res = await http.get(Uri.parse('$baseUrl/customers'), headers: headers);
    return jsonDecode(res.body);
  }

  static Future<Map<String, dynamic>> getSuppliers() async {
    final headers = await _headers();
    final res = await http.get(Uri.parse('$baseUrl/suppliers'), headers: headers);
    return jsonDecode(res.body);
  }

  static Future<Map<String, dynamic>> getAccounts() async {
    final headers = await _headers();
    final res = await http.get(Uri.parse('$baseUrl/accounts'), headers: headers);
    return jsonDecode(res.body);
  }

  // POS Kasir
  static Future<Map<String, dynamic>> getPosData() async {
    final headers = await _headers();
    final res = await http.get(Uri.parse('$baseUrl/pos/data'), headers: headers);
    return jsonDecode(res.body);
  }

  static Future<Map<String, dynamic>> checkoutPos({
    required String saleType,
    int? customerId,
    double discount = 0,
    required double paidAmount,
    required String paymentMethod,
    int? accountId,
    String? notes,
    required List<Map<String, dynamic>> items,
  }) async {
    final headers = await _headers();
    final res = await http.post(
      Uri.parse('$baseUrl/pos/checkout'),
      headers: headers,
      body: jsonEncode({
        'sale_type': saleType,
        'customer_id': customerId,
        'discount': discount,
        'paid_amount': paidAmount,
        'payment_method': paymentMethod,
        'account_id': accountId,
        'notes': notes,
        'items': items,
      }),
    );
    return jsonDecode(res.body);
  }

  // Digital / Pulsa
  static Future<Map<String, dynamic>> getDigitalData() async {
    final headers = await _headers();
    final res = await http.get(Uri.parse('$baseUrl/digital/data'), headers: headers);
    return jsonDecode(res.body);
  }

  static Future<Map<String, dynamic>> checkoutDigital({
    required int digitalProductId,
    required String customerNumber,
    required int depositAccountId,
    required int cashAccountId,
    double? sellingPrice,
    double? hpp,
    String? notes,
  }) async {
    final headers = await _headers();
    final res = await http.post(
      Uri.parse('$baseUrl/digital/checkout'),
      headers: headers,
      body: jsonEncode({
        'digital_product_id': digitalProductId,
        'customer_number': customerNumber,
        'deposit_account_id': depositAccountId,
        'cash_account_id': cashAccountId,
        'selling_price': sellingPrice,
        'hpp': hpp,
        'notes': notes,
      }),
    );
    return jsonDecode(res.body);
  }

  // Transfer Agen
  static Future<Map<String, dynamic>> getTransfers({String? status}) async {
    final headers = await _headers();
    final url = status != null ? '$baseUrl/transfers?status=$status' : '$baseUrl/transfers';
    final res = await http.get(Uri.parse(url), headers: headers);
    return jsonDecode(res.body);
  }

  static Future<Map<String, dynamic>> requestTransfer({
    required String bankName,
    required String accountNumber,
    required String accountHolder,
    required double amount,
    String? notes,
  }) async {
    final headers = await _headers();
    final res = await http.post(
      Uri.parse('$baseUrl/transfers'),
      headers: headers,
      body: jsonEncode({
        'bank_name': bankName,
        'account_number': accountNumber,
        'account_holder': accountHolder,
        'amount': amount,
        'notes': notes,
      }),
    );
    return jsonDecode(res.body);
  }

  // Kas & Akuntansi
  static Future<Map<String, dynamic>> getCashTransactions({String? type}) async {
    final headers = await _headers();
    final url = type != null ? '$baseUrl/cash-transactions?type=$type' : '$baseUrl/cash-transactions';
    final res = await http.get(Uri.parse(url), headers: headers);
    return jsonDecode(res.body);
  }

  static Future<Map<String, dynamic>> storeCashTransaction({
    required String type,
    required int accountId,
    required int oppositeAccountId,
    required double amount,
    required String description,
  }) async {
    final headers = await _headers();
    final res = await http.post(
      Uri.parse('$baseUrl/cash-transactions'),
      headers: headers,
      body: jsonEncode({
        'type': type,
        'account_id': accountId,
        'opposite_account_id': oppositeAccountId,
        'amount': amount,
        'description': description,
      }),
    );
    return jsonDecode(res.body);
  }

  // Laporan Keuangan
  static Future<Map<String, dynamic>> getReports({String? startDate, String? endDate}) async {
    final headers = await _headers();
    final res = await http.get(
      Uri.parse('$baseUrl/reports?start_date=$startDate&end_date=$endDate'),
      headers: headers,
    );
    return jsonDecode(res.body);
  }
}
