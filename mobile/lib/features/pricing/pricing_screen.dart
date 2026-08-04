import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/price_row.dart';

/// Tarifs 3 niveaux (détail / demi-gros / gros) : GET /prices.
class PricingScreen extends StatefulWidget {
  const PricingScreen({super.key});

  @override
  State<PricingScreen> createState() => _PricingScreenState();
}

class _PricingScreenState extends State<PricingScreen> {
  final _api = ApiClient.instance;
  final _scrollController = ScrollController();
  final _searchController = TextEditingController();
  Timer? _debounce;

  final List<PriceRow> _rows = [];
  int _page = 0;
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
        _rows.clear();
        _firstLoadDone = false;
      }
    });

    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/prices',
        queryParameters: {
          'per_page': 50,
          'page': _page + 1,
          if (_query.isNotEmpty) 'search': _query,
        },
      );
      final body = res.data!;
      final data = body['data'] as List<dynamic>? ?? [];
      final meta = body['meta'] as Map<String, dynamic>? ?? {};
      if (!mounted) return;
      setState(() {
        _page = (meta['current_page'] as num?)?.toInt() ?? _page + 1;
        _lastPage = (meta['last_page'] as num?)?.toInt() ?? _page;
        _rows.addAll(
          data.map((e) => PriceRow.fromJson(e as Map<String, dynamic>)),
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
    if (!context.watch<AuthProvider>().can('price.view')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Tarifs')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 4),
            child: TextField(
              controller: _searchController,
              onChanged: _onSearchChanged,
              decoration: InputDecoration(
                hintText: 'Rechercher un article…',
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
    if (_error != null && _rows.isEmpty) {
      return ErrorView(message: _error!, onRetry: () => _load(reset: true));
    }
    if (_rows.isEmpty) {
      return const EmptyView(
        icon: Icons.sell_outlined,
        message: 'Aucun article trouvé.',
      );
    }

    return RefreshIndicator(
      onRefresh: () => _load(reset: true),
      child: ListView.builder(
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(bottom: 24, top: 4),
        itemCount: _rows.length + (_hasMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index >= _rows.length) {
            return const Padding(
              padding: EdgeInsets.all(16),
              child: Center(child: CircularProgressIndicator()),
            );
          }
          return _PriceCard(row: _rows[index]);
        },
      ),
    );
  }
}

class _PriceCard extends StatelessWidget {
  const _PriceCard({required this.row});

  final PriceRow row;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              row.name,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            Text(
              row.sku,
              style: const TextStyle(
                fontFamily: 'monospace',
                fontSize: 12,
                color: AppTheme.navy,
              ),
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: _PricePill(
                    label: 'Détail',
                    level: row.prices['detail'],
                    color: AppTheme.navy,
                  ),
                ),
                const SizedBox(width: 6),
                Expanded(
                  child: _PricePill(
                    label: 'Demi-gros',
                    level: row.prices['semi_gros'],
                    color: AppTheme.sky,
                  ),
                ),
                const SizedBox(width: 6),
                Expanded(
                  child: _PricePill(
                    label: 'Gros',
                    level: row.prices['gros'],
                    color: AppTheme.success,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _PricePill extends StatelessWidget {
  const _PricePill({
    required this.label,
    required this.level,
    required this.color,
  });

  final String label;
  final PriceLevel? level;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final amount = level?.amount;
    final minQty = level?.minQuantity;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              color: color,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            amount == null ? '—' : formatMoney(amount),
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: amount == null ? Colors.grey : color,
            ),
          ),
          if (minQty != null && minQty > 1)
            Text(
              'dès $minQty',
              style: TextStyle(fontSize: 10, color: Colors.grey.shade600),
            ),
        ],
      ),
    );
  }
}
