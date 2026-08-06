import 'package:flutter/material.dart';

/// Lieu de stockage (GET /warehouses, WarehouseResource).
class Warehouse {
  const Warehouse({
    required this.id,
    required this.code,
    required this.name,
    this.typeCode,
    this.typeName,
    this.isActive = true,
  });

  final int id;
  final String code;
  final String name;

  /// Code du type de lieu : `depot`, `pos` (point de vente) ou `vehicle`.
  /// Présent seulement quand le serveur charge la relation `type`.
  final String? typeCode;
  final String? typeName;

  final bool isActive;

  String get label => code.isEmpty ? name : '$code — $name';

  /// Icône correspondant au type de lieu (dépôt, point de vente, véhicule).
  IconData get icon => warehouseTypeIcon(typeCode);

  /// Libellé lisible du type (« Dépôt », « Point de vente », « Véhicule »).
  String? get typeLabel => warehouseTypeLabel(typeCode) ?? typeName;

  factory Warehouse.fromJson(Map<String, dynamic> json) {
    final type = json['type'] as Map<String, dynamic>?;
    return Warehouse(
      id: json['id'] as int,
      code: json['code'] as String? ?? '',
      name: json['name'] as String? ?? '',
      typeCode: type?['code'] as String?,
      typeName: type?['name'] as String?,
      isActive: json['is_active'] != false,
    );
  }
}

/// Icône d'un type de lieu ; l'entrepôt sert de valeur par défaut.
IconData warehouseTypeIcon(String? typeCode) => switch (typeCode) {
      'pos' => Icons.storefront_outlined,
      'vehicle' => Icons.local_shipping_outlined,
      'depot' => Icons.warehouse_outlined,
      _ => Icons.warehouse_outlined,
    };

/// Libellé français d'un type de lieu, `null` si le type est inconnu.
String? warehouseTypeLabel(String? typeCode) => switch (typeCode) {
      'pos' => 'Point de vente',
      'vehicle' => 'Véhicule',
      'depot' => 'Dépôt',
      _ => null,
    };
