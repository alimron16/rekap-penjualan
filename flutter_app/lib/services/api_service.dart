import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static const String baseUrl = 'https://pos.moonbyte.my.id/api';
  static const Duration defaultTimeout = Duration(seconds: 12);

  static const String _dashboardCacheKey = 'cached_dashboard_summary';
  static const String _posCacheKey = 'cached_pos_master_data';

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

  // --- Session & Authentication ---
  static Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/login'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'email': email, 'password': password}),
      ).timeout(defaultTimeout);

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', data['token']);
        await prefs.setString('user_data', jsonEncode(data['user']));
      }
      return data;
    } on TimeoutException {
      return {'success': false, 'message': 'Koneksi lambat / waktu permintaan habis.'};
    } on SocketException {
      return {'success': false, 'message': 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.'};
    } catch (e) {
      return {'success': false, 'message': 'Gagal masuk: $e'};
    }
  }

  static Future<void> logout() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('auth_token');
      await prefs.remove('user_data');
    } catch (_) {}
  }

  static Future<Map<String, dynamic>?> getUser() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final str = prefs.getString('user_data');
      if (str != null) return jsonDecode(str);
    } catch (_) {}
    return null;
  }

  // --- Stale-While-Revalidate Caching ---
  static Future<void> saveCachedDashboard(Map<String, dynamic> data) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_dashboardCacheKey, jsonEncode(data));
    } catch (_) {}
  }

  static Future<Map<String, dynamic>?> getCachedDashboard() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final str = prefs.getString(_dashboardCacheKey);
      if (str != null && str.isNotEmpty) {
        return jsonDecode(str) as Map<String, dynamic>;
      }
    } catch (_) {}
    return null;
  }

  static Future<void> saveCachedPosData(Map<String, dynamic> data) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_posCacheKey, jsonEncode(data));
    } catch (_) {}
  }

  static Future<Map<String, dynamic>?> getCachedPosData() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final str = prefs.getString(_posCacheKey);
      if (str != null && str.isNotEmpty) {
        return jsonDecode(str) as Map<String, dynamic>;
      }
    } catch (_) {}
    return null;
  }

  // --- Centralized HTTP Handlers with Timeout, 401 Session Interceptor, and Error Handling ---
  static Future<Map<String, dynamic>> _handleResponse(http.Response res) async {
    if (res.statusCode == 401) {
      await logout();
      return {
        'success': false,
        'is_auth_error': true,
        'message': 'Sesi login telah berakhir. Silakan masuk kembali.',
      };
    }

    try {
      final decoded = jsonDecode(res.body);
      if (decoded is Map<String, dynamic>) {
        if (res.statusCode >= 400) {
          return {
            'success': false,
            'message': decoded['message'] ?? decoded['error'] ?? 'Terjadi kesalahan pada server (${res.statusCode})',
            ...decoded,
          };
        }
        if (!decoded.containsKey('success')) {
          decoded['success'] = true;
        }
        return decoded;
      } else if (decoded is List) {
        return {'success': true, 'data': decoded};
      }
      return {'success': res.statusCode >= 200 && res.statusCode < 300};
    } catch (e) {
      return {
        'success': false,
        'message': 'Gagal membaca respon server (${res.statusCode})',
      };
    }
  }

  static Future<Map<String, dynamic>> _get(String url, {Duration? timeout}) async {
    try {
      final headers = await _headers();
      final res = await http.get(Uri.parse(url), headers: headers).timeout(timeout ?? defaultTimeout);
      return await _handleResponse(res);
    } on TimeoutException {
      return {
        'success': false,
        'is_timeout': true,
        'message': 'Koneksi internet lambat / waktu habis. Silakan coba lagi.',
      };
    } on SocketException {
      return {
        'success': false,
        'is_network_error': true,
        'message': 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.',
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Koneksi terganggu: $e',
      };
    }
  }

  static Future<Map<String, dynamic>> _post(String url, Map<String, dynamic> body, {Duration? timeout}) async {
    try {
      final headers = await _headers();
      final res = await http.post(
        Uri.parse(url),
        headers: headers,
        body: jsonEncode(body),
      ).timeout(timeout ?? defaultTimeout);
      return await _handleResponse(res);
    } on TimeoutException {
      return {
        'success': false,
        'is_timeout': true,
        'message': 'Koneksi lambat / waktu permintaan habis.',
      };
    } on SocketException {
      return {
        'success': false,
        'is_network_error': true,
        'message': 'Tidak dapat terhubung ke server. Periksa jaringan Anda.',
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Gagal mengirim data: $e',
      };
    }
  }

  static Future<Map<String, dynamic>> _put(String url, Map<String, dynamic> body, {Duration? timeout}) async {
    try {
      final headers = await _headers();
      final res = await http.put(
        Uri.parse(url),
        headers: headers,
        body: jsonEncode(body),
      ).timeout(timeout ?? defaultTimeout);
      return await _handleResponse(res);
    } on TimeoutException {
      return {
        'success': false,
        'is_timeout': true,
        'message': 'Koneksi lambat / waktu permintaan habis.',
      };
    } on SocketException {
      return {
        'success': false,
        'is_network_error': true,
        'message': 'Tidak dapat terhubung ke server. Periksa jaringan Anda.',
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Gagal memperbarui data: $e',
      };
    }
  }

  static Future<Map<String, dynamic>> _delete(String url, {Duration? timeout}) async {
    try {
      final headers = await _headers();
      final res = await http.delete(Uri.parse(url), headers: headers).timeout(timeout ?? defaultTimeout);
      return await _handleResponse(res);
    } on TimeoutException {
      return {
        'success': false,
        'is_timeout': true,
        'message': 'Koneksi lambat / waktu permintaan habis.',
      };
    } on SocketException {
      return {
        'success': false,
        'is_network_error': true,
        'message': 'Tidak dapat terhubung ke server. Periksa jaringan Anda.',
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Gagal menghapus data: $e',
      };
    }
  }

  // --- Dashboard & Notifications ---
  static Future<Map<String, dynamic>> getDashboard({String? startDate, String? endDate}) async {
    final query = (startDate != null && endDate != null)
        ? '?start_date=${Uri.encodeComponent(startDate)}&end_date=${Uri.encodeComponent(endDate)}'
        : '';
    final res = await _get('$baseUrl/dashboard$query', timeout: const Duration(seconds: 15));
    if (res['success'] == true) {
      await saveCachedDashboard(res);
    }
    return res;
  }

  static Future<Map<String, dynamic>> pollNotifications() async {
    return await _get('$baseUrl/notifications/poll', timeout: const Duration(seconds: 8));
  }

  // --- Master Data: Products ---
  static Future<Map<String, dynamic>> getProducts({String? search}) async {
    final url = search != null && search.isNotEmpty
        ? '$baseUrl/products?q=${Uri.encodeComponent(search)}'
        : '$baseUrl/products';
    return await _get(url);
  }

  static Future<Map<String, dynamic>> storeProduct(Map<String, dynamic> data) async {
    return await _post('$baseUrl/products', data);
  }

  static Future<Map<String, dynamic>> updateProduct(int id, Map<String, dynamic> data) async {
    return await _put('$baseUrl/products/$id', data);
  }

  static Future<Map<String, dynamic>> deleteProduct(int id) async {
    return await _delete('$baseUrl/products/$id');
  }

  // --- Master Data: Multi Products ---
  static Future<Map<String, dynamic>> getMultiProducts({String? search, String? trxType, String? category}) async {
    final params = <String>[];
    if (search != null && search.isNotEmpty) params.add('q=${Uri.encodeComponent(search)}');
    if (trxType != null && trxType != 'ALL') params.add('trx_type=${Uri.encodeComponent(trxType)}');
    if (category != null && category != 'ALL') params.add('category=${Uri.encodeComponent(category)}');
    final query = params.isNotEmpty ? '?${params.join('&')}' : '';
    return await _get('$baseUrl/multi-products$query');
  }

  static Future<Map<String, dynamic>> storeMultiProduct(Map<String, dynamic> data) async {
    return await _post('$baseUrl/multi-products', data);
  }

  static Future<Map<String, dynamic>> updateMultiProduct(int id, Map<String, dynamic> data) async {
    return await _put('$baseUrl/multi-products/$id', data);
  }

  static Future<Map<String, dynamic>> deleteMultiProduct(int id) async {
    return await _delete('$baseUrl/multi-products/$id');
  }

  // --- Master Data: Customers ---
  static Future<Map<String, dynamic>> getCustomers() async {
    return await _get('$baseUrl/customers');
  }

  static Future<Map<String, dynamic>> storeCustomer(Map<String, dynamic> data) async {
    return await _post('$baseUrl/customers', data);
  }

  static Future<Map<String, dynamic>> updateCustomer(int id, Map<String, dynamic> data) async {
    return await _put('$baseUrl/customers/$id', data);
  }

  static Future<Map<String, dynamic>> deleteCustomer(int id) async {
    return await _delete('$baseUrl/customers/$id');
  }

  // --- Master Data: Suppliers ---
  static Future<Map<String, dynamic>> getSuppliers() async {
    return await _get('$baseUrl/suppliers');
  }

  static Future<Map<String, dynamic>> storeSupplier(Map<String, dynamic> data) async {
    return await _post('$baseUrl/suppliers', data);
  }

  static Future<Map<String, dynamic>> updateSupplier(int id, Map<String, dynamic> data) async {
    return await _put('$baseUrl/suppliers/$id', data);
  }

  static Future<Map<String, dynamic>> deleteSupplier(int id) async {
    return await _delete('$baseUrl/suppliers/$id');
  }

  // --- Master Data: Accounts (COA) ---
  static Future<Map<String, dynamic>> getAccounts() async {
    return await _get('$baseUrl/accounts');
  }

  static Future<Map<String, dynamic>> storeAccount(Map<String, dynamic> data) async {
    return await _post('$baseUrl/accounts', data);
  }

  static Future<Map<String, dynamic>> updateAccount(int id, Map<String, dynamic> data) async {
    return await _put('$baseUrl/accounts/$id', data);
  }

  static Future<Map<String, dynamic>> deleteAccount(int id) async {
    return await _delete('$baseUrl/accounts/$id');
  }

  // --- POS Kasir ---
  static Future<Map<String, dynamic>> getPosData() async {
    final res = await _get('$baseUrl/pos/data', timeout: const Duration(seconds: 15));
    if (res['success'] == true) {
      await saveCachedPosData(res);
    }
    return res;
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
    return await _post(
      '$baseUrl/pos/checkout',
      {
        'sale_type': saleType,
        'customer_id': customerId,
        'discount': discount,
        'paid_amount': paidAmount,
        'payment_method': paymentMethod,
        'account_id': accountId,
        'notes': notes,
        'items': items,
      },
      timeout: const Duration(seconds: 20),
    );
  }

  // --- Digital / Pulsa ---
  static Future<Map<String, dynamic>> getDigitalData() async {
    return await _get('$baseUrl/digital/data');
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
    return await _post(
      '$baseUrl/digital/checkout',
      {
        'digital_product_id': digitalProductId,
        'customer_number': customerNumber,
        'deposit_account_id': depositAccountId,
        'cash_account_id': cashAccountId,
        'selling_price': sellingPrice,
        'hpp': hpp,
        'notes': notes,
      },
      timeout: const Duration(seconds: 20),
    );
  }

  // --- Transfer Agen ---
  static Future<Map<String, dynamic>> getTransfers({String? status}) async {
    final url = status != null ? '$baseUrl/transfers?status=$status' : '$baseUrl/transfers';
    return await _get(url);
  }

  static Future<Map<String, dynamic>> requestTransfer({
    required String bankName,
    required String accountNumber,
    required String accountHolder,
    required double amount,
    String? notes,
  }) async {
    return await _post('$baseUrl/transfers', {
      'bank_name': bankName,
      'account_number': accountNumber,
      'account_holder': accountHolder,
      'amount': amount,
      'notes': notes,
    });
  }

  static Future<Map<String, dynamic>> approveTransfer(int id, {required int sourceAccountId, String? notes}) async {
    return await _post('$baseUrl/transfers/$id/approve', {
      'source_account_id': sourceAccountId,
      'notes': notes,
    });
  }

  static Future<Map<String, dynamic>> rejectTransfer(int id, {required String notes}) async {
    return await _post('$baseUrl/transfers/$id/reject', {
      'notes': notes,
    });
  }

  static Future<Map<String, dynamic>> checkPendingTransfers() async {
    return await _get('$baseUrl/transfers/pending-check');
  }

  // --- Kas & Akuntansi ---
  static Future<Map<String, dynamic>> getCashTransactions({String? type}) async {
    final url = type != null ? '$baseUrl/cash-transactions?type=$type' : '$baseUrl/cash-transactions';
    return await _get(url);
  }

  static Future<Map<String, dynamic>> storeCashTransaction({
    required String type,
    required int accountId,
    required int oppositeAccountId,
    required double amount,
    required String description,
  }) async {
    return await _post('$baseUrl/cash-transactions', {
      'type': type,
      'account_id': accountId,
      'opposite_account_id': oppositeAccountId,
      'amount': amount,
      'description': description,
    });
  }

  // --- Piutang & Retur ---
  static Future<Map<String, dynamic>> getReceivables() async {
    return await _get('$baseUrl/receivables');
  }

  static Future<Map<String, dynamic>> payReceivable({
    required String date,
    required int saleId,
    required int customerId,
    required double amount,
    required int accountId,
    String? notes,
  }) async {
    return await _post('$baseUrl/receivables/pay', {
      'date': date,
      'sale_id': saleId,
      'customer_id': customerId,
      'amount': amount,
      'account_id': accountId,
      'notes': notes,
    });
  }

  static Future<Map<String, dynamic>> getReturns() async {
    return await _get('$baseUrl/returns');
  }

  static Future<Map<String, dynamic>> storeReturn({
    required String date,
    int? saleId,
    int? customerId,
    required int productId,
    required double qty,
    required double refundAmount,
    required int accountId,
    String? notes,
  }) async {
    return await _post('$baseUrl/returns', {
      'date': date,
      'sale_id': saleId,
      'customer_id': customerId,
      'product_id': productId,
      'qty': qty,
      'refund_amount': refundAmount,
      'account_id': accountId,
      'notes': notes,
    });
  }

  // --- Pembelian & Hutang ---
  static Future<Map<String, dynamic>> getPurchases() async {
    return await _get('$baseUrl/purchases');
  }

  static Future<Map<String, dynamic>> storePurchase({
    required String date,
    required int supplierId,
    required String paymentMethod,
    int? accountId,
    double paidAmount = 0,
    double discount = 0,
    String? notes,
    required List<Map<String, dynamic>> items,
  }) async {
    return await _post(
      '$baseUrl/purchases',
      {
        'date': date,
        'supplier_id': supplierId,
        'payment_method': paymentMethod,
        'account_id': accountId,
        'paid_amount': paidAmount,
        'discount': discount,
        'notes': notes,
        'items': items,
      },
      timeout: const Duration(seconds: 20),
    );
  }

  static Future<Map<String, dynamic>> payDebt({
    required String date,
    required int purchaseId,
    required int supplierId,
    required double amount,
    required int accountId,
    String? notes,
  }) async {
    return await _post('$baseUrl/purchases/pay-debt', {
      'date': date,
      'purchase_id': purchaseId,
      'supplier_id': supplierId,
      'amount': amount,
      'account_id': accountId,
      'notes': notes,
    });
  }

  // --- Persediaan / Penyesuaian Stok ---
  static Future<Map<String, dynamic>> getInventoryAdjustments() async {
    return await _get('$baseUrl/inventory/adjustments');
  }

  static Future<Map<String, dynamic>> storeInventoryAdjustment({
    required int productId,
    required double qty,
    required String type,
    double? costPrice,
    String? notes,
  }) async {
    return await _post('$baseUrl/inventory/adjustments', {
      'product_id': productId,
      'qty': qty,
      'type': type,
      'cost_price': costPrice,
      'notes': notes,
    });
  }

  // --- Laporan Keuangan ---
  static Future<Map<String, dynamic>> getReports({String? startDate, String? endDate}) async {
    final s = startDate ?? '';
    final e = endDate ?? '';
    return await _get('$baseUrl/reports?start_date=$s&end_date=$e');
  }

  static Future<Map<String, dynamic>> getSalesReport({String? startDate, String? endDate, String saleType = 'all'}) async {
    final s = startDate ?? '';
    final e = endDate ?? '';
    return await _get('$baseUrl/reports/sales?start_date=$s&end_date=$e&sale_type=$saleType');
  }

  static Future<Map<String, dynamic>> getPurchasesReport({String? startDate, String? endDate}) async {
    final s = startDate ?? '';
    final e = endDate ?? '';
    return await _get('$baseUrl/reports/purchases?start_date=$s&end_date=$e');
  }

  static Future<Map<String, dynamic>> getCashReport({String? startDate, String? endDate}) async {
    final s = startDate ?? '';
    final e = endDate ?? '';
    return await _get('$baseUrl/reports/cash?start_date=$s&end_date=$e');
  }

  static Future<Map<String, dynamic>> getProfitLossReport({String? startDate, String? endDate}) async {
    final s = startDate ?? '';
    final e = endDate ?? '';
    return await _get('$baseUrl/reports/profit-loss?start_date=$s&end_date=$e');
  }

  static Future<Map<String, dynamic>> getBalanceSheetReport({String? asOfDate}) async {
    final d = asOfDate ?? '';
    return await _get('$baseUrl/reports/balance-sheet?as_of_date=$d');
  }

  static Future<Map<String, dynamic>> getDebtsReceivablesReport() async {
    return await _get('$baseUrl/reports/debts-receivables');
  }

  // --- Users & Settings ---
  static Future<Map<String, dynamic>> getUsers() async {
    return await _get('$baseUrl/users');
  }

  static Future<Map<String, dynamic>> storeUser(Map<String, dynamic> data) async {
    return await _post('$baseUrl/users', data);
  }

  static Future<Map<String, dynamic>> updateUser(int id, Map<String, dynamic> data) async {
    return await _put('$baseUrl/users/$id', data);
  }

  static Future<Map<String, dynamic>> deleteUser(int id) async {
    return await _delete('$baseUrl/users/$id');
  }

  static Future<Map<String, dynamic>> toggleUserStatus(int id) async {
    return await _post('$baseUrl/users/$id/toggle-status', {});
  }

  static Future<Map<String, dynamic>> getSettings() async {
    return await _get('$baseUrl/settings');
  }
}
