/// Article complet du catalogue (GET /products, ProductResource).
///
/// Distinct de `Product` (modèle allégé du sélecteur d'articles) : porte
/// tous les champs éditables de la fiche article.
class CatalogProduct {
  const CatalogProduct({
    required this.id,
    required this.sku,
    this.barcode,
    required this.name,
    this.description,
    this.categoryId,
    this.categoryName,
    this.brandId,
    this.unitId,
    this.salePrice,
    this.costPrice,
    this.taxRate,
    this.minStock,
    required this.isSerialized,
    required this.isActive,
    this.currentStock,
  });

  final int id;
  final String sku;
  final String? barcode;
  final String name;
  final String? description;
  final int? categoryId;
  final String? categoryName;
  final int? brandId;
  final int? unitId;
  final double? salePrice;

  /// Prix d'achat : renvoyé uniquement avec `product.view_cost_price`.
  final double? costPrice;
  final double? taxRate;

  /// Seuil d'alerte de stock.
  final int? minStock;
  final bool isSerialized;
  final bool isActive;

  /// Stock du lieu demandé (présent seulement si `warehouse_id` est passé).
  final int? currentStock;

  factory CatalogProduct.fromJson(Map<String, dynamic> json) {
    final category = json['category'] as Map<String, dynamic>?;
    return CatalogProduct(
      id: json['id'] as int,
      sku: json['sku'] as String? ?? '',
      barcode: json['barcode'] as String?,
      name: json['name'] as String? ?? '',
      description: json['description'] as String?,
      categoryId: (json['category_id'] as num?)?.toInt(),
      categoryName: category?['name'] as String?,
      brandId: (json['brand_id'] as num?)?.toInt(),
      unitId: (json['unit_id'] as num?)?.toInt(),
      salePrice: (json['sale_price'] as num?)?.toDouble(),
      costPrice: (json['cost_price'] as num?)?.toDouble(),
      taxRate: (json['tax_rate'] as num?)?.toDouble(),
      minStock: (json['min_stock'] as num?)?.toInt(),
      isSerialized: json['is_serialized'] == true,
      isActive: json['is_active'] != false,
      currentStock: (json['current_stock'] as num?)?.toInt(),
    );
  }
}
