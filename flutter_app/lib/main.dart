import 'package:flutter/material.dart';
import 'screens/login_screen.dart';
import 'screens/dashboard_screen.dart';
import 'screens/transfer_screen.dart';
import 'screens/products_screen.dart';
import 'services/api_service.dart';
import 'services/notification_service.dart';
import 'utils/theme_config.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await NotificationService.init();

  final token = await ApiService.getToken();
  final String initialRoute = (token != null && token.isNotEmpty) ? '/dashboard' : '/login';

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
