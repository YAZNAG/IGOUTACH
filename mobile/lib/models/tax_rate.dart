/// Taux de TVA (GET /tax-rates, TaxRateResource).
class TaxRate {
  const TaxRate({
    required this.id,
    required this.rate,
    required this.label,
    required this.isDefault,
    required this.position,
    required this.isActive,
  });

  final int id;
  final double rate;
  final String label;
  final bool isDefault;
  final int position;
  final bool isActive;

  factory TaxRate.fromJson(Map<String, dynamic> json) => TaxRate(
        id: json['id'] as int,
        rate: (json['rate'] as num?)?.toDouble() ?? 0,
        label: json['label'] as String? ?? '',
        isDefault: json['is_default'] == true,
        position: (json['position'] as num?)?.toInt() ?? 0,
        isActive: json['is_active'] != false,
      );
}
