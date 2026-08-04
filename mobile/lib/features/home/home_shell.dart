import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/auth_provider.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../credits/credits_screen.dart';
import '../customers/customers_screen.dart';
import '../pricing/pricing_screen.dart';
import '../sales/sales_screen.dart';
import '../stock/stock_screen.dart';

/// Description d'un module de l'accueil.
class _Module {
  const _Module({
    required this.title,
    required this.icon,
    required this.color,
    required this.permission,
    required this.builder,
  });

  final String title;
  final IconData icon;
  final Color color;
  final String permission;
  final WidgetBuilder builder;
}

/// Accueil : grille de modules filtrée par les permissions de l'utilisateur.
/// Chaque utilisateur voit une application différente selon son profil.
class HomeShell extends StatelessWidget {
  const HomeShell({super.key});

  static final List<_Module> _modules = [
    _Module(
      title: 'Mon stock',
      icon: Icons.inventory,
      color: AppTheme.navy,
      permission: 'stock.view',
      builder: (_) => const StockScreen(),
    ),
    _Module(
      title: 'Clients',
      icon: Icons.people,
      color: AppTheme.sky,
      permission: 'customer.view',
      builder: (_) => const CustomersScreen(),
    ),
    _Module(
      title: 'Ventes',
      icon: Icons.point_of_sale,
      color: AppTheme.success,
      permission: 'sale.create',
      builder: (_) => const SalesScreen(),
    ),
    _Module(
      title: 'Crédits clients',
      icon: Icons.credit_card,
      color: const Color(0xFF9333EA),
      permission: 'payment.view',
      builder: (_) => const CreditsScreen(),
    ),
    _Module(
      title: 'Tarifs',
      icon: Icons.sell,
      color: const Color(0xFF0D9488),
      permission: 'price.view',
      builder: (_) => const PricingScreen(),
    ),
  ];

  Future<void> _confirmLogout(BuildContext context) async {
    final auth = context.read<AuthProvider>();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Déconnexion'),
        content: const Text('Voulez-vous vraiment vous déconnecter ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(false),
            child: const Text('Annuler'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(
              backgroundColor: AppTheme.danger,
              minimumSize: const Size(0, 44),
            ),
            onPressed: () => Navigator.of(dialogContext).pop(true),
            child: const Text('Se déconnecter'),
          ),
        ],
      ),
    );
    if (confirmed == true) {
      await auth.logout();
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final user = auth.user;
    final allowed =
        _modules.where((m) => auth.can(m.permission)).toList(growable: false);

    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'IGOUTECH',
              style: TextStyle(fontSize: 16, letterSpacing: 1.5),
            ),
            Text(
              user?.name ?? '',
              style: const TextStyle(fontSize: 13, color: Colors.white70),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Se déconnecter',
            onPressed: () => _confirmLogout(context),
          ),
        ],
      ),
      body: allowed.isEmpty
          ? const EmptyView(
              icon: Icons.lock_outline,
              message:
                  'Aucun module autorisé, contactez l\'administrateur.',
            )
          : GridView.builder(
              padding: const EdgeInsets.all(16),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
                childAspectRatio: 1.15,
              ),
              itemCount: allowed.length,
              itemBuilder: (context, index) {
                final module = allowed[index];
                return _ModuleCard(module: module);
              },
            ),
    );
  }
}

class _ModuleCard extends StatelessWidget {
  const _ModuleCard({required this.module});

  final _Module module;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: module.color,
      borderRadius: BorderRadius.circular(20),
      child: InkWell(
        borderRadius: BorderRadius.circular(20),
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute<void>(builder: module.builder),
        ),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.18),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(module.icon, color: Colors.white, size: 28),
              ),
              Text(
                module.title,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
