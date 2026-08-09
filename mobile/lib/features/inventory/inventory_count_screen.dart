import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/inventory.dart';
import '../../models/stock_item.dart';
import 'inventories_screen.dart' show inventoryStatusBadge;

/// Ligne de la feuille de comptage : un article du lieu.
class _CountRow {
  _CountRow({
    required this.productId,
    required this.sku,
    required this.name,
    required this.systemQuantity,
  });

  final int productId;
  final String sku;
  final String name;

  /// Stock théorique du lieu, tel que l'application le connaît.
  final int systemQuantity;

  /// Quantité comptée, `null` tant que l'article n'a pas été compté.
  int? counted;
  String reason = '';

  /// Le comptage existe-t-il déjà côté serveur ?
  bool savedOnServer = false;

  bool get isCounted => counted != null;

  int get difference => (counted ?? systemQuantity) - systemQuantity;

  bool get hasGap => isCounted && difference != 0;
}

/// Écran de comptage d'un inventaire.
///
/// Comptage PARTIEL assumé : seuls les articles saisis sont envoyés au
/// serveur (`PUT /inventories/{id}/lines`) ; les autres restent « non
/// comptés » et ne seront pas régularisés à la validation.
class InventoryCountScreen extends StatefulWidget {
  const InventoryCountScreen({
    super.key,
    required this.inventoryId,
    required this.reference,
    required this.warehouseId,
  });

  final int inventoryId;
  final String reference;
  final int? warehouseId;

  @override
  State<InventoryCountScreen> createState() => _InventoryCountScreenState();
}

class _InventoryCountScreenState extends State<InventoryCountScreen> {
  final _api = ApiClient.instance;
  final _searchController = TextEditingController();
  Timer? _debounce;

  /// Contrôleurs créés à la demande (la liste peut compter 1 000 articles).
  final Map<int, TextEditingController> _countControllers = {};
  final Map<int, TextEditingController> _reasonControllers = {};

  List<_CountRow> _rows = [];
  Inventory? _inventory;

  bool _loading = true;
  String? _error;
  bool _offline = false;
  bool _saving = false;
  String _query = '';
  bool _onlyRemaining = false;

