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

  /// Lieu sélectionné, `null` si la liste n'a pas pu être chargée (403).
  Warehouse? get selected {
    for (final w in warehouses) {
      if (w.id == selectedId) return w;
    }
    return null;
  }

  String? get selectedLabel => selected?.label;

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

/// Rappel du lieu courant sous le titre de l'AppBar : icône selon le type
/// (dépôt, point de vente, véhicule) puis nom du lieu.
class WarehouseAppBarLabel extends StatelessWidget
    implements PreferredSizeWidget {
  const WarehouseAppBarLabel({super.key, required this.warehouse});

  final Warehouse warehouse;

  @override
  Size get preferredSize => const Size.fromHeight(30);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
      child: Row(
        children: [
          Icon(
            warehouse.icon,
            size: 17,
            color: Colors.white.withValues(alpha: 0.85),
          ),
          const SizedBox(width: 7),
          Expanded(
            child: Text(
              warehouse.label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.85),
                fontSize: 14,
                height: 1.2,
              ),
            ),
          ),
        ],
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
      margin: const EdgeInsets.fromLTRB(12, 10, 12, 0),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AppTheme.sky.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppTheme.sky.withValues(alpha: 0.25)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon ?? Icons.info_outline, size: 20, color: AppTheme.sky),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(
                fontSize: 14,
                height: 1.35,
                color: AppTheme.navy,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
