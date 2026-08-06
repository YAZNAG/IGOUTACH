import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:igoutech_mobile/core/theme.dart';
import 'package:igoutech_mobile/core/widgets.dart';
import 'package:igoutech_mobile/main.dart';

void main() {
  testWidgets('L\'application démarre sur l\'écran de chargement',
      (WidgetTester tester) async {
    await tester.pumpWidget(const IgoutechApp());

    // Le splash affiche le nom de l'application pendant l'initialisation.
    expect(find.text('IGOUTECH'), findsOneWidget);
  });

  testWidgets('La quantité se saisit au clavier et ne reste jamais vide',
      (WidgetTester tester) async {
    var quantity = 1;

    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.light(),
        home: Scaffold(
          body: StatefulBuilder(
            builder: (context, setState) => Column(
              children: [
                QuantityStepper(
                  quantity: quantity,
                  onChanged: (value) => setState(() => quantity = value),
                ),
                // Cible de sortie de champ pour tester le retour au minimum.
                const TextField(key: Key('ailleurs')),
              ],
            ),
          ),
        ),
      ),
    );

    // Saisie directe : vendre 250 unités ne demande pas 249 appuis.
    await tester.enterText(find.byType(TextField).first, '250');
    await tester.pump();
    expect(quantity, 250);

    // Le bouton + repart de la valeur saisie.
    await tester.tap(find.byIcon(Icons.add));
    await tester.pump();
    expect(quantity, 251);

    // Champ vidé puis quitté : retour au minimum, jamais d'état invalide.
    await tester.enterText(find.byType(TextField).first, '');
    await tester.tap(find.byKey(const Key('ailleurs')));
    await tester.pumpAndSettle();
    expect(quantity, 1);
  });

  // Les écrans sont utilisés sur des téléphones étroits (360 dp) comme sur
  // des tablettes : aucun composant partagé ne doit déborder. En mode debug,
  // un débordement lève une exception, ce qui fait échouer ces tests.
  for (final size in const [Size(360, 640), Size(1024, 768)]) {
    testWidgets(
      'Les composants partagés ne débordent pas en ${size.width.toInt()} dp',
      (WidgetTester tester) async {
        tester.view.physicalSize = size;
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.reset);

        await tester.pumpWidget(
          MaterialApp(
            theme: AppTheme.light(),
            home: Scaffold(
              bottomNavigationBar: BottomActionBar(
                label: 'Enregistrer la vente',
                summaryLabel: 'Total',
                summaryValue: '1 234 567,89 DH',
                onPressed: () {},
              ),
              body: ListView(
                children: [
                  const SkeletonCard(hasLeading: true, lines: 3),
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Expanded(
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Câble électrique souple 3G2,5 couronne '
                                  'de 100 mètres',
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                StatusBadge(
                                  label: 'En attente d\'approbation',
                                  color: AppTheme.warning,
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 10),
                          AmountText(
                            '1 234 567,89 DH',
                            label: 'Solde 1 234 567,89 DH',
                          ),
                        ],
                      ),
                    ),
                  ),
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: QuantityStepper(
                        quantity: 12,
                        onChanged: (value) {},
                      ),
                    ),
                  ),
                  const SizedBox(
                    height: 360,
                    child: EmptyView(
                      icon: Icons.people_outline,
                      title: 'Aucun client',
                      message: 'Créez une fiche client pour suivre '
                          'ses achats et son encours.',
                      actionLabel: 'Ajouter un client',
                    ),
                  ),
                ],
              ),
            ),
          ),
        );

        await tester.pump();
        expect(tester.takeException(), isNull);
      },
    );
  }
}
