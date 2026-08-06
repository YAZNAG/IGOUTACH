import 'parse.dart';

/// Alerte consolidée du pilotage (GET /alerts).
///
/// Le serveur renvoie sept alertes fixes, chacune avec un compteur et une
/// sévérité : `ok`, `warn`, `bad` ou `sky`.
class AlertItem {
  const AlertItem({
    required this.key,
    required this.label,
    required this.count,
    required this.severity,
  });

  /// `low_stock`, `below_floor`, `over_credit`, `late_transfers`,
  /// `overdue_invoices`, `draft_inventories`, `pending_expenses`.
  final String key;
  final String label;
  final int count;

  /// `ok`, `warn`, `bad` ou `sky`.
  final String severity;

  bool get isClear => count == 0;

  factory AlertItem.fromJson(Map<String, dynamic> json) => AlertItem(
        key: json['key'] as String? ?? '',
        label: json['label'] as String? ?? '',
        count: asIntOr(json['count']),
        severity: json['severity'] as String? ?? 'ok',
      );
}
