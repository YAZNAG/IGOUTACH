/// Catégorie d'articles (GET /categories, CategoryResource).
///
/// L'API n'expose pas `parent_id` : la liste est donc plate côté mobile,
/// même si le classement (`position`) est géré côté web.
class ProductCategory {
  const ProductCategory({
    required this.id,
    required this.name,
    this.position,
    required this.requiresSerial,
    required this.isActive,
    this.productsCount,
  });

  final int id;
  final String name;
  final int? position;

  /// Les articles de cette catégorie exigent un numéro de série.
  final bool requiresSerial;
  final bool isActive;
  final int? productsCount;

  factory ProductCategory.fromJson(Map<String, dynamic> json) =>
      ProductCategory(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        position: (json['position'] as num?)?.toInt(),
        requiresSerial: json['requires_serial'] == true,
        isActive: json['is_active'] != false,
        productsCount: (json['products_count'] as num?)?.toInt(),
      );
}
