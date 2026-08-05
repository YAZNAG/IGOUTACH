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
