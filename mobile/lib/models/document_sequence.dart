/// Numérotation d'un type de document (GET /document-sequences).
class DocumentSequence {
  const DocumentSequence({
    required this.id,
    required this.key,
    required this.prefix,
    required this.current,
  });

  final int id;

  /// Type de document : `sale`, `purchase_order`, `goods_receipt`…
  final String key;
  final String prefix;

  /// Dernier numéro attribué ; le prochain document portera `current + 1`.
  final int current;

  /// Libellé français du type de document.
  String get label => switch (key) {
        'sale' => 'Ventes / factures',
        'quote' => 'Devis',
        'purchase_order' => 'Bons de commande',
        'goods_receipt' => 'Bons de réception',
        'transfer' => 'Transferts',
        'payment' => 'Règlements',
        'expense' => 'Charges',
        'inventory' => 'Inventaires',
        'customer' => 'Clients',
        'supplier' => 'Fournisseurs',
        'product' => 'Articles',
        _ => key,
      };

  /// Aperçu du prochain numéro généré.
  String get preview =>
      '$prefix${(current + 1).toString().padLeft(4, '0')}';

  factory DocumentSequence.fromJson(Map<String, dynamic> json) =>
      DocumentSequence(
        id: json['id'] as int,
        key: json['key'] as String? ?? '',
        prefix: json['prefix'] as String? ?? '',
        current: (json['current'] as num?)?.toInt() ?? 0,
      );
}
