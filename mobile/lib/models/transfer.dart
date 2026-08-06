import 'parse.dart';

/// Transfert inter-lieux, ligne de liste (GET /transfers).
class TransferSummary {
  const TransferSummary({
    required this.id,
    required this.reference,
    this.from,
    this.to,
    this.status,
    this.statusName,
    required this.linesCount,
    this.sentAt,
    this.receivedAt,
    this.daysInTransit,
    required this.isLate,
  });

  final int id;
  final String reference;

  /// Codes des lieux (source / destination).
  final String? from;
  final String? to;

  /// `in_transit`, `received` ou `cancelled`.
  final String? status;
  final String? statusName;
  final int linesCount;

  /// `Y-m-d H:i`.
  final String? sentAt;
  final String? receivedAt;

  /// Nombre de jours en transit (uniquement si `in_transit`).
  final int? daysInTransit;

  /// Transit depuis plus de 3 jours.
  final bool isLate;

  bool get isInTransit => status == 'in_transit';

  factory TransferSummary.fromJson(Map<String, dynamic> json) =>
      TransferSummary(
        id: json['id'] as int,
        reference: json['reference'] as String? ?? '',
        from: json['from'] as String?,
        to: json['to'] as String?,
        status: json['status'] as String?,
        statusName: json['status_name'] as String?,
        linesCount: asIntOr(json['lines_count']),
        sentAt: json['sent_at'] as String?,
        receivedAt: json['received_at'] as String?,
        daysInTransit: asInt(json['days_in_transit']),
        isLate: json['is_late'] == true,
      );
}

/// Ligne d'un transfert (GET /transfers/{id} → `lines`).
class TransferLine {
  const TransferLine({
    required this.id,
    this.sku,
    this.name,
    required this.quantitySent,
    this.quantityReceived,
  });

  final int id;
  final String? sku;
  final String? name;
  final int quantitySent;

  /// `null` tant que le transfert n'est pas réceptionné.
  final int? quantityReceived;

  factory TransferLine.fromJson(Map<String, dynamic> json) => TransferLine(
        id: json['id'] as int,
        sku: json['sku'] as String?,
        name: json['name'] as String?,
        quantitySent: asIntOr(json['quantity_sent']),
        quantityReceived: asInt(json['quantity_received']),
      );
}

/// Détail d'un transfert (GET /transfers/{id}).
class TransferDetail {
  const TransferDetail({
    required this.id,
    required this.reference,
    this.from,
    this.to,
    this.status,
    this.sentAt,
    this.receivedAt,
    this.note,
    required this.lines,
  });

  final int id;
  final String reference;
  final String? from;
  final String? to;
  final String? status;
  final String? sentAt;
  final String? receivedAt;
  final String? note;
  final List<TransferLine> lines;

  bool get isInTransit => status == 'in_transit';

  factory TransferDetail.fromJson(Map<String, dynamic> json) => TransferDetail(
        id: json['id'] as int,
        reference: json['reference'] as String? ?? '',
        from: json['from'] as String?,
        to: json['to'] as String?,
        status: json['status'] as String?,
        sentAt: json['sent_at'] as String?,
        receivedAt: json['received_at'] as String?,
        note: json['note'] as String?,
        lines: (json['lines'] as List<dynamic>? ?? const [])
            .map((e) => TransferLine.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}
