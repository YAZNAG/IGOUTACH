import 'parse.dart';

/// Ligne du rapport de ventes (GET /reports/sales → `data.rows`).
///
/// Le sens des colonnes dépend du regroupement :
/// - `warehouse` / `seller` : [documents] = nombre de documents,
///   [secondary] = encaissé ;
/// - `product` : [documents] = quantité vendue, [secondary] = coût (CMUP).
class SalesReportRow {
  const SalesReportRow({
    required this.label,
    required this.documents,
    required this.revenue,
    required this.secondary,
  });

  final String label;
  final int documents;
  final double revenue;
  final double secondary;

  factory SalesReportRow.fromJson(Map<String, dynamic> json) => SalesReportRow(
        label: json['label'] as String? ?? '—',
        documents: asIntOr(json['documents']),
        revenue: asDoubleOr(json['revenue']),
        secondary: asDoubleOr(json['collected']),
      );
}

/// Valorisation du stock par lieu (GET /reports/stock-valuation).
class StockValuationRow {
  const StockValuationRow({
    required this.code,
    required this.name,
    required this.units,
    required this.value,
  });

  final String code;
  final String name;
  final int units;
  final double value;

  String get label => code.isEmpty ? name : '$code — $name';

  factory StockValuationRow.fromJson(Map<String, dynamic> json) =>
      StockValuationRow(
        code: json['code'] as String? ?? '',
        name: json['name'] as String? ?? '',
        units: asIntOr(json['units']),
        value: asDoubleOr(json['value']),
      );
}

/// Article dormant (GET /reports/dormant-products → `data.rows`).
class DormantProductRow {
  const DormantProductRow({
    required this.sku,
    required this.name,
    required this.quantity,
    required this.immobilizedValue,
  });

  final String sku;
  final String name;
  final int quantity;
  final double immobilizedValue;

  factory DormantProductRow.fromJson(Map<String, dynamic> json) =>
      DormantProductRow(
        sku: json['sku'] as String? ?? '',
        name: json['name'] as String? ?? '',
        quantity: asIntOr(json['quantity']),
        immobilizedValue: asDoubleOr(json['immobilized_value']),
      );
}

/// Marge réalisée par article (GET /reports/margins → `data.rows`).
class MarginRow {
  const MarginRow({
    required this.sku,
    required this.name,
    required this.quantity,
    required this.revenue,
    required this.cost,
    required this.margin,
  });

  final String sku;
  final String name;
  final int quantity;
  final double revenue;
  final double cost;
  final double margin;

  /// Taux de marge sur chiffre d'affaires, `null` si le CA est nul.
  double? get marginPercent => revenue == 0 ? null : margin / revenue * 100;

  factory MarginRow.fromJson(Map<String, dynamic> json) => MarginRow(
        sku: json['sku'] as String? ?? '',
        name: json['name'] as String? ?? '',
        quantity: asIntOr(json['quantity']),
        revenue: asDoubleOr(json['revenue']),
        cost: asDoubleOr(json['cost']),
        margin: asDoubleOr(json['margin']),
      );
}
