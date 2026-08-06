import 'package:flutter/material.dart';

import '../../core/widgets.dart';

/// Date + heure lisibles : `05/08/2026 14:32`.
String formatDateTime(DateTime? value) {
  if (value == null) return '—';
  final local = value.toLocal();
  return '${local.day.toString().padLeft(2, '0')}/'
      '${local.month.toString().padLeft(2, '0')}/'
      '${local.year} '
      '${local.hour.toString().padLeft(2, '0')}:'
      '${local.minute.toString().padLeft(2, '0')}';
}

/// Libellé français d'un module de permission (`access` → « Accès »).
String moduleLabel(String module) => switch (module) {
      'access' => 'Accès',
      'catalog' => 'Catalogue',
      'customers' => 'Clients',
      'expenses' => 'Charges',
      'payments' => 'Règlements',
      'pricing' => 'Tarifs',
      'purchases' => 'Achats',
      'reports' => 'Rapports',
      'sales' => 'Ventes',
      'settings' => 'Paramètres',
      'stock' => 'Stock',
      'warehouses' => 'Lieux',
      _ => module,
    };

/// Message de succès (SnackBar verte flottante).
void showSuccessMessage(BuildContext context, String message) =>
    showSuccessSnack(ScaffoldMessenger.of(context), message);

/// Message d'échec (SnackBar rouge flottante).
void showFailureMessage(BuildContext context, String message) =>
    showErrorSnack(ScaffoldMessenger.of(context), message);

/// Ligne « libellé : valeur » utilisée dans les fiches de détail.
class DetailRow extends StatelessWidget {
  const DetailRow({super.key, required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(
              label,
              style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
            ),
          ),
          Expanded(
            child: Text(value, style: const TextStyle(fontSize: 13)),
          ),
        ],
      ),
    );
  }
}

