import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/product.dart';
import '../shared/payment_sheet.dart';
import '../shared/product_picker.dart';
import 'sales_screen.dart' show downloadSalePdf, downloadSaleDocument;

/// Ligne d'une vente, telle qu'affichée et modifiée.
class _Ligne {
  _Ligne({
    required this.productId,
    required this.sku,
    required this.name,
    required this.quantity,
    required this.unitPrice,
  });

  final int productId;
  final String? sku;
  final String? name;
  int quantity;
  double unitPrice;

  double get total => quantity * unitPrice;

  factory _Ligne.fromJson(Map<String, dynamic> j) => _Ligne(
        productId: (j['product_id'] as num?)?.toInt() ?? 0,
        sku: j['sku'] as String?,
        name: j['name'] as String?,
        quantity: (j['quantity'] as num?)?.toInt() ?? 1,
        unitPrice: (j['unit_price'] as num?)?.toDouble() ?? 0,
      );
}

/// Détail d'une vente ou d'un devis.
///
/// Tant que le document est en brouillon, tout se modifie : lignes, quantités,
/// prix. Un devis suit exactement le même chemin qu'un bon — c'est la même
/// saisie, seule la sortie diffère : le bon se confirme et sort du stock, le
/// devis se convertit en vente.
///
/// Une fois confirmée, la vente a sorti du stock et engagé la créance : elle
/// devient consultable, ses trois documents sont accessibles, et elle peut
/// être réglée.
class SaleDetailScreen extends StatefulWidget {
  const SaleDetailScreen({super.key, required this.saleId, this.converti = false});

  final int saleId;

  /// Devis déjà transformé en vente : on ne le convertit pas deux fois.
  final bool converti;

  @override
  State<SaleDetailScreen> createState() => _SaleDetailScreenState();
}

class _SaleDetailScreenState extends State<SaleDetailScreen> {
  final _api = ApiClient.instance;

  bool _chargement = true;
  String? _erreur;
  bool _horsLigne = false;
  bool _enregistrement = false;

  String _reference = '';
  String _statut = 'draft';
  String _type = 'invoice';
  String? _client;
  int? _clientId;
  double _total = 0;
  double _paye = 0;
  List<_Ligne> _lignes = [];

  /// Passe à `true` dès qu'une action change l'état côté serveur : la liste
  /// qui nous a ouverts doit se recharger.
  bool _modifie = false;
  late bool _converti = widget.converti;

  bool get _brouillon => _statut == 'draft';

  bool get _devis => _type == 'quote';

  /// Reste dû d'une facture confirmée.
  double get _restantDu => (_total - _paye).clamp(0, double.infinity);

  @override
  void initState() {
    super.initState();
    _charger();
  }

  Future<void> _charger() async {
    setState(() {
      _chargement = true;
      _erreur = null;
    });
    try {
      final res = await _api.dio.get<Map<String, dynamic>>('/sales/${widget.saleId}');
      final d = res.data!['data'] as Map<String, dynamic>;
      if (!mounted) return;
      setState(() {
        _reference = d['reference'] as String? ?? '';
        _statut = d['status'] as String? ?? 'draft';
        _type = d['type'] as String? ?? 'invoice';
        final client = d['customer'] as Map<String, dynamic>?;
        _client = client?['name'] as String?;
        _clientId = (client?['id'] as num?)?.toInt();
        _total = (d['total'] as num?)?.toDouble() ?? 0;
        _paye = (d['paid_amount'] as num?)?.toDouble() ?? 0;
        _lignes = (d['lines'] as List<dynamic>? ?? [])
            .map((e) => _Ligne.fromJson(e as Map<String, dynamic>))
            .toList();
        _chargement = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _erreur = friendlyError(e);
        _horsLigne = isNetworkError(e);
        _chargement = false;
      });
    }
  }

  double get _totalLocal => _lignes.fold(0, (s, l) => s + l.total);

