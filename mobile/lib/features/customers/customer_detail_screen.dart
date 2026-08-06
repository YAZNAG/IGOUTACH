import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/customer.dart';
import '../shared/customer_account.dart';

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
  bool _offline = false;

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
        _offline = isNetworkError(e);
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final customer = widget.customer;

    return Scaffold(
      appBar: AppBar(title: Text(customer.name)),
      body: RefreshIndicator(
        onRefresh: _loadStatement,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.only(top: 8, bottom: 24),
          children: [
            Card(
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
                            customer.name,
                            style: const TextStyle(
                              fontSize: 19,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.navy,
                              height: 1.25,
                            ),
                          ),
                        ),
                        if (customer.isBlocked) ...[
                          const SizedBox(width: 8),
                          const StatusBadge(
                            label: 'Bloqué',
                            color: AppTheme.danger,
                            icon: Icons.block,
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 10),
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
                child: CreditCells(
                  creditLimit: customer.creditLimit,
                  balance: customer.balance,
                  available: customer.availableCredit,
                  overLimit: customer.isOverLimit,
                ),
              ),
            ),
            const SectionTitle(
              'Relevé de compte',
              padding: EdgeInsets.fromLTRB(16, 20, 16, 4),
            ),
            if (_loading)
              const ListSkeleton(itemCount: 4, hasLeading: true)
            else if (_error != null)
              ErrorView(
                message: _error!,
                offline: _offline,
                onRetry: _loadStatement,
              )
            else if ((_entries ?? []).isEmpty)
              const EmptyView(
                icon: Icons.receipt_long_outlined,
                title: 'Aucune écriture',
                message: 'Ce client n\'a encore ni facture ni règlement.',
              )
            else
              ...(_entries ?? []).map((entry) => StatementTile(entry: entry)),
          ],
        ),
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
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 104,
            child: Text(
              label,
              style: const TextStyle(color: AppTheme.textMuted, fontSize: 15),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
