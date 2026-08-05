import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/customer.dart';
import '../../models/product.dart';
import '../../models/warehouse.dart';
import '../shared/product_picker.dart';
import 'sales_screen.dart' show downloadSalePdf;

/// Ligne de vente en cours de saisie.
class _LineDraft {
  _LineDraft({required this.product});

  final Product product;
  int quantity = 1;
  double? unitPrice;
  String? priceTypeCode;
  bool loadingPrice = true;
  String? priceError;

  /// Jeton anti-course : seule la dernière requête de prix est retenue.
  int priceRequestId = 0;

  double? get lineTotal =>
      unitPrice == null ? null : unitPrice! * quantity;
}

/// Création d'une vente (facture) : client (ou passage), lieu, articles,
/// prix résolus par le serveur (paliers), confirmation et PDF.
class CreateSaleScreen extends StatefulWidget {
  const CreateSaleScreen({super.key});

  @override
  State<CreateSaleScreen> createState() => _CreateSaleScreenState();
}

class _CreateSaleScreenState extends State<CreateSaleScreen> {
  final _api = ApiClient.instance;

  bool _walkIn = false;
  Customer? _customer;

  List<Warehouse> _warehouses = [];
  int? _warehouseId;
  bool _loadingWarehouses = true;

  final List<_LineDraft> _lines = [];

  bool _creating = false;

  @override
  void initState() {
    super.initState();
    _loadWarehouses();
  }

  Future<void> _loadWarehouses() async {
    final user = context.read<AuthProvider>().user;
    try {
      final res = await _api.dio.get<Map<String, dynamic>>('/warehouses');
      final data = res.data!['data'] as List<dynamic>? ?? [];
      final warehouses = data
          .map((e) => Warehouse.fromJson(e as Map<String, dynamic>))
          .where((w) => w.isActive)
          .toList();
      if (!mounted) return;
      setState(() {
        _warehouses = warehouses;
        // Présélectionne le lieu de l'utilisateur s'il en a un.
        if (user?.warehouseId != null &&
            warehouses.any((w) => w.id == user!.warehouseId)) {
          _warehouseId = user!.warehouseId;
        } else if (warehouses.isNotEmpty) {
          _warehouseId = warehouses.first.id;
        }
        _loadingWarehouses = false;
      });
    } catch (_) {
      // Sans la permission warehouse.view : repli sur le lieu de l'utilisateur.
      if (!mounted) return;
      setState(() {
        if (user?.warehouseId != null) {
          _warehouses = [
            Warehouse(
              id: user!.warehouseId!,
              code: '',
              name: 'Mon lieu',
            ),
          ];
          _warehouseId = user.warehouseId;
        }
        _loadingWarehouses = false;
      });
    }
  }

  // ── Ajout d'articles (recherche mutualisée : ProductPickerField) ────────

  void _addProduct(Product product) {
    final existing =
        _lines.where((l) => l.product.id == product.id).firstOrNull;
    if (existing != null) {
      _changeQuantity(existing, existing.quantity + 1);
      return;
    }

    final line = _LineDraft(product: product);
    setState(() => _lines.add(line));
    _fetchPrice(line);
  }

  // ── Prix (résolus côté serveur, rechargés à chaque changement) ──────────

