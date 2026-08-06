import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/stock_movement.dart';
import '../shared/warehouse_scope.dart';
import 'movement_detail_screen.dart';

/// Liste des mouvements de stock d'un lieu — implémentation commune aux
/// entrées (`/stock/entries`) et aux sorties (`/stock/exits`), qui partagent
/// exactement la même forme de réponse (`data` + `totals` + `meta`).
///
/// Les écrans publics [StockEntriesScreen] et [StockExitsScreen] ne font que
/// la paramétrer (titre, chemin, sens des quantités, libellé du coût).
class MovementsListScreen extends StatefulWidget {
  const MovementsListScreen({
    super.key,
    required this.title,
    required this.basePath,
    required this.isExit,
  });

  final String title;

  /// `/stock/entries` ou `/stock/exits`.
  final String basePath;

  /// Sorties : quantités affichées en −rouge et coût libellé « CMUP ».
  final bool isExit;

  @override
  State<MovementsListScreen> createState() => _MovementsListScreenState();
}

class _MovementsListScreenState extends State<MovementsListScreen> {
  final _api = ApiClient.instance;
  final _scrollController = ScrollController();
  final _searchController = TextEditingController();
  Timer? _debounce;

  final List<StockMovementRow> _rows = [];
  StockMovementTotals _totals = StockMovementTotals.empty;

  int _page = 0;
  int _lastPage = 1;
  bool _loading = false;
  bool _firstLoadDone = false;
  String? _error;
  bool _offline = false;
  String _query = '';

  DateTime? _dateFrom;
  DateTime? _dateTo;

  WarehouseScope? _scope;
  bool _scopeReady = false;

  bool get _hasMore => _page < _lastPage;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    _init();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _scrollController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _init() async {
    final userWarehouseId = context.read<AuthProvider>().user?.warehouseId;
    final scope = await WarehouseScope.load(userWarehouseId);
    if (!mounted) return;
    setState(() {
      _scope = scope;
      _scopeReady = true;
    });
    _load(reset: true);
  }

  void _onScroll() {
    if (!_scrollController.hasClients) return;
    final position = _scrollController.position;
    if (position.pixels > position.maxScrollExtent - 300 &&
        _hasMore &&
        !_loading) {
      _load();
    }
  }

