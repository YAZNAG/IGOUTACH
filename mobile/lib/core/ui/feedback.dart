import 'package:flutter/material.dart';

import '../theme.dart';

// Messages courts (SnackBar) et dialogues de confirmation partagés.
//
// Les fonctions de SnackBar prennent un ScaffoldMessengerState plutôt qu'un
// BuildContext : les appels ont presque toujours lieu après un `await`, où le
// contexte peut ne plus être monté.

enum _SnackKind { success, error, info }

void _showSnack(
  ScaffoldMessengerState messenger,
  String message,
  _SnackKind kind, {
  SnackBarAction? action,
}) {
  final (IconData icon, Color color) = switch (kind) {
    _SnackKind.success => (Icons.check_circle_outline, AppTheme.success),
    _SnackKind.error => (Icons.error_outline, AppTheme.danger),
    _SnackKind.info => (Icons.info_outline, AppTheme.navy),
  };

  messenger
    ..hideCurrentSnackBar()
    ..showSnackBar(
      SnackBar(
        backgroundColor: color,
        behavior: SnackBarBehavior.floating,
        margin: const EdgeInsets.fromLTRB(12, 0, 12, 16),
        duration: Duration(seconds: kind == _SnackKind.error ? 5 : 3),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppTheme.radiusField),
        ),
        action: action,
        content: Row(
          children: [
            Icon(icon, color: Colors.white, size: 22),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                message,
                style: const TextStyle(
                  fontSize: 15,
                  color: Colors.white,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
          ],
        ),
      ),
    );
}

/// Message de succès (vert, icône ✓).
void showSuccessSnack(ScaffoldMessengerState messenger, String message) =>
    _showSnack(messenger, message, _SnackKind.success);

/// Message d'erreur (rouge, icône !), affiché un peu plus longtemps.
void showErrorSnack(
  ScaffoldMessengerState messenger,
  String message, {
  SnackBarAction? action,
}) =>
    _showSnack(messenger, message, _SnackKind.error, action: action);

/// Information neutre (marine, icône i).
void showInfoSnack(ScaffoldMessengerState messenger, String message) =>
    _showSnack(messenger, message, _SnackKind.info);

/// Dialogue de confirmation générique.
///
/// Retourne `true` seulement si l'utilisateur confirme explicitement.
Future<bool> confirmAction(
  BuildContext context, {
  required String title,
  required String message,
  String confirmLabel = 'Confirmer',
  String cancelLabel = 'Annuler',
  Color confirmColor = AppTheme.navy,
  IconData? icon,
}) async {
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      icon: icon == null ? null : Icon(icon, size: 32, color: confirmColor),
      title: Text(title),
      content: Text(message),
      actionsPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(dialogContext).pop(false),
          child: Text(cancelLabel),
        ),
        FilledButton(
          style: FilledButton.styleFrom(
            backgroundColor: confirmColor,
            minimumSize: const Size(120, AppTheme.minTapTarget),
          ),
          onPressed: () => Navigator.of(dialogContext).pop(true),
          child: Text(confirmLabel),
        ),
      ],
    ),
  );
  return confirmed == true;
}

/// Confirmation avant d'abandonner un formulaire déjà rempli.
///
/// Retourne `true` si l'utilisateur accepte de perdre sa saisie.
Future<bool> confirmDiscard(BuildContext context) => confirmAction(
      context,
      icon: Icons.report_problem_outlined,
      title: 'Abandonner la saisie ?',
      message: 'Les informations saisies seront perdues.',
      confirmLabel: 'Abandonner',
      cancelLabel: 'Continuer la saisie',
      confirmColor: AppTheme.danger,
    );
