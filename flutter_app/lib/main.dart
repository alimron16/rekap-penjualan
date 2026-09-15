import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:workmanager/workmanager.dart';
import 'screens/login_screen.dart';
import 'screens/dashboard_screen.dart';
import 'screens/transfer_screen.dart';
import 'screens/products_screen.dart';
import 'services/api_service.dart';
import 'services/notification_service.dart';
import 'utils/theme_config.dart';

// ============================================================
// FCM BACKGROUND MESSAGE HANDLER (top-level, vm:entry-point)
// Must be a top-level function (not inside a class).
// This runs in a separate Dart isolate when the app is KILLED.
// ============================================================
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  await NotificationService.init();

  final title = message.notification?.title ?? message.data['title'] ?? '🔔 Elephant POS';
  final body = message.notification?.body ?? message.data['body'] ?? 'Ada notifikasi baru untuk Anda.';
  final notifId = DateTime.now().millisecondsSinceEpoch ~/ 1000;

  await NotificationService.showNotification(
    id: notifId,
    title: title,
    body: body,
    payload: message.data['route'],
  );
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // ─── 1. Firebase (FCM) ───────────────────────────────────────
  try {
    await Firebase.initializeApp();
    FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);
  } catch (e) {
    debugPrint('Firebase init error: $e');
  }

  // ─── 2. Local Notifications ──────────────────────────────────
  try {
    await NotificationService.init();
  } catch (e) {
    debugPrint('Notification init error: $e');
  }

  // ─── 3. WorkManager (fallback polling every 15 min) ──────────
  try {
    await Workmanager().initialize(callbackDispatcher, isInDebugMode: false);
    await Workmanager().registerPeriodicTask(
      'elephant_pos_bg_poll',
      'elephant_pos_check_notifications',
      frequency: const Duration(minutes: 15),
      constraints: Constraints(networkType: NetworkType.connected),
    );
  } catch (e) {
    debugPrint('Workmanager init error: $e');
  }

  // ─── 4. Auto-route based on stored token ─────────────────────
  String initialRoute = '/login';
  try {
    final token = await ApiService.getToken();
    if (token != null && token.isNotEmpty) {
      initialRoute = '/dashboard';
    }
  } catch (e) {
    debugPrint('Token check error: $e');
  }

  runApp(ElephantPosApp(initialRoute: initialRoute));
}

class ElephantPosApp extends StatelessWidget {
  final String initialRoute;
  const ElephantPosApp({super.key, required this.initialRoute});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'ELEPHANT POS',
      debugShowCheckedModeBanner: false,
      theme: ThemeConfig.themeData,
      initialRoute: initialRoute,
      routes: {
        '/login': (context) => const LoginScreen(),
        '/dashboard': (context) => const DashboardScreen(),
        '/transfer': (context) => const TransferScreen(),
        '/products': (context) => const ProductsScreen(),
      },
    );
  }
}
