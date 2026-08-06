/// Niveau de prix détaillé d'un article (GET /products/{id}/prices).
class PriceLevelDetail {
  const PriceLevelDetail({
    required this.priceTypeId,
    required this.code,
    required this.name,
    this.minQuantity,
    this.amount,
    required this.minMarginPercent,
    this.marginPercent,
    required this.floorPrice,
    required this.belowFloor,
  });

  final int priceTypeId;

  /// `detail`, `semi_gros` ou `gros`.
  final String code;
  final String name;
  final int? minQuantity;
  final double? amount;

  /// Marge minimale exigée sur ce niveau (%).
  final double minMarginPercent;
  final double? marginPercent;

  /// Prix plancher = coût × (1 + marge minimale).
  final double floorPrice;
  final bool belowFloor;

  factory PriceLevelDetail.fromJson(Map<String, dynamic> json) =>
      PriceLevelDetail(
        priceTypeId: (json['price_type_id'] as num?)?.toInt() ?? 0,
        code: json['code'] as String? ?? '',
        name: json['name'] as String? ?? '',
        minQuantity: (json['min_quantity'] as num?)?.toInt(),
        amount: (json['amount'] as num?)?.toDouble(),
        minMarginPercent:
            (json['min_margin_percent'] as num?)?.toDouble() ?? 0,
        marginPercent: (json['margin_percent'] as num?)?.toDouble(),
        floorPrice: (json['floor_price'] as num?)?.toDouble() ?? 0,
        belowFloor: json['below_floor'] == true,
      );
}

/// Tarification complète d'un article (GET /products/{id}/prices).
class ProductPricing {
  const ProductPricing({
    required this.productId,
    required this.sku,
    required this.name,
    this.unitCost,
    required this.levels,
  });

  final int productId;
  final String sku;
  final String name;

  /// Coût unitaire : `null` sans la permission `product.view_cost_price`.
  final double? unitCost;
  final List<PriceLevelDetail> levels;

  factory ProductPricing.fromJson(Map<String, dynamic> json) {
    final product = json['product'] as Map<String, dynamic>? ?? {};
    return ProductPricing(
      productId: (product['id'] as num?)?.toInt() ?? 0,
      sku: product['sku'] as String? ?? '',
      name: product['name'] as String? ?? '',
      unitCost: (json['unit_cost'] as num?)?.toDouble(),
      levels: (json['levels'] as List<dynamic>? ?? [])
          .map((e) => PriceLevelDetail.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

/// Tarif sous son prix plancher (GET /prices/below-floor).
class BelowFloorRow {
  const BelowFloorRow({
    required this.productId,
    required this.sku,
    required this.name,
    required this.priceType,
    required this.amount,
  });

  final int productId;
  final String sku;
  final String name;
  final String priceType;
  final double amount;

  factory BelowFloorRow.fromJson(Map<String, dynamic> json) => BelowFloorRow(
        productId: (json['product_id'] as num?)?.toInt() ?? 0,
        sku: json['sku'] as String? ?? '',
        name: json['name'] as String? ?? '',
        priceType: json['price_type'] as String? ?? '',
        amount: (json['amount'] as num?)?.toDouble() ?? 0,
      );
}

/// Ligne de prévisualisation d'une MAJ en masse par % (POST /prices/bulk-update).
class BulkPercentRow {
  const BulkPercentRow({
    required this.productId,
    required this.sku,
    required this.name,
    required this.current,
    required this.next,
  });

  final int productId;
  final String sku;
  final String name;
  final double current;
  final double next;

  factory BulkPercentRow.fromJson(Map<String, dynamic> json) => BulkPercentRow(
        productId: (json['product_id'] as num?)?.toInt() ?? 0,
        sku: json['sku'] as String? ?? '',
        name: json['name'] as String? ?? '',
        current: (json['current'] as num?)?.toDouble() ?? 0,
        next: (json['next'] as num?)?.toDouble() ?? 0,
      );
}

/// Ligne de prévisualisation d'une MAJ en masse par marge
/// (POST /prices/bulk-margin) : un couple actuel/futur par niveau demandé.
class BulkMarginRow {
  const BulkMarginRow({
    required this.productId,
    required this.sku,
    required this.name,
    required this.cost,
    required this.levels,
  });

  final int productId;
  final String sku;
  final String name;

  /// Coût unitaire retenu pour le calcul de la marge.
  final double cost;

  /// Clé : code du niveau ; valeurs : prix actuel (peut être nul) et futur.
  final Map<String, ({double? current, double next})> levels;

  factory BulkMarginRow.fromJson(Map<String, dynamic> json) {
    final raw = json['levels'] as Map<String, dynamic>? ?? {};
    return BulkMarginRow(
      productId: (json['product_id'] as num?)?.toInt() ?? 0,
      sku: json['sku'] as String? ?? '',
      name: json['name'] as String? ?? '',
      cost: (json['cost'] as num?)?.toDouble() ?? 0,
      levels: raw.map((code, value) {
        final level = value as Map<String, dynamic>;
        return MapEntry(code, (
          current: (level['current'] as num?)?.toDouble(),
          next: (level['next'] as num?)?.toDouble() ?? 0,
        ));
      }),
    );
  }
}
