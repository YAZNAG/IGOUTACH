import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/product_cost.dart';

/// Prix d'achat des articles : GET /product-costs.
///
/// La page répond à une seule question : combien cet article m'a coûté, et
/// combien me rapporte chacun de mes trois tarifs. Le prix retenu est celui
/// du dernier bon de réception, réglé ou non. Aucune moyenne de stock n'entre
/// dans le calcul : elle mélangeait des arrivages anciens et ne correspondait
/// à aucun prix qu'on puisse négocier.
class PurchasePricesScreen extends StatefulWidget {
  const PurchasePricesScreen({super.key});

  @override
  State<PurchasePricesScreen> createState() => _PurchasePricesScreenState();
}

class _PurchasePricesScreenState extends State<PurchasePricesScreen> {
  final _api = ApiClient.instance;
  final _scrollController = ScrollController();
  final _searchController = TextEditingController();
  Timer? _debounce;

  final List<ProductCostRow> _rows = [];
  int _page = 0;
  int _lastPage = 1;
  int _total = 0;
  bool _loading = false;
  bool _firstLoadDone = false;
  String? _error;
  bool _offline = false;
  String _query = '';

  /// Restreint la liste aux articles réellement reçus par un bon. Sans ce
  /// filtre, les centaines d'articles entrés par l'inventaire d'ouverture
  /// noient la poignée dont on connaît le prix payé.
  bool _seulementAchetes = false;

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
    if (position.pixels > position.maxScrollExtent - 300 && _hasMore && !_loading) {
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
        '/product-costs',
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
        _total = (meta['total'] as num?)?.toInt() ?? 0;
        _rows.addAll(data.map((e) => ProductCostRow.fromJson(e as Map<String, dynamic>)));
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

  /// Le filtre se fait côté écran : l'API pagine sur l'ensemble, et une page
  /// peut n'en contenir aucun. Le compteur dit donc ce qui est visible ici,
  /// pas ce que compte le serveur.
  List<ProductCostRow> get _visibles => _seulementAchetes
      ? _rows.where((r) => r.lastPurchasePrice != null).toList()
      : _rows;

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('product.view_cost_price')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(title: const Text("Prix d'achat")),
      body: Column(
        children: [
          AppSearchField(
            controller: _searchController,
            onChanged: _onSearchChanged,
            hintText: 'Rechercher un article…',
          ),
          _buildFiltre(),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildFiltre() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 12, 6),
      child: Row(
        children: [
          Expanded(
            child: Text(
              _seulementAchetes
                  ? '${_visibles.length} article(s) reçus par bon'
                  : '$_total article(s)',
              style: const TextStyle(fontSize: 13, color: AppTheme.textMuted),
            ),
          ),
          const Text(
            'Achetés seulement',
            style: TextStyle(fontSize: 13, color: AppTheme.textMuted),
          ),
          Switch(
            value: _seulementAchetes,
            onChanged: (v) => setState(() => _seulementAchetes = v),
          ),
        ],
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

    final rows = _visibles;
    if (rows.isEmpty) {
      return EmptyView(
        icon: Icons.local_offer_outlined,
        title: _query.isEmpty ? 'Aucun article' : 'Aucun résultat',
        message: _query.isEmpty
            ? 'Aucun article à afficher.'
            : 'Aucun article ne correspond à « $_query ».',
      );
    }

    return RefreshIndicator(
      onRefresh: () => _load(reset: true),
      child: ListView.builder(
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(bottom: 24, top: 4),
        itemCount: rows.length + (_hasMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index >= rows.length) return const SkeletonCard(lines: 3);
          return _CarteAchat(row: rows[index]);
        },
      ),
    );
  }
}

class _CarteAchat extends StatelessWidget {
  const _CarteAchat({required this.row});

  final ProductCostRow row;

  /// Libellé du règlement du bon. Le prix d'achat s'applique dans tous les
  /// cas : c'est une information de suivi fournisseur, pas une condition.
  String? get _reglement => switch (row.lastPurchasePaymentStatus) {
        'paid' => 'payé',
        'partial' => 'payé en partie',
        'unpaid' => 'non payé',
        _ => null,
      };

  Color get _couleurReglement =>
      row.lastPurchasePaymentStatus == 'paid' ? AppTheme.success : AppTheme.warning;

