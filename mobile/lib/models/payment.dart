/// Encaissement client (GET /payments).
class PaymentRow {
  const PaymentRow({
    required this.id,
    required this.reference,
    this.customer,
    this.method,
    required this.amount,
    this.chequeStatus,
    this.chequeReference,
    this.receivedAt,
  });

  final int id;
  final String reference;
  final String? customer;
  final String? method;
  final double amount;

  /// `received`, `deposited`, `cleared` ou `bounced` — `null` hors chèque.
  final String? chequeStatus;
  final String? chequeReference;
  final String? receivedAt;

  bool get isCheque => chequeStatus != null;

  /// Le cycle est terminé une fois le chèque encaissé ou impayé.
  bool get chequeCycleClosed =>
      chequeStatus == 'cleared' || chequeStatus == 'bounced';

  factory PaymentRow.fromJson(Map<String, dynamic> json) => PaymentRow(
        id: json['id'] as int,
        reference: json['reference'] as String? ?? '',
        customer: json['customer'] as String?,
        method: json['method'] as String?,
        amount: (json['amount'] as num?)?.toDouble() ?? 0,
        chequeStatus: json['cheque_status'] as String?,
        chequeReference: json['cheque_reference'] as String?,
        receivedAt: json['received_at'] as String?,
      );
}
