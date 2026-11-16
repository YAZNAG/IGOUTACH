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

/// Une vente qui contient l'article, et ce qu'il reste à retourner dessus.
class _SaleOption {
  _SaleOption({
    required this.saleLineId,
    required this.reference,
    required this.date,
    required this.customer,
    required this.quantity,
    required this.unitPrice,
    required this.returnable,
  });

  factory _SaleOption.fromJson(Map<String, dynamic> j) => _SaleOption(
        saleLineId: (j['sale_line_id'] as num).toInt(),
        reference: (j['reference'] as String?) ?? '—',
        date: (j['date'] as String?) ?? '',
        customer: (j['customer'] as String?) ?? 'Client de passage',
        quantity: (j['quantity'] as num?)?.toInt() ?? 0,
        unitPrice: (j['unit_price'] as num?)?.toDouble() ?? 0,
        returnable: (j['returnable'] as num?)?.toInt() ?? 0,
      );

  final int saleLineId;
  final String reference;
  final String date;
  final String customer;
  final int quantity;
  final double unitPrice;
  final int returnable;

  String get label =>
      '$reference · ${formatDateTexte(date)} · $customer · '
      '$quantity × ${formatMoney(unitPrice)}'
      '${returnable == 0 ? ' · tout retourné' : returnable < quantity ? ' · reste $returnable' : ''}';
}

/// Ligne de retour en cours de saisie.
class _ReturnLine {
  _ReturnLine({required this.product});

  final Product product;
  int quantity = 1;

  /// `resellable` (remis en stock) ou `defective` (tracé puis sorti en SAV).
  String condition = 'resellable';

  /// Les ventes confirmées qui contiennent l'article (null tant que ça charge).
  List<_SaleOption>? sales;

  /// La vente d'où revient l'article : elle fixe le prix remboursé et le
  /// maximum retournable. Null = retour sans vente rattachée.
  _SaleOption? sale;

