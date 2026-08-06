import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/auth_provider.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/warehouse.dart';
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
    required this.subtitle,
    required this.icon,
    required this.permission,
    required this.builder,
  });

  final String title;

  /// Une ligne qui dit à quoi sert le module, dans les mots du métier.
  final String subtitle;
  final IconData icon;
  final String permission;
  final WidgetBuilder builder;
}

/// Groupe de modules affiché sous un petit titre, avec sa couleur d'accent.
class _Section {
  const _Section({
    required this.title,
    required this.color,
    required this.modules,
  });

  final String title;
  final Color color;
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
  /// Lieu de rattachement de l'utilisateur, `null` si aucun lieu ou si
  /// `GET /warehouses` est refusé (permission `warehouse.view`).
  Warehouse? _warehouse;

  static final List<_Section> _sections = [
    _Section(
      title: 'Stock',
      color: AppTheme.accentStock,
      modules: [
        _Module(
          title: 'Mon stock',
          subtitle: 'Voir les quantités disponibles',
          icon: Icons.inventory_2_rounded,
          permission: 'stock.view',
          builder: (_) => const StockScreen(),
        ),
        _Module(
          title: 'Entrées',
          subtitle: 'Réceptions et retours reçus',
          icon: Icons.move_to_inbox_rounded,
          permission: 'stock.view',
          builder: (_) => const StockEntriesScreen(),
        ),
        _Module(
          title: 'Sorties',
          subtitle: 'Ventes et transferts expédiés',
          icon: Icons.outbox_rounded,
          permission: 'stock.view',
          builder: (_) => const StockExitsScreen(),
        ),
        _Module(
          title: 'Inventaire',
          subtitle: 'Compter et régulariser le stock',
          icon: Icons.fact_check_rounded,
          permission: 'inventory.create',
          builder: (_) => const InventoriesScreen(),
        ),
        _Module(
          title: 'Retour client',
          subtitle: 'Reprendre un article rendu',
          icon: Icons.assignment_return_rounded,
          permission: 'stock.entry',
          builder: (_) => const CustomerReturnScreen(),
        ),
      ],
    ),
    _Section(
      title: 'Commerce',
      color: AppTheme.accentCommerce,
      modules: [
        _Module(
          title: 'Clients',
          subtitle: 'Fiches, encours et relevés',
          icon: Icons.people_alt_rounded,
          permission: 'customer.view',
          builder: (_) => const CustomersScreen(),
        ),
        _Module(
          title: 'Ventes',
          subtitle: 'Facturer et encaisser',
          icon: Icons.point_of_sale_rounded,
          permission: 'sale.create',
          builder: (_) => const SalesScreen(),
        ),
        _Module(
          title: 'Crédits clients',
          subtitle: 'Suivre ce qui reste dû',
          icon: Icons.credit_card_rounded,
          permission: 'payment.view',
          builder: (_) => const CreditsScreen(),
        ),
      ],
    ),
    _Section(
      title: 'Gestion',
      color: AppTheme.accentAdmin,
      modules: [
        _Module(
          title: 'Charges',
          subtitle: 'Saisir les dépenses du lieu',
          icon: Icons.receipt_long_rounded,
          permission: 'expense.create',
          builder: (_) => const ExpensesScreen(),
        ),
        _Module(
          title: 'Tarifs',
          subtitle: 'Prix détail, demi-gros et gros',
          icon: Icons.sell_rounded,
          permission: 'price.view',
          builder: (_) => const PricingScreen(),
        ),
      ],
    ),
  ];

  @override
  void initState() {
    super.initState();
    _loadWarehouse();
  }

  Future<void> _loadWarehouse() async {
    final userWarehouseId = context.read<AuthProvider>().user?.warehouseId;
    if (userWarehouseId == null) return;
    final scope = await WarehouseScope.load(userWarehouseId);
    if (!mounted) return;
    // 403 sur /warehouses : on n'affiche simplement rien de plus.
    setState(() => _warehouse = scope.selected);
  }