  /// Des quantités saisies n'ont pas encore été envoyées au serveur.
  bool get _hasUnsavedCounts =>
      _rows.any((r) => r.isCounted && !r.savedOnServer);

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchController.dispose();
    for (final controller in _countControllers.values) {
      controller.dispose();
    }
    for (final controller in _reasonControllers.values) {
      controller.dispose();
    }
    super.dispose();
  }

  // ── Chargement ──────────────────────────────────────────────────────────

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      // Le stock du lieu fournit la liste complète des articles + théorique.
      final stockRes = await _api.dio.get<Map<String, dynamic>>(
        '/stock',
        queryParameters: {
          'per_page': 1000,
          'warehouse_id': ?widget.warehouseId,
        },
      );
      final stockData = stockRes.data!['data'] as List<dynamic>? ?? [];
      final rows = stockData
          .map((e) => StockItem.fromJson(e as Map<String, dynamic>))
          .map(
            (item) => _CountRow(
              productId: item.productId,
              sku: item.sku,
              name: item.name,
              systemQuantity: item.quantity,
            ),
          )
          .toList();

      // Comptages déjà saisis (reprise d'un comptage étalé sur plusieurs jours).
      final invRes = await _api.dio.get<Map<String, dynamic>>(
        '/inventories/${widget.inventoryId}',
      );
      final inventory =
          Inventory.fromJson(invRes.data!['data'] as Map<String, dynamic>);

      _applyLines(rows, inventory.lines ?? const []);

      if (!mounted) return;
      setState(() {
        _rows = rows;
        _inventory = inventory;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = friendlyError(e);
        _offline = isNetworkError(e);
        _loading = false;
      });
    }
  }

  /// Interception du retour arrière : un comptage non enregistré serait
  /// perdu, ce qui peut représenter une heure de travail en dépôt.
  Future<void> _handlePop(bool didPop) async {
    if (didPop) return;
    final navigator = Navigator.of(context);
    if (!_hasUnsavedCounts ||
        await confirmAction(
          context,
          icon: Icons.report_problem_outlined,
          title: 'Quitter sans enregistrer ?',
          message: 'Des quantités comptées n\'ont pas encore été '
              'enregistrées. Elles seront perdues.',
          confirmLabel: 'Quitter',
          cancelLabel: 'Rester',
          confirmColor: AppTheme.danger,
        )) {
      navigator.pop();
    }
  }

  void _applyLines(List<_CountRow> rows, List<InventoryLine> lines) {
    final byProduct = {for (final row in rows) row.productId: row};
    for (final line in lines) {
      final row = byProduct[line.productId];
      if (row == null) continue;
      row.counted = line.countedQuantity;
      row.reason = line.reason ?? '';
      row.savedOnServer = true;
      _countControllers[row.productId]?.text = '${line.countedQuantity}';
      _reasonControllers[row.productId]?.text = row.reason;
    }
  }

  // ── Saisie ──────────────────────────────────────────────────────────────

  TextEditingController _countController(_CountRow row) =>
      _countControllers.putIfAbsent(
        row.productId,
        () => TextEditingController(
          text: '${row.counted ?? row.systemQuantity}',
        ),
      );

  TextEditingController _reasonController(_CountRow row) =>
      _reasonControllers.putIfAbsent(
        row.productId,
        () => TextEditingController(text: row.reason),
      );

  void _onCountChanged(_CountRow row, String value) {
    final trimmed = value.trim();
    setState(() {
      // Champ vidé : retour au théorique. Un « 0 » saisi reste un zéro et
      // sera bien appliqué à la validation.
      row.counted = trimmed.isEmpty
          ? row.systemQuantity
          : (int.tryParse(trimmed) ?? row.systemQuantity);
    });
  }

  /// Coche/décoche la ligne : c'est ce qui décide si elle sera comptée.
  ///
  /// À l'ouverture on part du théorique — le compteur ne corrige que ce qui
  /// diffère. Décocher retire la ligne du comptage, y compris côté serveur
  /// si elle y était déjà : l'article garde alors son stock.
  Future<void> _toggleCount(_CountRow row, bool coche) async {
    if (coche) {
      setState(() {
        row.counted = row.systemQuantity;
        _countControllers[row.productId]?.text = '${row.systemQuantity}';
      });
      return;
    }

    await _removeCount(row);
  }

  void _onSearchChanged(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 250), () {
      setState(() => _query = value.trim().toLowerCase());
    });
  }

  List<_CountRow> get _visibleRows => _rows.where((row) {
        if (_onlyRemaining && row.isCounted) return false;
        if (_query.isEmpty) return true;
        return row.name.toLowerCase().contains(_query) ||
            row.sku.toLowerCase().contains(_query);
      }).toList();

  int get _countedCount => _rows.where((r) => r.isCounted).length;

  bool get _isDraft => _inventory?.isDraft ?? false;

  // ── Enregistrement ──────────────────────────────────────────────────────

  Future<void> _save() async {
    final entered = _rows.where((r) => r.isCounted).toList();
    final messenger = ScaffoldMessenger.of(context);

    if (entered.isEmpty) {
      showErrorSnack(messenger, 'Saisissez au moins une quantité comptée.');
      return;
    }

    // Le serveur refuse (422) toute ligne en écart sans motif : on l'anticipe.
    final missing =
        entered.where((r) => r.hasGap && r.reason.trim().isEmpty).toList();
    if (missing.isNotEmpty) {
      showErrorSnack(
        messenger,
        'Motif d\'écart obligatoire pour ${missing.length} article'
        '${missing.length > 1 ? 's' : ''} : ${missing.first.name}.',
      );
      return;
    }

    setState(() => _saving = true);
    try {
      final res = await _api.dio.put<Map<String, dynamic>>(
        '/inventories/${widget.inventoryId}/lines',
        data: {
          'lines': entered
              .map(
                (r) => {
                  'product_id': r.productId,
                  'counted_quantity': r.counted,
                  if (r.reason.trim().isNotEmpty) 'reason': r.reason.trim(),
                },
              )
              .toList(),
        },
      );
      final inventory =
          Inventory.fromJson(res.data!['data'] as Map<String, dynamic>);
      if (!mounted) return;
      setState(() {
        _inventory = inventory;
        for (final row in entered) {
          row.savedOnServer = true;
        }
        _saving = false;
      });
      showSuccessSnack(
        messenger,
        '${entered.length} comptage${entered.length > 1 ? 's' : ''} '
        'enregistré${entered.length > 1 ? 's' : ''}.',
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      showErrorSnack(messenger, friendlyError(e));
    }
  }

  /// Retire le comptage d'un article : côté serveur s'il y est déjà,
  /// sinon simplement dans la saisie en cours.
  Future<void> _removeCount(_CountRow row) async {
    final messenger = ScaffoldMessenger.of(context);

    if (row.savedOnServer) {
      try {
        await _api.dio.delete<Map<String, dynamic>>(
          '/inventories/${widget.inventoryId}/lines/${row.productId}',
        );
      } catch (e) {
        showErrorSnack(messenger, friendlyError(e));
        return;
      }
    }

    if (!mounted) return;
    setState(() {
      row.counted = null;
      row.reason = '';
      row.savedOnServer = false;
      // Le champ retrouve le théorique : il reste lisible, simplement verrouillé.
      _countControllers[row.productId]?.text = '${row.systemQuantity}';
      _reasonControllers[row.productId]?.clear();
    });
  }

  Future<void> _approve() async {
    final remaining = _rows.length - _countedCount;
    final confirmed = await confirmAction(
      context,
      icon: Icons.verified_outlined,
      title: 'Valider l\'inventaire',
      message: 'La validation régularise le stock des articles comptés.\n\n'
          '$_countedCount article${_countedCount > 1 ? 's' : ''} compté'
          '${_countedCount > 1 ? 's' : ''} · '
          '$remaining non compté${remaining > 1 ? 's' : ''}.\n\n'
          'Les articles non comptés ne seront pas modifiés.',
      confirmLabel: 'Valider',
      confirmColor: AppTheme.success,
    );
    if (!confirmed || !mounted) return;

    final messenger = ScaffoldMessenger.of(context);
    setState(() => _saving = true);
    try {
      await _api.dio.post<Map<String, dynamic>>(
        '/inventories/${widget.inventoryId}/approve',
      );
      if (!mounted) return;
      setState(() => _saving = false);
      showSuccessSnack(messenger, 'Inventaire validé, stock régularisé.');
      Navigator.of(context).pop();
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      showErrorSnack(messenger, friendlyError(e));
    }
  }

  // ── UI ──────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.can('inventory.create')) {
      return const NotAllowedView();
    }

    final canApprove = auth.can('inventory.approve');

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) => _handlePop(didPop),
      child: Scaffold(
        appBar: AppBar(
          title: Text(widget.reference),
          actions: [
            if (canApprove && _isDraft && !_loading)
              IconButton(
                icon: const Icon(Icons.verified_outlined),
                tooltip: 'Valider l\'inventaire',
                onPressed: _saving ? null : _approve,
              ),
          ],
        ),
        body: _loading
            ? const ListSkeleton(itemCount: 8)
            : _error != null
                ? ErrorView(
                    message: _error!,
                    offline: _offline,
                    onRetry: _load,
                  )
                : Column(
                    children: [
                      _buildHeader(),
                      Expanded(child: _buildList()),
                    ],
                  ),
        bottomNavigationBar: _isDraft && !_loading && _error == null
            ? BottomActionBar(
                label: 'Enregistrer le comptage',
                icon: Icons.save_outlined,
                loading: _saving,
                summaryLabel: 'Articles comptés',
                summaryValue: '$_countedCount / ${_rows.length}',
                onPressed: _save,
              )
            : null,
      ),
    );
  }

  Widget _buildHeader() {
    final total = _rows.length;
    final progress = total == 0 ? 0.0 : _countedCount / total;
    final (statusLabel, statusColor) =
        inventoryStatusBadge(_inventory?.status ?? 'draft');

    return Container(
      color: Colors.white,
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  '$_countedCount compté${_countedCount > 1 ? 's' : ''} '
                  '/ $total',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: AppTheme.amountStyle(fontSize: 16),
                ),
              ),
              const SizedBox(width: 8),
              StatusBadge(label: statusLabel, color: statusColor),
            ],
          ),
          const SizedBox(height: 8),
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 8,
              backgroundColor: AppTheme.navy.withValues(alpha: 0.08),
            ),
          ),
          const SizedBox(height: 4),
          AppSearchField(
            controller: _searchController,
            onChanged: _onSearchChanged,
            hintText: 'Rechercher un article…',
            padding: const EdgeInsets.only(top: 10),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              FilterChip(
                label: const Text('Restant à compter'),
                selected: _onlyRemaining,
                onSelected: (value) =>
                    setState(() => _onlyRemaining = value),
              ),
              const Spacer(),
              if (!_isDraft)
                const Text(
                  'Comptage clôturé',
                  style: TextStyle(fontSize: 14, color: AppTheme.textMuted),
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildList() {
    final rows = _visibleRows;
    if (rows.isEmpty) {
      return EmptyView(
        icon: Icons.inventory_2_outlined,
        title: _onlyRemaining ? 'Comptage terminé' : 'Aucun résultat',
        message: _onlyRemaining
            ? 'Tous les articles affichés sont comptés.'
            : 'Aucun article ne correspond à la recherche.',
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(top: 4, bottom: 24),
        itemCount: rows.length,
        itemBuilder: (context, index) => _buildRow(rows[index]),
      ),
    );
  }

  Widget _buildRow(_CountRow row) {
    return Card(
      key: ValueKey(row.productId),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 10, 12, 10),
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
                      const SizedBox(height: 6),
                      Wrap(
                        spacing: 8,
                        runSpacing: 6,
                        crossAxisAlignment: WrapCrossAlignment.center,
                        children: [
                          // Le théorique est toujours lisible : le compteur
                          // compare son comptage au stock connu, comme au web.
                          Text(
                            'Théorique : ${formatQuantity(row.systemQuantity)}',
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              color: AppTheme.navy,
                            ),
                          ),
                          if (row.hasGap)
                            StatusBadge(
                              label: 'Écart '
                                  '${row.difference > 0 ? '+' : ''}'
                                  '${row.difference}',
                              color: row.difference > 0
                                  ? AppTheme.warning
                                  : AppTheme.danger,
                            ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                // Cocher déverrouille la saisie. Décocher retire la ligne du
                // comptage : l'article garde son stock, il n'est pas mis à zéro.
                Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Checkbox(
                      value: row.isCounted,
                      onChanged: _isDraft ? (v) => _toggleCount(row, v ?? false) : null,
                    ),
                    const Text(
                      'Modifier',
                      style: TextStyle(fontSize: 10, color: AppTheme.textMuted),
                    ),
                  ],
                ),
                const SizedBox(width: 4),
                SizedBox(
                  width: 88,
                  child: TextField(
                    controller: _countController(row),
                    // Verrouillé tant que la case n'est pas cochée : le champ
                    // affiche le théorique sans qu'on puisse le modifier par
                    // inadvertance.
                    enabled: _isDraft && row.isCounted,
                    keyboardType: TextInputType.number,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      fontFeatures: AppTheme.tabularFigures,
                      color: row.isCounted ? AppTheme.ink : AppTheme.textFaint,
                    ),
                    inputFormatters: [
                      FilteringTextInputFormatter.digitsOnly,
                    ],
                    decoration: const InputDecoration(
                      labelText: 'Compté',
                      isDense: true,
                      contentPadding:
                          EdgeInsets.symmetric(horizontal: 8, vertical: 14),
                    ),
                    onChanged: (value) => _onCountChanged(row, value),
                  ),
                ),
              ],
            ),
            if (row.hasGap) ...[
              const SizedBox(height: 10),
              TextField(
                controller: _reasonController(row),
                enabled: _isDraft,
                maxLength: 191,
                textCapitalization: TextCapitalization.sentences,
                decoration: InputDecoration(
                  labelText: 'Motif de l\'écart *',
                  hintText: 'Ex. : casse, erreur de saisie',
                  prefixIcon: const Icon(Icons.report_problem_outlined),
                  isDense: true,
                  counterText: '',
                  errorText: row.reason.trim().isEmpty
                      ? 'Obligatoire pour enregistrer cet écart.'
                      : null,
                ),
                onChanged: (value) => setState(() => row.reason = value),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
