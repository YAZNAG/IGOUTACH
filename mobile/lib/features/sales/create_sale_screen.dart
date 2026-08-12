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
import '../shared/payment_sheet.dart';

/// Ligne de vente en cours de saisie.
class _LineDraft {
  _LineDraft({required this.product});

  final Product product;
  int quantity = 1;
  double? unitPrice;
  String? priceTypeCode;
  bool loadingPrice = true;
  String? priceError;

  /// Prix saisi à la main pour cette vente seulement.
  ///
  /// Il ne touche pas au tarif de l'article : celui-ci reste ce qu'il est pour
  /// toutes les autres ventes. Une fois le prix forcé, changer la quantité ne
  /// le réécrit plus — sinon le vendeur verrait son prix négocié disparaître
  /// en ajustant une unité.
  bool prixForce = false;

  /// Prix d'achat, plancher absolu de la vente. `null` quand l'utilisateur n'a
  /// pas le droit de consulter les coûts : le serveur reste seul juge.
  double? prixPlancher;

  /// Jeton anti-course : seule la dernière requête de prix est retenue.
  int priceRequestId = 0;

  /// Amortisseur de saisie : la quantité peut être tapée au clavier, on
  /// n'interroge le serveur qu'une fois la frappe terminée.
  Timer? priceDebounce;

  double? get lineTotal =>
      unitPrice == null ? null : unitPrice! * quantity;
}

/// Création d'une vente : client (ou passage), lieu, articles, prix résolus
/// par le serveur (paliers), confirmation et PDF.
///
/// [type] vaut `invoice` (facture, valeur par défaut) ou `quote` (devis).
/// Un devis n'étant qu'une proposition, l'étape de confirmation — qui déduit
/// le stock — est ignorée dans ce cas.
class CreateSaleScreen extends StatefulWidget {
  const CreateSaleScreen({super.key, this.type = 'invoice'});

  /// `invoice` ou `quote`.
  final String type;

  @override
  State<CreateSaleScreen> createState() => _CreateSaleScreenState();
}

class _CreateSaleScreenState extends State<CreateSaleScreen> {
  final _api = ApiClient.instance;

  bool get _isQuote => widget.type == 'quote';

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

