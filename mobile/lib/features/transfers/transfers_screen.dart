import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import 'transfer_form_screen.dart';
import 'transfer_detail_screen.dart';

/// Libellé et couleur d'un état de transfert.
(String, Color) etatTransfert(String? code, String? nom) => switch (code) {
      'requested' => ('Demandé', AppTheme.warning),
      'in_transit' => ('En transit', AppTheme.warning),
      'received' => ('Reçu', AppTheme.success),
      'refused' => ('Refusé', AppTheme.danger),
      'cancelled' => ('Annulé', AppTheme.danger),
      _ => (nom ?? code ?? '—', AppTheme.textMuted),
    };

/// Un transfert, tel que le renvoie GET /transfers.
class Transfert {
  const Transfert({
    required this.id,
    required this.reference,
    required this.lignes,
    this.depuis,
    this.vers,
    this.etat,
    this.etatNom,
    this.envoyeLe,
    this.recuLe,
    this.joursEnTransit,
    this.enRetard = false,
  });

  final int id;
  final String reference;
  final int lignes;
  final String? depuis;
  final String? vers;
  final String? etat;
  final String? etatNom;
  final String? envoyeLe;
  final String? recuLe;
  final int? joursEnTransit;
  final bool enRetard;

  factory Transfert.fromJson(Map<String, dynamic> j) => Transfert(
        id: (j['id'] as num).toInt(),
        reference: j['reference'] as String? ?? '',
        lignes: (j['lines_count'] as num?)?.toInt() ?? 0,
        depuis: j['from'] as String?,
        vers: j['to'] as String?,
        etat: j['status'] as String?,
        etatNom: j['status_name'] as String?,
        envoyeLe: j['sent_at'] as String?,
        recuLe: j['received_at'] as String?,
        joursEnTransit: (j['days_in_transit'] as num?)?.toInt(),
        enRetard: j['is_late'] == true,
      );
}

/// Transferts de stock entre lieux : liste, création et suivi.
///
/// L'administrateur y fait tout le circuit — choisir les deux lieux, garnir
/// les lignes, puis approuver, expédier ou réceptionner. Le responsable de
/// lieu, lui, garde son écran de demande : il ne choisit pas sa destination.
class TransfersScreen extends StatefulWidget {
  const TransfersScreen({super.key});

  @override
  State<TransfersScreen> createState() => _TransfersScreenState();
}

class _TransfersScreenState extends State<TransfersScreen> {
  final _api = ApiClient.instance;
  final _defilement = ScrollController();

  final List<Transfert> _transferts = [];
  int _page = 0;
  int _dernierePage = 1;
  bool _chargement = false;
  bool _premierChargementFait = false;
  String? _erreur;
  bool _horsLigne = false;

  /// `null` = tous les états.
  String? _filtre;

  bool get _encore => _page < _dernierePage;

  @override
  void initState() {
    super.initState();
    _defilement.addListener(_auDefilement);
    _charger(reset: true);
  }

  @override
  void dispose() {
    _defilement.dispose();
    super.dispose();
  }

  void _auDefilement() {
    if (!_defilement.hasClients) return;
    final p = _defilement.position;
    if (p.pixels > p.maxScrollExtent - 300 && _encore && !_chargement) _charger();
  }

  Future<void> _charger({bool reset = false}) async {
    if (_chargement) return;
    setState(() {
      _chargement = true;
      if (reset) {
        _erreur = null;
        _page = 0;
        _dernierePage = 1;
        _transferts.clear();
        _premierChargementFait = false;
      }
    });

    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/transfers',
        queryParameters: {'page': _page + 1, 'status': ?_filtre},
      );
      final corps = res.data!;
      final donnees = corps['data'] as List<dynamic>? ?? [];
      final meta = corps['meta'] as Map<String, dynamic>? ?? {};

