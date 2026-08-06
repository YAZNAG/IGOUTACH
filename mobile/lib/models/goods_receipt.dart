import 'parse.dart';
import 'purchase_order.dart' show PartyRef;

/// Ligne valorisée d'un bon de réception (GoodsReceiptDetailResource).
class GoodsReceiptLine {
  const GoodsReceiptLine({
    required this.id,
    this.purchaseOrderLineId,
    required this.sku,
    required this.name,
    required this.quantity,
    required this.unitPrice,
    required this.lineTotal,
    this.overReceiptReason,
  });

  final int id;
  final int? purchaseOrderLineId;
  final String sku;
  final String name;
  final int quantity;
  final double unitPrice;
  final double lineTotal;
  final String? overReceiptReason;

  factory GoodsReceiptLine.fromJson(Map<String, dynamic> json) {
    final product = json['product'] as Map<String, dynamic>?;

    return GoodsReceiptLine(
      id: json['id'] as int,
      purchaseOrderLineId: asInt(json['purchase_order_line_id']),
      sku: product?['sku'] as String? ?? '',
      name: product?['name'] as String? ?? '',
      quantity: asIntOr(json['quantity']),
      unitPrice: asDoubleOr(json['unit_price']),
      lineTotal: asDoubleOr(json['line_total']),
      overReceiptReason: json['over_receipt_reason'] as String?,
    );
  }
}

/// Bon de réception (GET /goods-receipts et /goods-receipts/{id}).
class GoodsReceipt {
  const GoodsReceipt({
    required this.id,
    required this.number,
    this.purchaseOrderId,
    this.purchaseOrderNumber,
    this.supplier,
    this.warehouse,
    this.receivedAt,
    this.invoiceNumber,
    this.notes,
    this.linesCount,
    required this.totalQuantity,
    required this.totalAmount,
    required this.paymentStatus,
    required this.amountPaid,
    required this.remainingAmount,
    this.createdBy,
    this.lines,
  });

  final int id;
  final String number;
  final int? purchaseOrderId;
  final String? purchaseOrderNumber;
  final PartyRef? supplier;
  final PartyRef? warehouse;

  /// `Y-m-d H:i:s`.
  final String? receivedAt;
  final String? invoiceNumber;
  final String? notes;
  final int? linesCount;
  final int totalQuantity;

  /// Montant HT de la réception.
  final double totalAmount;

  /// `unpaid`, `partial` ou `paid`.
  final String paymentStatus;
  final double amountPaid;
  final double remainingAmount;
  final String? createdBy;

  /// Présent uniquement sur le détail.
  final List<GoodsReceiptLine>? lines;

  bool get isSettled => remainingAmount <= 0.005;

  factory GoodsReceipt.fromJson(Map<String, dynamic> json) {
    final order = json['purchase_order'] as Map<String, dynamic>?;
    final createdBy = json['created_by'] as Map<String, dynamic>?;
    final rawLines = json['lines'] as List<dynamic>?;

    return GoodsReceipt(
      id: json['id'] as int,
      number: json['number'] as String? ?? '',
      purchaseOrderId: asInt(order?['id']),
      purchaseOrderNumber: order?['number'] as String?,
      supplier: PartyRef.fromJson(json['supplier']),
      warehouse: PartyRef.fromJson(json['warehouse']),
      receivedAt: json['received_at'] as String?,
      invoiceNumber: json['invoice_number'] as String?,
      notes: json['notes'] as String?,
      linesCount: asInt(json['lines_count']),
      totalQuantity: asIntOr(json['total_quantity']),
      totalAmount: asDoubleOr(json['total_amount']),
      paymentStatus: json['payment_status'] as String? ?? 'unpaid',
      amountPaid: asDoubleOr(json['amount_paid']),
      remainingAmount: asDoubleOr(json['remaining_amount']),
      createdBy: createdBy?['name'] as String?,
      lines: rawLines
          ?.map((e) => GoodsReceiptLine.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}
