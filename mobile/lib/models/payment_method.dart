/// Mode de paiement (GET /payment-methods, PaymentMethodResource).
class PaymentMethod {
  const PaymentMethod({
    required this.id,
    required this.code,
    required this.name,
    this.type,
    this.isActive = true,
  });

  final int id;
  final String code;
  final String name;
  final String? type;
  final bool isActive;

  factory PaymentMethod.fromJson(Map<String, dynamic> json) => PaymentMethod(
        id: json['id'] as int,
        code: json['code'] as String? ?? '',
        name: json['name'] as String? ?? '',
        type: json['type'] as String?,
        isActive: json['is_active'] != false,
      );
}
