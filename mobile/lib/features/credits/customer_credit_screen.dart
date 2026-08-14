import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/customer.dart';
import '../shared/customer_account.dart';
import '../shared/payment_sheet.dart';

/// Facture encore due (GET /customers/{id}/open-invoices).
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

/// Détail du crédit d'un client : relevé de compte
/// (GET /customers/{id}/statement), factures dues réglables une à une, et
/// encaissement direct sur l'encours.
class CustomerCreditScreen extends StatefulWidget {
  const CustomerCreditScreen({
    super.key,
    required this.customerId,
    required this.customerName,
  });

  final int customerId;
  final String customerName;

  @override
  State<CustomerCreditScreen> createState() => _CustomerCreditScreenState();
}

class _CustomerCreditScreenState extends State<CustomerCreditScreen> {
  final _api = ApiClient.instance;

  List<StatementEntry> _entries = [];

  /// Factures encore dues, réglables une par une.
  List<_FactureDue> _facturesDues = [];
  double _balance = 0;
  double _creditLimit = 0;
  bool _isBlocked = false;
  String _name = '';

  bool _loading = true;
  String? _error;
  bool _offline = false;

  /// `true` si un encaissement a été enregistré : l'écran appelant
  /// (balance âgée) doit alors se recharger.
  bool _changed = false;

  @override
  void initState() {
    super.initState();
    _name = widget.customerName;
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/customers/${widget.customerId}/statement',
      );
      final data = res.data!['data'] as Map<String, dynamic>;
      final customer = data['customer'] as Map<String, dynamic>?;
      final entries = (data['entries'] as List<dynamic>? ?? [])
          .map((e) => StatementEntry.fromJson(e as Map<String, dynamic>))
          .toList();
      // Le relevé dit ce que le client doit ; les factures ouvertes disent
      // sur quoi. Un refus ici (droit d'encaisser absent) ne doit pas priver
      // du relevé lui-même.
      List<_FactureDue> dues = [];
      try {
        final resFactures = await _api.dio.get<Map<String, dynamic>>(
          '/customers/${widget.customerId}/open-invoices',
        );
        dues = (resFactures.data!['data'] as List<dynamic>? ?? [])
            .map((e) => _FactureDue.fromJson(e as Map<String, dynamic>))
            .toList();
      } catch (_) {
        dues = [];
      }

      if (!mounted) return;
      setState(() {
        _name = customer?['name'] as String? ?? _name;
        _balance = (data['balance'] as num?)?.toDouble() ?? 0;
        _creditLimit = (data['credit_limit'] as num?)?.toDouble() ?? 0;
        _isBlocked = data['is_blocked'] == true;
        _entries = entries;
        _facturesDues = dues;
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

  Future<void> _collect() async {
    final messenger = ScaffoldMessenger.of(context);
    final saved = await showPaymentSheet(
      context,
      customerId: widget.customerId,
      customerName: _name,
      dueAmount: _balance > 0 ? _balance : null,
    );
    if (!mounted || !saved) return;
    _changed = true;
    showSuccessSnack(messenger, 'Encaissement enregistré.');
    _load();
  }

  /// Règle une facture précise, plutôt que l'encours global.
  Future<void> _reglerFacture(_FactureDue facture) async {
    final messenger = ScaffoldMessenger.of(context);
    final saved = await showPaymentSheet(
      context,
      customerId: widget.customerId,
      customerName: _name,
      saleId: facture.id,
      saleReference: facture.reference,
      dueAmount: facture.remaining,
    );
    if (!mounted || !saved) return;
    _changed = true;
    showSuccessSnack(messenger, 'Facture ${facture.reference} réglée.');
    _load();
  }

  @override
  Widget build(BuildContext context) {
    final canCollect = context.watch<AuthProvider>().can('payment.create');

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) Navigator.of(context).pop(_changed);
      },
      child: Scaffold(
        appBar: AppBar(title: Text(_name)),
        floatingActionButton: canCollect && !_loading && _error == null
            ? FloatingActionButton.extended(
                onPressed: _collect,
                icon: const Icon(Icons.payments_outlined),
                label: const Text('Encaisser'),
              )
            : null,
        body: _loading
            ? const ListSkeleton(itemCount: 5, hasLeading: true)
            : _error != null
                ? ErrorView(
                    message: _error!,
                    offline: _offline,
                    onRetry: _load,
                  )
                : RefreshIndicator(
                    onRefresh: _load,
                    child: ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.only(top: 8, bottom: 96),
                      children: [
                        _buildSummary(),
                        if (canCollect && _facturesDues.isNotEmpty) ...[
                          const SectionTitle(
                            'Factures à régler',
                            padding: EdgeInsets.fromLTRB(16, 20, 16, 4),
                          ),
                          ..._facturesDues.map(_carteFacture),
                        ],
                        const SectionTitle(
                          'Relevé de compte',
                          padding: EdgeInsets.fromLTRB(16, 20, 16, 4),
                        ),
                        if (_entries.isEmpty)
                          const Padding(
                            padding: EdgeInsets.only(top: 32),
                            child: EmptyView(
                              icon: Icons.receipt_long_outlined,
                              title: 'Aucune écriture',
                              message: 'Ce client n\'a encore ni facture '
                                  'ni règlement.',
                            ),
                          )
                        else
                          ..._entries.map((e) => StatementTile(entry: e)),
                      ],
                    ),
                  ),
      ),
    );
  }

  /// Une facture due, avec son bouton de règlement.
  ///
  /// Régler facture par facture plutôt que l'encours global permet de dire
  /// exactement ce que le versement solde — c'est ce que le client demande
  /// quand il paie « la facture de mardi ».
  Widget _carteFacture(_FactureDue facture) {
    return Card(
      child: ListTile(
        leading: const Icon(Icons.receipt_long_outlined, color: AppTheme.sky),
        title: Text(
          facture.reference,
          style: const TextStyle(
            fontFamily: 'monospace',
            fontWeight: FontWeight.w600,
            fontSize: 14,
          ),
        ),
        subtitle: Text(
          'Reste dû ${formatMoney(facture.remaining)}'
          '${facture.date == null ? '' : ' · ${facture.date}'}',
          style: const TextStyle(fontSize: 12.5),
        ),
        trailing: FilledButton(
          onPressed: () => _reglerFacture(facture),
          style: FilledButton.styleFrom(visualDensity: VisualDensity.compact),
          child: const Text('Régler'),
        ),
      ),
    );
  }

  Widget _buildSummary() {
    final available = _creditLimit - _balance;
    final overLimit = _creditLimit > 0 && _balance > _creditLimit;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(
                    _name,
                    style: const TextStyle(
                      fontSize: 19,
                      fontWeight: FontWeight.bold,
                      color: AppTheme.navy,
                      height: 1.25,
                    ),
                  ),
                ),
                if (_isBlocked) ...[
                  const SizedBox(width: 8),
                  const StatusBadge(
                    label: 'Bloqué',
                    color: AppTheme.danger,
                    icon: Icons.block,
                  ),
                ],
              ],
            ),
            const SizedBox(height: 14),
            CreditCells(
              creditLimit: _creditLimit,
              balance: _balance,
              available: available,
              overLimit: overLimit,
            ),
          ],
        ),
      ),
    );
  }
}
