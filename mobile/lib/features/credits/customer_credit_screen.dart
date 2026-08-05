import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/customer.dart';
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
        _loading = false;
      });
    }
  }

  Future<void> _collect() async {
    final saved = await showPaymentSheet(
      context,
      customerId: widget.customerId,
      customerName: _name,
      dueAmount: _balance > 0 ? _balance : null,
    );
    if (!mounted || !saved) return;
    _changed = true;
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
      content: Text('Encaissement enregistré.'),
      backgroundColor: AppTheme.success,
    ));
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
            ? const LoadingView()
            : _error != null
                ? ErrorView(message: _error!, onRetry: _load)
                : RefreshIndicator(
                    onRefresh: _load,
                    child: ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.only(bottom: 96),
                      children: [
                        _buildSummary(),
                        const Padding(
                          padding: EdgeInsets.fromLTRB(16, 16, 16, 4),
                          child: Text(
                            'Relevé de compte',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.navy,
                            ),
                          ),
                        ),
                        if (_entries.isEmpty)
                          const Padding(
                            padding: EdgeInsets.only(top: 32),
                            child: EmptyView(
                              icon: Icons.receipt_long_outlined,
                              message: 'Aucune écriture pour ce client.',
                            ),
                          )
                        else
                          ..._entries.map((e) => _StatementTile(entry: e)),
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
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    _name,
                    style: const TextStyle(
                      fontSize: 17,
                      fontWeight: FontWeight.bold,
                      color: AppTheme.navy,
                    ),
                  ),
                ),
                if (_isBlocked)
                  const StatusBadge(label: 'Bloqué', color: AppTheme.danger),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _Cell(
                    label: 'Plafond',
                    value: formatMoney(_creditLimit),
                    color: AppTheme.navy,
                  ),
                ),
                Expanded(
                  child: _Cell(
                    label: 'Encours',
                    value: formatMoney(_balance),
                    color: overLimit ? AppTheme.danger : AppTheme.warning,
                  ),
                ),
                Expanded(
                  child: _Cell(
                    label: 'Disponible',
                    value: formatMoney(available < 0 ? 0 : available),
                    color: AppTheme.success,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _Cell extends StatelessWidget {
  const _Cell({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(
          label,
          style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
        ),
        const SizedBox(height: 4),
        Text(
          value,
          textAlign: TextAlign.center,
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 13,
            color: color,
          ),
        ),
      ],
    );
  }
}

class _StatementTile extends StatelessWidget {
  const _StatementTile({required this.entry});

  final StatementEntry entry;

  @override
  Widget build(BuildContext context) {
    // `invoice` augmente l'encours ; `payment` le diminue.
    final isDebit = entry.type == 'invoice';
    final color = isDebit ? AppTheme.danger : AppTheme.success;
    final label = switch (entry.type) {
      'invoice' => 'Facture',
      'payment' => 'Règlement',
      'adjustment' => 'Ajustement',
      _ => entry.type,
    };

    return Card(
      child: ListTile(
        dense: true,
        leading: Icon(
          isDebit ? Icons.call_made : Icons.call_received,
          color: color,
        ),
        title: Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Text(
          [
            if (entry.date != null) entry.date!,
            if ((entry.note ?? '').isNotEmpty) entry.note!,
          ].join(' · '),
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 12),
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(
              formatMoney(entry.amount),
              style: TextStyle(fontWeight: FontWeight.bold, color: color),
            ),
            Text(
              'Solde : ${formatMoney(entry.balanceAfter)}',
              style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
            ),
          ],
        ),
      ),
    );
  }
}
