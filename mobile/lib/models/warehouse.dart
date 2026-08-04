/// Lieu de stockage (GET /warehouses, WarehouseResource).
class Warehouse {
  const Warehouse({
    required this.id,
    required this.code,
    required this.name,
    this.isActive = true,
  });

  final int id;
  final String code;
  final String name;
  final bool isActive;

  String get label => code.isEmpty ? name : '$code — $name';

  factory Warehouse.fromJson(Map<String, dynamic> json) => Warehouse(
        id: json['id'] as int,
        code: json['code'] as String? ?? '',
        name: json['name'] as String? ?? '',
        isActive: json['is_active'] != false,
      );
}
