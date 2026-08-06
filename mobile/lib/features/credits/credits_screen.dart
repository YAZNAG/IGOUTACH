import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/aging_row.dart';
import 'customer_credit_screen.dart';

/// Crédits clients : balance âgée (GET /customers-aging).
class CreditsScreen extends StatefulWidget {
  const CreditsScreen({super.key});

  @override
  State<CreditsScreen> createState() => _CreditsScreenState();
}

class _CreditsScreenState extends State<CreditsScreen> {
  final _api = ApiClient.instance;

  List<AgingRow>? _rows;
  bool _loading = true;
  String? _error;
  bool _offline = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res =
          await _api.dio.get<Map<String, dynamic>>('/customers-aging');
      final data = res.data!['data'] as List<dynamic>? ?? [];
      final rows = data
          .map((e) => AgingRow.fromJson(e as Map<String, dynamic>))
          .toList()
        ..sort((a, b) => b.totalDue.compareTo(a.totalDue));
      if (!mounted) return;
      setState(() {
        _rows = rows;
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

  double get _totalDue =>
      (_rows ?? []).fold(0, (sum, r) => sum + r.totalDue);

  Future<void> _openCustomer(AgingRow row) async {
    final changed = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => CustomerCreditScreen(
          customerId: row.customerId!,
          customerName: row.customer ?? 'Client',
        ),
      ),
    );
    // Un encaissement a été saisi : la balance âgée n'est plus à jour.
    if (changed == true) _load();
  }

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('payment.view')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Crédits clients')),
      body: _loading
          ? const ListSkeleton(itemCount: 5, lines: 2)
          : _error != null
              ? ErrorView(message: _error!, offline: _offline, onRetry: _load)
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.only(bottom: 24),
                    children: [
                      _buildHeader(),
                      if ((_rows ?? []).isEmpty)
                        const Padding(
                          padding: EdgeInsets.only(top: 48),
                          child: EmptyView(
                            icon: Icons.credit_score,
                            title: 'Aucun crédit en cours',
                            message: 'Tous les clients sont à jour : '
                                'rien n\'est dû aujourd\'hui.',
                          ),
                        )
                      else
                        ...(_rows ?? []).map(
                          (row) => _AgingCard(
                            row: row,
                            onTap: row.customerId == null
                                ? null
                                : () => _openCustomer(row),
                          ),
                        ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildHeader() {
    final count = (_rows ?? []).length;

    return Container(
      margin: const EdgeInsets.fromLTRB(12, 12, 12, 6),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppTheme.navy, AppTheme.navyDeep],
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Total dû par les clients',
            style: TextStyle(color: Colors.white70, fontSize: 15),
          ),
          const SizedBox(height: 6),
          FittedBox(
            fit: BoxFit.scaleDown,
            alignment: Alignment.centerLeft,
            child: Text(
              formatMoney(_totalDue),
              maxLines: 1,
              style: AppTheme.amountStyle(fontSize: 30, color: Colors.white),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            '$count client${count > 1 ? 's' : ''} avec encours',
            style: const TextStyle(color: Colors.white70, fontSize: 14),
          ),
        ],
      ),
    );
  }
}

class _AgingCard extends StatelessWidget {
  const _AgingCard({required this.row, this.onTap});

  final AgingRow row;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Expanded(
                    child: Text(
                      row.customer ?? 'Client inconnu',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 16,
                        height: 1.25,
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  AmountText(
                    formatMoney(row.totalDue),
                    fontSize: 17,
                    color: AppTheme.danger,
                  ),
                  if (onTap != null)
                    const Icon(
                      Icons.chevron_right,
                      color: AppTheme.textMuted,
                    ),
                ],
              ),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _Bucket(label: '0-30 j', amount: row.bucket0to30,
                      color: AppTheme.success),
                  _Bucket(label: '31-60 j', amount: row.bucket31to60,
                      color: AppTheme.warning),
                  _Bucket(label: '61-90 j', amount: row.bucket61to90,
                      color: const Color(0xFFEA580C)),
                  _Bucket(label: '+90 j', amount: row.bucketOver90,
                      color: AppTheme.danger),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Bucket extends StatelessWidget {
  const _Bucket({
    required this.label,
    required this.amount,
    required this.color,
  });

  final String label;
  final double amount;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final active = amount > 0;
    final displayColor = active ? color : AppTheme.textFaint;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: displayColor.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: displayColor.withValues(alpha: 0.22)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 13,
              height: 1.2,
              color: displayColor,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            formatMoney(amount),
            maxLines: 1,
            style: AppTheme.amountStyle(fontSize: 14, color: displayColor),
          ),
        ],
      ),
    );
  }
}
