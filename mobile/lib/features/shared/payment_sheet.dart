import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/payment_method.dart';

/// Facture encore due d'un client (GET /customers/{id}/open-invoices).
class _FactureDue {
  const _FactureDue({
    required this.id,
    required this.reference,
    required this.remaining,
    this.date,
  });

  final int id;
  final String reference;
  final double remaining;
  final String? date;

  factory _FactureDue.fromJson(Map<String, dynamic> j) => _FactureDue(
        id: (j['id'] as num).toInt(),
        reference: j['reference'] as String? ?? '',
        remaining: (j['remaining'] as num?)?.toDouble() ?? 0,
        date: j['date'] as String?,
      );
}

/// Ouvre la feuille de règlement (encaissement client).
///
/// - depuis une vente : [saleId] renseigné, [dueAmount] = reste dû ;
/// - depuis les crédits clients : [saleId] nul, [dueAmount] = encours, et
///   l'on peut désigner les factures à solder.
///
/// Retourne `true` si un encaissement a bien été enregistré.
Future<bool> showPaymentSheet(
  BuildContext context, {
  required int customerId,
  required String customerName,
  int? saleId,
  String? saleReference,
  double? dueAmount,
}) async {
  final saved = await showModalBottomSheet<bool>(
    context: context,
    isScrollControlled: true,
    builder: (_) => _PaymentSheet(
      customerId: customerId,
      customerName: customerName,
      saleId: saleId,
      saleReference: saleReference,
      dueAmount: dueAmount,
    ),
  );
  return saved == true;
}

class _PaymentSheet extends StatefulWidget {
  const _PaymentSheet({
    required this.customerId,
    required this.customerName,
    this.saleId,
    this.saleReference,
    this.dueAmount,
  });

  final int customerId;
  final String customerName;
  final int? saleId;
  final String? saleReference;
  final double? dueAmount;

  @override
  State<_PaymentSheet> createState() => _PaymentSheetState();
}

class _PaymentSheetState extends State<_PaymentSheet> {
  final _api = ApiClient.instance;
  final _formKey = GlobalKey<FormState>();
  final _amountController = TextEditingController();
  final _noteController = TextEditingController();

  // Effet remis par le client : chèque ou traite, mêmes champs.
  final _chequeNumero = TextEditingController();
  final _chequeBanque = TextEditingController();
  final _chequeSignataire = TextEditingController();
  DateTime? _chequeDate;

  /// `false` : l'effet est au nom du client ; `true` : au nom d'un tiers.
  bool _autreSignataire = false;

  List<PaymentMethod> _methods = [];
  int? _methodId;

  /// `false` quand GET /payment-methods échoue (permission absente) :
  /// l'encaissement reste possible, `payment_method_id` étant facultatif.
  bool _methodsAvailable = true;
  bool _loadingMethods = true;

  /// Factures dues du client, et montant affecté à chacune.
  List<_FactureDue> _factures = [];
  final Map<int, double> _ventilation = {};
  bool _loadingFactures = false;

  DateTime _receivedAt = DateTime.now();
  bool _saving = false;
  String? _error;

  /// Validation à la volée déclenchée après la première tentative.
  bool _submitted = false;

  /// Effet bancaire : le code du mode fait foi, un libellé peut être n'importe quoi.
  String get _codeMode {
    final mode = _methods.where((m) => m.id == _methodId).firstOrNull;
    return (mode?.code ?? '').toUpperCase();
  }

  bool get _estEffet => _codeMode == 'CHEQUE' || _codeMode == 'TRAITE';

  String get _instrument => _codeMode == 'TRAITE' ? 'traite' : 'cheque';

  String get _motEffet => _codeMode == 'TRAITE' ? 'la traite' : 'le chèque';

  /// Le règlement d'une facture précise n'a rien à ventiler : il porte déjà
  /// sur un document.
  bool get _peutVentiler => widget.saleId == null;

  double get _totalVentile =>
      _ventilation.values.fold<double>(0, (somme, v) => somme + v);

  @override
  void initState() {
    super.initState();
    final due = widget.dueAmount;
    if (due != null && due > 0) {
      _amountController.text = due.toStringAsFixed(2);
    }
    _loadMethods();
    if (_peutVentiler) _loadFactures();
  }

