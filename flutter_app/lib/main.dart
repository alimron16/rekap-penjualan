import 'package:flutter/material.dart';
import 'package:workmanager/workmanager.dart';
import 'screens/login_screen.dart';
import 'screens/dashboard_screen.dart';
import 'screens/transfer_screen.dart';
import 'screens/products_screen.dart';
import 'services/api_service.dart';
import 'services/notification_service.dart';
import 'utils/theme_config.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  try {
    await NotificationService.init();
    await Workmanager().initialize(
      callbackDispatcher,
      isInDebugMode: false,
    );
    await Workmanager().registerPeriodicTask(
      "elephant_pos_bg_poll",
      "elephant_pos_check_notifications",
      frequency: const Duration(minutes: 15),
      constraints: Constraints(
        networkType: NetworkType.connected,
      ),
    );
  } catch (e) {
    debugPrint('Notification & Workmanager init error: $e');
  }

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
