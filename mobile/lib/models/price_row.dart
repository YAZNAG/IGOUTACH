/// Niveau de prix (détail / demi-gros / gros) d'un article.
class PriceLevel {
  const PriceLevel({this.amount, this.minQuantity});

  final double? amount;
  final int? minQuantity;

  factory PriceLevel.fromJson(Map<String, dynamic> json) => PriceLevel(
        amount: (json['amount'] as num?)?.toDouble(),
        minQuantity: (json['min_quantity'] as num?)?.toInt(),
      );
}

/// Article avec ses trois niveaux de prix (GET /prices).
class PriceRow {
  const PriceRow({
    required this.id,
    required this.sku,
    required this.name,
    required this.prices,
  });

  final int id;
  final String sku;
  final String name;

  /// Clés : `detail`, `semi_gros`, `gros`.
  final Map<String, PriceLevel> prices;

  factory PriceRow.fromJson(Map<String, dynamic> json) {
    final raw = json['prices'] as Map<String, dynamic>? ?? {};
    return PriceRow(
      id: json['id'] as int,
      sku: json['sku'] as String? ?? '',
      name: json['name'] as String? ?? '',
      prices: raw.map(
        (code, level) => MapEntry(
          code,
          PriceLevel.fromJson(level as Map<String, dynamic>),
        ),
      ),
    );
  }
}
