import 'package:flutter_test/flutter_test.dart';

import 'package:igoutech_mobile/main.dart';

void main() {
  testWidgets('L\'application démarre sur l\'écran de chargement',
      (WidgetTester tester) async {
    await tester.pumpWidget(const IgoutechApp());

    // Le splash affiche le nom de l'application pendant l'initialisation.
    expect(find.text('IGOUTECH'), findsOneWidget);
  });
}
