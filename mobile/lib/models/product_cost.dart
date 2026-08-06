/// Coût d'un article (GET /product-costs).
class ProductCostRow {
  const ProductCostRow({
    required this.id,
    required this.sku,
    required this.name,
    this.category,
    required this.totalQuantity,
    required this.cmup,
    required this.stockValue,
    this.lastPurchasePrice,
    this.lastPurchaseAt,
    this.detailPrice,
    this.marginPercent,
    required this.belowCost,
  });

  final int id;
  final String sku;
  final String name;
  final String? category;
  final int totalQuantity;

  /// Coût moyen unitaire pondéré, tous lieux confondus.
  final double cmup;
  final double stockValue;
  final double? lastPurchasePrice;
  final String? lastPurchaseAt;
  final double? detailPrice;
  final double? marginPercent;

  /// Le prix détail en vigueur est inférieur au CMUP : vente à perte.
  final bool belowCost;

  factory ProductCostRow.fromJson(Map<String, dynamic> json) => ProductCostRow(
        id: json['id'] as int,
        sku: json['sku'] as String? ?? '',
        name: json['name'] as String? ?? '',
        category: json['category'] as String?,
        totalQuantity: (json['total_quantity'] as num?)?.toInt() ?? 0,
        cmup: (json['cmup'] as num?)?.toDouble() ?? 0,
        stockValue: (json['stock_value'] as num?)?.toDouble() ?? 0,
        lastPurchasePrice: (json['last_purchase_price'] as num?)?.toDouble(),
        lastPurchaseAt: json['last_purchase_at'] as String?,
        detailPrice: (json['detail_price'] as num?)?.toDouble(),
        marginPercent: (json['margin_percent'] as num?)?.toDouble(),
        belowCost: json['below_cost'] == true,
      );
}

/// Totaux du bloc `totals` de GET /product-costs (sur l'ensemble filtré).
class ProductCostTotals {
  const ProductCostTotals({
    required this.totalQuantity,
    required this.totalValue,
  });

  final int totalQuantity;
  final double totalValue;

  factory ProductCostTotals.fromJson(Map<String, dynamic> json) =>
      ProductCostTotals(
        totalQuantity: (json['total_quantity'] as num?)?.toInt() ?? 0,
        totalValue: (json['total_value'] as num?)?.toDouble() ?? 0,
      );
}
