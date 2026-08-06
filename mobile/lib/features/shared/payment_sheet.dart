import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/payment_method.dart';

/// Ouvre la feuille de règlement (encaissement client).
///
/// - depuis une vente : [saleId] renseigné, [dueAmount] = reste dû ;
/// - depuis les crédits clients : [saleId] nul, [dueAmount] = encours.
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

  List<PaymentMethod> _methods = [];
  int? _methodId;

  /// `false` quand GET /payment-methods échoue (permission absente) :
  /// l'encaissement reste possible, `payment_method_id` étant facultatif.
  bool _methodsAvailable = true;
  bool _loadingMethods = true;

  DateTime _receivedAt = DateTime.now();
  bool _saving = false;
  String? _error;

  /// Validation à la volée déclenchée après la première tentative.
  bool _submitted = false;

  @override
  void initState() {
    super.initState();
    final due = widget.dueAmount;
    if (due != null && due > 0) {
      _amountController.text = due.toStringAsFixed(2);
    }
    _loadMethods();
  }

  @override
  void dispose() {
    _amountController.dispose();
    _noteController.dispose();
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

  void _fillAll() {
    final due = widget.dueAmount;
    if (due == null) return;
    _amountController.text = due.toStringAsFixed(2);
  }

  double? get _amount {
    final raw = _amountController.text.trim().replaceAll(',', '.');
    return double.tryParse(raw);
  }

  Future<void> _submit() async {
    setState(() => _submitted = true);
    if (!(_formKey.currentState?.validate() ?? false)) return;

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
                      : 'Régler la vente',
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
    );
  }
}
