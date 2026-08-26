import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';

/// Liste paginée branchée sur un point d'API, avec recherche facultative.
///
/// Les écrans de consultation de l'application répètent tous la même mécanique :
/// charger une page, en charger une autre au défilement, filtrer par une
/// recherche différée, distinguer « vide » de « en panne ». L'écrire une fois
/// évite d'avoir cinq variantes qui divergent au premier correctif.
///
/// [T] est le type de ligne ; [depuisJson] le construit, [carte] le dessine.
class ListePaginee<T> extends StatefulWidget {
  const ListePaginee({
    super.key,
    required this.titre,
    required this.chemin,
    required this.depuisJson,
    required this.carte,
    this.parametres = const {},
    this.rechercheCle = 'search',
    this.indiceRecherche,
    this.messageVide = 'Rien à afficher pour le moment.',
    this.iconeVide = Icons.inbox_outlined,
    this.actionFlottante,
    this.enTete,
  });

  final String titre;

  /// Chemin de l'API, sans le préfixe de version.
  final String chemin;

  final T Function(Map<String, dynamic>) depuisJson;
  final Widget Function(BuildContext, T) carte;

  /// Paramètres constants ajoutés à chaque appel.
  final Map<String, dynamic> parametres;

  /// Nom du paramètre de recherche. `null` masque le champ.
  final String? rechercheCle;
  final String? indiceRecherche;

  final String messageVide;
  final IconData iconeVide;
  final Widget? actionFlottante;

  /// Bandeau affiché au-dessus de la liste — un total, un avertissement.
  final Widget? enTete;

  @override
  State<ListePaginee<T>> createState() => _ListePagineeState<T>();
}

class _ListePagineeState<T> extends State<ListePaginee<T>> {
  final _api = ApiClient.instance;
  final _defilement = ScrollController();
  final _recherche = TextEditingController();
  Timer? _differe;

  final List<T> _lignes = [];
  int _page = 0;
  int _dernierePage = 1;
  int _total = 0;
  bool _chargement = false;
  bool _premierChargementFait = false;
  String? _erreur;
  bool _horsLigne = false;
  String _terme = '';

  bool get _encore => _page < _dernierePage;

  @override
  void initState() {
    super.initState();
    _defilement.addListener(_auDefilement);
    _charger(reset: true);
  }

  @override
  void dispose() {
    _differe?.cancel();
    _defilement.dispose();
    _recherche.dispose();
    super.dispose();
  }

  void _auDefilement() {
    if (!_defilement.hasClients) return;
    final position = _defilement.position;
    if (position.pixels > position.maxScrollExtent - 300 && _encore && !_chargement) {
      _charger();
    }
  }

  /// La recherche attend que la frappe s'arrête : une requête par caractère
  /// saturerait un réseau de magasin sans rien apporter.
  void _surSaisie(String valeur) {
    _differe?.cancel();
    _differe = Timer(const Duration(milliseconds: 400), () {
      if (valeur == _terme) return;
      setState(() => _terme = valeur);
      _charger(reset: true);
    });
  }

  Future<void> _charger({bool reset = false}) async {
    if (_chargement) return;
    setState(() {
      _chargement = true;
      if (reset) {
        _erreur = null;
        _page = 0;
        _dernierePage = 1;
        _lignes.clear();
        _premierChargementFait = false;
      }
    });

    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        widget.chemin,
        queryParameters: {
          'page': _page + 1,
          ...widget.parametres,
          if (widget.rechercheCle != null && _terme.isNotEmpty)
            widget.rechercheCle!: _terme,
        },
      );

      final corps = res.data!;
      final donnees = corps['data'] as List<dynamic>? ?? [];
      final meta = corps['meta'] as Map<String, dynamic>? ?? {};

