import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../models/warehouse.dart';

/// Filtre « lieu » des listes documentaires (bons de commande, réceptions,
/// transferts).
///
/// Variante de [WarehouseScope] qui autorise en plus le choix « Tous les
/// lieux » : un administrateur global consulte par défaut l'ensemble des
/// documents, alors qu'un utilisateur rattaché à un lieu reste borné à
/// CE lieu (sélecteur verrouillé, conformément à la règle produit).
class WarehouseFilter {
  const WarehouseFilter({
    required this.warehouses,
    required this.selectedId,
    required this.locked,
  });

  final List<Warehouse> warehouses;

  /// `null` = tous les lieux.
  final int? selectedId;

  /// L'utilisateur est rattaché à un lieu : il ne peut pas en changer.
  final bool locked;

  static const WarehouseFilter empty =
      WarehouseFilter(warehouses: [], selectedId: null, locked: false);

  WarehouseFilter select(int? id) => WarehouseFilter(
        warehouses: warehouses,
        selectedId: id,
        locked: locked,
      );

  String? get selectedLabel {
    for (final w in warehouses) {
      if (w.id == selectedId) return w.label;
    }
    return null;
  }

  /// Charge la liste des lieux ; en cas de 403 (`warehouse.view` absent) le
  /// filtre est vide et donc masqué.
  static Future<WarehouseFilter> load(int? userWarehouseId) async {
    List<Warehouse> list;
    try {
      final res = await ApiClient.instance.dio.get<Map<String, dynamic>>(
        '/warehouses',
      );
      final data = res.data!['data'] as List<dynamic>? ?? const [];
      list = data
          .map((e) => Warehouse.fromJson(e as Map<String, dynamic>))
          .where((w) => w.isActive)
          .toList();
    } catch (_) {
      list = const [];
    }

    return WarehouseFilter(
      warehouses: list,
      selectedId: userWarehouseId,
      locked: userWarehouseId != null,
    );
  }
}

/// Menu déroulant du filtre « lieu » (masqué s'il n'y a rien à choisir).
class WarehouseFilterField extends StatelessWidget {
  const WarehouseFilterField({
    super.key,
    required this.filter,
    required this.onChanged,
  });

  final WarehouseFilter filter;
  final ValueChanged<int?> onChanged;

  @override
  Widget build(BuildContext context) {
    if (filter.locked || filter.warehouses.length < 2) {
      return const SizedBox.shrink();
    }

    return DropdownButtonFormField<int?>(
      initialValue: filter.selectedId,
      isExpanded: true,
      decoration: const InputDecoration(
        labelText: 'Lieu',
        prefixIcon: Icon(Icons.warehouse_outlined),
        isDense: true,
      ),
      items: [
        const DropdownMenuItem<int?>(child: Text('Tous les lieux')),
        ...filter.warehouses.map(
          (w) => DropdownMenuItem<int?>(
            value: w.id,
            child: Text(w.label, overflow: TextOverflow.ellipsis),
          ),
        ),
      ],
      onChanged: onChanged,
    );
  }
}
