import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/dashboard.dart';
import '../../models/parse.dart';
import '../../models/report_row.dart';

/// Vue globale de la direction : GET /dashboard (permission
/// `stock.view_global`).
///
/// Le point d'entrée `/dashboard` ne renvoie que le stock consolidé
/// (`summary` + top articles). Les indicateurs financiers proviennent donc
/// des rapports de pilotage, chargés en complément et sans blocage : si
/// l'utilisateur n'a pas `report.consolidated` (ou `payment.view` pour les
/// créances), les cartes concernées sont simplement masquées.
class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final _api = ApiClient.instance;

  DashboardData? _data;
  bool _loading = true;
  String? _error;

  // Compléments facultatifs (null = indisponible pour cet utilisateur).
  double? _stockValue;
  List<StockValuationRow> _valuation = const [];
  double? _salesToday;
  double? _salesMonth;
  double? _collectedMonth;
  double? _customerDue;
  int? _activeAlerts;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final res = await _api.dio.get<Map<String, dynamic>>('/dashboard');
      final data = res.data!['data'] as Map<String, dynamic>;
      if (!mounted) return;
      setState(() {
        _data = DashboardData.fromJson(data);
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = friendlyError(e);
        _loading = false;
      });
      return;
    }

    await Future.wait([
      _loadValuation(),
      _loadSales(),
      _loadCustomerDue(),
      _loadAlerts(),
    ]);
  }

  Future<void> _loadValuation() async {
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/reports/stock-valuation',
      );
      final data = res.data!['data'] as Map<String, dynamic>;
      final rows = data['warehouses'] as List<dynamic>? ?? const [];
      if (!mounted) return;
      setState(() {
        _stockValue = asDouble(data['total_value']);
        _valuation = rows
            .map((e) => StockValuationRow.fromJson(e as Map<String, dynamic>))
            .toList();
      });
    } catch (_) {
      // Permission `report.consolidated` absente : carte masquée.
    }
  }

  Future<void> _loadSales() async {
    final now = DateTime.now();
    final today = apiDate(now);
    final firstOfMonth = apiDate(DateTime(now.year, now.month));

    try {
      final results = await Future.wait([
        _fetchSalesTotals(from: today, to: today),
        _fetchSalesTotals(from: firstOfMonth, to: today),
      ]);
      if (!mounted) return;
      setState(() {
        _salesToday = results[0].$1;
        _salesMonth = results[1].$1;
        _collectedMonth = results[1].$2;
      });
    } catch (_) {
      // Rapports inaccessibles : cartes masquées.
    }
  }

  /// Retourne (chiffre d'affaires, encaissé) sur la période.
  Future<(double, double)> _fetchSalesTotals({
    required String from,
    required String to,
  }) async {
    final res = await _api.dio.get<Map<String, dynamic>>(
      '/reports/sales',
      queryParameters: {'from': from, 'to': to, 'group': 'warehouse'},
    );
    final data = res.data!['data'] as Map<String, dynamic>;
    final rows = (data['rows'] as List<dynamic>? ?? const [])
        .map((e) => SalesReportRow.fromJson(e as Map<String, dynamic>))
        .toList();

    return (
      rows.fold<double>(0, (sum, r) => sum + r.revenue),
      rows.fold<double>(0, (sum, r) => sum + r.secondary),
    );
  }

  Future<void> _loadCustomerDue() async {
    try {
      final res = await _api.dio.get<Map<String, dynamic>>('/customers-aging');
      final rows = res.data!['data'] as List<dynamic>? ?? const [];
      final total = rows.fold<double>(
        0,
        (sum, e) => sum + asDoubleOr((e as Map<String, dynamic>)['total_due']),
      );
      if (!mounted) return;
      setState(() => _customerDue = total);
    } catch (_) {
      // Permission `payment.view` absente : carte masquée.
    }
  }

  Future<void> _loadAlerts() async {
    try {
      final res = await _api.dio.get<Map<String, dynamic>>('/alerts');
      final rows = res.data!['data'] as List<dynamic>? ?? const [];
      final active = rows
          .where((e) => asIntOr((e as Map<String, dynamic>)['count']) > 0)
          .length;
      if (!mounted) return;
      setState(() => _activeAlerts = active);
    } catch (_) {
      // Permission `report.consolidated` absente : carte masquée.
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('stock.view_global')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Vue globale')),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading) return const LoadingView();
    if (_error != null) return ErrorView(message: _error!, onRetry: _load);

    final data = _data;
    if (data == null) {
      return const EmptyView(message: 'Aucune donnée consolidée.');
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(top: 8, bottom: 32),
        children: [
          _buildIndicators(data.summary),
          _buildValuationSection(),
          _buildTopProducts(data.stock),
        ],
      ),
    );
  }

  Widget _buildIndicators(DashboardSummary summary) {
    final cards = <Widget>[
      if (_stockValue != null)
        _IndicatorCard(
          icon: Icons.savings_outlined,
          label: 'Valeur du stock',
          value: formatMoney(_stockValue),
          color: AppTheme.navy,
        ),
      _IndicatorCard(
        icon: Icons.inventory_2_outlined,
        label: 'Unités en stock',
        value: formatQuantity(summary.totalUnits),
        color: AppTheme.sky,
      ),
      if (_salesToday != null)
        _IndicatorCard(
          icon: Icons.today_outlined,
          label: 'Ventes du jour',
          value: formatMoney(_salesToday),
          color: AppTheme.success,
        ),
      if (_salesMonth != null)
        _IndicatorCard(
          icon: Icons.calendar_month_outlined,
          label: 'Ventes du mois',
          value: formatMoney(_salesMonth),
          hint: _collectedMonth == null
              ? null
              : 'Encaissé ${formatMoney(_collectedMonth)}',
          color: AppTheme.success,
        ),
      if (_customerDue != null)
        _IndicatorCard(
          icon: Icons.request_quote_outlined,
          label: 'Créances clients',
          value: formatMoney(_customerDue),
          color: _customerDue! > 0 ? AppTheme.danger : AppTheme.success,
        ),
      if (_activeAlerts != null)
        _IndicatorCard(
          icon: Icons.notification_important_outlined,
          label: 'Alertes actives',
          value: '$_activeAlerts / 7',
          color: _activeAlerts! > 0 ? AppTheme.warning : AppTheme.success,
        ),
      _IndicatorCard(
        icon: Icons.warehouse_outlined,
        label: 'Lieux actifs',
        value: formatQuantity(summary.warehouses),
        color: AppTheme.navy,
      ),
      _IndicatorCard(
        icon: Icons.category_outlined,
        label: 'Articles actifs',
        value: formatQuantity(summary.products),
        hint: '${formatQuantity(summary.distinctInStock)} avec du stock',
        color: AppTheme.navy,
      ),
    ];

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12),
      child: LayoutBuilder(
        builder: (context, constraints) {
          // Deux colonnes sur téléphone, trois dès 600 px de large.
          final columns = constraints.maxWidth >= 600 ? 3 : 2;
          const spacing = 12.0;
          final width =
              (constraints.maxWidth - spacing * (columns - 1)) / columns;

          return Wrap(
            spacing: spacing,
            runSpacing: spacing,
            children: cards
                .map((card) => SizedBox(width: width, child: card))
                .toList(),
          );
        },
      ),
    );
  }

  Widget _buildValuationSection() {
    if (_valuation.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const _SectionTitle('Répartition par lieu'),
        Card(
          child: Column(
            children: [
              for (final row in _valuation)
                ListTile(
                  dense: true,
                  leading: const Icon(
                    Icons.warehouse_outlined,
                    color: AppTheme.navy,
                  ),
                  title: Text(
                    row.label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                  subtitle: Text(
                    '${formatQuantity(row.units)} unités',
                    style: const TextStyle(fontSize: 12),
                  ),
                  trailing: Text(
                    formatMoney(row.value),
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      color: AppTheme.navy,
                    ),
                  ),
                ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildTopProducts(List<ConsolidatedStockRow> stock) {
    if (stock.isEmpty) {
      return const Padding(
        padding: EdgeInsets.only(top: 24),
        child: EmptyView(
          icon: Icons.inventory_2_outlined,
          message: 'Aucun article en stock pour le moment.',
        ),
      );
    }

    final rows = stock.take(20).toList();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const _SectionTitle('Articles les plus stockés'),
        Card(
          child: Column(
            children: [
              for (final row in rows)
                ListTile(
                  dense: true,
                  title: Text(
                    row.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                  subtitle: Text(
                    row.sku,
                    style: const TextStyle(
                      fontFamily: 'monospace',
                      fontSize: 12,
                    ),
                  ),
                  trailing: Text(
                    formatQuantity(row.totalQuantity),
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      color: AppTheme.navy,
                      fontSize: 15,
                    ),
                  ),
                ),
            ],
          ),
        ),
      ],
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.title);

  final String title;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 24, 16, 4),
      child: Text(
        title,
        style: const TextStyle(
          fontSize: 15,
          fontWeight: FontWeight.bold,
          color: AppTheme.navy,
        ),
      ),
    );
  }
}

class _IndicatorCard extends StatelessWidget {
  const _IndicatorCard({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
    this.hint,
  });

  final IconData icon;
  final String label;
  final String value;
  final Color color;
  final String? hint;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              children: [
                Icon(icon, size: 18, color: color),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    label,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(fontSize: 11, color: Colors.grey.shade700),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              value,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 17,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            if (hint != null)
              Padding(
                padding: const EdgeInsets.only(top: 2),
                child: Text(
                  hint!,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(fontSize: 10, color: Colors.grey.shade600),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
