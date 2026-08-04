import 'package:intl/intl.dart';

final NumberFormat _money = NumberFormat('#,##0.00', 'fr_FR');
final NumberFormat _quantity = NumberFormat('#,##0', 'fr_FR');

/// Formate un montant en dirhams : `12 345,67 DH`.
String formatMoney(num? value) =>
    value == null ? '—' : '${_money.format(value)} DH';

/// Formate une quantité entière : `1 250`.
String formatQuantity(num? value) =>
    value == null ? '—' : _quantity.format(value);
