import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/product.dart';
import '../shared/product_picker.dart';
import '../shared/warehouse_scope.dart';

/// Ligne de retour en cours de saisie.
class _ReturnLine {
  _ReturnLine({required this.product});

  final Product product;
  int quantity = 1;

  /// `resellable` (remis en stock) ou `defective` (tracé puis sorti en SAV).
  String condition = 'resellable';
}

/// Retour client : remise en stock d'articles rendus par un client.
///
/// `POST /stock/return-multi` enregistre toutes les lignes dans une seule
/// transaction : en cas d'erreur, aucune ligne n'est appliquée. Un article
/// « défectueux » entre en stock puis en ressort aussitôt (sortie SAV).
class CustomerReturnScreen extends StatefulWidget {
  const CustomerReturnScreen({super.key});

  @override
  State<CustomerReturnScreen> createState() => _CustomerReturnScreenState();
}

class _CustomerReturnScreenState extends State<CustomerReturnScreen> {
  final _api = ApiClient.instance;
  final _note = TextEditingController();

  final List<_ReturnLine> _lines = [];

  WarehouseScope? _scope;
  bool _scopeReady = false;

  DateTime _date = DateTime.now();
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _init();
  }

  @override
  void dispose() {
    _note.dispose();
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
  }

  void _addProduct(Product product) {
    final existing =
        _lines.where((l) => l.product.id == product.id).firstOrNull;
    if (existing != null) {
      setState(() => existing.quantity++);
      return;
    }
    setState(() => _lines.add(_ReturnLine(product: product)));
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 1)),
      helpText: 'Date du retour',
    );
    if (picked != null) setState(() => _date = picked);
  }

  /// Note libre du retour (la date part désormais dans `occurred_at`).
  String _noteFor() => _note.text.trim();

  Future<void> _submit() async {
    final messenger = ScaffoldMessenger.of(context);
    final warehouseId = _scope?.selectedId;

    if (warehouseId == null) {
      messenger.showSnackBar(const SnackBar(
        content: Text('Sélectionnez un lieu.'),
        backgroundColor: AppTheme.danger,
      ));
      return;
    }
    if (_lines.isEmpty) {
      messenger.showSnackBar(const SnackBar(
        content: Text('Ajoutez au moins un article.'),
        backgroundColor: AppTheme.danger,
      ));
      return;
    }

    setState(() => _saving = true);

    final note = _noteFor();
    final count = _lines.length;

    try {
      // Toutes les lignes en une seule requête : le serveur les enregistre
      // dans une transaction, donc jamais de retour à moitié saisi.
      await _api.dio.post<Map<String, dynamic>>(
        '/stock/return-multi',
        data: {
          'warehouse_id': warehouseId,
          'occurred_at': apiDate(_date),
          if (note.isNotEmpty) 'note': note,
          'lines': _lines
              .map((l) => {
                    'product_id': l.product.id,
                    'quantity': l.quantity,
                    'condition': l.condition,
                  })
              .toList(),
        },
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      messenger.showSnackBar(SnackBar(
        content: Text('Aucune ligne enregistrée — ${friendlyError(e)}'),
        backgroundColor: AppTheme.danger,
      ));
      return;
    }

    if (!mounted) return;
    setState(() => _saving = false);

    messenger.showSnackBar(SnackBar(
      content: Text('Retour enregistré : $count article${count > 1 ? 's' : ''}.'),
      backgroundColor: AppTheme.success,
    ));
    Navigator.of(context).pop(true);
  }

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('stock.entry')) {
      return const NotAllowedView();
    }

    final scope = _scope;

    return Scaffold(
      appBar: AppBar(title: const Text('Retour client')),
      body: !_scopeReady
          ? const LoadingView()
          : Column(
              children: [
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.only(bottom: 16),
                    children: [
                      if (scope != null &&
                          scope.canChoose &&
                          scope.warehouses.isNotEmpty)
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 8,
                            ),
                            child: DropdownButtonFormField<int>(
                              initialValue: scope.selectedId,
                              isExpanded: true,
                              decoration: const InputDecoration(
                                labelText: 'Lieu',
                                prefixIcon: Icon(Icons.warehouse_outlined),
                              ),
                              items: scope.warehouses
                                  .map(
                                    (w) => DropdownMenuItem(
                                      value: w.id,
                                      child: Text(
                                        w.label,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                  )
                                  .toList(),
                              onChanged: (value) => setState(
                                () => _scope = scope.copyWith(
                                  selectedId: value,
                                ),
                              ),
                            ),
                          ),
                        )
                      else
                        InfoBanner(
                          icon: Icons.warehouse_outlined,
                          message:
                              'Lieu : ${scope?.selectedLabel ?? 'votre lieu'}',
                        ),
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 4,
                          ),
                          child: ListTile(
                            contentPadding: EdgeInsets.zero,
                            leading: const Icon(
                              Icons.event_outlined,
                              color: AppTheme.navy,
                            ),
                            title: const Text('Date du retour'),
                            subtitle: Text(formatDate(_date)),
                            trailing: const Icon(Icons.edit_calendar_outlined),
                            onTap: _pickDate,
                          ),
                        ),
                      ),
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: ProductPickerField(
                            warehouseId: scope?.selectedId,
                            onSelected: _addProduct,
                          ),
                        ),
                      ),
                      if (_lines.isEmpty)
                        const Padding(
                          padding: EdgeInsets.all(24),
                          child: EmptyView(
                            icon: Icons.assignment_return_outlined,
                            message: 'Aucun article. Recherchez un article '
                                'ci-dessus pour l\'ajouter au retour.',
                          ),
                        )
                      else
                        ..._lines.map(
                          (line) => _LineCard(
                            key: ValueKey(line.product.id),
                            line: line,
                            onChanged: () => setState(() {}),
                            onRemove: () =>
                                setState(() => _lines.remove(line)),
                          ),
                        ),
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: TextField(
                            controller: _note,
                            maxLength: 120,
                            decoration: const InputDecoration(
                              labelText: 'Motif / note (facultatif)',
                              prefixIcon: Icon(Icons.notes_outlined),
                              counterText: '',
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                _buildBottomBar(),
              ],
            ),
    );
  }

  Widget _buildBottomBar() {
    final totalQuantity = _lines.fold<int>(0, (sum, l) => sum + l.quantity);

    return Container(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: AppTheme.navy.withValues(alpha: 0.1),
            blurRadius: 8,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  '${_lines.length} article${_lines.length > 1 ? 's' : ''}',
                  style: const TextStyle(fontSize: 14),
                ),
                Text(
                  '${formatQuantity(totalQuantity)} unité'
                  '${totalQuantity > 1 ? 's' : ''}',
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.navy,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            FilledButton.icon(
              onPressed: _saving ? null : _submit,
              icon: _saving
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.check),
              label: const Text('Enregistrer le retour'),
            ),
          ],
        ),
      ),
    );
  }
}

