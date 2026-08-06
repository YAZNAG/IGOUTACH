/// Conversions tolérantes pour les réponses de l'API.
///
/// Les agrégats SQL (`SUM`, `ROUND`, `COUNT`) sont sérialisés tantôt en
/// nombre, tantôt en chaîne selon le pilote MySQL : ces aides évitent un
/// plantage de `as num` sur les rapports.
double? asDouble(Object? value) {
  if (value == null) return null;
  if (value is num) return value.toDouble();
  if (value is String) return double.tryParse(value.trim().replaceAll(',', '.'));
  return null;
}

int? asInt(Object? value) => asDouble(value)?.round();

/// Idem, avec valeur de repli (0 par défaut).
double asDoubleOr(Object? value, [double fallback = 0]) =>
    asDouble(value) ?? fallback;

int asIntOr(Object? value, [int fallback = 0]) => asInt(value) ?? fallback;

/// Date issue de l'API (`Y-m-d` ou `Y-m-d H:i:s`), `null` si absente.
DateTime? asDate(Object? value) {
  if (value is! String || value.trim().isEmpty) return null;
  return DateTime.tryParse(value.trim().replaceFirst(' ', 'T'));
}
