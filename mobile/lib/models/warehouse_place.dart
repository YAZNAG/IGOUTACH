/// Type de lieu (GET /warehouse-types) : dépôt, point de vente, véhicule…
class WarehouseType {
  const WarehouseType({
    required this.id,
    required this.code,
    required this.name,
    required this.allowsSales,
    required this.allowsPurchaseReceipt,
    required this.requiresTransferApproval,
  });

  final int id;
  final String code;
  final String name;
  final bool allowsSales;
  final bool allowsPurchaseReceipt;
  final bool requiresTransferApproval;

  factory WarehouseType.fromJson(Map<String, dynamic> json) => WarehouseType(
        id: json['id'] as int,
        code: json['code'] as String? ?? '',
        name: json['name'] as String? ?? '',
        allowsSales: json['allows_sales'] == true,
        allowsPurchaseReceipt: json['allows_purchase_receipt'] == true,
        requiresTransferApproval: json['requires_transfer_approval'] == true,
      );
}

/// Lieu complet (GET /warehouses, WarehouseResource).
///
/// Distinct de `Warehouse` (modèle allégé des sélecteurs) : porte le type et
/// les coordonnées nécessaires au formulaire.
class WarehousePlace {
  const WarehousePlace({
    required this.id,
    required this.code,
    required this.name,
    this.warehouseTypeId,
    this.typeCode,
    this.typeName,
    this.managerId,
    this.parentId,
    this.address,
    this.city,
    this.phone,
    required this.isActive,
  });

  final int id;
  final String code;
  final String name;
  final int? warehouseTypeId;
  final String? typeCode;
  final String? typeName;
  final int? managerId;
  final int? parentId;
  final String? address;
  final String? city;
  final String? phone;
  final bool isActive;

  String get label => code.isEmpty ? name : '$code — $name';

  factory WarehousePlace.fromJson(Map<String, dynamic> json) {
    final type = json['type'] as Map<String, dynamic>?;
    return WarehousePlace(
      id: json['id'] as int,
      code: json['code'] as String? ?? '',
      name: json['name'] as String? ?? '',
      warehouseTypeId: (json['warehouse_type_id'] as num?)?.toInt(),
      typeCode: type?['code'] as String?,
      typeName: type?['name'] as String?,
      managerId: (json['manager_id'] as num?)?.toInt(),
      parentId: (json['parent_id'] as num?)?.toInt(),
      address: json['address'] as String?,
      city: json['city'] as String?,
      phone: json['phone'] as String?,
      isActive: json['is_active'] != false,
    );
  }
}
