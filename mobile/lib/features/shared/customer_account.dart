import 'package:flutter/material.dart';

import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/customer.dart';

/// Ligne du relevé de compte client (facture, règlement, ajustement).
///
/// Mutualisée entre la fiche client et l'écran de crédit : les deux
/// affichaient exactement le même contenu.
class StatementTile extends StatelessWidget {
  const StatementTile({super.key, required this.entry});

  final StatementEntry entry;

  @override
  Widget build(BuildContext context) {
    // `invoice` augmente l'encours ; `payment` le diminue.
    final isDebit = entry.type == 'invoice';
    final color = isDebit ? AppTheme.danger : AppTheme.success;
    final label = switch (entry.type) {
      'invoice' => 'Facture',
      'payment' => 'Règlement',
      'adjustment' => 'Ajustement',
      _ => entry.type,
    };

    return Card(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(14, 14, 16, 14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 40,
              height: 40,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: Icon(
                isDebit ? Icons.call_made : Icons.call_received,
                color: color,
                size: 20,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                      height: 1.25,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    [
                      if (entry.date != null) entry.date!,
                      if ((entry.note ?? '').isNotEmpty) entry.note!,
                    ].join(' · '),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 14,
                      color: AppTheme.textMuted,
                      height: 1.3,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),
            AmountText(
              formatMoney(entry.amount),
              color: color,
              label: 'Solde ${formatMoney(entry.balanceAfter)}',
            ),
          ],
        ),
      ),
    );
  }
}

/// Trois cellules de crédit : plafond, encours et disponible.
class CreditCells extends StatelessWidget {
  const CreditCells({
    super.key,
    required this.creditLimit,
    required this.balance,
    required this.available,
    required this.overLimit,
  });

  final double creditLimit;
  final double balance;
  final double available;
  final bool overLimit;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          child: _CreditCell(
            label: 'Plafond',
            value: formatMoney(creditLimit),
            color: AppTheme.navy,
          ),
        ),
        Expanded(
          child: _CreditCell(
            label: 'Encours',
            value: formatMoney(balance),
            color: overLimit ? AppTheme.danger : AppTheme.warning,
          ),
        ),
        Expanded(
          child: _CreditCell(
            label: 'Disponible',
            value: formatMoney(available < 0 ? 0 : available),
            color: AppTheme.success,
          ),
        ),
      ],
    );
  }
}

class _CreditCell extends StatelessWidget {
  const _CreditCell({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          label,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 14, color: AppTheme.textMuted),
        ),
        const SizedBox(height: 4),
        FittedBox(
          fit: BoxFit.scaleDown,
          child: Text(
            value,
            maxLines: 1,
            textAlign: TextAlign.center,
            style: AppTheme.amountStyle(fontSize: 16, color: color),
          ),
        ),
      ],
    );
  }
}
