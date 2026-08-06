import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../models/supplier.dart';

/// Charge les fournisseurs actifs (GET /suppliers).
///
/// Retourne une liste vide en cas de 403 (`supplier.view` absent) : les
/// écrans masquent alors simplement le filtre fournisseur.
Future<List<Supplier>> loadSuppliers() async {
  try {
    final res = await ApiClient.instance.dio.get<Map<String, dynamic>>(
      '/suppliers',
      queryParameters: {'per_page': 100, 'is_active': 1},
    );
    final data = res.data!['data'] as List<dynamic>? ?? const [];
    return data
        .map((e) => Supplier.fromJson(e as Map<String, dynamic>))
        .toList();
  } catch (_) {
    return const [];
  }
}

/// Menu déroulant de filtre fournisseur (« Tous les fournisseurs » inclus).
class SupplierFilterField extends StatelessWidget {
  const SupplierFilterField({
    super.key,
    required this.suppliers,
    required this.selectedId,
    required this.onChanged,
  });

  final List<Supplier> suppliers;
  final int? selectedId;
  final ValueChanged<int?> onChanged;

  @override
  Widget build(BuildContext context) {
    if (suppliers.isEmpty) return const SizedBox.shrink();

    return DropdownButtonFormField<int?>(
      initialValue: selectedId,
      isExpanded: true,
      decoration: const InputDecoration(
        labelText: 'Fournisseur',
        prefixIcon: Icon(Icons.local_shipping_outlined),
        isDense: true,
      ),
      items: [
        const DropdownMenuItem<int?>(child: Text('Tous les fournisseurs')),
        ...suppliers.map(
          (s) => DropdownMenuItem<int?>(
            value: s.id,
            child: Text(s.label, overflow: TextOverflow.ellipsis),
          ),
        ),
      ],
      onChanged: onChanged,
    );
  }
}
