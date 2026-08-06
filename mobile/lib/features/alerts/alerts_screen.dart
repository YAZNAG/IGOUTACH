import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/alert.dart';
import '../shared/status_badges.dart';

/// Alertes consolidées du pilotage : GET /alerts.
///
/// Le serveur renvoie sept alertes fixes (rupture/sous seuil, tarifs sous
/// plancher, crédit client dépassé, transferts en retard, factures échues,
/// inventaires brouillon, charges en attente). La route est protégée par
/// `report.consolidated` côté API : c'est cette permission qui est vérifiée
/// ici, sans quoi l'écran s'ouvrirait sur un 403.
class AlertsScreen extends StatefulWidget {
  const AlertsScreen({super.key});

  @override
  State<AlertsScreen> createState() => _AlertsScreenState();
}

class _AlertsScreenState extends State<AlertsScreen> {
  final _api = ApiClient.instance;

  List<AlertItem> _alerts = const [];
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
      final res = await _api.dio.get<Map<String, dynamic>>('/alerts');
      final data = res.data!['data'] as List<dynamic>? ?? const [];
      if (!mounted) return;
      setState(() {
        _alerts = data
            .map((e) => AlertItem.fromJson(e as Map<String, dynamic>))
            .toList();
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

  /// Icône associée à chaque alerte connue du serveur.
  IconData _iconFor(String key) => switch (key) {
        'low_stock' => Icons.production_quantity_limits_outlined,
        'below_floor' => Icons.price_change_outlined,
        'over_credit' => Icons.credit_card_off_outlined,
        'late_transfers' => Icons.local_shipping_outlined,
        'overdue_invoices' => Icons.receipt_long_outlined,
        'draft_inventories' => Icons.fact_check_outlined,
        'pending_expenses' => Icons.pending_actions_outlined,
        _ => Icons.warning_amber_outlined,
      };

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('report.consolidated')) {
      return const NotAllowedView();
    }

    final active = _alerts.where((a) => !a.isClear).length;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Alertes'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Actualiser',
            onPressed: _loading ? null : _load,
          ),
        ],
      ),
      body: Column(
        children: [
          if (!_loading && _error == null && _alerts.isNotEmpty)
            _buildHeader(active),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildHeader(int active) {
    return Card(
      color: active > 0 ? AppTheme.navy : AppTheme.success,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        child: Row(
          children: [
            Icon(
              active > 0 ? Icons.warning_amber_rounded : Icons.verified_outlined,
              color: Colors.white,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                active > 0
                    ? '$active alerte${active > 1 ? 's' : ''} '
                        'sur ${_alerts.length} nécessite'
                        '${active > 1 ? 'nt' : ''} votre attention.'
                    : 'Aucune alerte active. Tout est au vert.',
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w600,
                  fontSize: 13,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) return const LoadingView();
    if (_error != null) return ErrorView(message: _error!, onRetry: _load);
    if (_alerts.isEmpty) {
      return const EmptyView(
        icon: Icons.notifications_none,
        message: 'Aucune alerte à afficher.',
      );
    }

    // Les alertes actives d'abord, puis celles au vert.
    final sorted = [..._alerts]
      ..sort((a, b) => b.count.compareTo(a.count));

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(top: 4, bottom: 24),
        itemCount: sorted.length,
        itemBuilder: (context, index) {
          final alert = sorted[index];
          final color = alertSeverityColor(alert.severity);

          return Card(
            child: ListTile(
              leading: Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(_iconFor(alert.key), color: color),
              ),
              title: Text(
                alert.label,
                style: const TextStyle(
                  fontWeight: FontWeight.w600,
                  fontSize: 14,
                ),
              ),
              subtitle: Padding(
                padding: const EdgeInsets.only(top: 4),
                child: StatusBadge(
                  label: alert.isClear ? 'Aucun cas' : 'À traiter',
                  color: color,
                ),
              ),
              trailing: Text(
                formatQuantity(alert.count),
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: color,
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