  Future<void> _fetchPrice(_LineDraft line) async {
    final requestId = ++line.priceRequestId;
    setState(() {
      line.loadingPrice = true;
      line.priceError = null;
    });
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/sales/price',
        queryParameters: {
          'product_id': line.product.id,
          'quantity': line.quantity,
          if (!_walkIn && _customer != null) 'customer_id': _customer!.id,
        },
      );
      if (!mounted || requestId != line.priceRequestId) return;
      final data = res.data!['data'] as Map<String, dynamic>;
      setState(() {
        line.unitPrice = (data['unit_price'] as num?)?.toDouble();
        line.priceTypeCode = data['price_type_code'] as String?;
        line.loadingPrice = false;
      });
    } catch (e) {
      if (!mounted || requestId != line.priceRequestId) return;
      setState(() {
        line.unitPrice = null;
        line.priceTypeCode = null;
        line.loadingPrice = false;
        line.priceError = friendlyError(e);
      });
    }
  }

  void _refreshAllPrices() {
    for (final line in _lines) {
      _fetchPrice(line);
    }
  }

  void _changeQuantity(_LineDraft line, int quantity) {
    if (quantity < 1) return;
    setState(() => line.quantity = quantity);
    _fetchPrice(line);
  }

  void _removeLine(_LineDraft line) {
    setState(() => _lines.remove(line));
  }

  double get _total => _lines.fold(0, (sum, l) => sum + (l.lineTotal ?? 0));

  // ── Sélection du client ─────────────────────────────────────────────────

  Future<void> _pickCustomer() async {
    final selected = await showModalBottomSheet<Customer>(
      context: context,
      isScrollControlled: true,
      builder: (_) => const _CustomerPickerSheet(),
    );
    if (selected != null) {
      setState(() => _customer = selected);
      _refreshAllPrices();
    }
  }

  // ── Création ────────────────────────────────────────────────────────────

  Future<void> _submit() async {
    final messenger = ScaffoldMessenger.of(context);

    if (_warehouseId == null) {
      messenger.showSnackBar(const SnackBar(
        content: Text('Sélectionnez un lieu.'),
        backgroundColor: AppTheme.danger,
      ));
      return;
    }
    if (!_walkIn && _customer == null) {
      messenger.showSnackBar(const SnackBar(
        content: Text(
          'Sélectionnez un client ou activez « Client de passage ».',
        ),
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

    setState(() => _creating = true);
    try {
      // Le prix unitaire n'est pas envoyé : le serveur applique les paliers.
      final res = await _api.dio.post<Map<String, dynamic>>(
        '/sales',
        data: {
          'type': 'invoice',
          'customer_id': _walkIn ? null : _customer!.id,
          'warehouse_id': _warehouseId,
          'lines': _lines
              .map((l) => {
                    'product_id': l.product.id,
                    'quantity': l.quantity,
                  })
              .toList(),
        },
      );
      final data = res.data!['data'] as Map<String, dynamic>;
      final saleId = data['id'] as int;
      final reference = data['reference'] as String? ?? 'vente';
      if (!mounted) return;
      await _afterCreated(saleId, reference);
    } catch (e) {
      if (!mounted) return;
      setState(() => _creating = false);
      messenger.showSnackBar(SnackBar(
        content: Text(friendlyError(e)),
        backgroundColor: AppTheme.danger,
      ));
    }
  }

  Future<void> _afterCreated(int saleId, String reference) async {
    final confirm = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Vente créée'),
        content: Text(
          'La vente $reference a été créée en brouillon.\n'
          'Confirmer maintenant ? (le stock sera déduit)',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(false),
            child: const Text('Plus tard'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(minimumSize: const Size(0, 44)),
            onPressed: () => Navigator.of(dialogContext).pop(true),
            child: const Text('Confirmer'),
          ),
        ],
      ),
    );

    if (!mounted) return;

    if (confirm == true) {
      try {
        await _api.dio.post('/sales/$saleId/confirm');
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text('Vente $reference confirmée.'),
          backgroundColor: AppTheme.success,
        ));
      } catch (e) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text('Confirmation impossible : ${friendlyError(e)}'),
          backgroundColor: AppTheme.danger,
        ));
      }
    }

    if (!mounted) return;
    final wantPdf = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Facture PDF'),
        content: const Text('Télécharger la facture PDF maintenant ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(false),
            child: const Text('Non'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(minimumSize: const Size(0, 44)),
            onPressed: () => Navigator.of(dialogContext).pop(true),
            child: const Text('Télécharger'),
          ),
        ],
      ),
    );

    if (!mounted) return;
    if (wantPdf == true) {
      await downloadSalePdf(context, saleId, reference);
    }

    if (!mounted) return;
    Navigator.of(context).pop(true);
  }

  // ── UI ──────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('sale.create')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Nouvelle vente')),
      body: _loadingWarehouses
          ? const LoadingView()
          : Column(
              children: [
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.only(bottom: 16),
                    children: [
                      _buildCustomerCard(),
                      _buildWarehouseCard(),
                      _buildProductSearchCard(),
                      if (_lines.isEmpty)
                        const Padding(
                          padding: EdgeInsets.all(24),
                          child: EmptyView(
                            icon: Icons.shopping_cart_outlined,
                            message:
                                'Aucun article. Recherchez un article '
                                'ci-dessus pour l\'ajouter.',
                          ),
                        )
                      else
                        ..._lines.map(
                          (line) => _LineCard(
                            key: ValueKey(line.product.id),
                            line: line,
                            onQuantityChanged: (q) =>
                                _changeQuantity(line, q),
                            onRemove: () => _removeLine(line),
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

  Widget _buildCustomerCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        child: Column(
          children: [
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Client de passage'),
              subtitle: const Text(
                'Vente comptoir sans fiche client',
                style: TextStyle(fontSize: 12),
              ),
              value: _walkIn,
              activeThumbColor: AppTheme.sky,
              onChanged: (value) {
                setState(() {
                  _walkIn = value;
                  if (value) _customer = null;
                });
                _refreshAllPrices();
              },
            ),
            if (!_walkIn)
              ListTile(
                contentPadding: EdgeInsets.zero,
                leading: const Icon(Icons.person_outline,
                    color: AppTheme.navy),
                title: Text(
                  _customer?.name ?? 'Sélectionner un client…',
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    color: _customer == null
                        ? Colors.grey.shade600
                        : AppTheme.navy,
                  ),
                ),
                subtitle: _customer == null
                    ? null
                    : Text(
                        '${_customer!.code} · '
                        'Encours : ${formatMoney(_customer!.balance)}',
                        style: const TextStyle(fontSize: 12),
                      ),
                trailing: const Icon(Icons.chevron_right),
                onTap: _pickCustomer,
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildWarehouseCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        child: _warehouses.isEmpty
            ? const Text(
                'Aucun lieu disponible. Contactez l\'administrateur.',
                style: TextStyle(color: AppTheme.danger),
              )
            : DropdownButtonFormField<int>(
                initialValue: _warehouseId,
                decoration: const InputDecoration(
                  labelText: 'Lieu',
                  prefixIcon: Icon(Icons.warehouse_outlined),
                ),
                items: _warehouses
                    .map((w) => DropdownMenuItem(
                          value: w.id,
                          child: Text(w.label),
                        ))
                    .toList(),
                onChanged: (value) => setState(() => _warehouseId = value),
              ),
      ),
    );
  }

  Widget _buildProductSearchCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: ProductPickerField(
          // La clé force la reconstruction du champ quand le lieu change,
          // afin que les stocks affichés portent bien sur le lieu choisi.
          key: ValueKey('product-picker-$_warehouseId'),
          warehouseId: _warehouseId,
          onSelected: _addProduct,
        ),
      ),
    );
  }

  Widget _buildBottomBar() {
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
                const Text('Total', style: TextStyle(fontSize: 16)),
                Text(
                  formatMoney(_total),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.navy,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            FilledButton.icon(
              onPressed: _creating ? null : _submit,
              icon: _creating
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.check),
              label: const Text('Créer la vente'),
            ),
          ],
        ),
      ),
    );
  }
}

