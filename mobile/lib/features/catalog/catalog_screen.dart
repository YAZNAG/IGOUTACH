import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/auth_provider.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/catalog_product.dart';
import '../shared/liste_paginee.dart';

/// Catalogue des articles : référence, désignation, prix et stock.
///
/// Consultation seule sur mobile. Créer ou modifier un article se fait au
/// bureau, où l'on dispose des champs et du clavier pour le faire sans
/// erreur ; le terrain a besoin de retrouver une référence, pas de la saisir.
class CatalogScreen extends StatelessWidget {
  const CatalogScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.can('product.view')) {
      return const NotAllowedView();
    }

    final voitLesPrix = auth.can('price.view');

    return ListePaginee<CatalogProduct>(
      titre: 'Articles',
      chemin: '/products',
      indiceRecherche: 'Référence, nom ou code-barres…',
      messageVide: 'Aucun article au catalogue.',
      iconeVide: Icons.inventory_2_outlined,
      depuisJson: CatalogProduct.fromJson,
      carte: (context, a) {
        final stock = a.currentStock ?? 0;
        final sousSeuil = a.minStock != null && a.minStock! > 0 && stock < a.minStock!;

        return CarteListe(
          icone: Icons.inventory_2_outlined,
          couleurIcone: stock <= 0
              ? AppTheme.danger
              : (sousSeuil ? AppTheme.warning : AppTheme.sky),
          reference: a.sku,
          titre: a.name,
          sousTitre: [
            if ((a.categoryName ?? '').isNotEmpty) a.categoryName!,
            '$stock en stock',
          ].join(' · '),
          // Le prix de vente ne s'affiche qu'à qui a le droit de le consulter.
          montant: voitLesPrix ? a.salePrice : null,
          montantLibelle: voitLesPrix ? 'prix de vente' : null,
          badges: [
            if (stock <= 0)
              const StatusBadge(label: 'Rupture', color: AppTheme.danger)
            else if (sousSeuil)
              const StatusBadge(label: 'Sous seuil', color: AppTheme.warning),
            if (!a.isActive)
              const StatusBadge(label: 'Inactif', color: AppTheme.textMuted),
          ],
        );
      },
    );
  }
}