  @override
  void dispose() {
    _amountController.dispose();
    _noteController.dispose();
    _chequeNumero.dispose();
    _chequeBanque.dispose();
    _chequeSignataire.dispose();
    super.dispose();
  }

  Future<void> _loadMethods() async {
    try {
      final res =
          await _api.dio.get<Map<String, dynamic>>('/payment-methods');
      final data = res.data!['data'] as List<dynamic>? ?? [];
      final methods = data
          .map((e) => PaymentMethod.fromJson(e as Map<String, dynamic>))
          .where((m) => m.isActive)
          .toList();
      if (!mounted) return;
      setState(() {
        _methods = methods;
        _methodId = methods.isEmpty ? null : methods.first.id;
        _loadingMethods = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _methodsAvailable = false;
        _loadingMethods = false;
      });
    }
  }

  Future<void> _loadFactures() async {
    setState(() => _loadingFactures = true);
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/customers/${widget.customerId}/open-invoices',
      );
      final data = res.data!['data'] as List<dynamic>? ?? [];
      if (!mounted) return;
      setState(() {
        _factures = data
            .map((e) => _FactureDue.fromJson(e as Map<String, dynamic>))
            .toList();
        _loadingFactures = false;
      });
    } catch (_) {
      // Sans la liste, l'encaissement retombe sur l'encours global : c'est
      // le comportement d'avant, pas une impasse.
      if (!mounted) return;
      setState(() => _loadingFactures = false);
    }
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _receivedAt,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 1)),
      helpText: 'Date de l\'encaissement',
    );
    if (picked != null) setState(() => _receivedAt = picked);
  }

  Future<void> _pickChequeDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _chequeDate ?? DateTime.now(),
      // Une traite est souvent postdatée de plusieurs mois : la borner à
      // aujourd'hui empêcherait de saisir son échéance.
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 730)),
      helpText: 'Date portée sur l\'effet',
    );
    if (picked != null) setState(() => _chequeDate = picked);
  }

  void _fillAll() {
    final due = widget.dueAmount;
    if (due == null) return;
    _amountController.text = due.toStringAsFixed(2);
  }

  double? get _amount {
    final raw = _amountController.text.trim().replaceAll(',', '.');
    return double.tryParse(raw);
  }

  /// Coche une facture : par défaut on solde ce qui reste dû, dans la limite
  /// de ce qui n'a pas déjà été affecté ailleurs.
  void _basculerFacture(_FactureDue facture) {
    setState(() {
      if (_ventilation.containsKey(facture.id)) {
        _ventilation.remove(facture.id);
        return;
      }
      final saisi = _amount ?? 0;
      final restant = saisi > 0 ? saisi - _totalVentile : facture.remaining;
      final montant = restant <= 0
          ? facture.remaining
          : (restant < facture.remaining ? restant : facture.remaining);
      _ventilation[facture.id] = double.parse(montant.toStringAsFixed(2));
    });
  }

  Future<void> _modifierPart(_FactureDue facture) async {
    final controleur = TextEditingController(
      text: (_ventilation[facture.id] ?? facture.remaining).toStringAsFixed(2),
    );
    final valide = await showDialog<bool>(
      context: context,
      builder: (contexte) => AlertDialog(
        title: Text(facture.reference, style: const TextStyle(fontSize: 16)),
        content: TextField(
          controller: controleur,
          autofocus: true,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          decoration: InputDecoration(
            labelText: 'Montant affecté (DH)',
            helperText: 'Reste dû : ${formatMoney(facture.remaining)}',
            border: const OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(contexte).pop(false),
            child: const Text('Annuler'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(contexte).pop(true),
            child: const Text('Appliquer'),
          ),
        ],
      ),
    );
    final saisi = double.tryParse(controleur.text.trim().replaceAll(',', '.'));
    controleur.dispose();
    if (valide != true || !mounted) return;
    setState(() {
      if (saisi == null || saisi <= 0) {
        _ventilation.remove(facture.id);
      } else {
        _ventilation[facture.id] =
            double.parse((saisi > facture.remaining ? facture.remaining : saisi)
                .toStringAsFixed(2));
      }
    });
  }

  /// Message bloquant, ou null si la saisie peut partir.
  String? get _obstacle {
    final montant = _amount;
    if (montant == null || montant <= 0) return null;

    if (_estEffet) {
      if (_chequeNumero.text.trim().isEmpty) {
        return 'Saisissez le numéro de ${_motEffet == 'la traite' ? 'la traite' : 'du chèque'}.';
      }
      if (_chequeDate == null) {
        return 'Saisissez la date portée sur l\'effet.';
      }
      if (_autreSignataire && _chequeSignataire.text.trim().isEmpty) {
        return 'Indiquez le nom de la personne au nom de qui est établi l\'effet.';
      }
    }

    if (_ventilation.isNotEmpty &&
        (_totalVentile - montant).abs() > 0.001) {
      return 'La répartition (${formatMoney(_totalVentile)}) ne correspond pas '
          'au montant encaissé (${formatMoney(montant)}).';
    }

    return null;
  }

  Future<void> _submit() async {
    setState(() => _submitted = true);
    if (!(_formKey.currentState?.validate() ?? false)) return;

    final obstacle = _obstacle;
    if (obstacle != null) {
      setState(() => _error = obstacle);
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      await _api.dio.post<Map<String, dynamic>>(
        '/payments',
        data: {
          'customer_id': widget.customerId,
          'amount': _amount,
          'payment_method_id': ?_methodId,
          'sale_id': ?widget.saleId,
          'received_at': apiDate(_receivedAt),
          if (_noteController.text.trim().isNotEmpty)
            'note': _noteController.text.trim(),
          // Sans ventilation, le versement tombe dans l'encours global.
          if (_ventilation.isNotEmpty)
            'allocations': _ventilation.entries
                .map((e) => {'sale_id': e.key, 'amount': e.value})
                .toList(),
          // L'effet est déclaré avec le règlement : il entre au portefeuille
          // au nom qu'il porte, celui du client ou celui d'un tiers.
          if (_estEffet)
            'cheque': {
              'instrument': _instrument,
              'number': _chequeNumero.text.trim(),
              'cheque_date': apiDate(_chequeDate!),
              if (_chequeBanque.text.trim().isNotEmpty)
                'bank': _chequeBanque.text.trim(),
              'origin': _autreSignataire ? 'third_party' : 'customer',
              if (_autreSignataire)
                'drawer_name': _chequeSignataire.text.trim(),
            },
        },
      );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _saving = false;
        _error = friendlyError(e);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SafeArea(
        top: false,
        child: ConstrainedBox(
          constraints: BoxConstraints(
            maxHeight: MediaQuery.of(context).size.height * 0.9,
          ),
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
            child: Form(
              key: _formKey,
              autovalidateMode: _submitted
                  ? AutovalidateMode.onUserInteraction
                  : AutovalidateMode.disabled,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      margin: const EdgeInsets.only(bottom: 12),
                      decoration: BoxDecoration(
                        color: Colors.grey.shade300,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  Text(
                    widget.saleId == null
                        ? 'Encaisser un règlement'
                        : 'Régler la facture',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: AppTheme.navy,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    [
                      widget.customerName,
                      if ((widget.saleReference ?? '').isNotEmpty)
                        widget.saleReference!,
                    ].join(' · '),
                    style: const TextStyle(
                      fontSize: 15,
                      color: AppTheme.textMuted,
                    ),
                  ),
                  if (widget.dueAmount != null) ...[
                    const SizedBox(height: 10),
                    Text(
                      widget.saleId == null
                          ? 'Encours : ${formatMoney(widget.dueAmount)}'
                          : 'Reste dû : ${formatMoney(widget.dueAmount)}',
                      style: AppTheme.amountStyle(
                        fontSize: 16,
                        color: AppTheme.danger,
                      ),
                    ),
                  ],
                  const SizedBox(height: 20),
                  TextFormField(
                    controller: _amountController,
                    autofocus: true,
                    keyboardType:
                        const TextInputType.numberWithOptions(decimal: true),
                    onChanged: (_) => setState(() {}),
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                      fontFeatures: AppTheme.tabularFigures,
                    ),
                    inputFormatters: [
                      FilteringTextInputFormatter.allow(RegExp(r'[0-9.,]')),
                    ],
                    decoration: InputDecoration(
                      labelText: 'Montant (DH)',
                      prefixIcon: const Icon(Icons.payments_outlined),
                      suffixIcon: widget.dueAmount == null
                          ? null
                          : TextButton(
                              onPressed: _fillAll,
                              child: const Text('Tout'),
                            ),
                    ),
                    validator: (_) {
                      final amount = _amount;
                      if (amount == null || amount <= 0) {
                        return 'Saisissez un montant supérieur à 0.';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 12),
                  if (_loadingMethods)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 12),
                      child: Center(
                        child: SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      ),
                    )
                  else if (!_methodsAvailable || _methods.isEmpty)
                    const Padding(
                      padding: EdgeInsets.only(bottom: 12),
                      child: Text(
                        'Modes de paiement indisponibles : '
                        'l\'encaissement sera enregistré sans mode.',
                        style: TextStyle(
                          fontSize: 14,
                          color: AppTheme.textMuted,
                        ),
                      ),
                    )
                  else
                    DropdownButtonFormField<int>(
                      initialValue: _methodId,
                      isExpanded: true,
                      decoration: const InputDecoration(
                        labelText: 'Mode de paiement',
                        prefixIcon: Icon(Icons.account_balance_wallet_outlined),
                      ),
                      items: _methods
                          .map(
                            (m) => DropdownMenuItem(
                              value: m.id,
                              child: Text(m.name),
                            ),
                          )
                          .toList(),
                      onChanged: (value) => setState(() => _methodId = value),
                    ),
                  if (_estEffet) ...[
                    const SizedBox(height: 12),
                    _blocEffet(),
                  ],
                  const SizedBox(height: 12),
                  InkWell(
                    onTap: _pickDate,
                    borderRadius: BorderRadius.circular(AppTheme.radiusField),
                    child: InputDecorator(
                      decoration: const InputDecoration(
                        labelText: 'Date',
                        prefixIcon: Icon(Icons.event_outlined),
                        suffixIcon: Icon(Icons.edit_calendar_outlined),
                      ),
                      child: Text(
                        formatDate(_receivedAt),
                        style: const TextStyle(fontSize: 16),
                      ),
                    ),
                  ),
                  if (_peutVentiler && (_factures.isNotEmpty || _loadingFactures)) ...[
                    const SizedBox(height: 16),
                    _blocFactures(),
                  ],
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _noteController,
                    maxLength: 255,
                    textCapitalization: TextCapitalization.sentences,
                    decoration: const InputDecoration(
                      labelText: 'Note (facultatif)',
                      prefixIcon: Icon(Icons.notes_outlined),
                      counterText: '',
                    ),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 12),
                    ErrorBox(message: _error!),
                  ],
                  const SizedBox(height: 20),
                  FilledButton.icon(
                    onPressed: _saving ? null : _submit,
                    icon: _saving
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2.5,
                              color: Colors.white,
                            ),
                          )
                        : const Icon(Icons.check),
                    label: Text(
                      _saving
                          ? 'Enregistrement…'
                          : 'Enregistrer l\'encaissement',
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  /// Saisie de l'effet remis : numéro, date, banque et nom porté.
  ///
  /// Le nom du tireur est demandé à part : il est fréquent qu'un client règle
  /// avec le chèque d'un tiers, et c'est ce nom-là que la banque opposera en
  /// cas de rejet.
  Widget _blocEffet() {
    final titre = _codeMode == 'TRAITE' ? 'Détails de la traite' : 'Détails du chèque';

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF7F7F9),
        borderRadius: BorderRadius.circular(AppTheme.radiusField),
        border: Border.all(color: AppTheme.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(titre, style: const TextStyle(fontWeight: FontWeight.w600)),
          const SizedBox(height: 10),
          TextFormField(
            controller: _chequeNumero,
            decoration: const InputDecoration(
              labelText: 'N° / série',
              prefixIcon: Icon(Icons.tag),
            ),
          ),
          const SizedBox(height: 10),
          InkWell(
            onTap: _pickChequeDate,
            borderRadius: BorderRadius.circular(AppTheme.radiusField),
            child: InputDecorator(
              decoration: const InputDecoration(
                labelText: 'Date de l\'effet',
                prefixIcon: Icon(Icons.event_note_outlined),
                suffixIcon: Icon(Icons.edit_calendar_outlined),
              ),
              child: Text(
                _chequeDate == null ? 'À saisir' : formatDate(_chequeDate!),
                style: TextStyle(
                  fontSize: 16,
                  color: _chequeDate == null ? AppTheme.textMuted : null,
                ),
              ),
            ),
          ),
          const SizedBox(height: 10),
          TextFormField(
            controller: _chequeBanque,
            decoration: const InputDecoration(
              labelText: 'Banque (facultatif)',
              prefixIcon: Icon(Icons.account_balance_outlined),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            '${_motEffet[0].toUpperCase()}${_motEffet.substring(1)} est au nom de :',
            style: const TextStyle(fontSize: 13, color: AppTheme.textMuted),
          ),
          const SizedBox(height: 6),
          SegmentedButton<bool>(
            segments: [
              ButtonSegment(
                value: false,
                label: Text(
                  widget.customerName.length > 16
                      ? '${widget.customerName.substring(0, 15)}…'
                      : widget.customerName,
                ),
              ),
              const ButtonSegment(value: true, label: Text('Une autre personne')),
            ],
            selected: {_autreSignataire},
            showSelectedIcon: false,
            style: const ButtonStyle(visualDensity: VisualDensity.compact),
            onSelectionChanged: (choix) => setState(() {
              _autreSignataire = choix.first;
              // Revenir au client efface le nom saisi : le laisser traîner
              // ferait enregistrer un tireur que l'écran n'affiche plus.
              if (!_autreSignataire) _chequeSignataire.clear();
            }),
          ),
          if (_autreSignataire) ...[
            const SizedBox(height: 8),
            TextFormField(
              controller: _chequeSignataire,
              textCapitalization: TextCapitalization.words,
              decoration: const InputDecoration(
                labelText: 'Nom porté sur l\'effet',
                prefixIcon: Icon(Icons.person_outline),
              ),
            ),
          ],
        ],
      ),
    );
  }

  /// Choix des factures soldées par ce versement.
  Widget _blocFactures() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppTheme.radiusField),
        border: Border.all(color: AppTheme.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text(
            'Factures à régler',
            style: TextStyle(fontWeight: FontWeight.w600),
          ),
          const Text(
            'Sans sélection, le versement réduit l\'encours global.',
            style: TextStyle(fontSize: 12, color: AppTheme.textMuted),
          ),
          const SizedBox(height: 6),
          if (_loadingFactures)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 10),
              child: Center(
                child: SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              ),
            )
          else
            ..._factures.map((f) {
              final part = _ventilation[f.id];
              return CheckboxListTile(
                value: part != null,
                dense: true,
                contentPadding: EdgeInsets.zero,
                controlAffinity: ListTileControlAffinity.leading,
                onChanged: (_) => _basculerFacture(f),
                title: Text(
                  f.reference,
                  style: const TextStyle(
                    fontFamily: 'monospace',
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                  ),
                ),
                subtitle: Text(
                  'Reste dû ${formatMoney(f.remaining)}${f.date == null ? '' : ' · ${f.date}'}',
                  style: const TextStyle(fontSize: 12),
                ),
                secondary: part == null
                    ? null
                    : TextButton(
                        onPressed: () => _modifierPart(f),
                        child: Text(formatMoney(part)),
                      ),
              );
            }),
          if (_ventilation.isNotEmpty) ...[
            const Divider(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Réparti', style: TextStyle(fontSize: 13)),
                Text(
                  formatMoney(_totalVentile),
                  style: AppTheme.amountStyle(
                    fontSize: 14,
                    color: (_totalVentile - (_amount ?? 0)).abs() > 0.001
                        ? AppTheme.danger
                        : AppTheme.success,
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}
