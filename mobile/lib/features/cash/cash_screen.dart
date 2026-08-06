import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/cash_session.dart';
import '../shared/warehouse_scope.dart';

/// Caisse : ouverture (fonds initial), session en cours, clôture avec écart
/// et historique des sessions du lieu.
///
/// Endpoints : GET /cash-sessions/current, POST /cash-sessions/open,
/// POST /cash-sessions/{id}/close, GET /cash-sessions.
class CashScreen extends StatefulWidget {
  const CashScreen({super.key});

  @override
  State<CashScreen> createState() => _CashScreenState();
}

class _CashScreenState extends State<CashScreen> {
  final _api = ApiClient.instance;

  WarehouseScope? _scope;
  bool _loadingScope = true;

  CashSession? _current;
  List<CashSession> _history = [];
  int _page = 0;
  int _lastPage = 1;
  bool _loading = true;
  bool _loadingMore = false;
  String? _error;
  bool _busy = false;

  bool get _hasMore => _page < _lastPage;
  int? get _warehouseId => _scope?.selectedId;

  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    final userWarehouseId = context.read<AuthProvider>().user?.warehouseId;
    final scope = await WarehouseScope.load(userWarehouseId);
    if (!mounted) return;
    setState(() {
      _scope = scope;
      _loadingScope = false;
    });
    await _load();
  }

  Future<void> _load() async {
    if (_warehouseId == null) {
      setState(() {
        _loading = false;
        _error = null;
      });
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
      _page = 0;
      _lastPage = 1;
      _history = [];
    });

    try {
      final current = await _api.dio.get<Map<String, dynamic>>(
        '/cash-sessions/current',
        queryParameters: {'warehouse_id': _warehouseId},
      );
      final currentData = current.data!['data'] as Map<String, dynamic>?;

      final list = await _api.dio.get<Map<String, dynamic>>(
        '/cash-sessions',
        queryParameters: {'warehouse_id': _warehouseId, 'page': 1},
      );
      final data = list.data!['data'] as List<dynamic>? ?? [];
      final meta = list.data!['meta'] as Map<String, dynamic>? ?? {};

      if (!mounted) return;
      setState(() {
        _current =
            currentData == null ? null : CashSession.fromJson(currentData);
        _history = data
            .map((e) => CashSession.fromJson(e as Map<String, dynamic>))
            .toList();
        _page = (meta['current_page'] as num?)?.toInt() ?? 1;
        _lastPage = (meta['last_page'] as num?)?.toInt() ?? 1;
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

  Future<void> _loadMore() async {
    if (_loadingMore || !_hasMore) return;
    setState(() => _loadingMore = true);
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/cash-sessions',
        queryParameters: {'warehouse_id': _warehouseId, 'page': _page + 1},
      );
      final data = res.data!['data'] as List<dynamic>? ?? [];
      final meta = res.data!['meta'] as Map<String, dynamic>? ?? {};
      if (!mounted) return;
      setState(() {
        _history.addAll(
          data.map((e) => CashSession.fromJson(e as Map<String, dynamic>)),
        );
        _page = (meta['current_page'] as num?)?.toInt() ?? _page + 1;
        _lastPage = (meta['last_page'] as num?)?.toInt() ?? _page;
        _loadingMore = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loadingMore = false);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(friendlyError(e)),
        backgroundColor: AppTheme.danger,
      ));
    }
  }

  // ── Ouverture / clôture ─────────────────────────────────────────────────

  Future<void> _open() async {
    final amount = await _askAmount(
      title: 'Ouvrir la caisse',
      label: 'Fonds initial (DH)',
      helper: 'Montant en caisse au début de la session.',
      action: 'Ouvrir',
    );
    if (amount == null || !mounted) return;

    final messenger = ScaffoldMessenger.of(context);
    setState(() => _busy = true);
    try {
      await _api.dio.post<Map<String, dynamic>>(
        '/cash-sessions/open',
        data: {'warehouse_id': _warehouseId, 'opening_amount': amount},
      );
      if (!mounted) return;
      setState(() => _busy = false);
      messenger.showSnackBar(const SnackBar(
        content: Text('Caisse ouverte.'),
        backgroundColor: AppTheme.success,
      ));
      await _load();
    } catch (e) {
      if (!mounted) return;
      setState(() => _busy = false);
      messenger.showSnackBar(SnackBar(
        content: Text(friendlyError(e)),
        backgroundColor: AppTheme.danger,
      ));
    }
  }

  Future<void> _close(CashSession session) async {
    final amount = await _askAmount(
      title: 'Clôturer la caisse',
      label: 'Fonds comptés (DH)',
      helper: 'Montant réellement présent en caisse. '
          'L\'écart avec l\'attendu sera calculé par le serveur.',
      action: 'Clôturer',
    );
    if (amount == null || !mounted) return;

    final messenger = ScaffoldMessenger.of(context);
    setState(() => _busy = true);
    try {
      final res = await _api.dio.post<Map<String, dynamic>>(
        '/cash-sessions/${session.id}/close',
        data: {'closing_amount': amount},
      );
      final closed = CashSession.fromJson(
        res.data!['data'] as Map<String, dynamic>,
      );
      if (!mounted) return;
      setState(() => _busy = false);
      await _showClosingResult(closed);
      if (!mounted) return;
      await _load();
    } catch (e) {
      if (!mounted) return;
      setState(() => _busy = false);
      messenger.showSnackBar(SnackBar(
        content: Text(friendlyError(e)),
        backgroundColor: AppTheme.danger,
      ));
    }
  }

  Future<void> _showClosingResult(CashSession session) async {
    final difference = session.difference ?? 0;
    final color = difference == 0
        ? AppTheme.success
        : (difference > 0 ? AppTheme.warning : AppTheme.danger);
    final label = difference == 0
        ? 'Caisse juste'
        : (difference > 0 ? 'Excédent' : 'Manquant');

    await showDialog<void>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Caisse clôturée'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _KeyValue(
              label: 'Fonds d\'ouverture',
              value: formatMoney(session.openingAmount),
            ),
            _KeyValue(
              label: 'Encaissements',
              value: formatMoney(session.collected),
            ),
            _KeyValue(
              label: 'Attendu',
              value: formatMoney(session.expectedAmount),
            ),
            _KeyValue(
              label: 'Compté',
              value: formatMoney(session.closingAmount),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Text('$label : ', style: const TextStyle(fontSize: 13)),
                Text(
                  formatMoney(difference.abs()),
                  style: TextStyle(
                    color: color,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
          ],
        ),
        actions: [
          FilledButton(
            style: FilledButton.styleFrom(minimumSize: const Size(0, 44)),
            onPressed: () => Navigator.of(dialogContext).pop(),
            child: const Text('Fermer'),
          ),
        ],
      ),
    );
  }

  /// Boîte de saisie d'un montant ; retourne `null` si l'utilisateur annule.
  Future<double?> _askAmount({
    required String title,
    required String label,
    required String helper,
    required String action,
  }) {
    final controller = TextEditingController();
    final formKey = GlobalKey<FormState>();

    return showDialog<double>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(title),
        content: Form(
          key: formKey,
          child: TextFormField(
            controller: controller,
            autofocus: true,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            inputFormatters: [
              FilteringTextInputFormatter.allow(RegExp(r'[0-9.,]')),
            ],
            decoration: InputDecoration(
              labelText: label,
              helperText: helper,
              helperMaxLines: 3,
              prefixIcon: const Icon(Icons.point_of_sale_outlined),
            ),
            validator: (value) {
              final raw = (value ?? '').trim().replaceAll(',', '.');
              final parsed = double.tryParse(raw);
              if (parsed == null || parsed < 0) {
                return 'Saisissez un montant valide.';
              }
              return null;
            },
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(),
            child: const Text('Annuler'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(minimumSize: const Size(0, 44)),
            onPressed: () {
              if (!(formKey.currentState?.validate() ?? false)) return;
              final raw = controller.text.trim().replaceAll(',', '.');
              Navigator.of(dialogContext).pop(double.parse(raw));
            },
            child: Text(action),
          ),
        ],
      ),
    );
  }

  // ── UI ──────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('cash.manage')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Caisse')),
      body: _loadingScope ? const LoadingView() : _buildBody(),
    );
  }

  Widget _buildBody() {
    final scope = _scope!;

    if (_warehouseId == null) {
      return const EmptyView(
        icon: Icons.warehouse_outlined,
        message: 'Aucun lieu disponible : la caisse est rattachée à un lieu. '
            'Contactez l\'administrateur.',
      );
    }

    return Column(
      children: [
        WarehouseSelectorBar(
          scope: scope,
          onChanged: (id) {
            setState(() => _scope = scope.copyWith(selectedId: id));
            _load();
          },
        ),
        Expanded(
          child: _loading
              ? const LoadingView()
              : _error != null
                  ? ErrorView(message: _error!, onRetry: _load)
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        padding: const EdgeInsets.only(top: 8, bottom: 32),
                        children: [
                          _buildCurrentCard(),
                          const Padding(
                            padding: EdgeInsets.fromLTRB(16, 20, 16, 6),
                            child: Text(
                              'HISTORIQUE DES SESSIONS',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                                letterSpacing: 1.1,
                                color: AppTheme.navy,
                              ),
                            ),
                          ),
                          if (_history.isEmpty)
                            const Padding(
                              padding: EdgeInsets.only(top: 24),
                              child: EmptyView(
                                icon: Icons.history,
                                message: 'Aucune session enregistrée.',
                              ),
                            )
                          else
                            ..._history.map(
                              (session) => _SessionCard(session: session),
                            ),
                          if (_hasMore)
                            Padding(
                              padding: const EdgeInsets.all(16),
                              child: OutlinedButton(
                                onPressed: _loadingMore ? null : _loadMore,
                                child: _loadingMore
                                    ? const SizedBox(
                                        width: 18,
                                        height: 18,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                        ),
                                      )
                                    : const Text('Charger plus'),
                              ),
                            ),
                        ],
                      ),
                    ),
        ),
      ],
    );
  }

  Widget _buildCurrentCard() {
    final session = _current;

    if (session == null) {
      return Card(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Row(
                children: [
                  Icon(Icons.lock_outline, color: AppTheme.warning),
                  SizedBox(width: 8),
                  Text(
                    'Caisse fermée',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: AppTheme.navy,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              Text(
                'Aucune session ouverte sur ce lieu.',
                style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
              ),
              const SizedBox(height: 16),
              FilledButton.icon(
                onPressed: _busy ? null : _open,
                icon: _busy
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.lock_open),
                label: const Text('Ouvrir la caisse'),
              ),
            ],
          ),
        ),
      );
    }

    return Card(
      color: AppTheme.navy,
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Expanded(
                  child: Text(
                    'Session en cours',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const StatusBadge(label: 'Ouverte', color: Colors.white),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              'Fonds d\'ouverture',
              style: TextStyle(color: Colors.white.withValues(alpha: 0.7),
                  fontSize: 12),
            ),
            Text(
              formatMoney(session.openingAmount),
              style: const TextStyle(
                color: Colors.white,
                fontSize: 24,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 10),
            Text(
              [
                if ((session.openedAt ?? '').isNotEmpty)
                  'Ouverte le ${session.openedAt}',
                if ((session.openedBy ?? '').isNotEmpty)
                  'par ${session.openedBy}',
              ].join(' '),
              style: const TextStyle(color: Colors.white70, fontSize: 12),
            ),
            const SizedBox(height: 8),
            const Text(
              'Le cumul des encaissements de la session est calculé par le '
              'serveur au moment de la clôture.',
              style: TextStyle(color: Colors.white70, fontSize: 11),
            ),
            const SizedBox(height: 16),
            FilledButton.icon(
              style: FilledButton.styleFrom(
                backgroundColor: Colors.white,
                foregroundColor: AppTheme.navy,
              ),
              onPressed: _busy ? null : () => _close(session),
              icon: _busy
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.lock_outline),
              label: const Text('Clôturer la caisse'),
            ),
          ],
        ),
      ),
    );
  }
}