  @override
  void dispose() {
    for (final line in _lines) {
      line.priceDebounce?.cancel();
    }
    super.dispose();
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
        // Le plancher n'arrive que pour qui a le droit de voir les coûts.
        line.prixPlancher = (data['floor_price'] as num?)?.toDouble();
        // Un prix négocié survit au changement de quantité : le tarif du
        // palier est calculé, mais il ne reprend pas la main.
        if (!line.prixForce) {
          line.unitPrice = (data['unit_price'] as num?)?.toDouble();
          line.priceTypeCode = data['price_type_code'] as String?;
        }
        line.loadingPrice = false;
      });
    } catch (e) {
      if (!mounted || requestId != line.priceRequestId) return;
      setState(() {
        line.loadingPrice = false;
        // Un article sans tarif défini reste vendable si le vendeur a saisi
        // un prix : effacer sa saisie parce que le tarif manque serait absurde.
        if (line.prixForce) return;
        line.unitPrice = null;
        line.priceTypeCode = null;
        line.priceError = friendlyError(e);
      });
    }
  }

  /// Saisie d'un prix de vente pour cette ligne, sur cette vente seulement.
  ///
  /// Le tarif de l'article n'est pas touché : il reste celui du catalogue pour
  /// toutes les autres ventes. La seule limite est le coût d'achat — vendre en
  /// dessous ferait perdre de l'argent sur chaque unité.
  Future<void> _modifierPrix(_LineDraft line) async {
    final controleur = TextEditingController(
      text: line.unitPrice == null ? '' : line.unitPrice!.toStringAsFixed(2),
    );
    String? erreur;

    final valide = await showDialog<bool>(
      context: context,
      builder: (contexte) => StatefulBuilder(
        builder: (contexte, majDialogue) => AlertDialog(
          title: Text(line.product.name, style: const TextStyle(fontSize: 16)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TextField(
                controller: controleur,
                autofocus: true,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: InputDecoration(
                  labelText: 'Prix de vente (DH)',
                  errorText: erreur,
                  border: const OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 10),
              if (line.prixPlancher != null)
                Text(
                  'Minimum : ${formatMoney(line.prixPlancher)} DH',
                  style: const TextStyle(fontSize: 12.5, color: AppTheme.textMuted),
                ),
              const SizedBox(height: 4),
              const Text(
                'Ne s’applique qu’à cette vente. Le tarif de l’article reste inchangé.',
                style: TextStyle(fontSize: 11.5, color: AppTheme.textMuted),
              ),
            ],
          ),
          actions: [
            if (line.prixForce)
              TextButton(
                onPressed: () {
                  setState(() {
                    line.prixForce = false;
                    line.loadingPrice = true;
                  });
                  Navigator.of(contexte).pop(false);
                  _fetchPrice(line);
                },
                child: const Text('Reprendre le tarif'),
              ),
            TextButton(
              onPressed: () => Navigator.of(contexte).pop(false),
              child: const Text('Annuler'),
            ),
            FilledButton(
              onPressed: () {
                final saisi = double.tryParse(controleur.text.trim().replaceAll(',', '.'));
                if (saisi == null || saisi <= 0) {
                  majDialogue(() => erreur = 'Saisissez un prix.');
                  return;
                }
                // Contrôle local quand le plancher est connu. Sinon le serveur
                // refuse la vente : la règle tient dans les deux cas.
                if (line.prixPlancher != null && saisi < line.prixPlancher!) {
                  majDialogue(() => erreur =
                      'Sous le coût de l’article (${formatMoney(line.prixPlancher)} DH).');
                  return;
                }
                setState(() {
                  line.unitPrice = saisi;
                  line.prixForce = true;
                  line.priceError = null;
                });
                Navigator.of(contexte).pop(true);
              },
              child: const Text('Appliquer'),
            ),
          ],
        ),
      ),
    );

    controleur.dispose();
    if (valide == true && mounted) setState(() {});
  }

  void _refreshAllPrices() {
    for (final line in _lines) {
      line.priceDebounce?.cancel();
      _fetchPrice(line);
    }
  }

  /// Change la quantité d'une ligne et replanifie la résolution du prix.
  ///
  /// Le prix dépend des paliers (détail / demi-gros / gros) : il doit être
  /// redemandé au serveur, mais une seule fois quand la quantité est saisie
  /// au clavier — d'où l'amortissement de 300 ms.
  void _changeQuantity(_LineDraft line, int quantity) {
    if (quantity < 1 || quantity == line.quantity) return;
    setState(() {
      line.quantity = quantity;
      line.loadingPrice = true;
    });
    line.priceDebounce?.cancel();
    line.priceDebounce = Timer(
      const Duration(milliseconds: 300),
      () {
        if (mounted) _fetchPrice(line);
      },
    );
  }

  void _removeLine(_LineDraft line) {
    line.priceDebounce?.cancel();
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

  /// Interception du retour arrière : une vente en cours de saisie ne doit
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

    if (_warehouseId == null) {
      showErrorSnack(messenger, 'Sélectionnez un lieu.');
      return;
    }
    if (!_walkIn && _customer == null) {
      showErrorSnack(
        messenger,
        'Sélectionnez un client ou activez « Client de passage ».',
      );
      return;
    }
    if (_lines.isEmpty) {
      showErrorSnack(messenger, 'Ajoutez au moins un article.');
      return;
    }

    setState(() => _creating = true);
    try {
      // Le prix unitaire n'est envoyé que pour les lignes négociées : sur les
      // autres, le serveur applique les paliers, qui font foi.
      final res = await _api.dio.post<Map<String, dynamic>>(
        '/sales',
        data: {
          'type': widget.type,
          'customer_id': _walkIn ? null : _customer!.id,
          'warehouse_id': _warehouseId,
          'lines': _lines
              .map((l) => {
                    'product_id': l.product.id,
                    'quantity': l.quantity,
                    if (l.prixForce && l.unitPrice != null)
                      'unit_price': l.unitPrice,
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
      showErrorSnack(messenger, friendlyError(e));
    }
  }

  /// Propose de régler la vente : intégralement, en partie, ou à crédit.
  ///
  /// Un paiement partiel laisse le reste en créance sur le client — c'est
  /// exactement ce que fait « à crédit » pour la totalité. Les trois chemins
  /// aboutissent donc au même endroit, seul le montant encaissé change.
  Future<void> _reglerVente(int saleId, String reference) async {
    final messenger = ScaffoldMessenger.of(context);

    final choix = await showDialog<String>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => AlertDialog(
        icon: const Icon(Icons.payments_outlined, size: 30, color: AppTheme.brand),
        title: const Text('Règlement'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '$reference — ${formatMoney(_total)}',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 6),
            Text(
              _customer!.name,
              style: const TextStyle(fontSize: 13, color: AppTheme.textMuted),
            ),
          ],
        ),
        actionsOverflowDirection: VerticalDirection.down,
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop('credit'),
            child: const Text('À crédit'),
          ),
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop('partiel'),
            child: const Text('Paiement partiel'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(dialogContext).pop('total'),
            child: const Text('Payé'),
          ),
        ],
      ),
    );

    if (!mounted || choix == null || choix == 'credit') {
      if (choix == 'credit' && mounted) {
        showSuccessSnack(messenger, 'Vente portée au crédit de ${_customer!.name}.');
      }
      return;
    }

    // « Payé » pré-remplit le total, « partiel » laisse saisir le montant
    // reçu ; dans les deux cas le reste éventuel devient une créance.
    final encaisse = await showPaymentSheet(
      context,
      customerId: _customer!.id,
      customerName: _customer!.name,
      saleId: saleId,
      saleReference: reference,
      // Le montant est pré-rempli avec le total : « Payé » se confirme d'un
      // tap, « partiel » se corrige. Le reste devient une créance dans les
      // deux cas — c'est le serveur qui l'inscrit au grand-livre.
      dueAmount: _total,
    );

    if (!mounted) return;

    if (encaisse) {
      showSuccessSnack(messenger, 'Règlement enregistré.');
    }
  }

  Future<void> _afterCreated(int saleId, String reference) async {
    // Un devis n'est pas confirmé : il sera converti en vente plus tard.
    final confirm = _isQuote
        ? false
        : await showDialog<bool>(
            context: context,
            barrierDismissible: false,
            builder: (dialogContext) => AlertDialog(
              icon: const Icon(
                Icons.check_circle_outline,
                size: 32,
                color: AppTheme.success,
              ),
              title: const Text('Vente créée'),
              content: Text(
                'La vente $reference a été créée en brouillon.\n'
                'Confirmer maintenant ? (le stock sera déduit)',
              ),
              actionsPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              actions: [
                TextButton(
                  onPressed: () => Navigator.of(dialogContext).pop(false),
                  child: const Text('Plus tard'),
                ),
                FilledButton(
                  style: FilledButton.styleFrom(
                    minimumSize: const Size(120, AppTheme.minTapTarget),
                  ),
                  onPressed: () => Navigator.of(dialogContext).pop(true),
                  child: const Text('Confirmer'),
                ),
              ],
            ),
          );

    if (!mounted) return;
    final messenger = ScaffoldMessenger.of(context);

    if (confirm == true) {
      try {
        await _api.dio.post('/sales/$saleId/confirm');
        if (!mounted) return;
        showSuccessSnack(messenger, 'Vente $reference confirmée.');

        // Règlement : seul un client identifié peut rester devoir. Une vente
        // au comptoir est encaissée d'office, il n'y a personne à qui
        // réclamer le reste.
        if (!_walkIn && _customer != null) {
          await _reglerVente(saleId, reference);
        }
      } catch (e) {
        if (!mounted) return;
        showErrorSnack(
          messenger,
          'Confirmation impossible : ${friendlyError(e)}',
        );
      }
    }

    if (!mounted) return;
    final wantPdf = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => AlertDialog(
        icon: const Icon(
          Icons.picture_as_pdf_outlined,
          size: 32,
          color: AppTheme.sky,
        ),
        title: Text(_isQuote ? 'Devis PDF' : 'Facture PDF'),
        content: Text(
          _isQuote
              ? 'Télécharger le devis PDF maintenant ?'
              : 'Télécharger la facture PDF maintenant ?',
        ),
        actionsPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(false),
            child: const Text('Non'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(
              minimumSize: const Size(120, AppTheme.minTapTarget),
            ),
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

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) => _handlePop(didPop),
      child: Scaffold(
        appBar: AppBar(
          title: Text(_isQuote ? 'Nouveau devis' : 'Nouvelle vente'),
        ),
        body: _loadingWarehouses
            ? const FormSkeleton(fieldCount: 3)
            : ListView(
                padding: const EdgeInsets.only(top: 8, bottom: 16),
                children: [
                  _buildCustomerCard(),
                  _buildWarehouseCard(),
                  _buildProductSearchCard(),
                  if (_lines.isEmpty)
                    const Padding(
                      padding: EdgeInsets.only(top: 24),
                      child: EmptyView(
                        icon: Icons.shopping_cart_outlined,
                        title: 'Panier vide',
                        message: 'Recherchez un article ci-dessus '
                            'pour l\'ajouter à la vente.',
                      ),
                    )
                  else
                    ..._lines.map(
                      (line) => _LineCard(
                        key: ValueKey(line.product.id),
                        line: line,
                        onQuantityChanged: (q) => _changeQuantity(line, q),
                        onRemove: () => _removeLine(line),
                        onPriceTap: () => _modifierPrix(line),
                      ),
                    ),
                ],
              ),
        bottomNavigationBar: _loadingWarehouses
            ? null
            : BottomActionBar(
                label: _isQuote ? 'Créer le devis' : 'Créer la vente',
                loading: _creating,
                summaryLabel: 'Total',
                summaryValue: formatMoney(_total),
                onPressed: _submit,
              ),
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
              subtitle: const Text('Vente comptoir sans fiche client'),
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
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    color: _customer == null
                        ? AppTheme.textMuted
                        : AppTheme.navy,
                  ),
                ),
                subtitle: _customer == null
                    ? null
                    : Text(
                        '${_customer!.code} · '
                        'encours ${formatMoney(_customer!.balance)}',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
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

}

/// Carte d'une ligne : quantité (stepper), prix serveur, total, suppression.
class _LineCard extends StatelessWidget {
  const _LineCard({
    super.key,
    required this.line,
    required this.onQuantityChanged,
    required this.onRemove,
    required this.onPriceTap,
  });

  final _LineDraft line;
  final ValueChanged<int> onQuantityChanged;
  final VoidCallback onRemove;

  /// Ouvre la saisie d'un prix négocié pour cette ligne.
  final VoidCallback onPriceTap;

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
              Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  QuantityStepper(
                    quantity: line.quantity,
                    onChanged: onQuantityChanged,
                  ),
                  const SizedBox(width: 12),
                  // Toute la zone du prix est tactile : viser un petit crayon
                  // au pouce, debout dans un magasin, ne marche pas.
                  Expanded(
                    child: InkWell(
                      onTap: line.loadingPrice ? null : onPriceTap,
                      borderRadius: BorderRadius.circular(8),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 6),
                        child: _buildPrice(),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  /// Prix résolu par le serveur : chargement, erreur, ou montant + palier.
  Widget _buildPrice() {
    if (line.loadingPrice) {
      return const Align(
        alignment: Alignment.centerRight,
        child: SizedBox(
          width: 22,
          height: 22,
          child: CircularProgressIndicator(strokeWidth: 2.5),
        ),
      );
    }

    if (line.priceError != null) {
      return Text(
        line.priceError!,
        maxLines: 2,
        overflow: TextOverflow.ellipsis,
        textAlign: TextAlign.right,
        style: const TextStyle(color: AppTheme.danger, fontSize: 13),
      );
    }

    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        Text(
          '${formatMoney(line.unitPrice)} × ${line.quantity}',
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 13, color: AppTheme.textMuted),
        ),
        const SizedBox(height: 2),
        AmountText(formatMoney(line.lineTotal), fontSize: 18),
        const SizedBox(height: 4),
        // Un prix négocié ne doit pas se confondre avec un tarif : le badge
        // dit lequel des deux s'applique.
        if (line.prixForce)
          StatusBadge(label: 'Prix modifié', color: AppTheme.warning)
        else if (_priceTypeLabel.isNotEmpty)
          StatusBadge(label: _priceTypeLabel, color: AppTheme.sky)
        else
          const Text(
            'Toucher pour fixer le prix',
            style: TextStyle(fontSize: 11, color: AppTheme.textMuted),
          ),
      ],
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
  bool _offline = false;

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
        _offline = isNetworkError(e);
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
        height: MediaQuery.of(context).size.height * 0.78,
        child: Column(
          children: [
            const SizedBox(height: 10),
            Container(
              width: 44,
              height: 4,
              decoration: BoxDecoration(
                color: AppTheme.border,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const Padding(
              padding: EdgeInsets.fromLTRB(16, 14, 16, 0),
              child: Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  'Choisir un client',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: AppTheme.navy,
                  ),
                ),
              ),
            ),
            AppSearchField(
              controller: _searchController,
              autofocus: true,
              onChanged: _onChanged,
              hintText: 'Rechercher un client…',
            ),
            Expanded(
              child: _loading
                  ? const ListSkeleton(itemCount: 6)
                  : _error != null
                      ? ErrorView(
                          message: _error!,
                          offline: _offline,
                          onRetry: () =>
                              _search(_searchController.text.trim()),
                        )
                      : _results.isEmpty
                          ? const EmptyView(
                              icon: Icons.people_outline,
                              title: 'Aucun client trouvé',
                              message: 'Vérifiez l\'orthographe, ou créez '
                                  'la fiche depuis le module Clients.',
                            )
                          : ListView.builder(
                              padding: const EdgeInsets.only(bottom: 16),
                              itemCount: _results.length,
                              itemBuilder: (context, index) =>
                                  _buildCustomerRow(_results[index]),
                            ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCustomerRow(Customer customer) {
    return ListTile(
      enabled: !customer.isBlocked,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      title: Text(
        customer.name,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
      ),
      subtitle: Text(
        '${customer.code}'
        '${(customer.city ?? '').isNotEmpty ? ' · ${customer.city}' : ''}',
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
      ),
      trailing: customer.isBlocked
          ? const StatusBadge(label: 'Bloqué', color: AppTheme.danger)
          : AmountText(
              formatMoney(customer.balance),
              fontSize: 15,
              color: customer.isOverLimit ? AppTheme.danger : AppTheme.navy,
            ),
      onTap: customer.isBlocked
          ? null
          : () => Navigator.of(context).pop(customer),
    );
  }
}
