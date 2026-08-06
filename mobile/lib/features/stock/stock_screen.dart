import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/stock_item.dart';

/// Stock du lieu de l'utilisateur (GET /stock).
/// Recherche avec debounce + pagination infinie.
class StockScreen extends StatefulWidget {
  const StockScreen({super.key});

  @override
  State<StockScreen> createState() => _StockScreenState();
}

class _StockScreenState extends State<StockScreen> {
  final _api = ApiClient.instance;
  final _scrollController = ScrollController();
  final _searchController = TextEditingController();
  Timer? _debounce;

  final List<StockItem> _items = [];
  int _page = 1;
  int _lastPage = 1;
  bool _loading = false;
  bool _firstLoadDone = false;
  String? _error;
  bool _offline = false;
  String _query = '';

  bool get _hasMore => _page < _lastPage;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    _load(reset: true);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _scrollController.dispose();
    _searchController.dispose();
    super.dispose();
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
        _items.clear();
        _firstLoadDone = false;
      }
    });

    final warehouseId = context.read<AuthProvider>().user?.warehouseId;

    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/stock',
        queryParameters: {
          'per_page': 50,
          'page': _page + 1,
          if (_query.isNotEmpty) 'q': _query,
          'warehouse_id': ?warehouseId,
        },
      );
      final body = res.data!;
      final data = body['data'] as List<dynamic>? ?? [];
      final meta = body['meta'] as Map<String, dynamic>? ?? {};
      if (!mounted) return;
      setState(() {
        _page = (meta['current_page'] as num?)?.toInt() ?? _page + 1;
        _lastPage = (meta['last_page'] as num?)?.toInt() ?? _page;
        _items.addAll(
          data.map((e) => StockItem.fromJson(e as Map<String, dynamic>)),
        );
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

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('stock.view')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Mon stock')),
      body: Column(
        children: [
          AppSearchField(
            controller: _searchController,
            onChanged: _onSearchChanged,
            hintText: 'Rechercher par nom ou référence…',
          ),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildBody() {
    if (!_firstLoadDone) return const ListSkeleton(itemCount: 7);
    if (_error != null && _items.isEmpty) {
      return ErrorView(
        message: _error!,
        offline: _offline,
        onRetry: () => _load(reset: true),
      );
    }
    if (_items.isEmpty) {
      return EmptyView(
        icon: Icons.inventory_2_outlined,
        title: _query.isEmpty ? 'Stock vide' : 'Aucun résultat',
        message: _query.isEmpty
            ? 'Aucun article n\'est encore suivi dans ce lieu.'
            : 'Aucun article ne correspond à « $_query ».',
      );
    }

    return RefreshIndicator(
      onRefresh: () => _load(reset: true),
      child: ListView.builder(
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(bottom: 24, top: 4),
        itemCount: _items.length + (_hasMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index >= _items.length) return const SkeletonCard();
          return _StockTile(item: _items[index]);
        },
      ),
    );
  }
}

class _StockTile extends StatelessWidget {
  const _StockTile({required this.item});

  final StockItem item;

  @override
  Widget build(BuildContext context) {
    final Color badgeColor;
    final String badgeLabel;
    switch (item.status) {
      case 'rupture':
        badgeColor = AppTheme.danger;
        badgeLabel = 'Rupture';
      case 'low':
        badgeColor = AppTheme.warning;
        badgeLabel = 'Sous seuil';
      default:
        badgeColor = AppTheme.success;
        badgeLabel = 'OK';
    }

    final quantityColor =
        item.isBelowThreshold ? AppTheme.danger : AppTheme.navy;

    return Card(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                      height: 1.25,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    item.minStock > 0
                        ? '${item.sku} · seuil ${formatQuantity(item.minStock)}'
                        : item.sku,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 14,
                      color: AppTheme.textMuted,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 12),
            Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 5,
                  ),
                  decoration: BoxDecoration(
                    color: quantityColor.withValues(alpha: 0.10),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    formatQuantity(item.quantity),
                    style: AppTheme.amountStyle(
                      fontSize: 18,
                      color: quantityColor,
                    ),
                  ),
                ),
                const SizedBox(height: 6),
                StatusBadge(label: badgeLabel, color: badgeColor),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