  /// Ajoute un article, ou fait remonter celui déjà présent.
  Future<void> _ajouter(Product produit) async {
    final existante = _lignes.where((l) => l.productId == produit.id).firstOrNull;
    if (existante != null) {
      setState(() {
        _lignes.remove(existante);
        existante.quantity += 1;
        _lignes.insert(0, existante);
      });
      return;
    }

    double prix = 0;
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/sales/price',
        queryParameters: {
          'product_id': produit.id,
          'quantity': 1,
          if (_clientId != null) 'customer_id': _clientId,
        },
      );
      prix = ((res.data!['data'] as Map<String, dynamic>)['unit_price'] as num?)?.toDouble() ?? 0;
    } catch (_) {
      // Article sans tarif : le prix se saisit à la main.
    }

    if (!mounted) return;
    setState(() {
      _lignes.insert(0, _Ligne(
        productId: produit.id,
        sku: produit.sku,
        name: produit.name,
        quantity: 1,
        unitPrice: prix,
      ));
    });
  }

  Future<void> _modifierPrix(_Ligne ligne) async {
    final controleur = TextEditingController(text: ligne.unitPrice.toStringAsFixed(2));
    final valide = await showDialog<bool>(
      context: context,
      builder: (contexte) => AlertDialog(
        title: Text(ligne.name ?? '', style: const TextStyle(fontSize: 16)),
        content: TextField(
          controller: controleur,
          autofocus: true,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          decoration: const InputDecoration(
            labelText: 'Prix de vente (DH)',
            border: OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.of(contexte).pop(false), child: const Text('Annuler')),
          FilledButton(onPressed: () => Navigator.of(contexte).pop(true), child: const Text('Appliquer')),
        ],
      ),
    );
    final saisi = double.tryParse(controleur.text.trim().replaceAll(',', '.'));
    controleur.dispose();
    if (valide == true && saisi != null && saisi > 0 && mounted) {
      setState(() => ligne.unitPrice = saisi);
    }
  }

  Future<void> _enregistrer() async {
    if (_lignes.isEmpty) return;
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _enregistrement = true);
    try {
      await _api.dio.put<Map<String, dynamic>>(
        '/sales/${widget.saleId}',
        data: {
          'lines': _lignes
              .map((l) => {
                    'product_id': l.productId,
                    'quantity': l.quantity,
                    'unit_price': l.unitPrice,
                  })
              .toList(),
        },
      );
      if (!mounted) return;
      showSuccessSnack(messenger, _devis ? 'Devis enregistré.' : 'Bon enregistré.');
      _modifie = true;
      await _charger();
    } catch (e) {
      if (!mounted) return;
      showErrorSnack(messenger, friendlyError(e));
    } finally {
      if (mounted) setState(() => _enregistrement = false);
    }
  }

  Future<void> _confirmer() async {
    final confirme = await confirmAction(
      context,
      icon: Icons.check_circle_outline,
      title: 'Confirmer la vente',
      message: '$_reference\n${formatMoney(_totalLocal)}\n\n'
          'Le stock sortira et la facture sera émise. Le document ne sera plus modifiable.',
      confirmLabel: 'Confirmer',
      confirmColor: AppTheme.success,
    );
    if (!confirme || !mounted) return;

    final messenger = ScaffoldMessenger.of(context);
    setState(() => _enregistrement = true);
    try {
      await _api.dio.post<Map<String, dynamic>>('/sales/${widget.saleId}/confirm');
      if (!mounted) return;
      showSuccessSnack(messenger, 'Vente confirmée.');
      _modifie = true;
      await _charger();
    } catch (e) {
      if (!mounted) return;
      showErrorSnack(messenger, friendlyError(e));
    } finally {
      if (mounted) setState(() => _enregistrement = false);
    }
  }

  /// Transforme le devis en vente. Le devis reste, la vente naît en brouillon.
  Future<void> _convertir() async {
    final confirme = await confirmAction(
      context,
      icon: Icons.swap_horiz,
      title: 'Convertir en vente',
      message: 'Créer une vente à partir du devis $_reference ?\n'
          'Elle naîtra en brouillon, à confirmer ensuite.',
      confirmLabel: 'Convertir',
      confirmColor: AppTheme.success,
    );
    if (!confirme || !mounted) return;

    final messenger = ScaffoldMessenger.of(context);
    setState(() => _enregistrement = true);
    try {
      final res = await _api.dio.post<Map<String, dynamic>>(
        '/sales/${widget.saleId}/convert',
      );
      final reference =
          (res.data!['data'] as Map<String, dynamic>)['reference'] as String? ?? '';
      if (!mounted) return;
      setState(() {
        _converti = true;
        _modifie = true;
      });
      showSuccessSnack(messenger, 'Vente $reference créée à partir du devis.');
    } catch (e) {
      if (!mounted) return;
      showErrorSnack(messenger, friendlyError(e));
    } finally {
      if (mounted) setState(() => _enregistrement = false);
    }
  }

  /// Encaisse un règlement sur cette facture précise.
  Future<void> _regler() async {
    final clientId = _clientId;
    if (clientId == null) return;

    final regle = await showPaymentSheet(
      context,
      customerId: clientId,
      customerName: _client ?? 'Client',
      saleId: widget.saleId,
      saleReference: _reference,
      dueAmount: _restantDu,
    );
    if (!regle || !mounted) return;

    _modifie = true;
    showSuccessSnack(ScaffoldMessenger.of(context), 'Règlement enregistré.');
    await _charger();
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.can('sale.create')) return const NotAllowedView();

    // Le retour signale à la liste appelante qu'elle doit se recharger.
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) Navigator.of(context).pop(_modifie);
      },
      child: Scaffold(
      appBar: AppBar(
        title: Text(_reference.isEmpty ? 'Vente' : _reference),
        actions: [
          if (!_chargement && _erreur == null)
            IconButton(
              icon: const Icon(Icons.refresh),
              tooltip: 'Recharger',
              onPressed: _charger,
            ),
        ],
      ),
      body: _corps(),
      bottomNavigationBar: _chargement || _erreur != null ? null : _barreActions(),
      ),
    );
  }

  Widget _corps() {
    if (_chargement) return const ListSkeleton(itemCount: 4, lines: 2);
    if (_erreur != null) {
      return ErrorView(message: _erreur!, offline: _horsLigne, onRetry: _charger);
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 96),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        _client ?? 'Client de passage',
                        style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
                      ),
                    ),
                    // Un brouillon n'est pas une facture : le nom du document
                    // le dit, ici comme sur le PDF. Un devis reste un devis,
                    // brouillon ou non — il n'a jamais rien engagé.
                    StatusBadge(
                      label: _devis
                          ? (_converti ? 'Devis converti' : 'Devis')
                          : (_brouillon ? 'Bon' : 'Facture'),
                      color: _devis
                          ? (_converti ? AppTheme.sky : AppTheme.warning)
                          : (_brouillon ? AppTheme.warning : AppTheme.success),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                AmountText(formatMoney(_brouillon ? _totalLocal : _total), fontSize: 22),
                if (!_brouillon && !_devis && _paye > 0) ...[
                  const SizedBox(height: 4),
                  Text(
                    _restantDu <= 0
                        ? 'Réglée intégralement'
                        : 'Payé ${formatMoney(_paye)} · reste ${formatMoney(_restantDu)}',
                    style: TextStyle(
                      fontSize: 13,
                      color: _restantDu <= 0 ? AppTheme.success : AppTheme.danger,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
        if (_brouillon) ...[
          const SizedBox(height: 12),
          ProductPickerField(
            key: const ValueKey('ajout-ligne'),
            onSelected: _ajouter,
          ),
        ],
        const SizedBox(height: 12),
        ..._lignes.map(_carteLigne),
        if (_lignes.isEmpty)
          const Padding(
            padding: EdgeInsets.all(24),
            child: Text(
              'Aucune ligne.',
              textAlign: TextAlign.center,
              style: TextStyle(color: AppTheme.textMuted),
            ),
          ),
        const SizedBox(height: 12),
        _documents(),
      ],
    );
  }

  Widget _carteLigne(_Ligne ligne) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    ligne.name ?? '',
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                ),
                if (_brouillon)
                  IconButton(
                    icon: const Icon(Icons.delete_outline, color: AppTheme.danger),
                    tooltip: 'Retirer',
                    onPressed: () => setState(() => _lignes.remove(ligne)),
                  ),
              ],
            ),
            const SizedBox(height: 6),
            Row(
              children: [
                if (_brouillon)
                  QuantityStepper(
                    quantity: ligne.quantity,
                    onChanged: (q) => setState(() => ligne.quantity = q),
                  )
                else
                  Text('Qté ${ligne.quantity}',
                      style: const TextStyle(color: AppTheme.textMuted)),
                const SizedBox(width: 12),
                Expanded(
                  child: InkWell(
                    onTap: _brouillon ? () => _modifierPrix(ligne) : null,
                    borderRadius: BorderRadius.circular(8),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 4),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(
                            '${formatMoney(ligne.unitPrice)} × ${ligne.quantity}',
                            style: const TextStyle(fontSize: 12.5, color: AppTheme.textMuted),
                          ),
                          AmountText(formatMoney(ligne.total), fontSize: 16),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  /// Documents disponibles selon l'état du document.
  ///
  /// Un devis ou un bon n'a qu'une pièce : lui-même. Le bon de livraison et
  /// le bon de sortie n'existent qu'après confirmation, puisque rien n'est
  /// sorti du dépôt avant.
  Widget _documents() {
    final confirmee = !_brouillon && !_devis;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Padding(
              padding: EdgeInsets.only(bottom: 8),
              child: Text('Documents', style: TextStyle(fontWeight: FontWeight.w600)),
            ),
            OutlinedButton.icon(
              onPressed: () => downloadSalePdf(context, widget.saleId, _reference),
              icon: Icon(
                _devis
                    ? Icons.request_quote_outlined
                    : Icons.receipt_long_outlined,
                size: 18,
              ),
              label: Text(_devis ? 'Devis' : (_brouillon ? 'Bon' : 'Facture')),
            ),
            if (confirmee) ...[
              const SizedBox(height: 8),
              OutlinedButton.icon(
                onPressed: () => downloadSaleDocument(
                  context, widget.saleId, 'delivery-pdf', 'BL-$_reference',
                ),
                icon: const Icon(Icons.local_shipping_outlined, size: 18),
                label: const Text('Bon de livraison'),
              ),
              const SizedBox(height: 8),
              OutlinedButton.icon(
                onPressed: () => downloadSaleDocument(
                  context, widget.saleId, 'exit-pdf', 'BS-$_reference',
                ),
                icon: const Icon(Icons.outbox_outlined, size: 18),
                label: const Text('Bon de sortie'),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget? _barreActions() {
    final auth = context.watch<AuthProvider>();

    // Une facture confirmée et encore due se règle depuis son propre écran :
    // c'est là qu'on la lit, c'est là qu'on encaisse.
    if (!_brouillon && !_devis) {
      if (_restantDu <= 0 || _clientId == null || !auth.can('payment.create')) {
        return null;
      }
      return _barre([
        Expanded(
          child: FilledButton.icon(
            onPressed: _enregistrement ? null : _regler,
            icon: const Icon(Icons.payments_outlined, size: 18),
            label: Text('Régler ${formatMoney(_restantDu)}'),
          ),
        ),
      ]);
    }

    // Un devis ne sort rien du stock : il se convertit, il ne se confirme pas.
    final VoidCallback? action =
        _devis ? (_converti ? null : _convertir) : _confirmer;

    return _barre([
      Expanded(
        child: OutlinedButton(
          onPressed: _enregistrement || _lignes.isEmpty || !_brouillon
              ? null
              : _enregistrer,
          child: const Text('Enregistrer'),
        ),
      ),
      const SizedBox(width: 10),
      Expanded(
        child: FilledButton(
          onPressed:
              _enregistrement || _lignes.isEmpty || action == null ? null : action,
          child: Text(
            _enregistrement
                ? 'En cours…'
                : (_devis ? (_converti ? 'Déjà converti' : 'Convertir') : 'Confirmer'),
          ),
        ),
      ),
    ]);
  }

  /// Barre du bas : BottomActionBar ne porte qu'une action, on compose donc
  /// ici plutôt que de détourner le composant partagé.
  Widget _barre(List<Widget> enfants) {
    return SafeArea(
      child: Container(
        padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: AppTheme.border)),
        ),
        child: Row(children: enfants),
      ),
    );
  }
}