class _LineCard extends StatelessWidget {
  const _LineCard({
    super.key,
    required this.line,
    required this.onChanged,
    required this.onRemove,
  });

  final _ReturnLine line;
  final VoidCallback onChanged;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 12, 8, 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        line.product.name,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                      Text(
                        line.product.sku,
                        style: const TextStyle(
                          fontFamily: 'monospace',
                          fontSize: 12,
                          color: AppTheme.navy,
                        ),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(
                    Icons.delete_outline,
                    color: AppTheme.danger,
                  ),
                  tooltip: 'Supprimer la ligne',
                  onPressed: onRemove,
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Container(
                  decoration: BoxDecoration(
                    border: Border.all(
                      color: AppTheme.navy.withValues(alpha: 0.2),
                    ),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Row(
                    children: [
                      IconButton(
                        icon: const Icon(Icons.remove, size: 18),
                        visualDensity: VisualDensity.compact,
                        onPressed: line.quantity > 1
                            ? () {
                                line.quantity--;
                                onChanged();
                              }
                            : null,
                      ),
                      Text(
                        '${line.quantity}',
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 15,
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.add, size: 18),
                        visualDensity: VisualDensity.compact,
                        onPressed: () {
                          line.quantity++;
                          onChanged();
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: SegmentedButton<String>(
                    style: const ButtonStyle(
                      visualDensity: VisualDensity.compact,
                    ),
                    segments: const [
                      ButtonSegment(
                        value: 'resellable',
                        label: Text('Revendable', style: TextStyle(fontSize: 11)),
                      ),
                      ButtonSegment(
                        value: 'defective',
                        label: Text('Défectueux', style: TextStyle(fontSize: 11)),
                      ),
                    ],
                    selected: {line.condition},
                    showSelectedIcon: false,
                    onSelectionChanged: (selection) {
                      line.condition = selection.first;
                      onChanged();
                    },
                  ),
                ),
              ],
            ),
            if (line.condition == 'defective')
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text(
                  'Article défectueux : tracé en retour puis sorti en SAV '
                  '(stock inchangé).',
                  style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
