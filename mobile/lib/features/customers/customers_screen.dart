import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/customer.dart';
import '../shared/warehouse_scope.dart' show InfoBanner;
import 'create_customer_screen.dart';
import 'customer_detail_screen.dart';

/// Liste des clients (GET /customers) : recherche + pagination infinie.
class CustomersScreen extends StatefulWidget {
  const CustomersScreen({super.key});

  @override
  State<CustomersScreen> createState() => _CustomersScreenState();
}

class _CustomersScreenState extends State<CustomersScreen> {
  final _api = ApiClient.instance;
  final _scrollController = ScrollController();
  final _searchController = TextEditingController();
  Timer? _debounce;

  final List<Customer> _customers = [];
  int _page = 0;
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
        _customers.clear();
        _firstLoadDone = false;
      }
    });

    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/customers',
        queryParameters: {
          'per_page': 50,
          'page': _page + 1,
          if (_query.isNotEmpty) 'q': _query,
        },
      );
      final body = res.data!;
      final data = body['data'] as List<dynamic>? ?? [];
      final meta = body['meta'] as Map<String, dynamic>? ?? {};
      if (!mounted) return;
      setState(() {
        _page = (meta['current_page'] as num?)?.toInt() ?? _page + 1;
        _lastPage = (meta['last_page'] as num?)?.toInt() ?? _page;
        _customers.addAll(
          data.map((e) => Customer.fromJson(e as Map<String, dynamic>)),
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

  Future<void> _openCreate() async {
    final messenger = ScaffoldMessenger.of(context);
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => const CreateCustomerScreen()),
    );
    if (created == true) {
      showSuccessSnack(messenger, 'Client créé.');
      _load(reset: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.can('customer.view')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Clients')),
      floatingActionButton: auth.can('customer.create')
          ? FloatingActionButton.extended(
              onPressed: _openCreate,
              tooltip: 'Nouveau client',
              icon: const Icon(Icons.person_add_alt_1),
              label: const Text('Nouveau client'),
            )
          : null,
      body: Column(
        children: [
          // Le serveur restreint déjà la liste au créateur : on l'explique.
          if (!auth.can('customer.view_all'))
            const InfoBanner(
              message:
                  'Vous voyez uniquement les clients que vous avez créés.',
            ),
          AppSearchField(
            controller: _searchController,
            onChanged: _onSearchChanged,
            hintText: 'Rechercher (nom, code, téléphone)…',
          ),
          Expanded(child: _buildBody(canCreate: auth.can('customer.create'))),
        ],
      ),
    );
  }

  Widget _buildBody({required bool canCreate}) {
    if (!_firstLoadDone) {
      return const ListSkeleton(itemCount: 7, hasLeading: true);
    }
    if (_error != null && _customers.isEmpty) {
      return ErrorView(
        message: _error!,
        offline: _offline,
        onRetry: () => _load(reset: true),
      );
    }
    if (_customers.isEmpty) {
      return EmptyView(
        icon: Icons.people_outline,
        title: _query.isEmpty ? 'Aucun client' : 'Aucun résultat',
        message: _query.isEmpty
            ? 'Créez une fiche client pour suivre ses achats et son encours.'
            : 'Aucun client ne correspond à « $_query ».',
        actionLabel: _query.isEmpty && canCreate ? 'Ajouter un client' : null,
        actionIcon: Icons.person_add_alt_1,
        onAction: _query.isEmpty && canCreate ? _openCreate : null,
      );
    }

    return RefreshIndicator(
      onRefresh: () => _load(reset: true),
      child: ListView.builder(
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(bottom: 88, top: 4),
        itemCount: _customers.length + (_hasMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index >= _customers.length) {
            return const SkeletonCard(hasLeading: true);
          }
          final customer = _customers[index];
          return _CustomerTile(
            customer: customer,
            onTap: () => Navigator.of(context).push(
              MaterialPageRoute<void>(
                builder: (_) => CustomerDetailScreen(customer: customer),
              ),
            ),
          );
        },
      ),
    );
  }
}

class _CustomerTile extends StatelessWidget {
  const _CustomerTile({required this.customer, required this.onTap});

  final Customer customer;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(14, 14, 16, 14),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              CircleAvatar(
                radius: 22,
                backgroundColor: AppTheme.navy.withValues(alpha: 0.1),
                child: Text(
                  customer.name.isNotEmpty
                      ? customer.name[0].toUpperCase()
                      : '?',
                  style: const TextStyle(
                    color: AppTheme.navy,
                    fontSize: 17,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      customer.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        height: 1.25,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      [
                        customer.code,
                        if ((customer.city ?? '').isNotEmpty) customer.city!,
                      ].join(' · '),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 14,
                        color: AppTheme.textMuted,
                      ),
                    ),
                    if (customer.isBlocked) ...[
                      const SizedBox(height: 6),
                      const StatusBadge(
                        label: 'Bloqué',
                        color: AppTheme.danger,
                        icon: Icons.block,
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(width: 10),
              AmountText(
                formatMoney(customer.balance),
                label: 'Encours',
                color:
                    customer.isOverLimit ? AppTheme.danger : AppTheme.navy,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
