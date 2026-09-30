import 'package:intl/intl.dart';

final NumberFormat _money = NumberFormat('#,##0.00', 'fr_FR');
final NumberFormat _quantity = NumberFormat('#,##0', 'fr_FR');

/// Formate un montant en dirhams : `12 345,67 DH`.
String formatMoney(num? value) =>
    value == null ? '—' : '${_money.format(value)} DH';

/// Formate une quantité entière : `1 250`.
String formatQuantity(num? value) =>
    value == null ? '—' : _quantity.format(value);

/// Date au format attendu par l'API Laravel : `2026-08-05`.
String apiDate(DateTime date) =>
    '${date.year.toString().padLeft(4, '0')}-'
    '${date.month.toString().padLeft(2, '0')}-'
    '${date.day.toString().padLeft(2, '0')}';

/// Date lisible pour l'affichage : `05/08/2026`.
String formatDate(DateTime? date) => date == null
    ? '—'
    : '${date.day.toString().padLeft(2, '0')}/'
        '${date.month.toString().padLeft(2, '0')}/'
        '${date.year}';

/// Découpe un horodatage renvoyé par l'API (`2026-09-02 17:08`).
///
/// L'API exprime déjà l'heure dans le fuseau de l'entreprise
/// (Africa/Casablanca). La reconstruire avec `DateTime.parse` puis l'afficher
/// la ferait réinterpréter dans le fuseau du téléphone : une vente saisie à
/// 17 h s'afficherait à 18 h sur un appareil réglé sur l'Europe. On lit donc
/// les chiffres tels quels.
final RegExp _horodatage = RegExp(r'^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?');

/// Date lisible depuis une chaîne de l'API : `02/09/2026`.
String formatDateTexte(String? valeur) {
  if (valeur == null || valeur.isEmpty) return '—';
  final m = _horodatage.firstMatch(valeur);
  if (m == null) return valeur;
  return '${m.group(3)}/${m.group(2)}/${m.group(1)}';
}

/// Date et heure : `02/09/2026 à 17:08`.
///
/// Retombe sur la date seule quand la valeur ne porte pas d'heure : inventer
/// « 00:00 » laisserait croire à une saisie en pleine nuit.
String formatDateHeure(String? valeur) {
  if (valeur == null || valeur.isEmpty) return '—';
  final m = _horodatage.firstMatch(valeur);
  if (m == null) return valeur;
  final date = '${m.group(3)}/${m.group(2)}/${m.group(1)}';
  final heure = m.group(4);
  return heure == null ? date : '$date à $heure:${m.group(5)}';
}
