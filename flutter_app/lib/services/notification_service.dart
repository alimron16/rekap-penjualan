import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:workmanager/workmanager.dart';
import 'api_service.dart';
import '../utils/formatters.dart';

@pragma('vm:entry-point')
void callbackDispatcher() {
  Workmanager().executeTask((taskName, inputData) async {
    try {
      // In background isolate, re-initialize notifications
      await NotificationService.init();

      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null || token.isEmpty) return true;

      final res = await ApiService.pollNotifications();
      if (res['success'] == true) {
        final pendingCount = res['pending_transfers_count'] ?? 0;
        final latestPending = res['latest_pending'];
        final myUpdated = res['my_recent_updated'];

        final lastAlertId = prefs.getInt('last_alert_transfer_id') ?? 0;

        if (pendingCount > 0 && latestPending != null) {
          final currentId = latestPending['id'] ?? 0;
          if (currentId != lastAlertId) {
            await prefs.setInt('last_alert_transfer_id', currentId);
            final sender = latestPending['user']?['name'] ?? 'Toko Agen';
            final amount = Formatters.parseDouble(latestPending['amount']);
            await NotificationService.showNotification(
              id: 1001,
              title: '🔔 Request Transfer Masuk! (${Formatters.formatRupiah(amount)})',
              body: 'Pengajuan transfer dari $sender senilai ${Formatters.formatRupiah(amount)}. Silakan buka aplikasi untuk menyetujui.',
            );
          }
        }

        if (myUpdated != null) {
          final updatedId = myUpdated['id'] ?? 0;
          final lastMyAlertId = prefs.getInt('last_alert_my_transfer_id') ?? 0;
          if (updatedId != lastMyAlertId) {
            await prefs.setInt('last_alert_my_transfer_id', updatedId);
            final status = (myUpdated['status'] ?? '').toString().toUpperCase();
            final amount = Formatters.parseDouble(myUpdated['amount']);
            final isApproved = status == 'APPROVED';
            await NotificationService.showNotification(
              id: 1002,
              title: isApproved ? '✅ Transfer Berhasil Disetujui!' : '❌ Transfer Ditolak',
              body: 'Pengajuan transfer saldo Anda (${Formatters.formatRupiah(amount)}) telah ${isApproved ? 'disetujui' : 'ditolak'}.',
            );
          }
        }
      }
    } catch (e) {
      debugPrint('Error in WorkManager background task: $e');
    }
    return true;
  });
}

class NotificationService {
  static final FlutterLocalNotificationsPlugin _notificationsPlugin =
      FlutterLocalNotificationsPlugin();

  static const String channelId = 'elephant_pos_channel_high';
  static const String channelName = 'Elephant POS Notifikasi Penting';
  static const String channelDesc = 'Pemberitahuan real-time transfer agen, kasir, dan persediaan';

  static Future<void> init() async {
    const AndroidInitializationSettings initializationSettingsAndroid =
        AndroidInitializationSettings('@mipmap/ic_launcher');

    const InitializationSettings initializationSettings = InitializationSettings(
      android: initializationSettingsAndroid,
    );

    await _notificationsPlugin.initialize(
      settings: initializationSettings,
    );

    const AndroidNotificationChannel channel = AndroidNotificationChannel(
      channelId,
      channelName,
      description: channelDesc,
      importance: Importance.max,
      playSound: true,
      enableVibration: true,
      showBadge: true,
    );

    final androidPlugin = _notificationsPlugin
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>();

    await androidPlugin?.createNotificationChannel(channel);
  }

  static Future<bool> requestPermission() async {
    try {
      final androidPlugin = _notificationsPlugin
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>();
      
      final granted = await androidPlugin?.requestNotificationsPermission();
      if (granted == true) return true;

      final status = await Permission.notification.request();
      return status.isGranted;
    } catch (e) {
      debugPrint('Error requesting notification permission: $e');
      return false;
    }
  }

  static Future<void> showNotification({
    required int id,
    required String title,
    required String body,
    String? payload,
  }) async {
    await requestPermission();

    final AndroidNotificationDetails androidPlatformChannelSpecifics =
        AndroidNotificationDetails(
      channelId,
      channelName,
      channelDescription: channelDesc,
      importance: Importance.max,
      priority: Priority.max,
      icon: '@mipmap/ic_launcher',
      playSound: true,
      enableVibration: true,
      fullScreenIntent: true,
      category: AndroidNotificationCategory.status,
      styleInformation: BigTextStyleInformation(
        body,
        contentTitle: title,
        summaryText: 'Elephant POS',
      ),
    );

    final NotificationDetails platformChannelSpecifics =
        NotificationDetails(android: androidPlatformChannelSpecifics);

    await _notificationsPlugin.show(
      id: id,
      title: title,
      body: body,
      notificationDetails: platformChannelSpecifics,
      payload: payload,
    );
  }
}
