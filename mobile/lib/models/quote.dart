/// Devis (ligne de la liste GET /sales?type=quote).
///
/// Modèle dédié aux devis : il porte le champ `converted` renvoyé par
/// `SaleController::index` (devis déjà transformé en facture).
class QuoteSummary {
  const QuoteSummary({
    required this.id,
    required this.reference,
    required this.status,
    this.customer,
    this.warehouse,
    required this.total,
    required this.linesCount,
    required this.converted,
    this.createdAt,
  });

  final int id;
  final String reference;

  /// `draft`, `confirmed` ou `cancelled`.
  final String status;
  final String? customer;
  final String? warehouse;
  final double total;
  final int linesCount;

  /// Le devis a déjà donné lieu à une facture (`quote_id` renseigné).
  final bool converted;
  final String? createdAt;

  /// Un devis n'est convertible qu'une fois et s'il n'est pas annulé.
  bool get isConvertible => !converted && status != 'cancelled';

  factory QuoteSummary.fromJson(Map<String, dynamic> json) => QuoteSummary(
        id: json['id'] as int,
        reference: json['reference'] as String? ?? '',
        status: json['status'] as String? ?? 'draft',
        customer: json['customer'] as String?,
        warehouse: json['warehouse'] as String?,
        total: (json['total'] as num?)?.toDouble() ?? 0,
        linesCount: (json['lines_count'] as num?)?.toInt() ?? 0,
        converted: json['converted'] == true,
        createdAt: json['created_at'] as String?,
      );
}

/// Ligne d'un devis / d'une vente (GET /sales/{id}).
class SaleDetailLine {
  const SaleDetailLine({
    required this.sku,
    required this.name,
    required this.quantity,
    required this.unitPrice,
    this.priceTypeCode,
    required this.lineTotal,
  });

  final String sku;
  final String name;
  final int quantity;
  final double unitPrice;
  final String? priceTypeCode;
  final double lineTotal;

  factory SaleDetailLine.fromJson(Map<String, dynamic> json) => SaleDetailLine(
        sku: json['sku'] as String? ?? '',
        name: json['name'] as String? ?? '',
        quantity: (json['quantity'] as num?)?.toInt() ?? 0,
        unitPrice: (json['unit_price'] as num?)?.toDouble() ?? 0,
        priceTypeCode: json['price_type_code'] as String?,
        lineTotal: (json['line_total'] as num?)?.toDouble() ?? 0,
      );
}

/// Détail d'un devis / d'une vente (GET /sales/{id}).
class SaleDetail {
  const SaleDetail({
    required this.id,
    required this.reference,
    required this.type,
    required this.status,
    this.customerId,
    this.customerName,
    this.warehouse,
    required this.subtotal,
    required this.discountPercent,
    required this.total,
    required this.paidAmount,
    this.paymentStatus,
    this.confirmedAt,
    this.note,
    required this.lines,
  });

  final int id;
  final String reference;
  final String type;
  final String status;
  final int? customerId;
  final String? customerName;
  final String? warehouse;
  final double subtotal;
  final double discountPercent;
  final double total;
  final double paidAmount;
  final String? paymentStatus;
  final String? confirmedAt;
  final String? note;
  final List<SaleDetailLine> lines;

  factory SaleDetail.fromJson(Map<String, dynamic> json) {
    final customer = json['customer'] as Map<String, dynamic>?;
    return SaleDetail(
      id: json['id'] as int,
      reference: json['reference'] as String? ?? '',
      type: json['type'] as String? ?? 'quote',
      status: json['status'] as String? ?? 'draft',
      customerId: customer?['id'] as int?,
      customerName: customer?['name'] as String?,
      warehouse: json['warehouse'] as String?,
      subtotal: (json['subtotal'] as num?)?.toDouble() ?? 0,
      discountPercent: (json['discount_percent'] as num?)?.toDouble() ?? 0,
      total: (json['total'] as num?)?.toDouble() ?? 0,
      paidAmount: (json['paid_amount'] as num?)?.toDouble() ?? 0,
      paymentStatus: json['payment_status'] as String?,
      confirmedAt: json['confirmed_at'] as String?,
      note: json['note'] as String?,
      lines: (json['lines'] as List<dynamic>? ?? [])
          .map((e) => SaleDetailLine.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}
