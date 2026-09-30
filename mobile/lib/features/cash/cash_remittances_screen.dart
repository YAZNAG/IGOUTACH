import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';

/// Historique des transferts de caisse vers la caisse générale.
///
/// Un responsable y suit ce qu'il a remis et ce qui reste en attente ;
/// l'administration y confirme la réception, somme par somme. Les deux voient
/// la même liste : c'est elle qui fait foi quand un montant est discuté.
///
/// Endpoints : GET /cash-remittances, POST /cash-remittances/{id}/receive,
/// DELETE /cash-remittances/{id}.
class CashRemittancesScreen extends StatefulWidget {
  const CashRemittancesScreen({super.key, this.warehouseId});

  /// Lieu imposé (depuis l'écran Caisse). Nul : tous les lieux accessibles.
  final int? warehouseId;

  @override
  State<CashRemittancesScreen> createState() => _CashRemittancesScreenState();
}

class _CashRemittancesScreenState extends State<CashRemittancesScreen> {
  final _api = ApiClient.instance;

  /// '' = tous, 'pending' = en attente, 'received' = confirmés.
  String _statut = '';

  List<Map<String, dynamic>> _lignes = [];
  double _totalEnAttente = 0;
  int _page = 1;
  int _lastPage = 1;
  bool _loading = true;
  bool _loadingMore = false;
  String? _error;
  int? _busyId;