  bool get depasse => sale != null && quantity > sale!.returnable;
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
    final line = _ReturnLine(product: product);
    setState(() => _lines.add(line));
    _loadSales(line);
  }

  /// Les ventes qui contiennent l'article, la plus récente d'abord : la
  /// première encore retournable est proposée d'office.
  Future<void> _loadSales(_ReturnLine line) async {
    line
      ..sales = null
      ..sale = null;
    try {
      final r = await _api.dio.get<Map<String, dynamic>>(
        '/customer-returns/sales',
        queryParameters: {
          'product_id': line.product.id,
          if (_scope?.selectedId != null) 'warehouse_id': _scope!.selectedId,
        },
      );
      final rows = (r.data?['data'] as List<dynamic>? ?? const [])
          .map((e) => _SaleOption.fromJson(e as Map<String, dynamic>))
          .toList();
      if (!mounted) return;
      setState(() {
        line.sales = rows;
        line.sale = rows.where((v) => v.returnable > 0).firstOrNull;
        if (line.sale != null && line.quantity > line.sale!.returnable) {
          line.quantity = line.sale!.returnable;
        }
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => line.sales = const []);
    }
  }

  /// Le lieu change : les ventes proposées aussi.
  void _reloadAllSales() {
    for (final line in _lines) {
      _loadSales(line);
    }
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

  /// Interception du retour arrière : un retour en cours de saisie ne doit
  /// pas disparaître sur un appui malencontreux.
  Future<void> _handlePop(bool didPop) async {
    if (didPop) return;
    final navigator = Navigator.of(context);
    if (_lines.isEmpty || await confirmDiscard(context)) {
      navigator.pop();
    }
  }

  Future<void> _submit() async {
    final messenger = ScaffoldMessenger.of(context);
    final warehouseId = _scope?.selectedId;

    if (warehouseId == null) {
      showErrorSnack(messenger, 'Sélectionnez un lieu.');
      return;
    }
    if (_lines.isEmpty) {
      showErrorSnack(messenger, 'Ajoutez au moins un article.');
      return;
    }
    if (_lines.any((l) => l.depasse)) {
      showErrorSnack(
        messenger,
        'Une quantité dépasse ce que la vente contenait.',
      );
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
                    if (l.sale != null) 'sale_line_id': l.sale!.saleLineId,
                  })
              .toList(),
        },
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      showErrorSnack(
        messenger,
        'Aucune ligne enregistrée — ${friendlyError(e)}',
      );
      return;
    }

    if (!mounted) return;
    setState(() {
      _saving = false;
      // La saisie est enregistrée : plus rien à confirmer en quittant.
      _lines.clear();
    });

    showSuccessSnack(
      messenger,
      'Retour enregistré : $count article${count > 1 ? 's' : ''}.',
    );
    Navigator.of(context).pop(true);
  }

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('stock.entry')) {
      return const NotAllowedView();
    }

    final scope = _scope;
    final totalQuantity = _lines.fold<int>(0, (sum, l) => sum + l.quantity);
    final montantRendu = _lines.fold<double>(
      0,
      (sum, l) => sum + (l.sale == null ? 0 : l.quantity * l.sale!.unitPrice),
    );

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) => _handlePop(didPop),
      child: Scaffold(
        appBar: AppBar(title: const Text('Retour client')),
        body: !_scopeReady
            ? const FormSkeleton(fieldCount: 3)
            : ListView(
                    padding: const EdgeInsets.only(top: 8, bottom: 16),
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
                              onChanged: (value) {
                                setState(
                                  () => _scope = scope.copyWith(
                                    selectedId: value,
                                  ),
                                );
                                _reloadAllSales();
                              },
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
                          padding: EdgeInsets.only(top: 24, bottom: 8),
                          child: EmptyView(
                            icon: Icons.assignment_return_outlined,
                            title: 'Aucun article',
                            message: 'Recherchez un article ci-dessus '
                                'pour l\'ajouter au retour.',
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
                            textCapitalization: TextCapitalization.sentences,
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
        bottomNavigationBar: !_scopeReady
            ? null
            : BottomActionBar(
                label: 'Enregistrer le retour',
                loading: _saving,
                summaryLabel: '${_lines.length} article'
                    '${_lines.length > 1 ? 's' : ''}',
                summaryValue: montantRendu > 0
                    ? '${formatQuantity(totalQuantity)} u. · ${formatMoney(montantRendu)}'
                    : '${formatQuantity(totalQuantity)} unité'
                        '${totalQuantity > 1 ? 's' : ''}',
                onPressed: _submit,
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
        padding: const EdgeInsets.fromLTRB(16, 12, 10, 14),
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
                        line.product.name,
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
                        line.product.sku,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: AppTheme.codeStyle,
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
            const SizedBox(height: 10),
            // La vente d'origine : elle fixe le prix remboursé, le maximum
            // retournable et ce que le bénéfice doit retirer.
            if (line.sales == null)
              const Padding(
                padding: EdgeInsets.only(bottom: 10),
                child: Text(
                  'Recherche des ventes…',
                  style: TextStyle(fontSize: 13, color: AppTheme.textMuted),
                ),
              )
            else if (line.sales!.isEmpty)
              const Padding(
                padding: EdgeInsets.only(bottom: 10),
                child: Text(
                  'Aucune vente confirmée ne contient cet article : '
                  'retour sans vente rattachée.',
                  style: TextStyle(fontSize: 13, height: 1.3, color: AppTheme.warning),
                ),
              )
            else
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: DropdownButtonFormField<int>(
                  initialValue: line.sale?.saleLineId ?? 0,
                  isExpanded: true,
                  decoration: const InputDecoration(
                    labelText: 'Vente d\'origine',
                    prefixIcon: Icon(Icons.receipt_long_outlined),
                  ),
                  items: [
                    const DropdownMenuItem(
                      value: 0,
                      child: Text('— Sans vente rattachée —'),
                    ),
                    ...line.sales!.map(
                      (v) => DropdownMenuItem(
                        value: v.saleLineId,
                        enabled: v.returnable > 0,
                        child: Text(
                          v.label,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: 13,
                            color: v.returnable > 0 ? null : AppTheme.textMuted,
                          ),
                        ),
                      ),
                    ),
                  ],
                  onChanged: (value) {
                    line.sale = line.sales!
                        .where((v) => v.saleLineId == value)
                        .firstOrNull;
                    if (line.sale != null && line.quantity > line.sale!.returnable) {
                      line.quantity = line.sale!.returnable;
                    }
                    onChanged();
                  },
                ),
              ),
            Row(
              children: [
                QuantityStepper(
                  key: ValueKey('q-${line.sale?.saleLineId ?? 0}-${line.quantity}'),
                  quantity: line.quantity,
                  onChanged: (value) {
                    final max = line.sale?.returnable;
                    line.quantity = max != null && value > max ? max : value;
                    onChanged();
                  },
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    line.sale == null
                        ? 'unité(s) rendue(s)'
                        : 'max ${line.sale!.returnable} · '
                            '${formatMoney(line.quantity * line.sale!.unitPrice)}',
                    style: TextStyle(
                      fontSize: 14,
                      color: line.depasse ? AppTheme.danger : AppTheme.textMuted,
                    ),
                  ),
                ),
              ],
            ),
            if (line.sale != null)
              Padding(
                padding: const EdgeInsets.only(top: 6),
                child: Text(
                  'Vendu à ${formatMoney(line.sale!.unitPrice)} l\'unité à '
                  '${line.sale!.customer}.',
                  style: const TextStyle(
                    fontSize: 13,
                    height: 1.3,
                    color: AppTheme.textMuted,
                  ),
                ),
              ),
            const SizedBox(height: 12),
            // Sur sa propre ligne : deux libellés lisibles tiennent sans
            // être rognés, même sur un écran de 360 dp.
            SizedBox(
              width: double.infinity,
              child: SegmentedButton<String>(
                segments: const [
                  ButtonSegment(
                    value: 'resellable',
                    icon: Icon(Icons.inventory_2_outlined, size: 18),
                    label: Text('Revendable'),
                  ),
                  ButtonSegment(
                    value: 'defective',
                    icon: Icon(Icons.build_outlined, size: 18),
                    label: Text('Défectueux'),
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
            if (line.condition == 'defective')
              const Padding(
                padding: EdgeInsets.only(top: 10),
                child: Text(
                  'Article défectueux : tracé en retour puis sorti en SAV '
                  '(stock inchangé).',
                  style: TextStyle(
                    fontSize: 13,
                    height: 1.3,
                    color: AppTheme.textMuted,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
