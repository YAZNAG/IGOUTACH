import 'package:flutter/material.dart';

import 'movements_list_screen.dart';

/// Entrées de stock du lieu de l'utilisateur (GET /stock/entries).
///
/// Réceptions fournisseur, retours clients, transferts reçus et
/// régularisations d'inventaire positives : quantités en +vert, coût unitaire
/// affiché en « PU ».
class StockEntriesScreen extends StatelessWidget {
  const StockEntriesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const MovementsListScreen(
      title: 'Entrées',
      basePath: '/stock/entries',
      isExit: false,
    );
  }
}