/// Carte d'une ligne : quantité (stepper), prix serveur, total, suppression.
class _LineCard extends StatelessWidget {
  const _LineCard({
    super.key,
    required this.line,
    required this.onQuantityChanged,
    required this.onRemove,
  });

  final _LineDraft line;
  final ValueChanged<int> onQuantityChanged;
  final VoidCallback onRemove;

  String get _priceTypeLabel => switch (line.priceTypeCode) {
        'detail' => 'Détail',
        'semi_gros' => 'Demi-gros',
        'gros' => 'Gros',
        _ => '',
      };

  @override
  Widget build(BuildContext context) {
    return Dismissible(
      key: ValueKey('dismiss-${line.product.id}'),
      direction: DismissDirection.endToStart,
      onDismissed: (_) => onRemove(),
      background: Container(
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 24),
        margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: AppTheme.danger,
          borderRadius: BorderRadius.circular(16),
        ),
        child: const Icon(Icons.delete_outline, color: Colors.white),
      ),
      child: Card(
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
                          style:
                              const TextStyle(fontWeight: FontWeight.w600),
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
                  // Stepper de quantité
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
                              ? () => onQuantityChanged(line.quantity - 1)
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
                          onPressed: () =>
                              onQuantityChanged(line.quantity + 1),
                        ),
                      ],
                    ),
                  ),
                  const Spacer(),
                  if (line.loadingPrice)
                    const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  else if (line.priceError != null)
                    Flexible(
                      child: Text(
                        line.priceError!,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppTheme.danger,
                          fontSize: 12,
                        ),
                      ),
                    )
                  else
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          '${formatMoney(line.unitPrice)} × ${line.quantity}',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey.shade600,
                          ),
                        ),
                        Text(
                          formatMoney(line.lineTotal),
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            color: AppTheme.navy,
                          ),
                        ),
                        if (_priceTypeLabel.isNotEmpty)
                          StatusBadge(
                            label: _priceTypeLabel,
                            color: AppTheme.sky,
                          ),
                      ],
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Feuille de sélection d'un client avec recherche (GET /customers?q=).
class _CustomerPickerSheet extends StatefulWidget {
  const _CustomerPickerSheet();