  bool get _hasMore => _page < _lastPage;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Map<String, dynamic> get _filtres => {
        if (widget.warehouseId != null) 'warehouse_id': widget.warehouseId,
        if (_statut.isNotEmpty) 'status': _statut,
      };

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/cash-remittances',
        queryParameters: {..._filtres, 'page': 1},
      );
      final data = res.data!['data'] as List<dynamic>? ?? [];
      final meta = res.data!['meta'] as Map<String, dynamic>? ?? {};
      if (!mounted) return;
      setState(() {
        _lignes = data.cast<Map<String, dynamic>>();
        _totalEnAttente = (meta['pending_total'] as num?)?.toDouble() ?? 0;
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
        '/cash-remittances',
        queryParameters: {..._filtres, 'page': _page + 1},
      );
      final data = res.data!['data'] as List<dynamic>? ?? [];
      final meta = res.data!['meta'] as Map<String, dynamic>? ?? {};
      if (!mounted) return;
      setState(() {
        _lignes.addAll(data.cast<Map<String, dynamic>>());
        _page = (meta['current_page'] as num?)?.toInt() ?? _page + 1;
        _lastPage = (meta['last_page'] as num?)?.toInt() ?? _page;
        _loadingMore = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loadingMore = false);
      _direErreur(e);
    }
  }

  void _direErreur(Object e) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(friendlyError(e)),
      backgroundColor: AppTheme.danger,
    ));
  }

  /// L'administration confirme avoir reçu la somme.
  Future<void> _confirmer(Map<String, dynamic> remise) async {
    final montant = (remise['amount'] as num?)?.toDouble() ?? 0;
    final accepte = await confirmAction(
      context,
      icon: Icons.verified_outlined,
      title: 'Confirmer la réception',
      message: '${remise['reference']} · ${formatMoney(montant)}\n'
          'Lieu : ${remise['warehouse'] ?? '—'}\n\n'
          'Confirmez seulement après avoir compté cet argent : la caisse du '
          'lieu en sera définitivement déchargée.',
      confirmLabel: 'J\'ai reçu la somme',
      confirmColor: AppTheme.success,
    );
    if (!accepte || !mounted) return;

    setState(() => _busyId = remise['id'] as int?);
    try {
      await _api.dio.post<Map<String, dynamic>>(
        '/cash-remittances/${remise['id']}/receive',
      );
      if (!mounted) return;
      setState(() => _busyId = null);
      await _load();
    } catch (e) {
      if (!mounted) return;
      setState(() => _busyId = null);
      _direErreur(e);
    }
  }

  /// L'administration refuse : elle n'a pas reçu cette somme.
  ///
  /// Le montant revient au solde du lieu, et la ligne reste dans l'historique
  /// avec son motif : c'est la trace du désaccord.
  Future<void> _refuser(Map<String, dynamic> remise) async {
    final motif = await _demanderMotif(remise);
    if (motif == null || !mounted) return;

    setState(() => _busyId = remise['id'] as int?);
    try {
      await _api.dio.post<Map<String, dynamic>>(
        '/cash-remittances/${remise['id']}/refuse',
        data: {if (motif.trim().isNotEmpty) 'reason': motif.trim()},
      );
      if (!mounted) return;
      setState(() => _busyId = null);
      await _load();
    } catch (e) {
      if (!mounted) return;
      setState(() => _busyId = null);
      _direErreur(e);
    }
  }

  Future<String?> _demanderMotif(Map<String, dynamic> remise) {
    final controleur = TextEditingController();
    final montant = (remise['amount'] as num?)?.toDouble() ?? 0;

    return showDialog<String>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Refuser le transfert'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '${remise['reference']} · ${formatMoney(montant)}\n'
              'La somme reviendra au solde de ${remise['warehouse'] ?? 'ce lieu'}, '
              'et le refus restera dans l\'historique.',
              style: const TextStyle(fontSize: 13),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: controleur,
              autofocus: true,
              maxLength: 255,
              decoration: const InputDecoration(
                labelText: 'Motif',
                hintText: 'Somme non reçue, montant différent…',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(),
            child: const Text('Annuler'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(
              backgroundColor: AppTheme.danger,
              minimumSize: const Size(0, 44),
            ),
            onPressed: () => Navigator.of(dialogContext).pop(controleur.text),
            child: const Text('Refuser'),
          ),
        ],
      ),
    );
  }

  Future<void> _ouvrirJustificatif(String url) async {
    final ok = await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
    if (!ok && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('Impossible d\'ouvrir le justificatif.'),
        backgroundColor: AppTheme.danger,
      ));
    }
  }

  /// Le lieu annule un transfert déclaré par erreur, tant qu'il est en attente.
  Future<void> _annuler(Map<String, dynamic> remise) async {
    final montant = (remise['amount'] as num?)?.toDouble() ?? 0;
    final accepte = await confirmAction(
      context,
      icon: Icons.delete_outline,
      title: 'Annuler le transfert',
      message: '${remise['reference']} · ${formatMoney(montant)}\n\n'
          'La somme reviendra au solde de la caisse du lieu.',
      confirmLabel: 'Annuler le transfert',
      confirmColor: AppTheme.danger,
    );
    if (!accepte || !mounted) return;

    setState(() => _busyId = remise['id'] as int?);
    try {
      await _api.dio.delete<Map<String, dynamic>>(
        '/cash-remittances/${remise['id']}',
      );
      if (!mounted) return;
      setState(() => _busyId = null);
      await _load();
    } catch (e) {
      if (!mounted) return;
      setState(() => _busyId = null);
      _direErreur(e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.can('cash.remit') && !auth.can('cash.remit_receive')) {
      return const Scaffold(body: NotAllowedView());
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Transferts de caisse')),
      body: Column(
        children: [
          _barreFiltres(),
          Expanded(
            child: _loading
                ? const LoadingView()
                : _error != null
                    ? ErrorView(message: _error!, onRetry: _load)
                    : RefreshIndicator(
                        onRefresh: _load,
                        child: _lignes.isEmpty
                            ? ListView(
                                physics: const AlwaysScrollableScrollPhysics(),
                                children: const [
                                  SizedBox(height: 60),
                                  EmptyView(
                                    icon: Icons.swap_horiz,
                                    message: 'Aucun transfert à afficher.',
                                  ),
                                ],
                              )
                            : ListView.builder(
                                physics: const AlwaysScrollableScrollPhysics(),
                                padding: const EdgeInsets.only(bottom: 28),
                                itemCount: _lignes.length + (_hasMore ? 1 : 0),
                                itemBuilder: (_, i) {
                                  if (i == _lignes.length) {
                                    return Padding(
                                      padding: const EdgeInsets.all(16),
                                      child: OutlinedButton(
                                        onPressed: _loadingMore ? null : _loadMore,
                                        child: Text(
                                          _loadingMore ? 'Chargement…' : 'Charger plus',
                                        ),
                                      ),
                                    );
                                  }
                                  return _carte(_lignes[i], auth);
                                },
                              ),
                      ),
          ),
        ],
      ),
    );
  }

  Widget _barreFiltres() {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              for (final entree in const [
                ('', 'Tous'),
                ('pending', 'En attente'),
                ('received', 'Confirmés'),
                ('refused', 'Refusés'),
              ])
                Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ChoiceChip(
                    label: Text(entree.$2),
                    selected: _statut == entree.$1,
                    onSelected: (_) {
                      setState(() => _statut = entree.$1);
                      _load();
                    },
                  ),
                ),
            ],
          ),
          if (_totalEnAttente > 0) ...[
            const SizedBox(height: 8),
            Text(
              'En attente de confirmation : ${formatMoney(_totalEnAttente)}',
              style: const TextStyle(
                fontSize: 12.5,
                fontWeight: FontWeight.w600,
                color: AppTheme.warning,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _carte(Map<String, dynamic> r, AuthProvider auth) {
    final confirme = r['status'] == 'received';
    final refuse = r['status'] == 'refused';
    final enAttente = !confirme && !refuse;
    final montant = (r['amount'] as num?)?.toDouble() ?? 0;
    final occupe = _busyId == r['id'];
    final justificatif = r['proof_url'] as String?;

    return Card(
      margin: const EdgeInsets.fromLTRB(12, 8, 12, 0),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    r['reference'] as String? ?? '',
                    style: const TextStyle(
                      fontFamily: 'monospace',
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                Text(
                  formatMoney(montant),
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: AppTheme.navy,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Row(
              children: [
                StatusBadge(
                  label: confirme ? 'Reçu' : (refuse ? 'Refusé' : 'En attente'),
                  color: confirme
                      ? AppTheme.success
                      : (refuse ? AppTheme.danger : AppTheme.warning),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    '${r['warehouse'] ?? '—'} → Caisse générale',
                    style: const TextStyle(fontSize: 12.5),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 6),
            Text(
              [
                'Remis le ${r['remitted_at'] ?? '—'}',
                if ((r['created_by'] as String?)?.isNotEmpty ?? false)
                  'par ${r['created_by']}',
              ].join(' '),
              style: const TextStyle(fontSize: 11.5, color: AppTheme.textMuted),
            ),
            if (confirme)
              Text(
                'Reçu par ${r['received_by'] ?? 'l\'administration'}'
                '${r['received_at'] != null ? ' le ${r['received_at']}' : ''}',
                style: const TextStyle(fontSize: 11.5, color: AppTheme.success),
              ),
            if (refuse)
              Text(
                'Refusé par ${r['refused_by'] ?? 'l\'administration'}'
                '${r['refused_at'] != null ? ' le ${r['refused_at']}' : ''}'
                '${r['refusal_reason'] != null ? ' — ${r['refusal_reason']}' : ''}',
                style: const TextStyle(fontSize: 11.5, color: AppTheme.danger),
              ),
            if (justificatif != null)
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: InkWell(
                  onTap: () => _ouvrirJustificatif(justificatif),
                  child: const Row(
                    children: [
                      Icon(Icons.image_outlined, size: 16, color: AppTheme.navy),
                      SizedBox(width: 4),
                      Text(
                        'Voir le justificatif',
                        style: TextStyle(
                          fontSize: 12.5,
                          color: AppTheme.navy,
                          decoration: TextDecoration.underline,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            if ((r['note'] as String?)?.isNotEmpty ?? false)
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Text(
                  '« ${r['note']} »',
                  style: const TextStyle(
                    fontSize: 12,
                    fontStyle: FontStyle.italic,
                    color: AppTheme.textMuted,
                  ),
                ),
              ),
            if (enAttente) ...[
              const SizedBox(height: 10),
              if (auth.can('cash.remit_receive'))
                Row(
                  children: [
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: occupe ? null : () => _confirmer(r),
                        icon: const Icon(Icons.check, size: 18),
                        label: const Text('J\'ai reçu'),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: occupe ? null : () => _refuser(r),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: AppTheme.danger,
                        ),
                        icon: const Icon(Icons.close, size: 18),
                        label: const Text('Refuser'),
                      ),
                    ),
                  ],
                )
              else if (auth.can('cash.remit'))
                OutlinedButton(
                  onPressed: occupe ? null : () => _annuler(r),
                  style: OutlinedButton.styleFrom(foregroundColor: AppTheme.danger),
                  child: const Text('Annuler ce transfert'),
                ),
            ],
          ],
        ),
      ),
    );
  }
}
