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

  /// Stock théorique du lieu, masqué tant que rien n'est compté.
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
  bool _saving = false;
  String _query = '';
  bool _onlyRemaining = false;

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
        _loading = false;
      });
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
          text: row.counted == null ? '' : '${row.counted}',
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
      row.counted = trimmed.isEmpty ? null : int.tryParse(trimmed);
    });
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
      messenger.showSnackBar(const SnackBar(
        content: Text('Saisissez au moins une quantité comptée.'),
        backgroundColor: AppTheme.danger,
      ));
      return;
    }

    // Le serveur refuse (422) toute ligne en écart sans motif : on l'anticipe.
    final missing =
        entered.where((r) => r.hasGap && r.reason.trim().isEmpty).toList();
    if (missing.isNotEmpty) {
      messenger.showSnackBar(SnackBar(
        content: Text(
          'Motif d\'écart obligatoire pour ${missing.length} article'
          '${missing.length > 1 ? 's' : ''} : ${missing.first.name}.',
        ),
        backgroundColor: AppTheme.danger,
      ));
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
      messenger.showSnackBar(SnackBar(
        content: Text(
          '${entered.length} comptage${entered.length > 1 ? 's' : ''} '
          'enregistré${entered.length > 1 ? 's' : ''}.',
        ),
        backgroundColor: AppTheme.success,
      ));
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      messenger.showSnackBar(SnackBar(
        content: Text(friendlyError(e)),
        backgroundColor: AppTheme.danger,
      ));
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
        messenger.showSnackBar(SnackBar(
          content: Text(friendlyError(e)),
          backgroundColor: AppTheme.danger,
        ));
        return;
      }
    }

    if (!mounted) return;
    setState(() {
      row.counted = null;
      row.reason = '';
      row.savedOnServer = false;
      _countControllers[row.productId]?.clear();
      _reasonControllers[row.productId]?.clear();
    });
  }

  Future<void> _approve() async {
    final remaining = _rows.length - _countedCount;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Valider l\'inventaire'),
        content: Text(
          'La validation régularise le stock des articles comptés.\n\n'
          '$_countedCount article${_countedCount > 1 ? 's' : ''} compté'
          '${_countedCount > 1 ? 's' : ''} · '
          '$remaining non compté${remaining > 1 ? 's' : ''}.\n\n'
          'Les articles non comptés ne seront pas modifiés.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(false),
            child: const Text('Annuler'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(
              backgroundColor: AppTheme.success,
              minimumSize: const Size(0, 44),
            ),
            onPressed: () => Navigator.of(dialogContext).pop(true),
            child: const Text('Valider'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    final messenger = ScaffoldMessenger.of(context);
    setState(() => _saving = true);
    try {
      await _api.dio.post<Map<String, dynamic>>(
        '/inventories/${widget.inventoryId}/approve',
      );
      if (!mounted) return;
      setState(() => _saving = false);
      messenger.showSnackBar(const SnackBar(
        content: Text('Inventaire validé, stock régularisé.'),
        backgroundColor: AppTheme.success,
      ));
      Navigator.of(context).pop();
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      messenger.showSnackBar(SnackBar(
        content: Text(friendlyError(e)),
        backgroundColor: AppTheme.danger,
      ));
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

    return Scaffold(
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
      floatingActionButton: _isDraft && !_loading && _error == null
          ? FloatingActionButton.extended(
              onPressed: _saving ? null : _save,
              icon: _saving
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.save_outlined),
              label: const Text('Enregistrer'),
            )
          : null,
      body: _loading
          ? const LoadingView()
          : _error != null
              ? ErrorView(message: _error!, onRetry: _load)
              : Column(
                  children: [
                    _buildHeader(),
                    Expanded(child: _buildList()),
                  ],
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
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    color: AppTheme.navy,
                  ),
                ),
              ),
              StatusBadge(label: statusLabel, color: statusColor),
            ],
          ),
          const SizedBox(height: 6),
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 6,
              backgroundColor: AppTheme.navy.withValues(alpha: 0.08),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: _searchController,
            onChanged: _onSearchChanged,
            decoration: InputDecoration(
              hintText: 'Rechercher un article…',
              prefixIcon: const Icon(Icons.search),
              isDense: true,
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
          const SizedBox(height: 8),
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
                Text(
                  'Comptage clôturé',
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
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
        message: _onlyRemaining
            ? 'Tous les articles affichés sont comptés.'
            : 'Aucun article ne correspond à la recherche.',
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.only(top: 4, bottom: 96),
      itemCount: rows.length,
      itemBuilder: (context, index) => _buildRow(rows[index]),
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
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Text(
                            'Théorique : ',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey.shade600,
                            ),
                          ),
                          // Masqué tant que rien n'est saisi, pour éviter que
                          // le compteur recopie le stock théorique.
                          Text(
                            row.isCounted
                                ? formatQuantity(row.systemQuantity)
                                : '•••',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: row.isCounted
                                  ? AppTheme.navy
                                  : Colors.grey.shade500,
                            ),
                          ),
                          if (row.hasGap) ...[
                            const SizedBox(width: 8),
                            StatusBadge(
                              label:
                                  'Écart ${row.difference > 0 ? '+' : ''}${row.difference}',
                              color: row.difference > 0
                                  ? AppTheme.warning
                                  : AppTheme.danger,
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                SizedBox(
                  width: 84,
                  child: TextField(
                    controller: _countController(row),
                    enabled: _isDraft,
                    keyboardType: TextInputType.number,
                    textAlign: TextAlign.center,
                    inputFormatters: [
                      FilteringTextInputFormatter.digitsOnly,
                    ],
                    decoration: const InputDecoration(
                      labelText: 'Compté',
                      isDense: true,
                      contentPadding:
                          EdgeInsets.symmetric(horizontal: 8, vertical: 12),
                    ),
                    onChanged: (value) => _onCountChanged(row, value),
                  ),
                ),
                if (row.isCounted && _isDraft)
                  IconButton(
                    icon: const Icon(
                      Icons.backspace_outlined,
                      size: 18,
                      color: AppTheme.danger,
                    ),
                    tooltip: 'Retirer ce comptage',
                    onPressed: () => _removeCount(row),
                  ),
              ],
            ),
            if (row.hasGap) ...[
              const SizedBox(height: 8),
              TextField(
                controller: _reasonController(row),
                enabled: _isDraft,
                maxLength: 191,
                decoration: const InputDecoration(
                  labelText: 'Motif de l\'écart *',
                  prefixIcon: Icon(Icons.report_problem_outlined),
                  isDense: true,
                  counterText: '',
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
