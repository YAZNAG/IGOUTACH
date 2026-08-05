import 'package:flutter/material.dart';

import 'movements_list_screen.dart';

/// Sorties de stock du lieu de l'utilisateur (GET /stock/exits).
///
/// Ventes, transferts expédiés et régularisations négatives : quantités
/// affichées en −rouge et valorisées au CMUP de sortie.
class StockExitsScreen extends StatelessWidget {
  const StockExitsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const MovementsListScreen(
      title: 'Sorties',
      basePath: '/stock/exits',
      isExit: true,
    );
  }
}
