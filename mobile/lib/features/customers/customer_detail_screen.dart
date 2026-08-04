import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/customer.dart';

/// Fiche client : coordonnées, crédit (plafond / encours / disponible)
/// et relevé (GET /customers/{id}/statement).
class CustomerDetailScreen extends StatefulWidget {
  const CustomerDetailScreen({super.key, required this.customer});

  final Customer customer;

  @override
  State<CustomerDetailScreen> createState() => _CustomerDetailScreenState();
}

class _CustomerDetailScreenState extends State<CustomerDetailScreen> {
  final _api = ApiClient.instance;

  List<StatementEntry>? _entries;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadStatement();
  }

  Future<void> _loadStatement() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/customers/${widget.customer.id}/statement',
      );
      final data = res.data!['data'] as Map<String, dynamic>;
      final entries = (data['entries'] as List<dynamic>? ?? [])
          .map((e) => StatementEntry.fromJson(e as Map<String, dynamic>))
          .toList();
      if (!mounted) return;
      setState(() {
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

  @override
  Widget build(BuildContext context) {
    final customer = widget.customer;

    return Scaffold(
      appBar: AppBar(title: Text(customer.name)),
      body: ListView(
        padding: const EdgeInsets.only(bottom: 24),
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          customer.name,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: AppTheme.navy,
                          ),
                        ),
                      ),
                      if (customer.isBlocked)
                        const StatusBadge(
                          label: 'Bloqué',
                          color: AppTheme.danger,
                        ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  _InfoRow(label: 'Code', value: customer.code),
                  if ((customer.phone ?? '').isNotEmpty)
                    _InfoRow(label: 'Téléphone', value: customer.phone!),
                  if ((customer.city ?? '').isNotEmpty)
                    _InfoRow(label: 'Ville', value: customer.city!),
                  if ((customer.email ?? '').isNotEmpty)
                    _InfoRow(label: 'E-mail', value: customer.email!),
                  if ((customer.address ?? '').isNotEmpty)
                    _InfoRow(label: 'Adresse', value: customer.address!),
                ],
              ),
            ),
          ),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Expanded(
                    child: _CreditCell(
                      label: 'Plafond',
                      value: formatMoney(customer.creditLimit),
                      color: AppTheme.navy,
                    ),
                  ),
                  Expanded(
                    child: _CreditCell(
                      label: 'Encours',
                      value: formatMoney(customer.balance),
                      color: customer.isOverLimit
                          ? AppTheme.danger
                          : AppTheme.warning,
                    ),
                  ),
                  Expanded(
                    child: _CreditCell(
                      label: 'Disponible',
                      value: formatMoney(customer.availableCredit),
                      color: AppTheme.success,
                    ),
                  ),
                ],
              ),
            ),
          ),
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
          if (_loading)
            const Padding(
              padding: EdgeInsets.all(24),
              child: Center(child: CircularProgressIndicator()),
            )
          else if (_error != null)
            ErrorView(message: _error!, onRetry: _loadStatement)
          else if ((_entries ?? []).isEmpty)
            const EmptyView(
              icon: Icons.receipt_long_outlined,
              message: 'Aucune écriture pour ce client.',
            )
          else
            ...(_entries ?? []).map((entry) => _StatementTile(entry: entry)),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 90,
            child: Text(
              label,
              style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _CreditCell extends StatelessWidget {
  const _CreditCell({
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
