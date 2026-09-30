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
import 'cash_remittances_screen.dart';
import 'cash_transfer_sheet.dart';

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

  /// Detail du solde renvoye avec la session : fonds, entrees, sorties.
  Map<String, dynamic>? _solde;

  /// Reste de la derniere journee close : fonds propose au matin suivant.
  double _resteDeLaVeille = 0;
  Map<String, dynamic>? _derniereCloture;

  /// Remises declarees par ce lieu.
  List<Map<String, dynamic>> _remises = [];

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
      final soldeData = current.data!['cash'] as Map<String, dynamic>?;
      final reste =
          (current.data!['suggested_opening'] as num?)?.toDouble() ?? 0;
      final derniere = current.data!['last_closed'] as Map<String, dynamic>?;

      // L'historique et les remises dependent de droits distincts : un refus
      // sur l'un ne doit pas priver l'utilisateur de l'autre, ni de sa caisse.
      List<dynamic> data = [];
      Map<String, dynamic> meta = {};
      try {
        final list = await _api.dio.get<Map<String, dynamic>>(
          '/cash-sessions',
          queryParameters: {'warehouse_id': _warehouseId, 'page': 1},
        );
        data = list.data!['data'] as List<dynamic>? ?? [];
        meta = list.data!['meta'] as Map<String, dynamic>? ?? {};
      } catch (_) {
        data = [];
      }

      List<Map<String, dynamic>> remises = [];
      try {
        final res = await _api.dio.get<Map<String, dynamic>>(
          '/cash-remittances',
          queryParameters: {'warehouse_id': _warehouseId},
        );
        remises = ((res.data!['data'] as List<dynamic>? ?? []))
            .cast<Map<String, dynamic>>();
      } catch (_) {
        remises = [];
      }

      if (!mounted) return;
      setState(() {
        _current =
            currentData == null ? null : CashSession.fromJson(currentData);
        _solde = soldeData;
        _resteDeLaVeille = reste;
        _derniereCloture = derniere;
        _remises = remises;
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

  /// Ouvre la journée sur le reste de la veille.
  ///
  /// Le montant est proposé, pas imposé : si le tiroir ne contient pas ce que
  /// la clôture d'hier annonçait, le responsable corrige — et l'écart se lit
  /// alors dès l'ouverture, au lieu d'apparaître le soir sans explication.
  Future<void> _open() async {
    final cloture = _derniereCloture;
    final amount = await _askAmount(
      title: 'Ouvrir la caisse',
      label: 'Fonds initial (DH)',
      helper: cloture == null
          ? 'Montant en caisse au début de la journée.'
          : 'Reste de la journée précédente, clôturée le '
              '${cloture['closed_at']} : ${formatMoney(_resteDeLaVeille)}. '
              'Corrigez si le tiroir contient autre chose.',
      action: 'Ouvrir',
      initial: _resteDeLaVeille > 0 ? _resteDeLaVeille : null,
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

  /// Remet une partie du tiroir à l'administration.
  ///
  /// Le montant proposé par défaut est le solde entier : c'est le geste
  /// courant en fin de journée. Le serveur refuse toute somme supérieure à
  /// ce que la caisse contient.
  Future<void> _remettre() async {
    final solde = (_solde?['expected'] as num?)?.toDouble() ?? 0;
    final messenger = ScaffoldMessenger.of(context);

    final cree = await TransfertCaisseSheet.ouvrir(
      context,
      warehouseId: _warehouseId!,
      solde: solde,
    );
    if (!cree || !mounted) return;

    messenger.showSnackBar(const SnackBar(
      content: Text(
        'Transfert déclaré. Il reste en attente jusqu\'à confirmation de '
        'l\'administration.',
      ),
      backgroundColor: AppTheme.success,
    ));
    await _load();
  }

  /// Annule une remise déclarée par erreur, tant qu'elle n'est pas confirmée.
  Future<void> _annulerRemise(Map<String, dynamic> remise) async {
    final confirme = await confirmAction(
      context,
      icon: Icons.delete_outline,
      title: 'Annuler la remise',
      message: '${remise['reference']} · '
          '${formatMoney((remise['amount'] as num).toDouble())}\n\n'
          'La somme reviendra au solde de la caisse.',
      confirmLabel: 'Annuler la remise',
      confirmColor: AppTheme.danger,
    );
    if (!confirme || !mounted) return;

    final messenger = ScaffoldMessenger.of(context);
    try {
      await _api.dio.delete<Map<String, dynamic>>(
        '/cash-remittances/${remise['id']}',
      );
      if (!mounted) return;
      await _load();
    } catch (e) {
      if (!mounted) return;
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
    double? initial,
  }) {
    final controller = TextEditingController(
      text: initial == null ? '' : initial.toStringAsFixed(2),
    );
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
    // Tenir une caisse ne suppose pas de savoir l'administrer : celui qui
    // encaisse doit pouvoir consulter son tiroir et remettre son solde.
    if (!context.watch<AuthProvider>().can('payment.create')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Caisse'),
        actions: [
          if (context.watch<AuthProvider>().can('cash.remit') ||
              context.watch<AuthProvider>().can('cash.remit_receive'))
            IconButton(
              icon: const Icon(Icons.swap_horiz),
              tooltip: 'Transferts vers la caisse générale',
              onPressed: _ouvrirHistoriqueTransferts,
            ),
        ],
      ),
      body: _loadingScope ? const LoadingView() : _buildBody(),
    );
  }

  Future<void> _ouvrirHistoriqueTransferts() async {
    await Navigator.of(context).push<void>(MaterialPageRoute(
      builder: (_) => CashRemittancesScreen(warehouseId: _warehouseId),
    ));
    if (!mounted) return;
    // Une confirmation ou une annulation change le solde : on relit.
    await _load();
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
                          _carteSolde(),
                          _blocRemises(),
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

  /// Le solde du tiroir, décomposé.
  ///
  /// Un responsable qui ne tombe pas juste doit pouvoir dire où l'écart se
  /// trouve. Le total seul ne le permet pas.
  Widget _carteSolde() {
    final solde = _solde;
    if (solde == null) return const SizedBox.shrink();

    double v(String cle) => (solde[cle] as num?)?.toDouble() ?? 0;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'SOLDE DE LA CAISSE',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.bold,
                letterSpacing: 1.1,
                color: AppTheme.navy,
              ),
            ),
            const SizedBox(height: 10),
            _KeyValue(label: 'Fonds de départ', value: formatMoney(v('opening'))),
            _KeyValue(
              label: 'Encaissements en espèces',
              value: '+ ${formatMoney(v('cash_in'))}',
            ),
            _KeyValue(
              label: 'Charges payées en espèces',
              value: '− ${formatMoney(v('cash_expenses'))}',
            ),
            _KeyValue(
              label: 'Remis à l\'administration',
              value: '− ${formatMoney(v('remitted'))}',
            ),
            const Divider(height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'À avoir en caisse',
                  style: TextStyle(fontWeight: FontWeight.w600),
                ),
                Text(
                  formatMoney(v('expected')),
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: AppTheme.navy,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 6),
            const Text(
              'Chèques et virements ne passent pas par le tiroir : ils ne '
              'sont pas comptés ici.',
              style: TextStyle(fontSize: 11, color: AppTheme.textMuted),
            ),
            if (context.read<AuthProvider>().can('cash.remit')) ...[
              const SizedBox(height: 14),
              FilledButton.icon(
                onPressed: _busy || v('expected') <= 0 ? null : _remettre,
                icon: const Icon(Icons.upload_outlined, size: 18),
                label: const Text('Transférer à la caisse générale'),
              ),
            ],
          ],
        ),
      ),
    );
  }

  /// Les remises faites par ce lieu, et où elles en sont.
  Widget _blocRemises() {
    if (_remises.isEmpty) {
      return Padding(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 0),
        child: OutlinedButton.icon(
          onPressed: _ouvrirHistoriqueTransferts,
          icon: const Icon(Icons.swap_horiz, size: 18),
          label: const Text('Historique des transferts'),
        ),
      );
    }

    return Padding(
      padding: const EdgeInsets.only(top: 4),
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text(
                'REMISES À L\'ADMINISTRATION',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 1.1,
                  color: AppTheme.navy,
                ),
              ),
              const SizedBox(height: 4),
              ..._remises.take(5).map(_ligneRemise),
              const SizedBox(height: 6),
              OutlinedButton.icon(
                onPressed: _ouvrirHistoriqueTransferts,
                icon: const Icon(Icons.swap_horiz, size: 18),
                label: const Text('Tout l\'historique des transferts'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _ligneRemise(Map<String, dynamic> remise) {
    final confirmee = remise['status'] == 'received';
    final refusee = remise['status'] == 'refused';
    final montant = (remise['amount'] as num?)?.toDouble() ?? 0;

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  remise['reference'] as String? ?? '',
                  style: const TextStyle(
                    fontFamily: 'monospace',
                    fontSize: 12.5,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                Text(
                  confirmee
                      ? 'Reçue par ${remise['received_by'] ?? "l'administration"}'
                      : refusee
                          ? 'Refusée${remise['refusal_reason'] != null ? ' : ${remise['refusal_reason']}' : ''}'
                          : 'En attente de confirmation',
                  style: TextStyle(
                    fontSize: 11,
                    color: confirmee
                        ? AppTheme.success
                        : refusee
                            ? AppTheme.danger
                            : AppTheme.warning,
                  ),
                ),
              ],
            ),
          ),
          Text(
            formatMoney(montant),
            style: const TextStyle(fontWeight: FontWeight.w600),
          ),
          if (!confirmee && !refusee)
            IconButton(
              icon: const Icon(Icons.close, size: 18, color: AppTheme.danger),
              tooltip: 'Annuler le transfert',
              onPressed: () => _annulerRemise(remise),
            ),
        ],
      ),
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
                _derniereCloture == null
                    ? 'Aucune journée ouverte sur ce lieu.'
                    : 'Dernière journée clôturée le ${_derniereCloture!['closed_at']}, '
                        'reste en caisse : ${formatMoney(_resteDeLaVeille)}. '
                        'La nouvelle journée s\'ouvrira sur ce montant.',
                style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
              ),
              const SizedBox(height: 16),
              // Ouvrir la caisse est un droit à part : proposer le bouton à
              // qui ne l'a pas ne mènerait qu'à un refus du serveur.
              if (!context.watch<AuthProvider>().can('cash.open'))
                const Text(
                  'L\'ouverture de la caisse est réservée au responsable du lieu.',
                  style: TextStyle(fontSize: 12.5, color: AppTheme.textMuted),
                )
              else
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
              'Le détail du solde figure ci-dessous : fonds, encaissements en '
              'espèces, charges réglées et remises.',
              style: TextStyle(color: Colors.white70, fontSize: 11),
            ),
            const SizedBox(height: 16),
            if (context.watch<AuthProvider>().can('cash.manage'))
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
              // Le detail de la journee : d'ou vient l'argent, ou il est parti.
              if (session.cashIn != null)
                _KeyValue(
                  label: 'Encaissements en espèces',
                  value: '+ ${formatMoney(session.cashIn)}',
                ),
              if ((session.cashExpenses ?? 0) > 0)
                _KeyValue(
                  label: 'Charges payées en espèces',
                  value: '− ${formatMoney(session.cashExpenses)}',
                ),
              if ((session.remitted ?? 0) > 0)
                _KeyValue(
                  label: 'Transféré à la caisse générale',
                  value: '− ${formatMoney(session.remitted)}',
                ),
              if (session.cashIn == null)
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