  Future<void> _confirmLogout() async {
    final auth = context.read<AuthProvider>();
    final confirmed = await confirmAction(
      context,
      icon: Icons.logout_rounded,
      title: 'Se déconnecter',
      message: 'Vous devrez saisir à nouveau votre mot de passe '
          'à la prochaine ouverture.',
      confirmLabel: 'Se déconnecter',
      confirmColor: AppTheme.danger,
    );
    if (confirmed) await auth.logout();
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final user = auth.user;

    final sections = _sections
        .map(
          (section) => _Section(
            title: section.title,
            color: section.color,
            modules: section.modules
                .where((m) => auth.can(m.permission))
                .toList(growable: false),
          ),
        )
        .where((section) => section.modules.isNotEmpty)
        .toList(growable: false);

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 16,
        title: const Text('IGOUTECH', style: TextStyle(letterSpacing: 1.5)),
        actions: [
          PopupMenuButton<String>(
            icon: const Icon(Icons.more_vert),
            tooltip: 'Menu',
            position: PopupMenuPosition.under,
            onSelected: (value) {
              if (value == 'logout') _confirmLogout();
            },
            itemBuilder: (context) => [
              PopupMenuItem<String>(
                value: 'logout',
                child: Row(
                  children: [
                    const Icon(Icons.logout_rounded, color: AppTheme.danger),
                    const SizedBox(width: 12),
                    Text(
                      'Se déconnecter',
                      style: Theme.of(context).textTheme.bodyLarge,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadWarehouse,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
          children: [
            _HomeHeader(
              name: user?.name ?? '',
              role: _roleLabel(user?.roles ?? const []),
              warehouse: _warehouse,
            ),
            if (sections.isEmpty)
              const Padding(
                padding: EdgeInsets.only(top: 48),
                child: EmptyView(
                  icon: Icons.lock_outline,
                  title: 'Aucun module autorisé',
                  message: 'Votre compte n\'a encore aucun droit d\'accès. '
                      'Contactez l\'administrateur pour les activer.',
                ),
              )
            else
              for (final section in sections) ...[
                SectionTitle(section.title),
                for (final module in section.modules)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: _ModuleCard(module: module, color: section.color),
                  ),
              ],
          ],
        ),
      ),
    );
  }
}

/// Libellé du rôle principal (les rôles serveur sont déjà en français).
String _roleLabel(List<String> roles) {
  if (roles.isEmpty) return '';
  final role = roles.first.trim();
  if (role.isEmpty) return '';
  return role[0].toUpperCase() + role.substring(1);
}

/// Salutation selon l'heure : « Bonjour » le jour, « Bonsoir » le soir.
String _greeting(DateTime now) => now.hour >= 18 || now.hour < 5
    ? 'Bonsoir'
    : 'Bonjour';

/// En-tête de l'accueil : salutation, identité, rôle et lieu de travail.
class _HomeHeader extends StatelessWidget {
  const _HomeHeader({
    required this.name,
    required this.role,
    required this.warehouse,
  });

  final String name;
  final String role;
  final Warehouse? warehouse;

  @override
  Widget build(BuildContext context) {
    final initial = name.trim().isEmpty ? '?' : name.trim()[0].toUpperCase();
    final place = warehouse;

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppTheme.navy, AppTheme.navyDeep],
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: AppTheme.navy.withValues(alpha: 0.22),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Container(
                width: 52,
                height: 52,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.16),
                  shape: BoxShape.circle,
                ),
                child: Text(
                  initial,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 24,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${_greeting(DateTime.now())},',
                      style: const TextStyle(
                        color: Colors.white70,
                        fontSize: 15,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      name.isEmpty ? 'Bienvenue' : name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.w700,
                        height: 1.2,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (role.isNotEmpty || place != null) ...[
            const SizedBox(height: 16),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                if (role.isNotEmpty)
                  _HeaderChip(icon: Icons.badge_outlined, label: role),
                if (place != null)
                  _HeaderChip(
                    icon: place.icon,
                    label: place.typeLabel == null
                        ? place.name
                        : '${place.typeLabel} · ${place.name}',
                    highlighted: true,
                  ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

/// Pastille d'information de l'en-tête (rôle, lieu).
class _HeaderChip extends StatelessWidget {
  const _HeaderChip({
    required this.icon,
    required this.label,
    this.highlighted = false,
  });

  final IconData icon;
  final String label;

  /// Le lieu est mis en avant : c'est l'information qui change tout.
  final bool highlighted;

  @override
  Widget build(BuildContext context) {
    return Container(
      constraints: const BoxConstraints(maxWidth: 320),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
      decoration: BoxDecoration(
        color: highlighted
            ? AppTheme.sky.withValues(alpha: 0.28)
            : Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(
          color: highlighted
              ? AppTheme.sky.withValues(alpha: 0.65)
              : Colors.white.withValues(alpha: 0.22),
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 17, color: Colors.white),
          const SizedBox(width: 7),
          Flexible(
            child: Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: Colors.white,
                fontSize: 14,
                height: 1.2,
                fontWeight: highlighted ? FontWeight.w700 : FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Carte d'un module : grande icône teintée, titre, sous-titre explicatif.
class _ModuleCard extends StatelessWidget {
  const _ModuleCard({required this.module, required this.color});

  final _Module module;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(AppTheme.radiusCard),
      elevation: 0,
      child: Ink(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(AppTheme.radiusCard),
          border: Border.all(color: AppTheme.border),
          boxShadow: [
            BoxShadow(
              color: AppTheme.navy.withValues(alpha: 0.06),
              blurRadius: 10,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: InkWell(
          borderRadius: BorderRadius.circular(AppTheme.radiusCard),
          splashColor: color.withValues(alpha: 0.12),
          highlightColor: color.withValues(alpha: 0.06),
          onTap: () => Navigator.of(context).push(
            MaterialPageRoute<void>(builder: module.builder),
          ),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                Container(
                  width: 56,
                  height: 56,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Icon(module.icon, color: color, size: 30),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        module.title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.w700,
                          color: AppTheme.navy,
                          height: 1.2,
                        ),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        module.subtitle,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 14,
                          color: AppTheme.textMuted,
                          height: 1.25,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 6),
                Icon(
                  Icons.chevron_right_rounded,
                  color: color.withValues(alpha: 0.8),
                  size: 26,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