  @override
  Widget build(BuildContext context) {
    final achat = row.appliedPurchasePrice;
    final recu = row.lastPurchasePrice != null;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        row.name,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                          height: 1.25,
                        ),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        row.sku,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: AppTheme.codeStyle,
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 10),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      achat != null ? formatMoney(achat) : '—',
                      style: AppTheme.amountStyle(
                        fontSize: 20,
                        color: achat != null ? AppTheme.navy : AppTheme.textMuted,
                      ),
                    ),
                    const Text(
                      "prix d'achat",
                      style: TextStyle(fontSize: 11, color: AppTheme.textMuted),
                    ),
                  ],
                ),
              ],
            ),

            const SizedBox(height: 10),

            // D'où vient ce prix. Un chiffre sans sa pièce ne se discute pas
            // avec un fournisseur.
            if (recu) ...[
              Row(
                children: [
                  Expanded(
                    child: Text(
                      [
                        if (row.lastPurchaseNumber != null) row.lastPurchaseNumber!,
                        if (row.lastPurchaseAt != null) 'du ${formatDateTexte(row.lastPurchaseAt!)}',
                        if (row.lastPurchaseSupplier != null) '· ${row.lastPurchaseSupplier}',
                      ].join(' '),
                      maxLines: 2,
                      style: const TextStyle(fontSize: 12, color: AppTheme.textMuted),
                    ),
                  ),
                  if (_reglement != null) ...[
                    const SizedBox(width: 8),
                    StatusBadge(label: _reglement!, color: _couleurReglement),
                  ],
                ],
              ),
              if (row.purchaseCount > 1) ...[
                const SizedBox(height: 3),
                Text(
                  '${row.purchaseCount} réceptions au total',
                  style: const TextStyle(fontSize: 11.5, color: AppTheme.textMuted),
                ),
              ],
            ] else
              const Text(
                'Jamais reçu par un bon : ce prix vient de la fiche article.',
                style: TextStyle(fontSize: 12, color: AppTheme.textMuted),
              ),

            const SizedBox(height: 12),
            const Divider(height: 1, color: AppTheme.border),
            const SizedBox(height: 12),

            Row(
              children: [
                Expanded(
                  child: _Tarif(
                    libelle: 'Détail',
                    montant: row.detailPrice,
                    marge: row.marginDetail,
                    couleur: AppTheme.navy,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _Tarif(
                    libelle: 'Demi-gros',
                    montant: row.semiGrosPrice,
                    marge: row.marginSemiGros,
                    couleur: AppTheme.sky,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _Tarif(
                    libelle: 'Gros',
                    montant: row.grosPrice,
                    marge: row.marginGros,
                    couleur: AppTheme.success,
                  ),
                ),
              ],
            ),

            if (achat != null && row.grosPrice != null && row.grosPrice! < achat) ...[
              const SizedBox(height: 10),
              const Text(
                "Un tarif passe sous le prix d'achat : la vente se ferait à perte.",
                style: TextStyle(fontSize: 11.5, color: AppTheme.danger),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

/// Un tarif et sa marge sur le prix d'achat.
class _Tarif extends StatelessWidget {
  const _Tarif({
    required this.libelle,
    required this.montant,
    required this.marge,
    required this.couleur,
  });

  final String libelle;
  final double? montant;
  final double? marge;
  final Color couleur;

  @override
  Widget build(BuildContext context) {
    // Une marge négative se lit en rouge : c'est une vente à perte, pas une
    // marge faible.
    final perte = marge != null && marge! < 0;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10),
      decoration: BoxDecoration(
        color: couleur.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: couleur.withValues(alpha: 0.18)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            libelle,
            style: const TextStyle(fontSize: 10.5, color: AppTheme.textMuted),
          ),
          const SizedBox(height: 3),
          Text(
            montant != null ? formatMoney(montant!) : '—',
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: AppTheme.amountStyle(fontSize: 14),
          ),
          const SizedBox(height: 2),
          Text(
            marge != null ? '${marge! >= 0 ? '+' : ''}${marge!.toStringAsFixed(0)} %' : '—',
            maxLines: 1,
            style: TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w600,
              color: perte ? AppTheme.danger : couleur,
            ),
          ),
        ],
      ),
    );
  }
}