      if (!mounted) return;
      setState(() {
        _page = (meta['current_page'] as num?)?.toInt() ?? _page + 1;
        _dernierePage = (meta['last_page'] as num?)?.toInt() ?? _page;
        _transferts.addAll(
          donnees.map((e) => Transfert.fromJson(e as Map<String, dynamic>)),
        );
        _premierChargementFait = true;
        _chargement = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _erreur = friendlyError(e);
        _horsLigne = isNetworkError(e);
        _chargement = false;
        _premierChargementFait = true;
      });
    }
  }

  Future<void> _creer() async {
    final cree = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => const TransferFormScreen()),
    );
    if (cree == true) _charger(reset: true);
  }

  Future<void> _ouvrir(Transfert t) async {
    final change = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => TransferDetailScreen(transferId: t.id, reference: t.reference),
      ),
    );
    if (change == true) _charger(reset: true);
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.can('stock.view')) return const NotAllowedView();

    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(title: const Text('Transferts')),
      floatingActionButton: auth.can('transfer.create')
          ? FloatingActionButton.extended(
              onPressed: _creer,
              icon: const Icon(Icons.add),
              label: const Text('Nouveau transfert'),
            )
          : null,
      body: Column(
        children: [
          _filtres(),
          Expanded(child: _corps()),
        ],
      ),
    );
  }

  Widget _filtres() {
    const choix = [
      (null, 'Tous'),
      ('requested', 'Demandés'),
      ('in_transit', 'En transit'),
      ('received', 'Reçus'),
    ];

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.fromLTRB(12, 10, 12, 6),
      child: Row(
        children: [
          for (final (code, libelle) in choix)
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: ChoiceChip(
                label: Text(libelle),
                selected: _filtre == code,
                onSelected: (_) {
                  if (_filtre == code) return;
                  setState(() => _filtre = code);
                  _charger(reset: true);
                },
              ),
            ),
        ],
      ),
    );
  }

  Widget _corps() {
    if (!_premierChargementFait && _chargement) {
      return const ListSkeleton(itemCount: 5, lines: 2);
    }

    if (_erreur != null && _transferts.isEmpty) {
      return ErrorView(
        message: _erreur!,
        offline: _horsLigne,
        onRetry: () => _charger(reset: true),
      );
    }

    if (_transferts.isEmpty) {
      return const EmptyView(
        icon: Icons.swap_horiz_rounded,
        message: 'Aucun transfert pour ce filtre.',
      );
    }

    return RefreshIndicator(
      onRefresh: () => _charger(reset: true),
      child: ListView.builder(
        controller: _defilement,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(12, 4, 12, 88),
        itemCount: _transferts.length + (_encore ? 1 : 0),
        itemBuilder: (context, i) {
          if (i >= _transferts.length) return const SkeletonCard(lines: 2);
          final t = _transferts[i];
          final (libelle, couleur) = etatTransfert(t.etat, t.etatNom);

          return Card(
            clipBehavior: Clip.antiAlias,
            margin: const EdgeInsets.only(bottom: 8),
            child: InkWell(
              onTap: () => _ouvrir(t),
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            t.reference,
                            style: const TextStyle(
                              fontFamily: 'monospace',
                              fontWeight: FontWeight.w600,
                              fontSize: 13,
                            ),
                          ),
                        ),
                        StatusBadge(label: libelle, color: couleur),
                      ],
                    ),
                    const SizedBox(height: 8),
                    // Le sens du mouvement se lit d'un coup : d'où vers où.
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            t.depuis ?? '—',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w500),
                          ),
                        ),
                        const Padding(
                          padding: EdgeInsets.symmetric(horizontal: 8),
                          child: Icon(Icons.arrow_forward, size: 16, color: AppTheme.textMuted),
                        ),
                        Expanded(
                          child: Text(
                            t.vers ?? '—',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            textAlign: TextAlign.right,
                            style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w500),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      [
                        '${t.lignes} ligne${t.lignes > 1 ? 's' : ''}',
                        if (t.envoyeLe != null) 'expédié le ${t.envoyeLe}',
                        if (t.recuLe != null) 'reçu le ${t.recuLe}',
                      ].join(' · '),
                      style: const TextStyle(fontSize: 12, color: AppTheme.textMuted),
                    ),
                    // Une marchandise qui traîne en transit est de la
                    // marchandise que personne ne compte : le dire ici évite
                    // d'attendre l'inventaire pour s'en apercevoir.
                    if (t.enRetard)
                      Padding(
                        padding: const EdgeInsets.only(top: 8),
                        child: StatusBadge(
                          label: 'En transit depuis ${t.joursEnTransit} jours',
                          color: AppTheme.danger,
                        ),
                      ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
