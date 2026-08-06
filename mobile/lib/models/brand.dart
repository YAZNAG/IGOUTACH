/// Marque (GET /brands, BrandResource).
class Brand {
  const Brand({
    required this.id,
    this.code,
    required this.name,
    this.website,
    this.logoUrl,
    required this.position,
    required this.isActive,
    this.productsCount,
  });

  final int id;
  final String? code;
  final String name;
  final String? website;
  final String? logoUrl;
  final int position;
  final bool isActive;
  final int? productsCount;

  factory Brand.fromJson(Map<String, dynamic> json) => Brand(
        id: json['id'] as int,
        code: json['code'] as String?,
        name: json['name'] as String? ?? '',
        website: json['website'] as String?,
        logoUrl: json['logo_url'] as String?,
        position: (json['position'] as num?)?.toInt() ?? 0,
        isActive: json['is_active'] != false,
        productsCount: (json['products_count'] as num?)?.toInt(),
      );
}
