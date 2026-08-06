import 'package:flutter/material.dart';

import '../../core/theme.dart';

/// Libellé + couleur d'un statut de bon de commande.
///
/// Codes serveur : `draft`, `pending_approval`, `sent`,
/// `partially_received`, `received`, `cancelled`.
(String, Color) purchaseOrderStatusBadge(String? code) => switch (code) {
      'draft' => ('Brouillon', Colors.blueGrey),
      'pending_approval' => ('En attente d\'approbation', AppTheme.warning),
      'sent' => ('Envoyé', AppTheme.sky),
      'partially_received' => ('Partiellement reçu', AppTheme.warning),
      'received' => ('Reçu', AppTheme.success),
      'cancelled' => ('Annulé', AppTheme.danger),
      _ => ('Inconnu', Colors.grey),
    };

/// Libellé + couleur d'un statut de règlement fournisseur.
(String, Color) paymentStatusBadge(String? code) => switch (code) {
      'paid' => ('Réglé', AppTheme.success),
      'partial' => ('Partiel', AppTheme.warning),
      'unpaid' => ('Non réglé', AppTheme.danger),
      _ => ('—', Colors.grey),
    };

/// Libellé + couleur d'un statut de transfert (`in_transit`, `received`,
/// `cancelled`).
(String, Color) transferStatusBadge(String? code) => switch (code) {
      'in_transit' => ('En transit', AppTheme.warning),
      'received' => ('Reçu', AppTheme.success),
      'cancelled' => ('Annulé', AppTheme.danger),
      _ => ('Inconnu', Colors.grey),
    };

/// Couleur d'une sévérité d'alerte (`ok`, `warn`, `bad`, `sky`).
Color alertSeverityColor(String severity) => switch (severity) {
      'bad' => AppTheme.danger,
      'warn' => AppTheme.warning,
      'sky' => AppTheme.sky,
      _ => AppTheme.success,
    };
