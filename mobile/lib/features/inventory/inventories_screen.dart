import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/inventory.dart';
import '../shared/warehouse_scope.dart';
import 'inventory_count_screen.dart';

/// Libellé et couleur d'un statut d'inventaire.
(String, Color) inventoryStatusBadge(String status) => switch (status) {
      'approved' => ('Validé', AppTheme.success),
      'cancelled' => ('Annulé', AppTheme.danger),
      _ => ('Brouillon', AppTheme.warning),
    };

/// Inventaires du lieu de l'utilisateur (GET /inventories?warehouse_id=).
class InventoriesScreen extends StatefulWidget {
  const InventoriesScreen({super.key});

  @override
  State<InventoriesScreen> createState() => _InventoriesScreenState();
}

class _InventoriesScreenState extends State<InventoriesScreen> {
  final _api = ApiClient.instance;
  final _scrollController = ScrollController();

  final List<Inventory> _inventories = [];
  int _page = 0;
  int _lastPage = 1;
  bool _loading = false;
  bool _firstLoadDone = false;
  String? _error;
  bool _offline = false;

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
    _scrollController.dispose();
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

  Future<void> _load({bool reset = false}) async {
    if (_loading) return;
    setState(() {
      _loading = true;
      if (reset) {
        _error = null;
        _page = 0;
        _lastPage = 1;
        _inventories.clear();
        _firstLoadDone = false;
      }
    });

    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/inventories',
        queryParameters: {
          'page': _page + 1,
          'warehouse_id': ?_scope?.selectedId,
        },
      );
      final body = res.data!;
      final data = body['data'] as List<dynamic>? ?? [];
      final meta = body['meta'] as Map<String, dynamic>? ?? {};
      if (!mounted) return;
      setState(() {
        _page = (meta['current_page'] as num?)?.toInt() ?? _page + 1;
        _lastPage = (meta['last_page'] as num?)?.toInt() ?? _page;
        _inventories.addAll(
          data.map((e) => Inventory.fromJson(e as Map<String, dynamic>)),
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

  Future<void> _create() async {
    final warehouseId = _scope?.selectedId;
    if (warehouseId == null) {
      showErrorSnack(
        ScaffoldMessenger.of(context),
        'Aucun lieu sélectionné pour l\'inventaire.',
      );
      return;
    }

    final draft = await showDialog<({DateTime date, String note})>(
      context: context,
      builder: (_) => const _NewInventoryDialog(),
    );
    if (draft == null || !mounted) return;

    final messenger = ScaffoldMessenger.of(context);
    try {
      final res = await _api.dio.post<Map<String, dynamic>>(
        '/inventories',
        data: {
          'warehouse_id': warehouseId,
          'counted_at': apiDate(draft.date),
          if (draft.note.isNotEmpty) 'note': draft.note,
        },
      );
      final inventory =
          Inventory.fromJson(res.data!['data'] as Map<String, dynamic>);
      if (!mounted) return;
      await _open(inventory);
    } catch (e) {
      showErrorSnack(messenger, friendlyError(e));
    }
  }

  Future<void> _open(Inventory inventory) async {
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => InventoryCountScreen(
          inventoryId: inventory.id,
          reference: inventory.reference,
          warehouseId: inventory.warehouseId ?? _scope?.selectedId,
        ),
      ),
    );
    if (mounted) _load(reset: true);
  }

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('inventory.create')) {
      return const NotAllowedView();
    }

    final scope = _scope;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Inventaire'),
        bottom: scope?.selected == null
            ? null
            : WarehouseAppBarLabel(warehouse: scope!.selected!),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _create,
        icon: const Icon(Icons.add),
        label: const Text('Nouveau comptage'),
      ),
      body: !_scopeReady
          ? const ListSkeleton(itemCount: 5)
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
                Expanded(child: _buildBody()),
              ],
            ),
    );
  }

  Widget _buildBody() {
    if (!_firstLoadDone) return const ListSkeleton(itemCount: 5);
    if (_error != null && _inventories.isEmpty) {
      return ErrorView(
        message: _error!,
        offline: _offline,
        onRetry: () => _load(reset: true),
      );
    }
    if (_inventories.isEmpty) {
      return EmptyView(
        icon: Icons.fact_check_outlined,
        title: 'Aucun inventaire',
        message: 'Créez un comptage pour vérifier le stock réel '
            'et le régulariser.',
        actionLabel: 'Nouveau comptage',
        onAction: _create,
      );
    }

    return RefreshIndicator(
      onRefresh: () => _load(reset: true),
      child: ListView.builder(
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(top: 8, bottom: 96),
        itemCount: _inventories.length + (_hasMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index >= _inventories.length) return const SkeletonCard();
          final inventory = _inventories[index];
          final (label, color) = inventoryStatusBadge(inventory.status);
          final counted = inventory.linesCount ?? 0;

          return Card(
            clipBehavior: Clip.antiAlias,
            child: InkWell(
              onTap: () => _open(inventory),
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
                            inventory.reference,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: AppTheme.codeStyle.copyWith(fontSize: 16),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            [
                              if (inventory.countedAt != null)
                                inventory.countedAt!,
                              if (inventory.warehouseLabel != null)
                                inventory.warehouseLabel!,
                              '$counted article${counted > 1 ? 's' : ''} '
                                  'compté${counted > 1 ? 's' : ''}',
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
                    StatusBadge(label: label, color: color),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

/// Dialogue de création : date de comptage + note.
class _NewInventoryDialog extends StatefulWidget {
  const _NewInventoryDialog();

  @override
  State<_NewInventoryDialog> createState() => _NewInventoryDialogState();
}

class _NewInventoryDialogState extends State<_NewInventoryDialog> {
  final _note = TextEditingController();
  DateTime _date = DateTime.now();

  @override
  void dispose() {
    _note.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 1)),
      helpText: 'Date du comptage',
    );
    if (picked != null) setState(() => _date = picked);
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Nouveau comptage'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          InkWell(
            onTap: _pickDate,
            borderRadius: BorderRadius.circular(12),
            child: InputDecorator(
              decoration: const InputDecoration(
                labelText: 'Date de comptage',
                prefixIcon: Icon(Icons.event_outlined),
              ),
              child: Text(formatDate(_date)),
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _note,
            maxLength: 255,
            decoration: const InputDecoration(
              labelText: 'Note (facultatif)',
              prefixIcon: Icon(Icons.notes_outlined),
              counterText: '',
            ),
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('Annuler'),
        ),
        FilledButton(
          style: FilledButton.styleFrom(
            minimumSize: const Size(120, AppTheme.minTapTarget),
          ),
          onPressed: () => Navigator.of(context).pop(
            (date: _date, note: _note.text.trim()),
          ),
          child: const Text('Créer'),
        ),
      ],
    );
  }
}
