import 'package:flutter_test/flutter_test.dart';
import 'package:elephant_pos_flutter/main.dart';

void main() {
  testWidgets('App root test', (WidgetTester tester) async {
    await tester.pumpWidget(const ElephantPosApp(initialRoute: '/login'));
    expect(find.byType(ElephantPosApp), findsOneWidget);
  });
}
