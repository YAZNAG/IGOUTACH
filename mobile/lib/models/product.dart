/// Article du catalogue (GET /products, ProductResource).
class Product {
  const Product({
    required this.id,
    required this.sku,
    required this.name,
    this.currentStock,
  });

  final int id;
  final String sku;
  final String name;

  /// Stock du lieu demandé (présent seulement si `warehouse_id` est passé).
  final int? currentStock;

  factory Product.fromJson(Map<String, dynamic> json) => Product(
        id: json['id'] as int,
        sku: json['sku'] as String? ?? '',
        name: json['name'] as String? ?? '',
        currentStock: (json['current_stock'] as num?)?.toInt(),
      );
}
