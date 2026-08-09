import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/auth_provider.dart';
import '../../core/theme.dart';
import '../expenses/expenses_screen.dart';
import '../inventory/inventories_screen.dart';
import '../pricing/pricing_screen.dart';
import '../stock/customer_return_screen.dart';
import '../stock/movements_list_screen.dart';
import '../transfers/transfer_requests_screen.dart';
import '../credits/credits_screen.dart';
import '../payments/payments_screen.dart';

/// Menu « Plus » : tout ce qui ne tient pas dans les cinq onglets.
///
/// Chaque entrée est conditionnée par une permission. Un responsable qui n'a
/// pas le droit ne voit pas l'entrée grisée : il ne la voit pas du tout.
/// Afficher une porte fermée n'aide personne.
class PlusScreen extends StatelessWidget {
  const PlusScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final user = auth.user;

    final entrees = <_Entree>[
      _Entree(
        icone: Icons.swap_horiz_rounded,
        titre: 'Demandes de stock',
        sousTitre: 'Demander un réapprovisionnement',
        permission: 'transfer.request',
        page: () => const TransferRequestsScreen(),
      ),
      _Entree(
        icone: Icons.fact_check_outlined,
        titre: 'Inventaire',
        sousTitre: 'Comptage physique de mon lieu',
        permission: 'inventory.create',
        page: () => const InventoriesScreen(),
      ),
      _Entree(
        icone: Icons.south_west_rounded,
        titre: 'Entrées de stock',
        sousTitre: 'Ce qui est entré dans mon lieu',
        permission: 'stock.view',
        page: () => const MovementsListScreen(
          title: 'Entrées de stock',
          basePath: '/stock/entries',
          isExit: false,
        ),
      ),
      _Entree(
        icone: Icons.north_east_rounded,
        titre: 'Sorties de stock',
        sousTitre: 'Ce qui est sorti de mon lieu',
        permission: 'stock.view',
        page: () => const MovementsListScreen(
          title: 'Sorties de stock',
          basePath: '/stock/exits',
          isExit: true,
        ),
      ),
      _Entree(
        icone: Icons.assignment_return_outlined,
        titre: 'Retours clients',
        sousTitre: 'Reprendre une marchandise vendue',
        permission: 'sale.return',
        page: () => const CustomerReturnScreen(),
      ),
      _Entree(
        icone: Icons.account_balance_wallet_outlined,
        titre: 'Crédits clients',
        sousTitre: 'Encours par client, et encaissement',
        permission: 'credit.view',
        page: () => const CreditsScreen(),
      ),
      _Entree(
        icone: Icons.payments_outlined,
        titre: 'Règlements',
        sousTitre: 'Encaissements enregistrés',
        permission: 'payment.view',
        page: () => const PaymentsScreen(),
      ),
      _Entree(
        icone: Icons.receipt_long_outlined,
        titre: 'Charges',
        sousTitre: 'Dépenses du lieu',
        permission: 'expense.create',
        page: () => const ExpensesScreen(),
      ),
      _Entree(
        icone: Icons.sell_outlined,
        titre: 'Tarifs de vente',
        sousTitre: 'Consultation seule',
        permission: 'price.view',
        lecture: true,
        page: () => const PricingScreen(),
      ),
    ];

    final visibles = entrees.where((e) => auth.can(e.permission)).toList();

    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(title: const Text('Plus')),
      body: ListView(
        padding: const EdgeInsets.all(14),
        children: [
          _CarteProfil(nom: user?.name ?? '—', email: user?.email ?? ''),
          const SizedBox(height: 14),
          Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(AppTheme.radiusCard),
              border: Border.all(color: AppTheme.border),
            ),
            child: Column(
              children: [
                for (var i = 0; i < visibles.length; i++) ...[
                  _LigneEntree(entree: visibles[i]),
                  if (i < visibles.length - 1)
                    const Divider(height: 1, indent: 56, color: AppTheme.border),
                ],
              ],
            ),
          ),
          const SizedBox(height: 14),
          const _NoteReserve(),
          const SizedBox(height: 14),
          OutlinedButton.icon(
            onPressed: () => context.read<AuthProvider>().logout(),
            icon: const Icon(Icons.logout_rounded),
            label: const Text('Se déconnecter'),
          ),
        ],
      ),
    );
  }
}

class _Entree {
  const _Entree({
    required this.icone,
    required this.titre,
    required this.sousTitre,
    required this.permission,
    required this.page,
    this.lecture = false,
  });

  final IconData icone;
  final String titre;
  final String sousTitre;
  final String permission;
  final Widget Function() page;
  final bool lecture;
}

class _LigneEntree extends StatelessWidget {
  const _LigneEntree({required this.entree});

  final _Entree entree;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Container(
        width: 36,
        height: 36,
        decoration: BoxDecoration(
          color: AppTheme.brandSoft,
          borderRadius: BorderRadius.circular(9),
        ),
        child: Icon(entree.icone, size: 19, color: AppTheme.brand),
      ),
      title: Text(
        entree.titre,
        style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w500),
      ),
      subtitle: Text(
        entree.sousTitre,
        style: const TextStyle(fontSize: 12, color: AppTheme.textMuted),
      ),
      trailing: entree.lecture
          ? const _Etiquette(texte: 'Lecture')
          : const Icon(Icons.chevron_right, color: AppTheme.textFaint),
      onTap: () => Navigator.of(context).push(
        MaterialPageRoute<void>(builder: (_) => entree.page()),
      ),
    );
  }
}

class _Etiquette extends StatelessWidget {
  const _Etiquette({required this.texte});

  final String texte;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: AppTheme.skeleton,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        texte,
        style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
      ),
    );
  }
}

class _CarteProfil extends StatelessWidget {
  const _CarteProfil({required this.nom, required this.email});

  final String nom;
  final String email;

  @override
  Widget build(BuildContext context) {
    final initiales = nom.trim().isEmpty
        ? '?'
        : nom.trim().split(RegExp(r'\s+')).take(2).map((m) => m[0]).join().toUpperCase();

    return Container(
      padding: const EdgeInsets.all(13),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppTheme.radiusCard),
        border: Border.all(color: AppTheme.border),
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: AppTheme.brandSoft,
              borderRadius: BorderRadius.circular(11),
            ),
            child: Text(
              initiales,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w700,
                color: AppTheme.brand,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  nom,
                  style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
                ),
                Text(
                  email,
                  style: const TextStyle(fontSize: 12, color: AppTheme.textMuted),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _NoteReserve extends StatelessWidget {
  const _NoteReserve();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(11),
      decoration: BoxDecoration(
        color: const Color(0xFFF7F7F9),
        borderRadius: BorderRadius.circular(AppTheme.radiusField),
        border: Border.all(color: AppTheme.borderStrong, style: BorderStyle.solid),
      ),
      child: const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.lock_outline, size: 15, color: AppTheme.textMuted),
          SizedBox(width: 8),
          Expanded(
            child: Text(
              'Achats, fournisseurs, utilisateurs, rapports consolidés et '
              'paramètres sont réservés à l’administrateur.',
              style: TextStyle(fontSize: 12, color: AppTheme.textMuted, height: 1.4),
            ),
          ),
        ],
      ),
    );
  }
}
