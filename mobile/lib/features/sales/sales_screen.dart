import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/sale.dart';
import '../shared/payment_sheet.dart';
import 'create_sale_screen.dart';

/// Télécharge la facture PDF d'une vente puis l'ouvre.
/// Utilisée par la liste et par l'écran de création.
Future<void> downloadSalePdf(
  BuildContext context,
  int saleId,
  String reference,
) async {
  final messenger = ScaffoldMessenger.of(context);
  try {
    final res = await ApiClient.instance.dio.get<List<int>>(
      '/sales/$saleId/pdf',
      options: Options(responseType: ResponseType.bytes),
    );
    final dir = await getApplicationDocumentsDirectory();
    final safeName = reference.replaceAll(RegExp(r'[^\w\-]'), '_');
    final file = File('${dir.path}${Platform.pathSeparator}$safeName.pdf');
    await file.writeAsBytes(res.data ?? const []);
    await OpenFilex.open(file.path);
  } catch (e) {
    messenger.showSnackBar(
      SnackBar(
        content: Text('Téléchargement impossible : ${friendlyError(e)}'),
        backgroundColor: AppTheme.danger,
      ),
    );
  }
}

/// Liste des ventes (factures) : GET /sales?type=invoice.
class SalesScreen extends StatefulWidget {
  const SalesScreen({super.key});

  @override
  State<SalesScreen> createState() => _SalesScreenState();
}

class _SalesScreenState extends State<SalesScreen> {
  final _api = ApiClient.instance;
  final _scrollController = ScrollController();

  final List<SaleSummary> _sales = [];
  int _page = 0;
  int _lastPage = 1;
  bool _loading = false;
  bool _firstLoadDone = false;
  String? _error;
  int? _downloadingId;
  int? _payingId;

  bool get _hasMore => _page < _lastPage;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    _load(reset: true);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_scrollController.hasClients) return;
    final position = _scrollController.position;
    if (position.pixels > position.maxScrollExtent - 300 &&
        _hasMore &&
        !_loading) {
      _load();
    }
  }

  Future<void> _load({bool reset = false}) async {
    if (_loading) return;
    setState(() {
      _loading = true;
      if (reset) {
        _error = null;
        _page = 0;
        _lastPage = 1;
        _sales.clear();
        _firstLoadDone = false;
      }
    });

    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/sales',
        queryParameters: {
          'type': 'invoice',
          'per_page': 50,
          'page': _page + 1,
        },
      );
      final body = res.data!;
      final data = body['data'] as List<dynamic>? ?? [];
      final meta = body['meta'] as Map<String, dynamic>? ?? {};
      if (!mounted) return;
      setState(() {
        _page = (meta['current_page'] as num?)?.toInt() ?? _page + 1;
        _lastPage = (meta['last_page'] as num?)?.toInt() ?? _page;
        _sales.addAll(
          data.map((e) => SaleSummary.fromJson(e as Map<String, dynamic>)),
        );
        _firstLoadDone = true;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = friendlyError(e);
        _loading = false;
        _firstLoadDone = true;
      });
    }
  }

  Future<void> _download(SaleSummary sale) async {
    setState(() => _downloadingId = sale.id);
    await downloadSalePdf(context, sale.id, sale.reference);
    if (mounted) setState(() => _downloadingId = null);
  }

  /// Ouvre la feuille de règlement pour une vente.
  ///
  /// La liste `/sales` ne porte pas l'identifiant du client (seulement son
  /// nom) : on charge le détail `/sales/{id}` pour obtenir `customer.id`
  /// ainsi que le reste dû à jour.
  Future<void> _openPayment(SaleSummary sale) async {
    setState(() => _payingId = sale.id);
    final messenger = ScaffoldMessenger.of(context);

    try {
      final res =
          await _api.dio.get<Map<String, dynamic>>('/sales/${sale.id}');
      final data = res.data!['data'] as Map<String, dynamic>;
      final customer = data['customer'] as Map<String, dynamic>?;
      if (!mounted) return;
      setState(() => _payingId = null);

      if (customer == null) {
        messenger.showSnackBar(const SnackBar(
          content: Text('Vente au comptoir : aucun client à encaisser.'),
          backgroundColor: AppTheme.danger,
        ));
        return;
      }

      final total = (data['total'] as num?)?.toDouble() ?? sale.total;
      final paid = (data['paid_amount'] as num?)?.toDouble() ?? sale.paidAmount;
      final due = (total - paid) <= 0 ? null : total - paid;

      final saved = await showPaymentSheet(
        context,
        customerId: customer['id'] as int,
        customerName: customer['name'] as String? ?? sale.customer ?? '',
        saleId: sale.id,
        saleReference: sale.reference,
        dueAmount: due,
      );

      if (!mounted) return;
      if (saved) {
        messenger.showSnackBar(const SnackBar(
          content: Text('Règlement enregistré.'),
          backgroundColor: AppTheme.success,
        ));
        _load(reset: true);
      }
    } catch (e) {
      if (!mounted) return;
      setState(() => _payingId = null);
      messenger.showSnackBar(SnackBar(
        content: Text(friendlyError(e)),
        backgroundColor: AppTheme.danger,
      ));
    }
  }

  Future<void> _openCreate() async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => const CreateSaleScreen()),
    );
    if (created == true) {
      _load(reset: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.can('sale.create')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Ventes')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openCreate,
        icon: const Icon(Icons.add),
        label: const Text('Nouvelle vente'),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    final canRecordPayment = context.read<AuthProvider>().can('payment.create');
    if (!_firstLoadDone) return const LoadingView();
    if (_error != null && _sales.isEmpty) {
      return ErrorView(message: _error!, onRetry: () => _load(reset: true));
    }
    if (_sales.isEmpty) {
      return const EmptyView(
        icon: Icons.point_of_sale_outlined,
        message: 'Aucune vente pour le moment.',
      );
    }

    return RefreshIndicator(
      onRefresh: () => _load(reset: true),
      child: ListView.builder(
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(top: 8, bottom: 88),
        itemCount: _sales.length + (_hasMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index >= _sales.length) {
            return const Padding(
              padding: EdgeInsets.all(16),
              child: Center(child: CircularProgressIndicator()),
            );
          }
          final sale = _sales[index];
          return _SaleTile(
            sale: sale,
            downloading: _downloadingId == sale.id,
            onDownload: () => _download(sale),
            paying: _payingId == sale.id,
            onPay: canRecordPayment && sale.isSettleable
                ? () => _openPayment(sale)
                : null,
          );
        },
      ),
    );
  }
}

