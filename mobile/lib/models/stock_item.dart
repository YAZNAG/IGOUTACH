/// Ligne de stock d'un lieu (GET /stock).
class StockItem {
  const StockItem({
    required this.productId,
    required this.sku,
    required this.name,
    required this.quantity,
    required this.averageCost,
    required this.value,
    required this.minStock,
    required this.status,
  });

  final int productId;
  final String sku;
  final String name;
  final int quantity;
  final double averageCost;
  final double value;
  final int minStock;

  /// `ok`, `low` (sous le seuil) ou `rupture`.
  final String status;

  bool get isBelowThreshold => status != 'ok';

  factory StockItem.fromJson(Map<String, dynamic> json) => StockItem(
        productId: json['product_id'] as int,
        sku: json['sku'] as String? ?? '',
        name: json['name'] as String? ?? '',
        quantity: (json['quantity'] as num?)?.toInt() ?? 0,
        averageCost: (json['average_cost'] as num?)?.toDouble() ?? 0,
        value: (json['value'] as num?)?.toDouble() ?? 0,
        minStock: (json['min_stock'] as num?)?.toInt() ?? 0,
        status: json['status'] as String? ?? 'ok',
      );
}
