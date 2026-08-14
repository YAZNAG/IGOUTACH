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
import '../shared/period_export.dart';

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
    showErrorSnack(
      messenger,
      'Téléchargement impossible : ${friendlyError(e)}',
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
  Periode _periode = Periode.moisEnCours();
  final _scrollController = ScrollController();

  final List<SaleSummary> _sales = [];
  int _page = 0;
  int _lastPage = 1;
  bool _loading = false;
  bool _firstLoadDone = false;
  String? _error;
  bool _offline = false;
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
          'date_from': _periode.duIso,
          'date_to': _periode.auIso,
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
        _offline = isNetworkError(e);
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
        showErrorSnack(
          messenger,
          'Vente au comptoir : aucun client à encaisser.',
        );
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
        showSuccessSnack(messenger, 'Règlement enregistré.');
        _load(reset: true);
      }
    } catch (e) {
      if (!mounted) return;
      setState(() => _payingId = null);
      showErrorSnack(messenger, friendlyError(e));
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

  /// Droit d'effacer une vente annulée, lu une fois par construction.
  bool _peutSupprimer = false;

  /// Supprime définitivement une vente annulée.
  Future<void> _supprimer(SaleSummary sale) async {
    final confirme = await confirmAction(
      context,
      icon: Icons.delete_outline,
      title: 'Supprimer la vente',
      message: '${sale.reference}\n\n'
          'Elle disparaîtra de l\'historique. L\'opération est refusée si un '
          'règlement ou un mouvement de stock s\'y rattache.',
      confirmLabel: 'Supprimer',
      confirmColor: AppTheme.danger,
    );
    if (!confirme || !mounted) return;

    final messenger = ScaffoldMessenger.of(context);
    try {
      await _api.dio.delete<void>('/sales/${sale.id}');
      if (!mounted) return;
      showSuccessSnack(messenger, 'Vente supprimée.');
      _load(reset: true);
    } catch (e) {
      if (!mounted) return;
      showErrorSnack(messenger, friendlyError(e));
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.can('sale.create')) {
      return const NotAllowedView();
    }
    _peutSupprimer = auth.can('sale.cancel');

    return Scaffold(
      appBar: AppBar(title: const Text('Ventes')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openCreate,
        icon: const Icon(Icons.add),
        label: const Text('Nouvelle vente'),
      ),
      body: Column(
        children: [
          // La liste et l'export portent sur la même période : le document
          // reprend exactement ce que l'écran affiche.
          PeriodBar(
            periode: _periode,
            journal: 'sales',
            onChanged: (p) {
              setState(() => _periode = p);
              _load(reset: true);
            },
          ),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildBody() {
    final canRecordPayment = context.read<AuthProvider>().can('payment.create');
    if (!_firstLoadDone) return const ListSkeleton(itemCount: 6, lines: 3);
    if (_error != null && _sales.isEmpty) {
      return ErrorView(
        message: _error!,
        offline: _offline,
        onRetry: () => _load(reset: true),
      );
    }
    if (_sales.isEmpty) {
      return EmptyView(
        icon: Icons.point_of_sale_outlined,
        title: 'Aucune vente',
        message: 'Les factures que vous établirez apparaîtront ici.',
        actionLabel: 'Nouvelle vente',
        onAction: _openCreate,
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
          if (index >= _sales.length) return const SkeletonCard(lines: 3);
          final sale = _sales[index];
          return _SaleTile(
            sale: sale,
            downloading: _downloadingId == sale.id,
            onDownload: () => _download(sale),
            paying: _payingId == sale.id,
            onPay: canRecordPayment && sale.isSettleable
                ? () => _openPayment(sale)
                : null,
            // Seules les ventes annulées se suppriment ; le serveur refuse en
            // plus celles qui ont laissé une trace comptable ou de stock.
            onSupprimer: _peutSupprimer && sale.status == 'cancelled'
                ? () => _supprimer(sale)
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
    this.onSupprimer,
  });

  final SaleSummary sale;
  final bool downloading;
  final VoidCallback onDownload;
  final bool paying;

  /// `null` quand le règlement n'est pas possible (permission, vente au
  /// comptoir, vente non confirmée ou déjà soldée).
  final VoidCallback? onPay;

  /// `null` sauf sur une vente annulée, pour qui a le droit d'annuler.
  final VoidCallback? onSupprimer;

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
        padding: const EdgeInsets.fromLTRB(16, 14, 10, 12),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        sale.reference,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: AppTheme.codeStyle.copyWith(fontSize: 15),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        sale.customer ?? 'Client de passage',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                          height: 1.2,
                        ),
                      ),
                      if (sale.createdAt != null) ...[
                        const SizedBox(height: 2),
                        Text(
                          sale.createdAt!,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 13,
                            color: AppTheme.textMuted,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    AmountText(
                      formatMoney(sale.total),
                      fontSize: 17,
                      label: '${sale.linesCount} ligne'
                          '${sale.linesCount > 1 ? 's' : ''}',
                    ),
                    downloading
                        ? const SizedBox(
                            width: AppTheme.minTapTarget,
                            height: AppTheme.minTapTarget,
                            child: Center(
                              child: SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                ),
                              ),
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
            Wrap(
              spacing: 8,
              runSpacing: 6,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                StatusBadge(label: statusLabel, color: statusColor),
                if (payment != null)
                  StatusBadge(label: payment.$1, color: payment.$2),
              ],
            ),
            if (onSupprimer != null) ...[
              const SizedBox(height: 10),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: onSupprimer,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppTheme.danger,
                    side: const BorderSide(color: AppTheme.danger),
                  ),
                  icon: const Icon(Icons.delete_outline, size: 18),
                  label: const Text('Supprimer'),
                ),
              ),
            ],
            if (onPay != null) ...[
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: paying ? null : onPay,
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppTheme.success,
                        side: const BorderSide(color: AppTheme.success),
                      ),
                      icon: paying
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.payments_outlined, size: 20),
                      label: const Text('Régler'),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Flexible(
                    child: Text(
                      'Reste ${formatMoney(sale.dueAmount)}',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.right,
                      style: AppTheme.amountStyle(
                        fontSize: 14,
                        color: AppTheme.danger,
                        weight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}
