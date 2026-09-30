/// Ce qu'un client doit à un lieu donné.
///
/// Le même client peut devoir 500 DH à un point de vente et 1 000 DH à un
/// autre : c'est la ventilation, pas le total, qui dit à qui réclamer quoi.
class DuParLieu {
  const DuParLieu({required this.code, this.name, required this.due, required this.invoices});

  final String code;
  final String? name;
  final double due;
  final int invoices;

  factory DuParLieu.fromJson(Map<String, dynamic> json) => DuParLieu(
        code: json['code'] as String? ?? 'Sans lieu',
        name: json['name'] as String?,
        due: (json['due'] as num?)?.toDouble() ?? 0,
        invoices: (json['invoices'] as num?)?.toInt() ?? 0,
      );
}

/// Balance âgée d'un client (GET /customers-aging).
class AgingRow {
  const AgingRow({
    this.customerId,
    this.customer,
    required this.bucket0to30,
    required this.bucket31to60,
    required this.bucket61to90,
    required this.bucketOver90,
    required this.totalDue,
    this.invoices = 0,
    this.parLieu = const [],
  });

  final int? customerId;
  final String? customer;
  final double bucket0to30;
  final double bucket31to60;
  final double bucket61to90;
  final double bucketOver90;
  final double totalDue;
  final int invoices;
  final List<DuParLieu> parLieu;

  factory AgingRow.fromJson(Map<String, dynamic> json) => AgingRow(
        customerId: json['customer_id'] as int?,
        customer: json['customer'] as String?,
        bucket0to30: (json['bucket_0_30'] as num?)?.toDouble() ?? 0,
        bucket31to60: (json['bucket_31_60'] as num?)?.toDouble() ?? 0,
        bucket61to90: (json['bucket_61_90'] as num?)?.toDouble() ?? 0,
        bucketOver90: (json['bucket_over_90'] as num?)?.toDouble() ?? 0,
        totalDue: (json['total_due'] as num?)?.toDouble() ?? 0,
        invoices: (json['invoices'] as num?)?.toInt() ?? 0,
        parLieu: (json['by_warehouse'] as List<dynamic>? ?? const [])
            .map((e) => DuParLieu.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}

/// Totaux renvoyés à côté de la liste : le dû de chaque lieu.
class AgingMeta {
  const AgingMeta({this.lieuUnique, required this.totalDue, required this.parLieu});

  /// Renseigné quand l'utilisateur ne voit qu'un seul lieu.
  final int? lieuUnique;
  final double totalDue;
  final List<DuParLieu> parLieu;

  factory AgingMeta.fromJson(Map<String, dynamic> json) => AgingMeta(
        lieuUnique: json['scoped_warehouse_id'] as int?,
        totalDue: (json['total_due'] as num?)?.toDouble() ?? 0,
        parLieu: (json['by_warehouse'] as List<dynamic>? ?? const [])
            .map((e) => DuParLieu.fromJson({...e as Map<String, dynamic>, 'invoices': 0}))
            .toList(),
      );
}
