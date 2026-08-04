import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/aging_row.dart';

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
        _loading = false;
      });
    }
  }

  double get _totalDue =>
      (_rows ?? []).fold(0, (sum, r) => sum + r.totalDue);

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('payment.view')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Crédits clients')),
      body: _loading
          ? const LoadingView()
          : _error != null
              ? ErrorView(message: _error!, onRetry: _load)
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
                            message: 'Aucun crédit client en cours.',
                          ),
                        )
                      else
                        ...(_rows ?? []).map((row) => _AgingCard(row: row)),
                    ],
                  ),
                ),
    );
  }

  Widget _buildHeader() {
    return Card(
      color: AppTheme.navy,
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Total dû par les clients',
              style: TextStyle(color: Colors.white70, fontSize: 13),
            ),
            const SizedBox(height: 6),
            Text(
              formatMoney(_totalDue),
              style: const TextStyle(
                color: Colors.white,
                fontSize: 26,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              '${(_rows ?? []).length} client${(_rows ?? []).length > 1 ? 's' : ''} avec encours',
              style: const TextStyle(color: Colors.white70, fontSize: 12),
            ),
          ],
        ),
      ),
    );
  }
}

class _AgingCard extends StatelessWidget {
  const _AgingCard({required this.row});

  final AgingRow row;

  @override
  Widget build(BuildContext context) {
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
                    row.customer ?? 'Client inconnu',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 15,
                    ),
                  ),
                ),
                Text(
                  formatMoney(row.totalDue),
                  style: const TextStyle(
                    color: AppTheme.danger,
                    fontWeight: FontWeight.bold,
                    fontSize: 15,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Wrap(
              spacing: 6,
              runSpacing: 6,
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
    final displayColor = active ? color : Colors.grey.shade400;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
      decoration: BoxDecoration(
        color: displayColor.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        children: [
          Text(
            label,
            style: TextStyle(fontSize: 10, color: displayColor),
          ),
          Text(
            formatMoney(amount),
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: displayColor,
            ),
          ),
        ],
      ),
    );
  }
}
