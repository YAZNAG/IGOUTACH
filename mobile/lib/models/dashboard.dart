import 'parse.dart';

/// Indicateurs consolidés (GET /dashboard → `data.summary`).
class DashboardSummary {
  const DashboardSummary({
    required this.warehouses,
    required this.products,
    required this.totalUnits,
    required this.distinctInStock,
  });

  /// Lieux actifs.
  final int warehouses;

  /// Articles actifs du catalogue.
  final int products;

  /// Unités en stock, tous lieux confondus.
  final int totalUnits;

  /// Références distinctes ayant du stock.
  final int distinctInStock;

  static const DashboardSummary empty = DashboardSummary(
    warehouses: 0,
    products: 0,
    totalUnits: 0,
    distinctInStock: 0,
  );

  factory DashboardSummary.fromJson(Map<String, dynamic> json) =>
      DashboardSummary(
        warehouses: asIntOr(json['warehouses']),
        products: asIntOr(json['products']),
        totalUnits: asIntOr(json['total_units']),
        distinctInStock: asIntOr(json['distinct_in_stock']),
      );
}

/// Ligne du stock consolidé (GET /dashboard → `data.stock`).
class ConsolidatedStockRow {
  const ConsolidatedStockRow({
    required this.productId,
    required this.sku,
    required this.name,
    required this.totalQuantity,
  });

  final int productId;
  final String sku;
  final String name;
  final int totalQuantity;

  factory ConsolidatedStockRow.fromJson(Map<String, dynamic> json) =>
      ConsolidatedStockRow(
        productId: asIntOr(json['product_id']),
        sku: json['sku'] as String? ?? '',
        name: json['name'] as String? ?? '',
        totalQuantity: asIntOr(json['total_quantity']),
      );
}

/// Réponse complète de GET /dashboard.
class DashboardData {
  const DashboardData({required this.summary, required this.stock});

  final DashboardSummary summary;
  final List<ConsolidatedStockRow> stock;

  factory DashboardData.fromJson(Map<String, dynamic> json) {
    final summary = json['summary'] as Map<String, dynamic>?;
    final stock = json['stock'] as List<dynamic>? ?? const [];

    return DashboardData(
      summary: summary == null
          ? DashboardSummary.empty
          : DashboardSummary.fromJson(summary),
      stock: stock
          .map((e) => ConsolidatedStockRow.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}
