/// Session de caisse (GET /cash-sessions, /cash-sessions/current).
class CashSession {
  const CashSession({
    required this.id,
    this.warehouse,
    this.openedBy,
    this.openedAt,
    required this.openingAmount,
    this.closedAt,
    this.closingAmount,
    this.expectedAmount,
    this.difference,
    required this.status,
  });

  final int id;

  /// Code du lieu (le serveur n'expose que `warehouse.code`).
  final String? warehouse;
  final String? openedBy;
  final String? openedAt;
  final double openingAmount;
  final String? closedAt;
  final double? closingAmount;

  /// Attendu = fonds d'ouverture + encaissements rattachés (calculé à la clôture).
  final double? expectedAmount;

  /// Écart = compté − attendu (négatif : manquant).
  final double? difference;

  /// `open` ou `closed`.
  final String status;

  bool get isOpen => status == 'open';

  /// Encaissements de la session, déduits de l'attendu (connus à la clôture
  /// uniquement : l'API n'expose pas le cumul d'une session ouverte).
  double? get collected =>
      expectedAmount == null ? null : expectedAmount! - openingAmount;

  factory CashSession.fromJson(Map<String, dynamic> json) => CashSession(
        id: json['id'] as int,
        warehouse: json['warehouse'] as String?,
        openedBy: json['opened_by'] as String?,
        openedAt: json['opened_at'] as String?,
        openingAmount: (json['opening_amount'] as num?)?.toDouble() ?? 0,
        closedAt: json['closed_at'] as String?,
        closingAmount: (json['closing_amount'] as num?)?.toDouble(),
        expectedAmount: (json['expected_amount'] as num?)?.toDouble(),
        difference: (json['difference'] as num?)?.toDouble(),
        status: json['status'] as String? ?? 'open',
      );
}