      if (!mounted) return;
      setState(() {
        _page = (meta['current_page'] as num?)?.toInt() ?? _page + 1;
        _dernierePage = (meta['last_page'] as num?)?.toInt() ?? _page;
        _total = (meta['total'] as num?)?.toInt() ?? donnees.length;
        _lignes.addAll(
          donnees.map((e) => widget.depuisJson(e as Map<String, dynamic>)),
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(
        title: Text(widget.titre),
        actions: [
          if (_premierChargementFait && _erreur == null)
            Padding(
              padding: const EdgeInsets.only(right: 12),
              child: Center(
                child: Text(
                  '$_total',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppTheme.textMuted,
                  ),
                ),
              ),
            ),
        ],
      ),
      floatingActionButton: widget.actionFlottante,
      body: Column(
        children: [
          if (widget.rechercheCle != null)
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 10, 12, 4),
              child: TextField(
                controller: _recherche,
                onChanged: _surSaisie,
                textInputAction: TextInputAction.search,
                decoration: InputDecoration(
                  hintText: widget.indiceRecherche ?? 'Rechercher…',
                  prefixIcon: const Icon(Icons.search),
                  suffixIcon: _recherche.text.isEmpty
                      ? null
                      : IconButton(
                          icon: const Icon(Icons.close),
                          onPressed: () {
                            _recherche.clear();
                            _surSaisie('');
                          },
                        ),
                  isDense: true,
                ),
              ),
            ),
          if (widget.enTete != null) widget.enTete!,
          Expanded(child: _corps()),
        ],
      ),
    );
  }

  Widget _corps() {
    if (!_premierChargementFait && _chargement) {
      return const ListSkeleton(itemCount: 6, lines: 2);
    }

    if (_erreur != null && _lignes.isEmpty) {
      return ErrorView(
        message: _erreur!,
        offline: _horsLigne,
        onRetry: () => _charger(reset: true),
      );
    }

    if (_lignes.isEmpty) {
      // Une recherche sans résultat n'est pas une liste vide : le dire évite
      // de croire que la base l'est.
      return EmptyView(
        icon: _terme.isEmpty ? widget.iconeVide : Icons.search_off,
        title: _terme.isEmpty ? null : 'Aucun résultat',
        message: _terme.isEmpty
            ? widget.messageVide
            : 'Rien ne correspond à « $_terme ».',
      );
    }

    return RefreshIndicator(
      onRefresh: () => _charger(reset: true),
      child: ListView.builder(
        controller: _defilement,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(12, 4, 12, 88),
        itemCount: _lignes.length + (_encore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index >= _lignes.length) return const SkeletonCard(lines: 2);

          return widget.carte(context, _lignes[index]);
        },
      ),
    );
  }
}

/// Carte de liste commune : un titre en tête, des lignes secondaires, un
/// montant à droite. Les quatre écrans de consultation s'y rangent.
class CarteListe extends StatelessWidget {
  const CarteListe({
    super.key,
    required this.titre,
    this.reference,
    this.sousTitre,
    this.montant,
    this.montantLibelle,
    this.badges = const [],
    this.icone,
    this.couleurIcone,
    this.onTap,
  });

  final String titre;

  /// Code ou numéro, affiché en chasse fixe : c'est ainsi qu'on le reconnaît.
  final String? reference;
  final String? sousTitre;
  final double? montant;
  final String? montantLibelle;
  final List<Widget> badges;
  final IconData? icone;
  final Color? couleurIcone;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      margin: const EdgeInsets.only(bottom: 8),
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (icone != null) ...[
                Container(
                  width: 38,
                  height: 38,
                  decoration: BoxDecoration(
                    color: (couleurIcone ?? AppTheme.brand).withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(icone, size: 19, color: couleurIcone ?? AppTheme.brand),
                ),
                const SizedBox(width: 12),
              ],
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (reference != null)
                      Text(
                        reference!,
                        style: const TextStyle(
                          fontFamily: 'monospace',
                          fontSize: 11.5,
                          color: AppTheme.textMuted,
                        ),
                      ),
                    Text(
                      titre,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w600),
                    ),
                    if (sousTitre != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 2),
                        child: Text(
                          sousTitre!,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontSize: 12.5, color: AppTheme.textMuted),
                        ),
                      ),
                    if (badges.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(top: 8),
                        child: Wrap(spacing: 6, runSpacing: 4, children: badges),
                      ),
                  ],
                ),
              ),
              if (montant != null) ...[
                const SizedBox(width: 10),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    AmountText(formatMoney(montant), fontSize: 15),
                    if (montantLibelle != null)
                      Text(
                        montantLibelle!,
                        style: const TextStyle(fontSize: 10.5, color: AppTheme.textFaint),
                      ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
