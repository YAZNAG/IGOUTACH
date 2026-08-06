import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/parse.dart';
import '../../models/report_row.dart';

/// Rapports de pilotage (permission `report.consolidated`) :
/// ventes, valorisation du stock, articles dormants et marges.
///
/// La période choisie en tête s'applique aux ventes et aux marges ; la
/// valorisation est un instantané et les dormants se règlent en nombre de
/// jours sans sortie.
class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key});

  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen>
    with SingleTickerProviderStateMixin {
  final _api = ApiClient.instance;

  late final TabController _tabs;

  DateTime _from = DateTime.now().subtract(const Duration(days: 30));
  DateTime _to = DateTime.now();

  /// `warehouse`, `seller` ou `product`.
  String _group = 'warehouse';

  /// Ancienneté minimale (en jours) d'un article dormant.
  int _dormantDays = 90;

  final _Section<SalesReportRow> _sales = _Section();
  final _Section<StockValuationRow> _valuation = _Section();
  final _Section<DormantProductRow> _dormant = _Section();
  final _Section<MarginRow> _margins = _Section();

  double _valuationTotal = 0;

  @override
  void initState() {
    super.initState();
    _tabs = TabController(length: 4, vsync: this)
      ..addListener(() {
        if (!_tabs.indexIsChanging) _loadCurrent();
      });
    _loadSales();
  }

  @override
  void dispose() {
    _tabs.dispose();
    super.dispose();
  }

  /// Charge l'onglet visible s'il ne l'a jamais été.
  void _loadCurrent({bool force = false}) {
    switch (_tabs.index) {
      case 0:
        if (force || !_sales.loaded) _loadSales();
      case 1:
        if (force || !_valuation.loaded) _loadValuation();
      case 2:
        if (force || !_dormant.loaded) _loadDormant();
      default:
        if (force || !_margins.loaded) _loadMargins();
    }
  }

  Future<void> _loadSales() async {
    setState(() => _sales.start());
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/reports/sales',
        queryParameters: {
          'from': apiDate(_from),
          'to': apiDate(_to),
          'group': _group,
        },
      );
      final data = res.data!['data'] as Map<String, dynamic>;
      final rows = (data['rows'] as List<dynamic>? ?? const [])
          .map((e) => SalesReportRow.fromJson(e as Map<String, dynamic>))
          .toList();
      if (!mounted) return;
      setState(() => _sales.success(rows));
    } catch (e) {
      if (!mounted) return;
      setState(() => _sales.fail(friendlyError(e)));
    }
  }

  Future<void> _loadValuation() async {
    setState(() => _valuation.start());
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/reports/stock-valuation',
      );
      final data = res.data!['data'] as Map<String, dynamic>;
      final rows = (data['warehouses'] as List<dynamic>? ?? const [])
          .map((e) => StockValuationRow.fromJson(e as Map<String, dynamic>))
          .toList();
      if (!mounted) return;
      setState(() {
        _valuationTotal = asDoubleOr(data['total_value']);
        _valuation.success(rows);
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _valuation.fail(friendlyError(e)));
    }
  }

  Future<void> _loadDormant() async {
    setState(() => _dormant.start());
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/reports/dormant-products',
        queryParameters: {'days': _dormantDays},
      );
      final data = res.data!['data'] as Map<String, dynamic>;
      final rows = (data['rows'] as List<dynamic>? ?? const [])
          .map((e) => DormantProductRow.fromJson(e as Map<String, dynamic>))
          .toList();
      if (!mounted) return;
      setState(() {
        _dormantDays = asIntOr(data['days'], _dormantDays);
        _dormant.success(rows);
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _dormant.fail(friendlyError(e)));
    }
  }

  Future<void> _loadMargins() async {
    setState(() => _margins.start());
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/reports/margins',
        queryParameters: {'from': apiDate(_from), 'to': apiDate(_to)},
      );
      final data = res.data!['data'] as Map<String, dynamic>;
      final rows = (data['rows'] as List<dynamic>? ?? const [])
          .map((e) => MarginRow.fromJson(e as Map<String, dynamic>))
          .toList();
      if (!mounted) return;
      setState(() => _margins.success(rows));
    } catch (e) {
      if (!mounted) return;
      setState(() => _margins.fail(friendlyError(e)));
    }
  }

  Future<void> _pickPeriod() async {
    final now = DateTime.now();
    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime(now.year + 1),
      initialDateRange: DateTimeRange(start: _from, end: _to),
      helpText: 'Période du rapport',
      saveText: 'Appliquer',
    );
    if (picked == null) return;
    setState(() {
      _from = picked.start;
      _to = picked.end;
      // Les deux rapports datés doivent être rechargés.
      _sales.invalidate();
      _margins.invalidate();
    });
    _loadCurrent();
  }

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('report.consolidated')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Rapports'),
        bottom: TabBar(
          controller: _tabs,
          isScrollable: true,
          tabAlignment: TabAlignment.start,
          indicatorColor: AppTheme.sky,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Ventes'),
            Tab(text: 'Valorisation'),
            Tab(text: 'Dormants'),
            Tab(text: 'Marges'),
          ],
        ),
      ),
      body: Column(
        children: [
          _buildPeriodBar(),
          Expanded(
            child: TabBarView(
              controller: _tabs,
              children: [
                _buildSalesTab(),
                _buildValuationTab(),
                _buildDormantTab(),
                _buildMarginsTab(),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPeriodBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 0),
      child: OutlinedButton.icon(
        onPressed: _pickPeriod,
        icon: const Icon(Icons.date_range, size: 18),
        label: Text(
          'Période : ${formatDate(_from)} → ${formatDate(_to)}',
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 13),
        ),
      ),
    );
  }

  // ── Onglet Ventes ───────────────────────────────────────────────────────

  Widget _buildSalesTab() {
    final isProduct = _group == 'product';

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(12, 12, 12, 0),
          child: SegmentedButton<String>(
            segments: const [
              ButtonSegment(value: 'warehouse', label: Text('Lieu')),
              ButtonSegment(value: 'seller', label: Text('Vendeur')),
              ButtonSegment(value: 'product', label: Text('Article')),
            ],
            selected: {_group},
            showSelectedIcon: false,
            onSelectionChanged: (values) {
              setState(() => _group = values.first);
              _loadSales();
            },
          ),
        ),
        Expanded(
          child: _SectionBody<SalesReportRow>(
            section: _sales,
            onRetry: _loadSales,
            emptyMessage: 'Aucune vente confirmée sur cette période.',
            builder: (rows) => _ReportTable(
              headers: [
                isProduct ? 'Article' : (_group == 'seller' ? 'Vendeur' : 'Lieu'),
                isProduct ? 'Qté' : 'Docs',
                'CA',
                isProduct ? 'Coût' : 'Encaissé',
              ],
              rows: rows
                  .map((r) => [
                        r.label,
                        formatQuantity(r.documents),
                        formatMoney(r.revenue),
                        formatMoney(r.secondary),
                      ])
                  .toList(),
              totals: [
                'Total',
                formatQuantity(
                  rows.fold<int>(0, (sum, r) => sum + r.documents),
                ),
                formatMoney(rows.fold<double>(0, (sum, r) => sum + r.revenue)),
                formatMoney(
                  rows.fold<double>(0, (sum, r) => sum + r.secondary),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  // ── Onglet Valorisation ─────────────────────────────────────────────────

  Widget _buildValuationTab() {
    return _SectionBody<StockValuationRow>(
      section: _valuation,
      onRetry: _loadValuation,
      emptyMessage: 'Aucun stock valorisé.',
      builder: (rows) => _ReportTable(
        headers: const ['Lieu', 'Unités', 'Valeur'],
        rows: rows
            .map((r) => [
                  r.label,
                  formatQuantity(r.units),
                  formatMoney(r.value),
                ])
            .toList(),
        totals: [
          'Total',
          formatQuantity(rows.fold<int>(0, (sum, r) => sum + r.units)),
          formatMoney(_valuationTotal),
        ],
      ),
    );
  }

  // ── Onglet Articles dormants ────────────────────────────────────────────

  Widget _buildDormantTab() {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(12, 12, 12, 0),
          child: Row(
            children: [
              const Text('Sans sortie depuis', style: TextStyle(fontSize: 13)),
              const SizedBox(width: 12),
              DropdownButton<int>(
                value: _dormantDays,
                items: const [
                  DropdownMenuItem(value: 30, child: Text('30 jours')),
                  DropdownMenuItem(value: 60, child: Text('60 jours')),
                  DropdownMenuItem(value: 90, child: Text('90 jours')),
                  DropdownMenuItem(value: 180, child: Text('180 jours')),
                  DropdownMenuItem(value: 365, child: Text('365 jours')),
                ],
                onChanged: (value) {
                  if (value == null) return;
                  setState(() => _dormantDays = value);
                  _loadDormant();
                },
              ),
            ],
          ),
        ),
        Expanded(
          child: _SectionBody<DormantProductRow>(
            section: _dormant,
            onRetry: _loadDormant,
            emptyMessage: 'Aucun article dormant : tout tourne.',
            builder: (rows) => _ReportTable(
              headers: const ['Article', 'Qté', 'Valeur immobilisée'],
              rows: rows
                  .map((r) => [
                        r.sku.isEmpty ? r.name : '${r.sku} — ${r.name}',
                        formatQuantity(r.quantity),
                        formatMoney(r.immobilizedValue),
                      ])
                  .toList(),
              totals: [
                'Total',
                formatQuantity(rows.fold<int>(0, (sum, r) => sum + r.quantity)),
                formatMoney(
                  rows.fold<double>(0, (sum, r) => sum + r.immobilizedValue),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  // ── Onglet Marges ───────────────────────────────────────────────────────

  Widget _buildMarginsTab() {
    return _SectionBody<MarginRow>(
      section: _margins,
      onRetry: _loadMargins,
      emptyMessage: 'Aucune marge calculable sur cette période.',
      builder: (rows) {
        final revenue = rows.fold<double>(0, (sum, r) => sum + r.revenue);
        final margin = rows.fold<double>(0, (sum, r) => sum + r.margin);

        return _ReportTable(
          headers: const ['Article', 'CA', 'Coût', 'Marge'],
          rows: rows
              .map((r) => [
                    r.sku.isEmpty ? r.name : '${r.sku} — ${r.name}',
                    formatMoney(r.revenue),
                    formatMoney(r.cost),
                    r.marginPercent == null
                        ? formatMoney(r.margin)
                        : '${formatMoney(r.margin)}\n'
                            '${r.marginPercent!.toStringAsFixed(1)} %',
                  ])
              .toList(),
          totals: [
            'Total',
            formatMoney(revenue),
            formatMoney(rows.fold<double>(0, (sum, r) => sum + r.cost)),
            revenue == 0
                ? formatMoney(margin)
                : '${formatMoney(margin)}\n'
                    '${(margin / revenue * 100).toStringAsFixed(1)} %',
          ],
        );
      },
    );
  }
}

/// État de chargement mutualisé d'un onglet.
class _Section<T> {
  List<T> rows = const [];
  bool loading = false;
  bool loaded = false;
  String? error;

  void start() {
    loading = true;
    error = null;
  }

  void success(List<T> data) {
    rows = data;
    loading = false;
    loaded = true;
  }

  void fail(String message) {
    error = message;
    loading = false;
    loaded = true;
  }

  void invalidate() => loaded = false;
}

/// Rend l'état (chargement / erreur / vide / tableau) d'un onglet.
class _SectionBody<T> extends StatelessWidget {
  const _SectionBody({
    required this.section,
    required this.onRetry,
    required this.emptyMessage,
    required this.builder,
  });

  final _Section<T> section;
  final VoidCallback onRetry;
  final String emptyMessage;
  final Widget Function(List<T> rows) builder;

  @override
  Widget build(BuildContext context) {
    if (section.loading) return const LoadingView();
    if (section.error != null) {
      return ErrorView(message: section.error!, onRetry: onRetry);
    }
    if (section.rows.isEmpty) {
      return EmptyView(icon: Icons.bar_chart_outlined, message: emptyMessage);
    }
    return builder(section.rows);
  }
}

/// Tableau simple : en-têtes, lignes et pied de totaux.
///
/// La première colonne est libellée (alignée à gauche), les suivantes sont
/// numériques (alignées à droite).
class _ReportTable extends StatelessWidget {
  const _ReportTable({
    required this.headers,
    required this.rows,
    required this.totals,
  });

  final List<String> headers;
  final List<List<String>> rows;
  final List<String> totals;

  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 24),
      itemCount: rows.length + 2,
      itemBuilder: (context, index) {
        if (index == 0) {
          return _Row(cells: headers, isHeader: true);
        }
        if (index <= rows.length) {
          return _Row(cells: rows[index - 1]);
        }
        return _Row(cells: totals, isTotal: true);
      },
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({
    required this.cells,
    this.isHeader = false,
    this.isTotal = false,
  });

  final List<String> cells;
  final bool isHeader;
  final bool isTotal;

  @override
  Widget build(BuildContext context) {
    final style = TextStyle(
      fontSize: isHeader ? 11 : 12,
      fontWeight: isHeader || isTotal ? FontWeight.bold : FontWeight.normal,
      color: isHeader
          ? Colors.grey.shade700
          : (isTotal ? AppTheme.navy : Colors.black87),
    );

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: isTotal
            ? AppTheme.navy.withValues(alpha: 0.06)
            : (isHeader ? Colors.transparent : Colors.white),
        border: Border(
          bottom: BorderSide(color: Colors.grey.shade200),
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          for (var i = 0; i < cells.length; i++)
            Expanded(
              flex: i == 0 ? 4 : 2,
              child: Text(
                cells[i],
                maxLines: i == 0 ? 2 : 2,
                overflow: TextOverflow.ellipsis,
                textAlign: i == 0 ? TextAlign.left : TextAlign.right,
                style: style,
              ),
            ),
        ],
      ),
    );
  }
}
