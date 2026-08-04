/// Vente (ligne de la liste GET /sales).
class SaleSummary {
  const SaleSummary({
    required this.id,
    required this.reference,
    required this.type,
    required this.status,
    this.customer,
    this.warehouse,
    required this.total,
    required this.paidAmount,
    this.paymentStatus,
    required this.linesCount,
    this.createdAt,
  });

  final int id;
  final String reference;
  final String type;

  /// `draft`, `confirmed` ou `cancelled`.
  final String status;
  final String? customer;
  final String? warehouse;
  final double total;
  final double paidAmount;

  /// `paid`, `partial` ou `unpaid`.
  final String? paymentStatus;
  final int linesCount;
  final String? createdAt;

  factory SaleSummary.fromJson(Map<String, dynamic> json) => SaleSummary(
        id: json['id'] as int,
        reference: json['reference'] as String? ?? '',
        type: json['type'] as String? ?? 'invoice',
        status: json['status'] as String? ?? 'draft',
        customer: json['customer'] as String?,
        warehouse: json['warehouse'] as String?,
        total: (json['total'] as num?)?.toDouble() ?? 0,
        paidAmount: (json['paid_amount'] as num?)?.toDouble() ?? 0,
        paymentStatus: json['payment_status'] as String?,
        linesCount: (json['lines_count'] as num?)?.toInt() ?? 0,
        createdAt: json['created_at'] as String?,
      );
}
