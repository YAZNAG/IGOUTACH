import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/customer.dart';
import '../shared/customer_account.dart';
import '../shared/payment_sheet.dart';

/// Détail du crédit d'un client : relevé de compte
/// (GET /customers/{id}/statement) et encaissement direct (sans vente).
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
      if (!mounted) return;
      setState(() {
        _name = customer?['name'] as String? ?? _name;
        _balance = (data['balance'] as num?)?.toDouble() ?? 0;
        _creditLimit = (data['credit_limit'] as num?)?.toDouble() ?? 0;
        _isBlocked = data['is_blocked'] == true;
        _entries = entries;
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
