/// Unité de mesure (GET /units, UnitResource).
class Unit {
  const Unit({
    required this.id,
    required this.code,
    required this.name,
    required this.isDecimal,
    required this.position,
    required this.isActive,
    this.productsCount,
  });

  final int id;
  final String code;
  final String name;

  /// Quantités décimales autorisées (kg, m…) ou non (pièce).
  final bool isDecimal;
  final int position;
  final bool isActive;
  final int? productsCount;

  factory Unit.fromJson(Map<String, dynamic> json) => Unit(
        id: json['id'] as int,
        code: json['code'] as String? ?? '',
        name: json['name'] as String? ?? '',
        isDecimal: json['is_decimal'] == true,
        position: (json['position'] as num?)?.toInt() ?? 0,
        isActive: json['is_active'] != false,
        productsCount: (json['products_count'] as num?)?.toInt(),
      );
}
