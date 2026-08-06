import 'parse.dart';

/// Référence courte (fournisseur, lieu…) telle que renvoyée par l'API :
/// `{id, name, code}`.
class PartyRef {
  const PartyRef({this.id, this.name, this.code});

  final int? id;
  final String? name;
  final String? code;

  String get label {
    final c = code ?? '';
    final n = name ?? '';
    if (c.isEmpty) return n.isEmpty ? '—' : n;
    return n.isEmpty ? c : '$c — $n';
  }

  static PartyRef? fromJson(Object? json) {
    if (json is! Map<String, dynamic>) return null;
    return PartyRef(
      id: asInt(json['id']),
      name: json['name'] as String?,
      code: json['code'] as String?,
    );
  }
}

/// Ligne d'un bon de commande (PurchaseOrderLineResource).
///
/// Un bon de commande ne porte AUCUN prix : seules les quantités
/// (commandée, reçue, reliquat) circulent.
class PurchaseOrderLine {
  const PurchaseOrderLine({
    required this.id,
    required this.productId,
    required this.sku,
    required this.name,
    required this.quantity,
    required this.receivedQuantity,
    required this.remaining,
    this.currentStock,
    this.lastPriceKnown,
  });

  final int id;
  final int? productId;
  final String sku;
  final String name;
  final int quantity;
  final int receivedQuantity;
  final int remaining;

  /// Stock actuel tous lieux confondus.
  final int? currentStock;

  /// Dernier prix d'achat connu (seulement si `product.view_cost_price`).
  final double? lastPriceKnown;

  factory PurchaseOrderLine.fromJson(Map<String, dynamic> json) {
    final product = json['product'] as Map<String, dynamic>?;

    return PurchaseOrderLine(
      id: json['id'] as int,
      productId: asInt(product?['id']),
      sku: product?['sku'] as String? ?? '',
      name: product?['name'] as String? ?? '',
      quantity: asIntOr(json['quantity']),
      receivedQuantity: asIntOr(json['received_quantity']),
      remaining: asIntOr(json['remaining']),
      currentStock: asInt(product?['current_stock']),
      lastPriceKnown: asDouble(json['last_price_known']),
    );
  }
}

/// Bon de commande (PurchaseOrderResource pour la liste,
/// PurchaseOrderDetailResource pour le détail : mêmes champs d'en-tête,
/// `lines` en plus et les compteurs en moins).
class PurchaseOrder {
  const PurchaseOrder({
    required this.id,
    required this.number,
    this.supplier,
    this.warehouse,
    this.orderedAt,
    this.expectedAt,
    this.statusCode,
    this.statusName,
    this.notes,
    this.linesCount,
    this.totalQuantity,
    this.totalReceived,
    this.createdBy,
    required this.canSend,
    required this.canApprove,
    required this.canReceive,
    required this.canCancel,
    this.lines,
  });

  final int id;
  final String number;
  final PartyRef? supplier;
  final PartyRef? warehouse;

  /// `Y-m-d H:i:s`.
  final String? orderedAt;

  /// `Y-m-d`.
  final String? expectedAt;

  /// `draft`, `pending_approval`, `sent`, `partially_received`,
  /// `received`, `cancelled`.
  final String? statusCode;
  final String? statusName;
  final String? notes;
  final int? linesCount;
  final int? totalQuantity;
  final int? totalReceived;
  final String? createdBy;

  final bool canSend;
  final bool canApprove;
  final bool canReceive;
  final bool canCancel;

  /// Présent uniquement sur le détail (GET /purchase-orders/{id}).
  final List<PurchaseOrderLine>? lines;

  /// Le PDF n'est produit que pour un bon envoyé ou plus (jamais brouillon).
  bool get hasPdf => statusCode != null && statusCode != 'draft';

  /// Reliquat total à réceptionner.
  int get remainingQuantity {
    final lines = this.lines;
    if (lines != null) {
      return lines.fold(0, (sum, l) => sum + l.remaining);
    }
    return (totalQuantity ?? 0) - (totalReceived ?? 0);
  }

  factory PurchaseOrder.fromJson(Map<String, dynamic> json) {
    final status = json['status'] as Map<String, dynamic>?;
    final createdBy = json['created_by'] as Map<String, dynamic>?;
    final rawLines = json['lines'] as List<dynamic>?;

    return PurchaseOrder(
      id: json['id'] as int,
      number: json['number'] as String? ?? '',
      supplier: PartyRef.fromJson(json['supplier']),
      warehouse: PartyRef.fromJson(json['warehouse']),
      orderedAt: json['ordered_at'] as String?,
      expectedAt: json['expected_at'] as String?,
      statusCode: status?['code'] as String?,
      statusName: status?['name'] as String?,
      notes: json['notes'] as String?,
      linesCount: asInt(json['lines_count']),
      totalQuantity: asInt(json['total_quantity']),
      totalReceived: asInt(json['total_received']),
      createdBy: createdBy?['name'] as String?,
      canSend: json['can_send'] == true,
      canApprove: json['can_approve'] == true,
      canReceive: json['can_receive'] == true,
      canCancel: json['can_cancel'] == true,
      lines: rawLines
          ?.map((e) => PurchaseOrderLine.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}
