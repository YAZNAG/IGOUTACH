/// Ligne comptée d'un inventaire (InventoryResource → `lines`).
class InventoryLine {
  const InventoryLine({
    required this.productId,
    this.sku,
    this.name,
    required this.systemQuantity,
    required this.countedQuantity,
    required this.difference,
    this.reason,
  });

  final int productId;
  final String? sku;
  final String? name;
  final int systemQuantity;
  final int countedQuantity;
  final int difference;
  final String? reason;

  factory InventoryLine.fromJson(Map<String, dynamic> json) => InventoryLine(
        productId: json['product_id'] as int,
        sku: json['sku'] as String?,
        name: json['name'] as String?,
        systemQuantity: (json['system_quantity'] as num?)?.toInt() ?? 0,
        countedQuantity: (json['counted_quantity'] as num?)?.toInt() ?? 0,
        difference: (json['difference'] as num?)?.toInt() ?? 0,
        reason: json['reason'] as String?,
      );
}

/// Inventaire (GET /inventories, InventoryResource).
class Inventory {
  const Inventory({
    required this.id,
    required this.reference,
    this.warehouseId,
    this.warehouseLabel,
    this.countedAt,
    required this.status,
    this.note,
    this.linesCount,
    this.lines,
  });

  final int id;
  final String reference;
  final int? warehouseId;
  final String? warehouseLabel;

  /// Format `Y-m-d`.
  final String? countedAt;

  /// `draft`, `approved` ou `cancelled`.
  final String status;
  final String? note;
  final int? linesCount;

  /// Présent uniquement sur le détail (GET /inventories/{id}).
  final List<InventoryLine>? lines;

  bool get isDraft => status == 'draft';

  factory Inventory.fromJson(Map<String, dynamic> json) {
    final warehouse = json['warehouse'] as Map<String, dynamic>?;
    final rawLines = json['lines'] as List<dynamic>?;

    return Inventory(
      id: json['id'] as int,
      reference: json['reference'] as String? ?? '',
      warehouseId: (json['warehouse_id'] as num?)?.toInt(),
      warehouseLabel: warehouse == null
          ? null
          : [
              warehouse['code'] as String? ?? '',
              warehouse['name'] as String? ?? '',
            ].where((s) => s.isNotEmpty).join(' — '),
      countedAt: json['counted_at'] as String?,
      status: json['status'] as String? ?? 'draft',
      note: json['note'] as String?,
      linesCount: (json['lines_count'] as num?)?.toInt(),
      lines: rawLines
          ?.map((e) => InventoryLine.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}