class _SessionCard extends StatelessWidget {
  const _SessionCard({required this.session});

  final CashSession session;

  @override
  Widget build(BuildContext context) {
    final difference = session.difference;
    final differenceColor = difference == null || difference == 0
        ? AppTheme.success
        : (difference > 0 ? AppTheme.warning : AppTheme.danger);

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
                    session.openedAt ?? 'Session #${session.id}',
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      color: AppTheme.navy,
                    ),
                  ),
                ),
                StatusBadge(
                  label: session.isOpen ? 'Ouverte' : 'Clôturée',
                  color: session.isOpen ? AppTheme.success : Colors.grey,
                ),
              ],
            ),
            if ((session.openedBy ?? '').isNotEmpty)
              Text(
                'Ouverte par ${session.openedBy}',
                style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
              ),
            const SizedBox(height: 8),
            _KeyValue(
              label: 'Fonds d\'ouverture',
              value: formatMoney(session.openingAmount),
            ),
            if (!session.isOpen) ...[
              _KeyValue(
                label: 'Encaissements',
                value: formatMoney(session.collected),
              ),
              _KeyValue(
                label: 'Attendu',
                value: formatMoney(session.expectedAmount),
              ),
              _KeyValue(
                label: 'Compté',
                value: formatMoney(session.closingAmount),
              ),
              if ((session.closedAt ?? '').isNotEmpty)
                _KeyValue(label: 'Clôturée le', value: session.closedAt!),
              const SizedBox(height: 6),
              Row(
                children: [
                  const Text('Écart : ', style: TextStyle(fontSize: 13)),
                  Text(
                    formatMoney(difference),
                    style: TextStyle(
                      color: differenceColor,
                      fontWeight: FontWeight.bold,
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

class _KeyValue extends StatelessWidget {
  const _KeyValue({required this.label, required this.value});

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
            style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
          ),
          Text(
            value,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}
