import 'parse.dart';
import 'purchase_order.dart' show PartyRef;

/// Réception restant à régler (GET /supplier-credits → `data.rows`).
class SupplierCreditRow {
  const SupplierCreditRow({
    required this.id,
    required this.number,
    this.purchaseOrderNumber,
    this.supplier,
    this.warehouse,
    this.receivedAt,
    this.invoiceNumber,
    required this.paymentStatus,
    required this.totalAmount,
    required this.amountPaid,
    required this.remainingAmount,
  });

  final int id;
  final String number;
  final String? purchaseOrderNumber;
  final PartyRef? supplier;
  final PartyRef? warehouse;

  /// `Y-m-d`.
  final String? receivedAt;
  final String? invoiceNumber;

  /// `unpaid`, `partial` ou `paid`.
  final String paymentStatus;
  final double totalAmount;
  final double amountPaid;
  final double remainingAmount;

  factory SupplierCreditRow.fromJson(Map<String, dynamic> json) {
    final order = json['purchase_order'] as Map<String, dynamic>?;

    return SupplierCreditRow(
      id: json['id'] as int,
      number: json['number'] as String? ?? '',
      purchaseOrderNumber: order?['number'] as String?,
      supplier: PartyRef.fromJson(json['supplier']),
      warehouse: PartyRef.fromJson(json['warehouse']),
      receivedAt: json['received_at'] as String?,
      invoiceNumber: json['invoice_number'] as String?,
      paymentStatus: json['payment_status'] as String? ?? 'unpaid',
      totalAmount: asDoubleOr(json['total_amount']),
      amountPaid: asDoubleOr(json['amount_paid']),
      remainingAmount: asDoubleOr(json['remaining_amount']),
    );
  }
}

/// Synthèse par fournisseur (GET /supplier-credits → `data.suppliers`).
class SupplierCreditBySupplier {
  const SupplierCreditBySupplier({
    this.supplier,
    required this.receiptsCount,
    required this.totalDue,
  });

  final PartyRef? supplier;
  final int receiptsCount;
  final double totalDue;

  factory SupplierCreditBySupplier.fromJson(Map<String, dynamic> json) =>
      SupplierCreditBySupplier(
        supplier: PartyRef.fromJson(json['supplier']),
        receiptsCount: asIntOr(json['receipts_count']),
        totalDue: asDoubleOr(json['total_due']),
      );
}

/// Réponse complète de GET /supplier-credits.
class SupplierCreditData {
  const SupplierCreditData({
    required this.rows,
    required this.suppliers,
    required this.totalDue,
    required this.receiptsCount,
  });

  final List<SupplierCreditRow> rows;
  final List<SupplierCreditBySupplier> suppliers;
  final double totalDue;
  final int receiptsCount;

  static const SupplierCreditData empty = SupplierCreditData(
    rows: [],
    suppliers: [],
    totalDue: 0,
    receiptsCount: 0,
  );

  factory SupplierCreditData.fromJson(Map<String, dynamic> json) =>
      SupplierCreditData(
        rows: (json['rows'] as List<dynamic>? ?? const [])
            .map((e) => SupplierCreditRow.fromJson(e as Map<String, dynamic>))
            .toList(),
        suppliers: (json['suppliers'] as List<dynamic>? ?? const [])
            .map((e) =>
                SupplierCreditBySupplier.fromJson(e as Map<String, dynamic>))
            .toList(),
        totalDue: asDoubleOr(json['total_due']),
        receiptsCount: asIntOr(json['receipts_count']),
      );
}

/// Règlement fournisseur (GET /goods-receipts/{id}/payments).
class SupplierPaymentRow {
  const SupplierPaymentRow({
    required this.id,
    required this.amount,
    this.paidAt,
    this.paymentMethod,
    this.notes,
    this.createdBy,
  });

  final int id;
  final double amount;

  /// `Y-m-d`.
  final String? paidAt;
  final String? paymentMethod;
  final String? notes;
  final String? createdBy;

  factory SupplierPaymentRow.fromJson(Map<String, dynamic> json) =>
      SupplierPaymentRow(
        id: json['id'] as int,
        amount: asDoubleOr(json['amount']),
        paidAt: json['paid_at'] as String?,
        paymentMethod: json['payment_method'] as String?,
        notes: json['notes'] as String?,
        createdBy: json['created_by'] as String?,
      );
}
