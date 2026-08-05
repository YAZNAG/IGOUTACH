import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/stock_movement.dart';

/// Détail d'un mouvement de stock : GET /stock/entries/{id} ou
/// GET /stock/exits/{id} selon [basePath].
class MovementDetailScreen extends StatefulWidget {
  const MovementDetailScreen({
    super.key,
    required this.movementId,
    required this.basePath,
    required this.isExit,
  });

  final int movementId;

  /// `/stock/entries` ou `/stock/exits`.
  final String basePath;
  final bool isExit;

  @override
  State<MovementDetailScreen> createState() => _MovementDetailScreenState();
}

class _MovementDetailScreenState extends State<MovementDetailScreen> {
  final _api = ApiClient.instance;

  StockMovementRow? _row;
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
      final res = await _api.dio.get<Map<String, dynamic>>(
        '${widget.basePath}/${widget.movementId}',
      );
      final row = StockMovementRow.fromJson(
        res.data!['data'] as Map<String, dynamic>,
      );
      if (!mounted) return;
      setState(() {
        _row = row;
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
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.isExit ? 'Détail sortie' : 'Détail entrée'),
      ),
      body: _loading
          ? const LoadingView()
          : _error != null
              ? ErrorView(message: _error!, onRetry: _load)
              : _buildDetail(_row!),
    );
  }

  Widget _buildDetail(StockMovementRow row) {
    final sign = widget.isExit ? '−' : '+';
    final color = widget.isExit ? AppTheme.danger : AppTheme.success;

    return ListView(
      padding: const EdgeInsets.only(bottom: 24),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  row.productName ?? '—',
                  style: const TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.navy,
                  ),
                ),
                Text(
                  row.sku ?? '',
                  style: const TextStyle(
                    fontFamily: 'monospace',
                    fontSize: 12,
                    color: AppTheme.navy,
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Text(
                      '$sign${formatQuantity(row.quantity)}'
                      '${(row.unit ?? '').isNotEmpty ? ' ${row.unit}' : ''}',
                      style: TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        color: color,
                      ),
                    ),
                    const Spacer(),
                    if ((row.typeName ?? '').isNotEmpty)
                      StatusBadge(label: row.typeName!, color: AppTheme.sky),
                  ],
                ),
              ],
            ),
          ),
        ),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                _Row(label: 'N° mouvement', value: '${row.id}'),
                _Row(label: 'Date', value: row.date ?? '—'),
                _Row(
                  label: 'Document source',
                  value: row.sourceLabel ?? '—',
                ),
                _Row(
                  label: 'Lieu',
                  value: [
                    row.warehouseCode ?? '',
                    row.warehouseName ?? '',
                  ].where((s) => s.isNotEmpty).join(' — '),
                ),
                _Row(
                  label: widget.isExit ? 'CMUP' : 'Prix unitaire',
                  value: formatMoney(row.unitCost),
                ),
                _Row(label: 'Valeur', value: formatMoney(row.lineValue)),
                _Row(
                  label: 'Solde après',
                  value: formatQuantity(row.balanceAfter),
                ),
                if ((row.author ?? '').isNotEmpty)
                  _Row(label: 'Auteur', value: row.author!),
                if ((row.note ?? '').isNotEmpty)
                  _Row(label: 'Note', value: row.note!),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.label, required this.value});

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
            width: 120,
            child: Text(
              label,
              style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }
}
