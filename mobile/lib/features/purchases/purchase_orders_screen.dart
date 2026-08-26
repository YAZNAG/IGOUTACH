import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/auth_provider.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../shared/liste_paginee.dart';

/// Libellé et couleur d'un état de commande.
(String, Color) etatCommande(String code) => switch (code) {
      'draft' => ('Brouillon', AppTheme.textMuted),
      'sent' => ('Envoyée', AppTheme.sky),
      'approved' => ('Approuvée', AppTheme.success),
      'partially_received' => ('Partiellement reçue', AppTheme.warning),
      'received' => ('Reçue', AppTheme.success),
      'cancelled' => ('Annulée', AppTheme.danger),
      _ => (code, AppTheme.textMuted),
    };

/// Un bon de commande, tel que le renvoie GET /purchase-orders.
class BonDeCommande {
  const BonDeCommande({
    required this.id,
    required this.numero,
    required this.etat,
    required this.lignes,
    required this.quantite,
    required this.recue,
    this.fournisseur,
    this.lieu,
    this.commandeLe,
    this.attendueLe,
  });

  final int id;
  final String numero;
  final String etat;
  final int lignes;
  final int quantite;
  final int recue;
  final String? fournisseur;
  final String? lieu;
  final String? commandeLe;
  final String? attendueLe;

  /// Reste à recevoir : c'est ce qui dit si la commande est encore ouverte.
  int get restant => (quantite - recue).clamp(0, quantite);

  factory BonDeCommande.fromJson(Map<String, dynamic> j) => BonDeCommande(
        id: (j['id'] as num).toInt(),
        numero: j['number'] as String? ?? '',
        etat: j['status'] as String? ?? '',
        lignes: (j['lines_count'] as num?)?.toInt() ?? 0,
        quantite: (j['total_quantity'] as num?)?.toInt() ?? 0,
        recue: (j['total_received'] as num?)?.toInt() ?? 0,
        fournisseur: (j['supplier'] as Map<String, dynamic>?)?['name'] as String?,
        lieu: (j['warehouse'] as Map<String, dynamic>?)?['code'] as String?,
        commandeLe: j['ordered_at'] as String?,
        attendueLe: j['expected_at'] as String?,
      );
}

/// Liste des bons de commande fournisseurs.
class PurchaseOrdersScreen extends StatelessWidget {
  const PurchaseOrdersScreen({super.key});

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('purchase.view')) {
      return const NotAllowedView();
    }

    return ListePaginee<BonDeCommande>(
      titre: 'Bons de commande',
      chemin: '/purchase-orders',
      indiceRecherche: 'Numéro ou fournisseur…',
      messageVide: 'Aucun bon de commande.',
      iconeVide: Icons.shopping_cart_outlined,
      depuisJson: BonDeCommande.fromJson,
      carte: (context, c) {
        final (libelle, couleur) = etatCommande(c.etat);

        return CarteListe(
          icone: Icons.shopping_cart_outlined,
          couleurIcone: couleur,
          reference: c.numero,
          titre: c.fournisseur ?? 'Fournisseur inconnu',
          sousTitre: [
            if (c.lieu != null) 'Vers ${c.lieu}',
            '${c.lignes} ligne${c.lignes > 1 ? 's' : ''}',
            if (c.attendueLe != null) 'attendue le ${c.attendueLe}',
          ].join(' · '),
          badges: [
            StatusBadge(label: libelle, color: couleur),
            // Le reste à recevoir est la seule question qu'on se pose devant
            // une commande envoyée : il mérite d'être lu sans ouvrir la fiche.
            if (c.restant > 0 && c.etat != 'cancelled' && c.etat != 'draft')
              StatusBadge(
                label: '${c.restant} à recevoir',
                color: AppTheme.warning,
              ),
          ],
        );
      },
    );
  }
}
