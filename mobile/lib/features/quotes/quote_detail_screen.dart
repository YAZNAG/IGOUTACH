import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/quote.dart';
import '../sales/sales_screen.dart' show downloadSalePdf;

/// Détail d'un devis (GET /sales/{id}) : lignes, totaux, PDF et conversion.
class QuoteDetailScreen extends StatefulWidget {
  const QuoteDetailScreen({
    super.key,
    required this.saleId,
    required this.reference,
    this.converted = false,
  });

  final int saleId;
  final String reference;

  /// Le devis a déjà été converti (bouton de conversion masqué).
  final bool converted;

  @override
  State<QuoteDetailScreen> createState() => _QuoteDetailScreenState();
}

class _QuoteDetailScreenState extends State<QuoteDetailScreen> {
  final _api = ApiClient.instance;

  SaleDetail? _sale;
  bool _loading = true;
  String? _error;
  bool _downloading = false;
  bool _converting = false;

  /// Passe à `true` dès qu'une conversion réussit : la liste doit se recharger.
  bool _changed = false;
  late bool _converted = widget.converted;

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
        '/sales/${widget.saleId}',
      );
      final data = res.data!['data'] as Map<String, dynamic>;
      if (!mounted) return;
      setState(() {
        _sale = SaleDetail.fromJson(data);
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

  Future<void> _download() async {
    setState(() => _downloading = true);
    await downloadSalePdf(context, widget.saleId, widget.reference);
    if (mounted) setState(() => _downloading = false);
  }

  Future<void> _convert() async {
    final messenger = ScaffoldMessenger.of(context);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Convertir en vente'),
        content: Text(
          'Créer une vente à partir du devis ${widget.reference} ?\n'
          'La vente sera créée en brouillon, à confirmer ensuite.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(false),
            child: const Text('Annuler'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(minimumSize: const Size(0, 44)),
            onPressed: () => Navigator.of(dialogContext).pop(true),
            child: const Text('Convertir'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    setState(() => _converting = true);
    try {
      final res = await _api.dio.post<Map<String, dynamic>>(
        '/sales/${widget.saleId}/convert',
      );
      final data = res.data!['data'] as Map<String, dynamic>;
      final reference = data['reference'] as String? ?? '';
      if (!mounted) return;
      setState(() {
        _converting = false;
        _converted = true;
        _changed = true;
      });
      messenger.showSnackBar(SnackBar(
        content: Text('Vente $reference créée à partir du devis.'),
        backgroundColor: AppTheme.success,
      ));
    } catch (e) {
      if (!mounted) return;
      setState(() => _converting = false);
      messenger.showSnackBar(SnackBar(
        content: Text(friendlyError(e)),
        backgroundColor: AppTheme.danger,
      ));
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('sale.create')) {
      return const NotAllowedView();
    }

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) Navigator.of(context).pop(_changed);
      },
      child: Scaffold(
        appBar: AppBar(title: Text(widget.reference)),
        body: _loading
            ? const LoadingView()
            : _error != null
                ? ErrorView(message: _error!, onRetry: _load)
                : _buildDetail(_sale!),
      ),
    );
  }

  Widget _buildDetail(SaleDetail sale) {
    final cancelled = sale.status == 'cancelled';

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(bottom: 32),
        children: [
          _buildHeaderCard(sale, cancelled),
          if (sale.lines.isEmpty)
            const Padding(
              padding: EdgeInsets.only(top: 32),
              child: EmptyView(
                icon: Icons.list_alt_outlined,
                message: 'Ce devis ne contient aucune ligne.',
              ),
            )
          else
            ...sale.lines.map((line) => _LineTile(line: line)),
          _buildTotalsCard(sale),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
            child: Column(
              children: [
                OutlinedButton.icon(
                  onPressed: _downloading ? null : _download,
                  style: OutlinedButton.styleFrom(
                    minimumSize: const Size.fromHeight(48),
                  ),
                  icon: _downloading
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.picture_as_pdf_outlined),
                  label: const Text('Devis PDF'),
                ),
                if (!_converted && !cancelled) ...[
                  const SizedBox(height: 10),
                  FilledButton.icon(
                    onPressed: _converting ? null : _convert,
                    icon: _converting
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Icon(Icons.swap_horiz),
                    label: const Text('Convertir en vente'),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeaderCard(SaleDetail sale, bool cancelled) {
    final (label, color) = cancelled
        ? ('Annulé', AppTheme.danger)
        : _converted
            ? ('Converti en vente', AppTheme.sky)
            : ('Devis', AppTheme.warning);

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
                    sale.reference,
                    style: const TextStyle(
                      fontFamily: 'monospace',
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                      color: AppTheme.navy,
                    ),
                  ),
                ),
                StatusBadge(label: label, color: color),
              ],
            ),
            const SizedBox(height: 10),
            _InfoLine(
              icon: Icons.person_outline,
              label: 'Client',
              value: sale.customerName ?? 'Passager',
            ),
            if ((sale.warehouse ?? '').isNotEmpty)
              _InfoLine(
                icon: Icons.warehouse_outlined,
                label: 'Lieu',
                value: sale.warehouse!,
              ),
            if ((sale.note ?? '').isNotEmpty)
              _InfoLine(
                icon: Icons.notes_outlined,
                label: 'Note',
                value: sale.note!,
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildTotalsCard(SaleDetail sale) {
    return Card(
      color: AppTheme.navy,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            _TotalLine(
              label: 'Sous-total',
              value: formatMoney(sale.subtotal),
            ),
            if (sale.discountPercent > 0)
              _TotalLine(
                label: 'Remise',
                value: '−${sale.discountPercent.toStringAsFixed(2)} %',
              ),
            const Divider(color: Colors.white24, height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Total',
                  style: TextStyle(color: Colors.white, fontSize: 16),
                ),
                Text(
                  formatMoney(sale.total),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
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

class _InfoLine extends StatelessWidget {
  const _InfoLine({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 16, color: Colors.grey.shade600),
          const SizedBox(width: 8),
          Text(
            '$label : ',
            style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _TotalLine extends StatelessWidget {
  const _TotalLine({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: const TextStyle(color: Colors.white70, fontSize: 13),
          ),
          Text(
            value,
            style: const TextStyle(color: Colors.white, fontSize: 13),
          ),
        ],
      ),
    );
  }
}

class _LineTile extends StatelessWidget {
  const _LineTile({required this.line});

  final SaleDetailLine line;

  String get _priceTypeLabel => switch (line.priceTypeCode) {
        'detail' => 'Détail',
        'semi_gros' => 'Demi-gros',
        'gros' => 'Gros',
        _ => '',
      };

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    line.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                  Text(
                    line.sku,
                    style: const TextStyle(
                      fontFamily: 'monospace',
                      fontSize: 12,
                      color: AppTheme.navy,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${formatQuantity(line.quantity)} × '
                    '${formatMoney(line.unitPrice)}',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey.shade600,
                    ),
                  ),
                ],
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  formatMoney(line.lineTotal),
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    color: AppTheme.navy,
                  ),
                ),
                if (_priceTypeLabel.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  StatusBadge(label: _priceTypeLabel, color: AppTheme.sky),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}
