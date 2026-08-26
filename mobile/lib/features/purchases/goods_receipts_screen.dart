import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../shared/liste_paginee.dart';

/// Libellé et couleur d'un état de règlement fournisseur.
(String, Color) etatReglement(String code) => switch (code) {
      'paid' => ('Payé', AppTheme.success),
      'partial' => ('Partiellement payé', AppTheme.warning),
      _ => ('Non payé', AppTheme.danger),
    };

/// Une réception, telle que la renvoie GET /goods-receipts.
class Reception {
  const Reception({
    required this.id,
    required this.numero,
    required this.lignes,
    required this.quantite,
    required this.montant,
    required this.paye,
    required this.restant,
    required this.etatPaiement,
    this.fournisseur,
    this.lieu,
    this.recueLe,
    this.commande,
    this.facture,
  });

  final int id;
  final String numero;
  final int lignes;
  final int quantite;
  final double montant;
  final double paye;
  final double restant;
  final String etatPaiement;
  final String? fournisseur;
  final String? lieu;
  final String? recueLe;
  final String? commande;
  final String? facture;

  factory Reception.fromJson(Map<String, dynamic> j) => Reception(
        id: (j['id'] as num).toInt(),
        numero: j['number'] as String? ?? '',
        lignes: (j['lines_count'] as num?)?.toInt() ?? 0,
        quantite: (j['total_quantity'] as num?)?.toInt() ?? 0,
        montant: (j['total_amount'] as num?)?.toDouble() ?? 0,
        paye: (j['amount_paid'] as num?)?.toDouble() ?? 0,
        restant: (j['remaining_amount'] as num?)?.toDouble() ?? 0,
        etatPaiement: j['payment_status'] as String? ?? 'unpaid',
        fournisseur: (j['supplier'] as Map<String, dynamic>?)?['name'] as String?,
        lieu: (j['warehouse'] as Map<String, dynamic>?)?['code'] as String?,
        recueLe: j['received_at'] as String?,
        commande: (j['purchase_order'] as Map<String, dynamic>?)?['number'] as String?,
        facture: j['invoice_number'] as String?,
      );
}

/// Liste des réceptions de marchandise.
class GoodsReceiptsScreen extends StatelessWidget {
  const GoodsReceiptsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('receipt.view')) {
      return const NotAllowedView();
    }

    return ListePaginee<Reception>(
      titre: 'Réceptions',
      chemin: '/goods-receipts',
      indiceRecherche: 'Numéro, fournisseur ou facture…',
      messageVide: 'Aucune réception enregistrée.',
      iconeVide: Icons.move_to_inbox_outlined,
      depuisJson: Reception.fromJson,
      carte: (context, r) {
        final (libelle, couleur) = etatReglement(r.etatPaiement);

        return CarteListe(
          icone: Icons.move_to_inbox_outlined,
          couleurIcone: couleur,
          reference: r.numero,
          titre: r.fournisseur ?? 'Fournisseur inconnu',
          sousTitre: [
            if (r.lieu != null) r.lieu!,
            '${r.quantite} unité${r.quantite > 1 ? 's' : ''}',
            if (r.recueLe != null) 'le ${r.recueLe}',
            if (r.commande != null) 'BC ${r.commande}',
          ].join(' · '),
          montant: r.montant,
          montantLibelle: r.restant > 0 ? '${formatMoney(r.restant)} dû' : 'soldé',
          badges: [StatusBadge(label: libelle, color: couleur)],
        );
      },
    );
  }
}
