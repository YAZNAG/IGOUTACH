import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../models/warehouse.dart';

/// Périmètre « lieu » d'un écran.
///
/// Règle produit : un utilisateur rattaché à un lieu travaille toujours sur
/// CE lieu (aucun sélecteur). Un administrateur global (sans `warehouse_id`)
/// choisit son lieu en haut de l'écran.
///
/// `GET /warehouses` exige la permission `warehouse.view` : en cas de 403 on
/// se rabat sur le lieu de l'utilisateur, et si celui-ci n'en a pas le filtre
/// est simplement masqué (le serveur renvoie alors tous les lieux).
class WarehouseScope {
  const WarehouseScope({
    required this.warehouses,
    required this.selectedId,
    required this.canChoose,
  });

  final List<Warehouse> warehouses;
  final int? selectedId;

  /// L'utilisateur peut-il changer de lieu (admin global + liste accessible) ?
  final bool canChoose;

  WarehouseScope copyWith({int? selectedId}) => WarehouseScope(
        warehouses: warehouses,
        selectedId: selectedId ?? this.selectedId,
        canChoose: canChoose,
      );

  String? get selectedLabel {
    for (final w in warehouses) {
      if (w.id == selectedId) return w.label;
    }
    return null;
  }

  /// Charge le périmètre pour l'utilisateur courant.
  static Future<WarehouseScope> load(int? userWarehouseId) async {
    if (userWarehouseId != null) {
      // Lieu imposé : inutile d'appeler /warehouses pour le nom, on tente
      // quand même (échec silencieux) afin d'afficher un libellé lisible.
      final list = await _fetch();
      return WarehouseScope(
        warehouses: list,
        selectedId: userWarehouseId,
        canChoose: false,
      );
    }

    final list = await _fetch();
    return WarehouseScope(
      warehouses: list,
      selectedId: list.isEmpty ? null : list.first.id,
      canChoose: list.isNotEmpty,
    );
  }

  static Future<List<Warehouse>> _fetch() async {
    try {
      final res = await ApiClient.instance.dio.get<Map<String, dynamic>>(
        '/warehouses',
      );
      final data = res.data!['data'] as List<dynamic>? ?? [];
      return data
          .map((e) => Warehouse.fromJson(e as Map<String, dynamic>))
          .where((w) => w.isActive)
          .toList();
    } catch (_) {
      // 403 (warehouse.view absent) ou réseau : pas de liste de lieux.
      return const [];
    }
  }
}

/// Bandeau de sélection du lieu, affiché uniquement quand l'utilisateur
/// n'est rattaché à aucun lieu et que la liste est accessible.
class WarehouseSelectorBar extends StatelessWidget {
  const WarehouseSelectorBar({
    super.key,
    required this.scope,
    required this.onChanged,
  });

  final WarehouseScope scope;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    if (!scope.canChoose || scope.warehouses.length < 2) {
      return const SizedBox.shrink();
    }

    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 0),
      child: DropdownButtonFormField<int>(
        initialValue: scope.selectedId,
        isExpanded: true,
        decoration: const InputDecoration(
          labelText: 'Lieu',
          prefixIcon: Icon(Icons.warehouse_outlined),
          contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        ),
        items: scope.warehouses
            .map(
              (w) => DropdownMenuItem(
                value: w.id,
                child: Text(w.label, overflow: TextOverflow.ellipsis),
              ),
            )
            .toList(),
        onChanged: (value) {
          if (value != null) onChanged(value);
        },
      ),
    );
  }
}

/// Bandeau d'information discret (fond bleu clair, icône « info »).
class InfoBanner extends StatelessWidget {
  const InfoBanner({super.key, required this.message, this.icon});

  final String message;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.fromLTRB(12, 8, 12, 0),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: AppTheme.sky.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon ?? Icons.info_outline, size: 16, color: AppTheme.sky),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(fontSize: 12, color: AppTheme.navy),
            ),
          ),
        ],
      ),
    );
  }
}
