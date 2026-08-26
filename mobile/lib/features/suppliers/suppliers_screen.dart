import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/auth_provider.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../shared/liste_paginee.dart';

/// Un fournisseur, tel que le renvoie GET /suppliers.
class Fournisseur {
  const Fournisseur({
    required this.id,
    required this.code,
    required this.nom,
    this.contact,
    this.telephone,
    this.ville,
    this.delaiPaiement = 0,
    this.actif = true,
  });

  final int id;
  final String code;
  final String nom;
  final String? contact;
  final String? telephone;
  final String? ville;
  final int delaiPaiement;
  final bool actif;

  factory Fournisseur.fromJson(Map<String, dynamic> j) => Fournisseur(
        id: (j['id'] as num).toInt(),
        code: j['code'] as String? ?? '',
        nom: j['name'] as String? ?? '',
        contact: j['contact_name'] as String?,
        telephone: j['phone'] as String?,
        ville: j['city'] as String?,
        delaiPaiement: (j['payment_terms_days'] as num?)?.toInt() ?? 0,
        actif: j['is_active'] != false,
      );
}

/// Liste des fournisseurs : consultation depuis le terrain.
class SuppliersScreen extends StatelessWidget {
  const SuppliersScreen({super.key});

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('supplier.view')) {
      return const NotAllowedView();
    }

    return ListePaginee<Fournisseur>(
      titre: 'Fournisseurs',
      chemin: '/suppliers',
      indiceRecherche: 'Nom, code ou ville…',
      messageVide: 'Aucun fournisseur enregistré.',
      iconeVide: Icons.local_shipping_outlined,
      depuisJson: Fournisseur.fromJson,
      carte: (context, f) => CarteListe(
        icone: Icons.storefront_outlined,
        couleurIcone: AppTheme.sky,
        reference: f.code,
        titre: f.nom,
        sousTitre: [
          if ((f.contact ?? '').isNotEmpty) f.contact!,
          if ((f.telephone ?? '').isNotEmpty) f.telephone!,
          if ((f.ville ?? '').isNotEmpty) f.ville!,
        ].join(' · '),
        badges: [
          if (!f.actif)
            const StatusBadge(label: 'Inactif', color: AppTheme.danger),
          if (f.delaiPaiement > 0)
            StatusBadge(
              label: 'Paiement à ${f.delaiPaiement} j',
              color: AppTheme.textMuted,
            ),
        ],
      ),
    );
  }
}
