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
  });

  final int? customerId;
  final String? customer;
  final double bucket0to30;
  final double bucket31to60;
  final double bucket61to90;
  final double bucketOver90;
  final double totalDue;

  factory AgingRow.fromJson(Map<String, dynamic> json) => AgingRow(
        customerId: json['customer_id'] as int?,
        customer: json['customer'] as String?,
        bucket0to30: (json['bucket_0_30'] as num?)?.toDouble() ?? 0,
        bucket31to60: (json['bucket_31_60'] as num?)?.toDouble() ?? 0,
        bucket61to90: (json['bucket_61_90'] as num?)?.toDouble() ?? 0,
        bucketOver90: (json['bucket_over_90'] as num?)?.toDouble() ?? 0,
        totalDue: (json['total_due'] as num?)?.toDouble() ?? 0,
      );
}