  @override
  State<_CustomerPickerSheet> createState() => _CustomerPickerSheetState();
}

class _CustomerPickerSheetState extends State<_CustomerPickerSheet> {
  final _api = ApiClient.instance;
  final _searchController = TextEditingController();
  Timer? _debounce;

  List<Customer> _results = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _search('');
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchController.dispose();
    super.dispose();
  }

  void _onChanged(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 300), () {
      _search(value.trim());
    });
  }

  Future<void> _search(String query) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/customers',
        queryParameters: {
          'per_page': 20,
          if (query.isNotEmpty) 'q': query,
        },
      );
      final data = res.data!['data'] as List<dynamic>? ?? [];
      if (!mounted) return;
      setState(() {
        _results = data
            .map((e) => Customer.fromJson(e as Map<String, dynamic>))
            .toList();
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

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SizedBox(
        height: MediaQuery.of(context).size.height * 0.75,
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(12),
              child: TextField(
                controller: _searchController,
                autofocus: true,
                onChanged: _onChanged,
                decoration: const InputDecoration(
                  hintText: 'Rechercher un client…',
                  prefixIcon: Icon(Icons.search),
                ),
              ),
            ),
            Expanded(
              child: _loading
                  ? const LoadingView()
                  : _error != null
                      ? ErrorView(
                          message: _error!,
                          onRetry: () =>
                              _search(_searchController.text.trim()),
                        )
                      : _results.isEmpty
                          ? const EmptyView(
                              icon: Icons.people_outline,
                              message: 'Aucun client trouvé.',
                            )
                          : ListView.builder(
                              itemCount: _results.length,
                              itemBuilder: (context, index) {
                                final customer = _results[index];
                                return ListTile(
                                  enabled: !customer.isBlocked,
                                  title: Text(customer.name),
                                  subtitle: Text(
                                    '${customer.code}'
                                    '${(customer.city ?? '').isNotEmpty ? ' · ${customer.city}' : ''}',
                                    style: const TextStyle(fontSize: 12),
                                  ),
                                  trailing: customer.isBlocked
                                      ? const StatusBadge(
                                          label: 'Bloqué',
                                          color: AppTheme.danger,
                                        )
                                      : Text(
                                          formatMoney(customer.balance),
                                          style: TextStyle(
                                            color: customer.isOverLimit
                                                ? AppTheme.danger
                                                : AppTheme.navy,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                  onTap: customer.isBlocked
                                      ? null
                                      : () => Navigator.of(context)
                                          .pop(customer),
                                );
                              },
                            ),
            ),
          ],
        ),
      ),
    );
  }
}