class _SaleTile extends StatelessWidget {
  const _SaleTile({
    required this.sale,
    required this.downloading,
    required this.onDownload,
    required this.paying,
    this.onPay,
  });

  final SaleSummary sale;
  final bool downloading;
  final VoidCallback onDownload;
  final bool paying;

  /// `null` quand le règlement n'est pas possible (permission, vente au
  /// comptoir, vente non confirmée ou déjà soldée).
  final VoidCallback? onPay;

  (String, Color) get _statusBadge => switch (sale.status) {
        'confirmed' => ('Confirmé', AppTheme.success),
        'cancelled' => ('Annulé', AppTheme.danger),
        _ => ('Brouillon', AppTheme.warning),
      };

  (String, Color)? get _paymentBadge => switch (sale.paymentStatus) {
        'paid' => ('Payé', AppTheme.success),
        'partial' => ('Partiel', AppTheme.warning),
        'unpaid' => ('Non payé', AppTheme.danger),
        _ => null,
      };

  @override
  Widget build(BuildContext context) {
    final (statusLabel, statusColor) = _statusBadge;
    final payment = _paymentBadge;

    return Card(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 12, 8, 12),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    sale.reference,
                    style: const TextStyle(
                      fontFamily: 'monospace',
                      fontWeight: FontWeight.bold,
                      color: AppTheme.navy,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    sale.customer ?? 'Passager',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 13),
                  ),
                  if (sale.createdAt != null)
                    Text(
                      sale.createdAt!,
                      style: TextStyle(
                        fontSize: 11,
                        color: Colors.grey.shade600,
                      ),
                    ),
                  const SizedBox(height: 6),
                  Wrap(
                    spacing: 6,
                    runSpacing: 4,
                    children: [
                      StatusBadge(label: statusLabel, color: statusColor),
                      if (payment != null)
                        StatusBadge(label: payment.$1, color: payment.$2),
                    ],
                  ),
                  if (onPay != null) ...[
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        OutlinedButton.icon(
                          onPressed: paying ? null : onPay,
                          style: OutlinedButton.styleFrom(
                            foregroundColor: AppTheme.success,
                            side: const BorderSide(color: AppTheme.success),
                            visualDensity: VisualDensity.compact,
                          ),
                          icon: paying
                              ? const SizedBox(
                                  width: 16,
                                  height: 16,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                  ),
                                )
                              : const Icon(Icons.payments_outlined, size: 18),
                          label: const Text('Régler'),
                        ),
                        const SizedBox(width: 8),
                        Flexible(
                          child: Text(
                            'Reste ${formatMoney(sale.dueAmount)}',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey.shade600,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  formatMoney(sale.total),
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 15,
                    color: AppTheme.navy,
                  ),
                ),
                Text(
                  '${sale.linesCount} ligne${sale.linesCount > 1 ? 's' : ''}',
                  style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                ),
                downloading
                    ? const Padding(
                        padding: EdgeInsets.all(12),
                        child: SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      )
                    : IconButton(
                        icon: const Icon(
                          Icons.picture_as_pdf_outlined,
                          color: AppTheme.sky,
                        ),
                        tooltip: 'Télécharger la facture',
                        onPressed: onDownload,
                      ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
