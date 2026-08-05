import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/auth_provider.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../credits/credits_screen.dart';
import '../customers/customers_screen.dart';
import '../expenses/expenses_screen.dart';
import '../inventory/inventories_screen.dart';
import '../pricing/pricing_screen.dart';
import '../sales/sales_screen.dart';
import '../shared/warehouse_scope.dart';
import '../stock/customer_return_screen.dart';
import '../stock/stock_entries_screen.dart';
import '../stock/stock_exits_screen.dart';
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

/// Groupe de modules affiché sous un petit titre.
class _Section {
  const _Section({required this.title, required this.modules});

  final String title;
  final List<_Module> modules;
}

/// Accueil : modules groupés par section et filtrés par les permissions.
/// Chaque utilisateur voit une application différente selon son profil.
class HomeShell extends StatefulWidget {
  const HomeShell({super.key});

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  /// Libellé du lieu de l'utilisateur, `null` si aucun lieu ou si
  /// `GET /warehouses` est refusé (permission `warehouse.view`).
  String? _warehouseLabel;

  static final List<_Section> _sections = [
    _Section(
      title: 'Mon stock',
      modules: [
        _Module(
          title: 'Mon stock',
          icon: Icons.inventory,
          color: AppTheme.navy,
          permission: 'stock.view',
          builder: (_) => const StockScreen(),
        ),
        _Module(
          title: 'Entrées',
          icon: Icons.move_to_inbox,
          color: AppTheme.success,
          permission: 'stock.view',
          builder: (_) => const StockEntriesScreen(),
        ),
        _Module(
          title: 'Sorties',
          icon: Icons.outbox,
          color: AppTheme.danger,
          permission: 'stock.view',
          builder: (_) => const StockExitsScreen(),
        ),
        _Module(
          title: 'Inventaire',
          icon: Icons.fact_check,
          color: const Color(0xFF7C3AED),
          permission: 'inventory.create',
          builder: (_) => const InventoriesScreen(),
        ),
        _Module(
          title: 'Retour client',
          icon: Icons.assignment_return,
          color: const Color(0xFF0891B2),
          permission: 'stock.entry',
          builder: (_) => const CustomerReturnScreen(),
        ),
      ],
    ),
    _Section(
      title: 'Commerce',
      modules: [
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
      ],
    ),
    _Section(
      title: 'Gestion',
      modules: [
        _Module(
          title: 'Charges',
          icon: Icons.receipt_long,
          color: const Color(0xFFEA580C),
          permission: 'expense.create',
          builder: (_) => const ExpensesScreen(),
        ),
        _Module(
          title: 'Tarifs',
          icon: Icons.sell,
          color: const Color(0xFF0D9488),
          permission: 'price.view',
          builder: (_) => const PricingScreen(),
        ),
      ],
    ),
  ];

  @override
  void initState() {
    super.initState();
    _loadWarehouseLabel();
  }

  Future<void> _loadWarehouseLabel() async {
    final userWarehouseId = context.read<AuthProvider>().user?.warehouseId;
    if (userWarehouseId == null) return;
    final scope = await WarehouseScope.load(userWarehouseId);
    if (!mounted) return;
    // 403 sur /warehouses : on n'affiche simplement rien de plus.
    setState(() => _warehouseLabel = scope.selectedLabel);
  }

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

    final sections = _sections
        .map(
          (section) => _Section(
            title: section.title,
            modules: section.modules
                .where((m) => auth.can(m.permission))
                .toList(growable: false),
          ),
        )
        .where((section) => section.modules.isNotEmpty)
        .toList(growable: false);

    final subtitle = [
      user?.name ?? '',
      ?_warehouseLabel,
    ].where((s) => s.isNotEmpty).join(' · ');

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
              subtitle,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
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
      body: sections.isEmpty
          ? const EmptyView(
              icon: Icons.lock_outline,
              message: 'Aucun module autorisé, contactez l\'administrateur.',
            )
          : ListView(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
              children: [
                for (final section in sections) ...[
                  Padding(
                    padding: const EdgeInsets.fromLTRB(4, 16, 4, 10),
                    child: Text(
                      section.title.toUpperCase(),
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        letterSpacing: 1.1,
                        color: AppTheme.navy.withValues(alpha: 0.6),
                      ),
                    ),
                  ),
                  GridView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    gridDelegate:
                        const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      crossAxisSpacing: 12,
                      mainAxisSpacing: 12,
                      childAspectRatio: 1.15,
                    ),
                    itemCount: section.modules.length,
                    itemBuilder: (context, index) =>
                        _ModuleCard(module: section.modules[index]),
                  ),
                ],
              ],
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
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
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
