/// Ligne du journal des entrées (GET /stock/entries) ou des sorties
/// (GET /stock/exits). Les deux contrôleurs renvoient la même forme ;
/// pour les sorties la quantité est déjà en valeur absolue et le coût
/// unitaire correspond au CMUP de sortie.
class StockMovementRow {
  const StockMovementRow({
    required this.id,
    this.date,
    this.typeCode,
    this.typeName,
    this.sourceLabel,
    this.warehouseCode,
    this.warehouseName,
    this.productId,
    this.sku,
    this.productName,
    this.unit,
    required this.quantity,
    required this.unitCost,
    required this.lineValue,
    required this.balanceAfter,
    this.note,
    this.author,
  });

  final int id;

  /// Format `Y-m-d H:i:s`.
  final String? date;
  final String? typeCode;
  final String? typeName;
  final String? sourceLabel;
  final String? warehouseCode;
  final String? warehouseName;
  final int? productId;
  final String? sku;
  final String? productName;
  final String? unit;
  final int quantity;
  final double unitCost;
  final double lineValue;
  final int balanceAfter;
  final String? note;
  final String? author;

  /// Date sans l'heure, pour les listes.
  String get shortDate =>
      (date ?? '').length >= 16 ? date!.substring(0, 16) : (date ?? '—');

  factory StockMovementRow.fromJson(Map<String, dynamic> json) {
    final type = json['type'] as Map<String, dynamic>?;
    final source = json['source'] as Map<String, dynamic>?;
    final warehouse = json['warehouse'] as Map<String, dynamic>?;
    final product = json['product'] as Map<String, dynamic>?;

    return StockMovementRow(
      id: json['id'] as int,
      date: json['date'] as String?,
      typeCode: type?['code'] as String?,
      typeName: type?['name'] as String?,
      sourceLabel: source?['label'] as String?,
      warehouseCode: warehouse?['code'] as String?,
      warehouseName: warehouse?['name'] as String?,
      productId: (product?['id'] as num?)?.toInt(),
      sku: product?['sku'] as String?,
      productName: product?['name'] as String?,
      unit: product?['unit'] as String?,
      quantity: (json['quantity'] as num?)?.toInt() ?? 0,
      unitCost: (json['unit_cost'] as num?)?.toDouble() ?? 0,
      lineValue: (json['line_value'] as num?)?.toDouble() ?? 0,
      balanceAfter: (json['balance_after'] as num?)?.toInt() ?? 0,
      note: json['note'] as String?,
      author: json['author'] as String?,
    );
  }
}

/// Totaux calculés sur l'ensemble filtré (clé `totals` de la réponse).
class StockMovementTotals {
  const StockMovementTotals({
    required this.linesCount,
    required this.totalQuantity,
    required this.totalValue,
  });

  final int linesCount;
  final int totalQuantity;
  final double totalValue;

  static const StockMovementTotals empty =
      StockMovementTotals(linesCount: 0, totalQuantity: 0, totalValue: 0);

  factory StockMovementTotals.fromJson(Map<String, dynamic> json) =>
      StockMovementTotals(
        linesCount: (json['lines_count'] as num?)?.toInt() ?? 0,
        totalQuantity: (json['total_quantity'] as num?)?.toInt() ?? 0,
        totalValue: (json['total_value'] as num?)?.toDouble() ?? 0,
      );
}