  void _onSearchChanged(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 300), () {
      _query = value.trim();
      _load(reset: true);
    });
  }

  Future<void> _load({bool reset = false}) async {
    if (_loading) return;
    setState(() {
      _loading = true;
      if (reset) {
        _error = null;
        _page = 0;
        _lastPage = 1;
        _rows.clear();
        _firstLoadDone = false;
      }
    });

    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        widget.basePath,
        queryParameters: {
          'per_page': 50,
          'page': _page + 1,
          'warehouse_id': ?_scope?.selectedId,
          'date_from': ?(_dateFrom == null ? null : apiDate(_dateFrom!)),
          'date_to': ?(_dateTo == null ? null : apiDate(_dateTo!)),
          if (_query.isNotEmpty) 'search': _query,
        },
      );
      final body = res.data!;
      final data = body['data'] as List<dynamic>? ?? [];
      final meta = body['meta'] as Map<String, dynamic>? ?? {};
      final totals = body['totals'] as Map<String, dynamic>?;
      if (!mounted) return;
      setState(() {
        _page = (meta['current_page'] as num?)?.toInt() ?? _page + 1;
        _lastPage = (meta['last_page'] as num?)?.toInt() ?? _page;
        _rows.addAll(
          data.map((e) => StockMovementRow.fromJson(e as Map<String, dynamic>)),
        );
        if (totals != null) {
          _totals = StockMovementTotals.fromJson(totals);
        }
        _firstLoadDone = true;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = friendlyError(e);
        _offline = isNetworkError(e);
        _loading = false;
        _firstLoadDone = true;
      });
    }
  }

  Future<void> _pickPeriod() async {
    final now = DateTime.now();
    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime(now.year + 1),
      initialDateRange: _dateFrom != null && _dateTo != null
          ? DateTimeRange(start: _dateFrom!, end: _dateTo!)
          : null,
      helpText: 'Période',
      saveText: 'Appliquer',
    );
    if (picked == null) return;
    setState(() {
      _dateFrom = picked.start;
      _dateTo = picked.end;
    });
    _load(reset: true);
  }

  void _clearPeriod() {
    setState(() {
      _dateFrom = null;
      _dateTo = null;
    });
    _load(reset: true);
  }

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('stock.view')) {
      return const NotAllowedView();
    }

    final scope = _scope;

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
        bottom: scope?.selected == null
            ? null
            : WarehouseAppBarLabel(warehouse: scope!.selected!),
      ),
      body: !_scopeReady
          ? const ListSkeleton(itemCount: 6, lines: 3)
          : Column(
              children: [
                if (scope != null)
                  WarehouseSelectorBar(
                    scope: scope,
                    onChanged: (id) {
                      setState(() => _scope = scope.copyWith(selectedId: id));
                      _load(reset: true);
                    },
                  ),
                _buildFilters(),
                _buildTotals(),
                Expanded(child: _buildBody()),
              ],
            ),
    );
  }

  Widget _buildFilters() {
    final hasPeriod = _dateFrom != null && _dateTo != null;

    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 0),
      child: Column(
        children: [
          AppSearchField(
            controller: _searchController,
            onChanged: _onSearchChanged,
            hintText: 'Rechercher un article (nom, référence)…',
            padding: EdgeInsets.zero,
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: _pickPeriod,
                  icon: const Icon(Icons.date_range, size: 20),
                  label: Text(
                    hasPeriod
                        ? '${formatDate(_dateFrom)} → ${formatDate(_dateTo)}'
                        : 'Toute la période',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ),
              if (hasPeriod)
                IconButton(
                  icon: const Icon(Icons.close),
                  tooltip: 'Effacer la période',
                  onPressed: _clearPeriod,
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTotals() {
    return Container(
      margin: const EdgeInsets.fromLTRB(12, 12, 12, 4),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppTheme.navy, AppTheme.navyDeep],
        ),
        borderRadius: BorderRadius.circular(AppTheme.radiusCard),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
        child: Row(
          children: [
            Expanded(
              child: _Total(
                label: 'Lignes',
                value: formatQuantity(_totals.linesCount),
              ),
            ),
            Expanded(
              child: _Total(
                label: 'Quantité',
                value: formatQuantity(_totals.totalQuantity),
              ),
            ),
            Expanded(
              child: _Total(
                label: 'Valeur',
                value: formatMoney(_totals.totalValue),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (!_firstLoadDone) return const ListSkeleton(itemCount: 6, lines: 3);
    if (_error != null && _rows.isEmpty) {
      return ErrorView(
        message: _error!,
        offline: _offline,
        onRetry: () => _load(reset: true),
      );
    }
    if (_rows.isEmpty) {
      return EmptyView(
        icon: widget.isExit
            ? Icons.outbox_outlined
            : Icons.move_to_inbox_outlined,
        title: widget.isExit ? 'Aucune sortie' : 'Aucune entrée',
        message: 'Aucun mouvement sur la période et le lieu sélectionnés. '
            'Élargissez la période pour en voir davantage.',
      );
    }

    return RefreshIndicator(
      onRefresh: () => _load(reset: true),
      child: ListView.builder(
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(top: 4, bottom: 24),
        itemCount: _rows.length + (_hasMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index >= _rows.length) return const SkeletonCard(lines: 3);
          final row = _rows[index];
          return _MovementCard(
            row: row,
            isExit: widget.isExit,
            onTap: () => Navigator.of(context).push(
              MaterialPageRoute<void>(
                builder: (_) => MovementDetailScreen(
                  movementId: row.id,
                  basePath: widget.basePath,
                  isExit: widget.isExit,
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class _Total extends StatelessWidget {
  const _Total({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          label,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(color: Colors.white70, fontSize: 13),
        ),
        const SizedBox(height: 3),
        FittedBox(
          fit: BoxFit.scaleDown,
          child: Text(
            value,
            maxLines: 1,
            textAlign: TextAlign.center,
            style: AppTheme.amountStyle(fontSize: 16, color: Colors.white),
          ),
        ),
      ],
    );
  }
}

class _MovementCard extends StatelessWidget {
  const _MovementCard({
    required this.row,
    required this.isExit,
    required this.onTap,
  });

  final StockMovementRow row;
  final bool isExit;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final color = isExit ? AppTheme.danger : AppTheme.success;
    final sign = isExit ? '−' : '+';

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          row.productName ?? '—',
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontWeight: FontWeight.w600,
                            fontSize: 16,
                            height: 1.25,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          [
                            row.shortDate,
                            if ((row.sku ?? '').isNotEmpty) row.sku!,
                            if ((row.sourceLabel ?? '').isNotEmpty)
                              row.sourceLabel!,
                          ].join(' · '),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 14,
                            color: AppTheme.textMuted,
                            height: 1.3,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 10),
                  Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      AmountText(
                        '$sign${formatQuantity(row.quantity)}',
                        fontSize: 20,
                        color: color,
                        label: formatMoney(row.lineValue),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Solde ${formatQuantity(row.balanceAfter)}',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 13,
                          color: AppTheme.textMuted,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 6,
                children: [
                  if ((row.typeName ?? '').isNotEmpty)
                    StatusBadge(label: row.typeName!, color: AppTheme.sky),
                  StatusBadge(
                    label: '${isExit ? 'CMUP' : 'PU'} '
                        '${formatMoney(row.unitCost)}',
                    color: AppTheme.navy,
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
