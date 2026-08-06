import 'package:flutter/material.dart';

import '../theme.dart';

/// Indicateur de chargement centré.
///
/// Conservé pour les écrans où la forme du contenu n'est pas prévisible ;
/// pour une liste, préférer `ListSkeleton`, nettement plus rassurant.
class LoadingView extends StatelessWidget {
  const LoadingView({super.key});

  @override
  Widget build(BuildContext context) =>
      const Center(child: CircularProgressIndicator());
}

/// Bloc d'état plein écran : grande icône ronde sur fond doux, titre,
/// explication et, si utile, un bouton d'action.
///
/// Sert de base aux états vides comme aux états d'erreur : une seule mise en
/// page pour toute l'application.
class StateMessage extends StatelessWidget {
  const StateMessage({
    super.key,
    required this.icon,
    required this.title,
    this.message,
    this.color = AppTheme.navy,
    this.actionLabel,
    this.onAction,
    this.actionIcon,
  });

  final IconData icon;
  final String title;
  final String? message;
  final Color color;
  final String? actionLabel;
  final VoidCallback? onAction;
  final IconData? actionIcon;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 84,
              height: 84,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.10),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 40, color: color),
            ),
            const SizedBox(height: 20),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: AppTheme.navy,
              ),
            ),
            if ((message ?? '').isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                message!,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 15,
                  height: 1.4,
                  color: AppTheme.textMuted,
                ),
              ),
            ],
            if (actionLabel != null && onAction != null) ...[
              const SizedBox(height: 24),
              FilledButton.icon(
                onPressed: onAction,
                style: FilledButton.styleFrom(
                  backgroundColor: color,
                  minimumSize: const Size(200, AppTheme.minTapTarget + 4),
                ),
                icon: Icon(actionIcon ?? Icons.add),
                label: Text(actionLabel!),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

/// État d'erreur avec bouton « Réessayer ».
///
/// Distingue visuellement l'absence de réseau (le geste utile est de vérifier
/// la connexion) d'une vraie erreur serveur (le geste utile est de réessayer
/// ou d'appeler un responsable).
class ErrorView extends StatelessWidget {
  const ErrorView({
    super.key,
    required this.message,
    required this.onRetry,
    this.offline = false,
  });

  final String message;
  final VoidCallback onRetry;

  /// `true` quand le serveur n'a pas répondu du tout (pas de réseau).
  final bool offline;

  @override
  Widget build(BuildContext context) {
    return StateMessage(
      icon: offline ? Icons.wifi_off_rounded : Icons.cloud_off_rounded,
      color: offline ? AppTheme.warning : AppTheme.danger,
      title: offline ? 'Pas de connexion' : 'Erreur',
      message: offline
          ? 'Vérifiez votre connexion, puis réessayez.\n$message'
          : message,
      actionLabel: 'Réessayer',
      actionIcon: Icons.refresh,
      onAction: onRetry,
    );
  }
}

/// État vide : icône douce, titre, explication et action facultative.
class EmptyView extends StatelessWidget {
  const EmptyView({
    super.key,
    required this.message,
    this.icon = Icons.inbox_outlined,
    this.title,
    this.actionLabel,
    this.onAction,
    this.actionIcon,
  });

  /// Phrase d'explication (obligatoire, tournée vers l'action).
  final String message;
  final IconData icon;

  /// Titre court ; à défaut, seule la phrase est affichée en titre.
  final String? title;
  final String? actionLabel;
  final VoidCallback? onAction;
  final IconData? actionIcon;

  @override
  Widget build(BuildContext context) {
    return StateMessage(
      icon: icon,
      color: AppTheme.navy,
      title: title ?? message,
      message: title == null ? null : message,
      actionLabel: actionLabel,
      actionIcon: actionIcon,
      onAction: onAction,
    );
  }
}

/// Écran affiché quand la permission requise est absente (garde-fou).
class NotAllowedView extends StatelessWidget {
  const NotAllowedView({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Accès refusé')),
      body: const EmptyView(
        icon: Icons.lock_outline,
        title: 'Accès refusé',
        message: 'Vous n\'avez pas la permission d\'accéder à cet écran. '
            'Contactez votre responsable.',
      ),
    );
  }
}
