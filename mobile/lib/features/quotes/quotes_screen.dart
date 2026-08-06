import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/quote.dart';
import '../sales/create_sale_screen.dart';
import '../sales/sales_screen.dart' show downloadSalePdf;
import 'quote_detail_screen.dart';

/// Liste des devis : GET /sales?type=quote.
///
/// Un devis se convertit une seule fois en vente (POST /sales/{id}/convert) ;
/// le serveur renvoie `converted` pour les devis déjà transformés.
class QuotesScreen extends StatefulWidget {
  const QuotesScreen({super.key});

  @override
  State<QuotesScreen> createState() => _QuotesScreenState();
}

class _QuotesScreenState extends State<QuotesScreen> {
  final _api = ApiClient.instance;
  final _scrollController = ScrollController();

  final List<QuoteSummary> _quotes = [];
  int _page = 0;
  int _lastPage = 1;
  bool _loading = false;
  bool _firstLoadDone = false;
  String? _error;
  int? _downloadingId;
  int? _convertingId;

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
        _quotes.clear();
        _firstLoadDone = false;
      }
    });

    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/sales',
        queryParameters: {
          'type': 'quote',
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
        _quotes.addAll(
          data.map((e) => QuoteSummary.fromJson(e as Map<String, dynamic>)),
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

  Future<void> _download(QuoteSummary quote) async {
    setState(() => _downloadingId = quote.id);
    await downloadSalePdf(context, quote.id, quote.reference);
    if (mounted) setState(() => _downloadingId = null);
  }

  Future<void> _convert(QuoteSummary quote) async {
    final messenger = ScaffoldMessenger.of(context);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Convertir en vente'),
        content: Text(
          'Créer une vente à partir du devis ${quote.reference} ?\n'
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

    setState(() => _convertingId = quote.id);
    try {
      final res = await _api.dio.post<Map<String, dynamic>>(
        '/sales/${quote.id}/convert',
      );
      final data = res.data!['data'] as Map<String, dynamic>;
      final reference = data['reference'] as String? ?? '';
      if (!mounted) return;
      setState(() => _convertingId = null);
      messenger.showSnackBar(SnackBar(
        content: Text(
          'Vente $reference créée à partir du devis ${quote.reference}.',
        ),
        backgroundColor: AppTheme.success,
      ));
      _load(reset: true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _convertingId = null);
      messenger.showSnackBar(SnackBar(
        content: Text(friendlyError(e)),
        backgroundColor: AppTheme.danger,
      ));
    }
  }

  Future<void> _open(QuoteSummary quote) async {
    final changed = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => QuoteDetailScreen(
          saleId: quote.id,
          reference: quote.reference,
          converted: quote.converted,
        ),
      ),
    );
    if (changed == true) _load(reset: true);
  }

  Future<void> _openCreate() async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => const CreateSaleScreen(type: 'quote'),
      ),
    );
    if (created == true) _load(reset: true);
  }

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('sale.create')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Devis')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openCreate,
        icon: const Icon(Icons.add),
        label: const Text('Nouveau devis'),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (!_firstLoadDone) return const LoadingView();
    if (_error != null && _quotes.isEmpty) {
      return ErrorView(message: _error!, onRetry: () => _load(reset: true));
    }
    if (_quotes.isEmpty) {
      return const EmptyView(
        icon: Icons.request_quote_outlined,
        message: 'Aucun devis pour le moment.',
      );
    }

    return RefreshIndicator(
      onRefresh: () => _load(reset: true),
      child: ListView.builder(
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(top: 8, bottom: 88),
        itemCount: _quotes.length + (_hasMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index >= _quotes.length) {
            return const Padding(
              padding: EdgeInsets.all(16),
              child: Center(child: CircularProgressIndicator()),
            );
          }
          final quote = _quotes[index];
          return _QuoteTile(
            quote: quote,
            downloading: _downloadingId == quote.id,
            converting: _convertingId == quote.id,
            onOpen: () => _open(quote),
            onDownload: () => _download(quote),
            onConvert: quote.isConvertible ? () => _convert(quote) : null,
          );
        },
      ),
    );
  }
}

class _QuoteTile extends StatelessWidget {
  const _QuoteTile({
    required this.quote,
    required this.downloading,
    required this.converting,
    required this.onOpen,
    required this.onDownload,
    this.onConvert,
  });

  final QuoteSummary quote;
  final bool downloading;
  final bool converting;
  final VoidCallback onOpen;
  final VoidCallback onDownload;

  /// `null` quand le devis est annulé ou déjà converti.
  final VoidCallback? onConvert;

  (String, Color) get _badge {
    if (quote.status == 'cancelled') return ('Annulé', AppTheme.danger);
    if (quote.converted) return ('Converti en vente', AppTheme.sky);
    return ('Devis', AppTheme.warning);
  }

  @override
  Widget build(BuildContext context) {
    final (label, color) = _badge;

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onOpen,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          quote.reference,
                          style: const TextStyle(
                            fontFamily: 'monospace',
                            fontWeight: FontWeight.bold,
                            color: AppTheme.navy,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          quote.customer ?? 'Passager',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontSize: 13),
                        ),
                        if (quote.createdAt != null)
                          Text(
                            quote.createdAt!,
                            style: TextStyle(
                              fontSize: 11,
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
                        formatMoney(quote.total),
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 15,
                          color: AppTheme.navy,
                        ),
                      ),
                      Text(
                        '${quote.linesCount} '
                        'ligne${quote.linesCount > 1 ? 's' : ''}',
                        style: TextStyle(
                          fontSize: 11,
                          color: Colors.grey.shade600,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 8),
              StatusBadge(label: label, color: color),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 4,
                children: [
                  OutlinedButton.icon(
                    onPressed: onOpen,
                    style: OutlinedButton.styleFrom(
                      visualDensity: VisualDensity.compact,
                    ),
                    icon: const Icon(Icons.open_in_new, size: 18),
                    label: const Text('Ouvrir'),
                  ),
                  OutlinedButton.icon(
                    onPressed: downloading ? null : onDownload,
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppTheme.sky,
                      side: const BorderSide(color: AppTheme.sky),
                      visualDensity: VisualDensity.compact,
                    ),
                    icon: downloading
                        ? const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.picture_as_pdf_outlined, size: 18),
                    label: const Text('Devis PDF'),
                  ),
                  if (onConvert != null)
                    OutlinedButton.icon(
                      onPressed: converting ? null : onConvert,
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppTheme.success,
                        side: const BorderSide(color: AppTheme.success),
                        visualDensity: VisualDensity.compact,
                      ),
                      icon: converting
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.swap_horiz, size: 18),
                      label: const Text('Convertir en vente'),
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
