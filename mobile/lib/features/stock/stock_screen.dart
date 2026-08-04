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
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 4),
            child: TextField(
              controller: _searchController,
              onChanged: _onSearchChanged,
              decoration: InputDecoration(
                hintText: 'Rechercher par nom ou référence…',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searchController.text.isEmpty
                    ? null
                    : IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () {
                          _searchController.clear();
                          _onSearchChanged('');
                        },
                      ),
              ),
            ),
          ),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildBody() {
    if (!_firstLoadDone) return const LoadingView();
    if (_error != null && _items.isEmpty) {
      return ErrorView(message: _error!, onRetry: () => _load(reset: true));
    }
    if (_items.isEmpty) {
      return const EmptyView(
        icon: Icons.inventory_2_outlined,
        message: 'Aucun article trouvé.',
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
          if (index >= _items.length) {
            return const Padding(
              padding: EdgeInsets.all(16),
              child: Center(child: CircularProgressIndicator()),
            );
          }
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

    return Card(
      child: ListTile(
        title: Text(
          item.name,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontWeight: FontWeight.w600),
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 4),
          child: Row(
            children: [
              Text(
                item.sku,
                style: const TextStyle(
                  fontFamily: 'monospace',
                  fontSize: 12,
                  color: AppTheme.navy,
                ),
              ),
              const SizedBox(width: 8),
              if (item.minStock > 0)
                Text(
                  'Seuil : ${formatQuantity(item.minStock)}',
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                ),
            ],
          ),
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: item.isBelowThreshold
                    ? AppTheme.danger.withValues(alpha: 0.12)
                    : AppTheme.navy.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                formatQuantity(item.quantity),
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 15,
                  color:
                      item.isBelowThreshold ? AppTheme.danger : AppTheme.navy,
                ),
              ),
            ),
            const SizedBox(height: 4),
            StatusBadge(label: badgeLabel, color: badgeColor),
          ],
        ),
      ),
    );
  }
}
