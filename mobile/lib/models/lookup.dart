/// Élément de référentiel pour les listes déroulantes
/// (catégories, marques, unités, types de lieu…).
class LookupItem {
  const LookupItem({required this.id, required this.name, this.code});

  final int id;
  final String name;
  final String? code;

  String get label =>
      (code ?? '').isEmpty ? name : '$name (${code!})';

  factory LookupItem.fromJson(Map<String, dynamic> json) => LookupItem(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        code: json['code'] as String?,
      );
}
