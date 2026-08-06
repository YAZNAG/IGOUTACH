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
        _offline = isNetworkError(e);
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
          ? const ListSkeleton(itemCount: 2, lines: 3)
          : _error != null
              ? ErrorView(
                  message: _error!,
                  offline: _offline,
                  onRetry: _load,
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: _buildDetail(_row!),
                ),
    );
  }

  Widget _buildDetail(StockMovementRow row) {
    final sign = widget.isExit ? '−' : '+';
    final color = widget.isExit ? AppTheme.danger : AppTheme.success;

    return ListView(
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
                Text(
                  row.productName ?? '—',
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.navy,
                    height: 1.25,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  row.sku ?? '',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: AppTheme.codeStyle,
                ),
                const SizedBox(height: 14),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Expanded(
                      child: FittedBox(
                        fit: BoxFit.scaleDown,
                        alignment: Alignment.centerLeft,
                        child: Text(
                          '$sign${formatQuantity(row.quantity)}'
                          '${(row.unit ?? '').isNotEmpty ? ' ${row.unit}' : ''}',
                          maxLines: 1,
                          style: AppTheme.amountStyle(
                            fontSize: 26,
                            color: color,
                          ),
                        ),
                      ),
                    ),
                    if ((row.typeName ?? '').isNotEmpty) ...[
                      const SizedBox(width: 10),
                      Flexible(
                        child: StatusBadge(
                          label: row.typeName!,
                          color: AppTheme.sky,
                        ),
                      ),
                    ],
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
            width: 132,
            child: Text(
              label,
              style: const TextStyle(color: AppTheme.textMuted, fontSize: 15),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }
}
