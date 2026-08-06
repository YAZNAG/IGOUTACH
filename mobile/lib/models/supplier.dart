/// Fournisseur (GET /suppliers, SupplierResource).
class Supplier {
  const Supplier({
    required this.id,
    required this.code,
    required this.name,
    this.city,
    this.phone,
    this.paymentTermsDays,
    this.isActive = true,
  });

  final int id;
  final String code;
  final String name;
  final String? city;
  final String? phone;
  final int? paymentTermsDays;
  final bool isActive;

  String get label => code.isEmpty ? name : '$code — $name';

  factory Supplier.fromJson(Map<String, dynamic> json) => Supplier(
        id: json['id'] as int,
        code: json['code'] as String? ?? '',
        name: json['name'] as String? ?? '',
        city: json['city'] as String?,
        phone: json['phone'] as String?,
        paymentTermsDays: (json['payment_terms_days'] as num?)?.toInt(),
        isActive: json['is_active'] != false,
      );
}
