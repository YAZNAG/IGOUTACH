import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/ui/skeletons.dart';
import '../../core/ui/states.dart';
import '../../models/lieu_overview.dart';
import '../cash/cash_screen.dart';
import '../expenses/expenses_screen.dart';
import '../sales/sale_detail_screen.dart';
import '../sales/sales_screen.dart';

/// Accueil du responsable : son lieu, rien d'autre.
///
/// Tout vient d'un seul appel (`/me/overview`) : l'écran s'ouvre en une
/// attente, ce qui compte sur un téléphone en réseau lent dans un magasin.
class AccueilResponsableScreen extends StatefulWidget {
  const AccueilResponsableScreen({super.key});

  @override
  State<AccueilResponsableScreen> createState() => _AccueilResponsableScreenState();
}

class _AccueilResponsableScreenState extends State<AccueilResponsableScreen> {
  late Future<LieuOverview> _futur;

  @override
  void initState() {
    super.initState();
    // La vérification des mises à jour a été remontée à la racine
    // (`UpdateWatcher`) : elle couvre désormais les deux profils et se relance
    // au retour d'arrière-plan.
    _futur = _charger();
  }

  Future<LieuOverview> _charger() async {
    final res = await ApiClient.instance.dio
        .get<Map<String, dynamic>>('/me/overview');
    return LieuOverview.fromJson(res.data!['data'] as Map<String, dynamic>);
  }

  Future<void> _rafraichir() async {
    final futur = _charger();
    setState(() => _futur = futur);
    await futur;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.background,
      body: SafeArea(
        child: FutureBuilder<LieuOverview>(
          future: _futur,
          builder: (context, snap) {
            if (snap.connectionState == ConnectionState.waiting) {
              return const ListSkeleton(itemCount: 5, lines: 3);
            }
            if (snap.hasError || !snap.hasData) {
              return ErrorView(
                message: snap.error == null
                    ? 'Impossible de charger l’aperçu.'
                    : friendlyError(snap.error!),
                onRetry: _rafraichir,
              );
            }

            final d = snap.data!;

            return RefreshIndicator(
              onRefresh: _rafraichir,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(14, 12, 14, 90),
                children: [
                  _EnTete(code: d.lieuCode, nom: d.lieuNom),
                  const SizedBox(height: 14),
                  // La caisse d'abord : c'est la question du soir, celle qui
                  // se règle avec l'administration.
                  if (d.caisse != null) ...[
                    _CarteCaisse(caisse: d.caisse!, onChange: _rafraichir),
                    const SizedBox(height: 12),
                  ],
                  _VentesDuJour(jour: d.jour, mois: d.mois),
                  const SizedBox(height: 12),
                  _JourneeDetail(
                    ventes: d.ventesDuJour,
                    charges: d.chargesDuJour,
                    totalCharges: d.chargesJour,
                  ),
                  const SizedBox(height: 12),
                  _CarteValeurStock(stock: d.stock),
                  const SizedBox(height: 12),
                  if (d.aTraiter.total > 0) ...[
                    _ATraiterCarte(aTraiter: d.aTraiter),
                    const SizedBox(height: 12),
                  ],
                  _SanteStock(stock: d.stock),
                  const SizedBox(height: 12),
                  _CarteCreances(creances: d.creances, chargesMois: d.chargesMois),
                  const SizedBox(height: 12),
                  if (d.serie.any((p) => p.chiffre > 0)) ...[
                    _CourbeVentes(serie: d.serie),
                    const SizedBox(height: 12),
                  ],
                  if (d.topArticles.isNotEmpty) _MeilleuresVentes(articles: d.topArticles),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}

// ── En-tête ────────────────────────────────────────────────────────────────

class _EnTete extends StatelessWidget {
  const _EnTete({required this.code, required this.nom});

  final String code;
  final String nom;

  @override
  Widget build(BuildContext context) {
    final maintenant = DateTime.now();

    return Row(
      children: [
        Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            color: AppTheme.brandSoft,
            borderRadius: BorderRadius.circular(12),
          ),
          child: const Icon(Icons.storefront_outlined, color: AppTheme.brand),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                code.isEmpty ? 'Mon lieu' : code,
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
              ),
              Text(
                nom.isEmpty ? formatDate(maintenant) : '$nom · ${formatDate(maintenant)}',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 12, color: AppTheme.textMuted),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

// ── Valeur du stock ────────────────────────────────────────────────────────

class _CarteValeurStock extends StatelessWidget {
  const _CarteValeurStock({required this.stock});

  final StockResume stock;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppTheme.brand, AppTheme.brandDeep],
        ),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Valeur de mon stock',
            style: TextStyle(fontSize: 12, color: Colors.white70),
          ),
          const SizedBox(height: 2),
          Text(
            formatMoney(stock.valeur),
            style: const TextStyle(
              fontSize: 26,
              fontWeight: FontWeight.w700,
              color: Colors.white,
              letterSpacing: -0.5,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            '${formatQuantity(stock.references)} références · '
            '${formatQuantity(stock.unites)} unités',
            style: const TextStyle(fontSize: 12, color: Colors.white70),
          ),
        ],
      ),
    );
  }
}

// ── Caisse ─────────────────────────────────────────────────────────────────

/// Ce qu'il doit y avoir dans le tiroir, et comment on en sort.
///
/// Le solde est décomposé : fonds, entrées, sorties. Un responsable qui ne
/// tombe pas juste doit pouvoir dire où l'écart se trouve, pas seulement
/// qu'il existe.
class _CarteCaisse extends StatelessWidget {
  const _CarteCaisse({required this.caisse, required this.onChange});

  final Caisse caisse;
  final Future<void> Function() onChange;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppTheme.navy,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.point_of_sale_outlined, size: 16, color: Colors.white70),
              const SizedBox(width: 6),
              const Expanded(
                child: Text(
                  'Solde de la caisse',
                  style: TextStyle(fontSize: 12, color: Colors.white70),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: caisse.sessionOuverte
                      ? AppTheme.success.withValues(alpha: 0.22)
                      : Colors.white24,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  caisse.sessionOuverte ? 'Ouverte' : 'Non ouverte',
                  style: const TextStyle(fontSize: 10.5, color: Colors.white),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            formatMoney(caisse.solde),
            style: const TextStyle(
              fontSize: 27,
              fontWeight: FontWeight.w700,
              color: Colors.white,
              letterSpacing: -0.6,
            ),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              _Poste(libelle: 'Fonds', valeur: caisse.fonds),
              _Poste(libelle: 'Entrées', valeur: caisse.entrees, signe: '+'),
              _Poste(libelle: 'Charges', valeur: caisse.chargesEspeces, signe: '−'),
              _Poste(libelle: 'Remis', valeur: caisse.remises, signe: '−'),
            ],
          ),
          if (caisse.remisesEnAttente > 0) ...[
            const SizedBox(height: 8),
            Text(
              '${formatMoney(caisse.remisesEnAttente)} en attente de confirmation '
              'par l\'administration.',
              style: const TextStyle(fontSize: 11, color: Colors.white70),
            ),
          ],
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () async {
                    await Navigator.of(context).push<void>(
                      MaterialPageRoute(builder: (_) => const CashScreen()),
                    );
                    await onChange();
                  },
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.white,
                    side: const BorderSide(color: Colors.white38),
                    visualDensity: VisualDensity.compact,
                  ),
                  icon: const Icon(Icons.savings_outlined, size: 17),
                  label: Text(
                    caisse.sessionOuverte
                        ? 'Gérer la caisse'
                        : 'Ouvrir la caisse de départ',
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _Poste extends StatelessWidget {
  const _Poste({required this.libelle, required this.valeur, this.signe = ''});

  final String libelle;
  final double valeur;
  final String signe;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            libelle,
            style: const TextStyle(fontSize: 10, color: Colors.white60),
          ),
          Text(
            '$signe${formatMoney(valeur)}',
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: Colors.white,
            ),
          ),
        ],
      ),
    );
  }
}

// ── Journée ────────────────────────────────────────────────────────────────

/// Ce qui s'est passé aujourd'hui, ligne à ligne.
class _JourneeDetail extends StatelessWidget {
  const _JourneeDetail({
    required this.ventes,
    required this.charges,
    required this.totalCharges,
  });

  final List<VenteDuJour> ventes;
  final List<ChargeDuJour> charges;
  final double totalCharges;

  @override
  Widget build(BuildContext context) {
    return _Carte(
      titre: 'La journée',
      enfant: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _SousTitre(
            texte: 'Ventes',
            compte: ventes.length,
            onVoirTout: () => Navigator.of(context).push<void>(
              MaterialPageRoute(builder: (_) => const SalesScreen()),
            ),
          ),
          if (ventes.isEmpty)
            const _LigneVide(texte: 'Aucune vente aujourd\'hui.')
          else
            ...ventes.take(6).map(
                  (v) => _LigneJour(
                    titre: v.reference,
                    sousTitre: [
                      v.client ?? 'Client de passage',
                      if (v.heure != null) v.heure!,
                    ].join(' · '),
                    montant: v.total,
                    // Ce qui reste dû se voit tout de suite : c'est ce qui
                    // n'est pas dans le tiroir.
                    note: v.restant > 0 ? '${formatMoney(v.restant)} dû' : null,
                    couleurNote: AppTheme.warning,
                    onTap: () => Navigator.of(context).push<void>(
                      MaterialPageRoute(
                        builder: (_) => SaleDetailScreen(saleId: v.id),
                      ),
                    ),
                  ),
                ),
          if (ventes.length > 6)
            _LigneVide(texte: 'et ${ventes.length - 6} autre(s)…'),
          const SizedBox(height: 10),
          _SousTitre(
            texte: 'Charges',
            compte: charges.length,
            montant: totalCharges,
            onVoirTout: () => Navigator.of(context).push<void>(
              MaterialPageRoute(builder: (_) => const ExpensesScreen()),
            ),
          ),
          if (charges.isEmpty)
            const _LigneVide(texte: 'Aucune charge aujourd\'hui.')
          else
            ...charges.take(6).map(
                  (c) => _LigneJour(
                    titre: c.libelle,
                    sousTitre: c.categorie ?? '—',
                    montant: c.montant,
                    note: c.due ? 'à régler' : null,
                    couleurNote: AppTheme.danger,
                  ),
                ),
          if (charges.length > 6)
            _LigneVide(texte: 'et ${charges.length - 6} autre(s)…'),
        ],
      ),
    );
  }
}

class _SousTitre extends StatelessWidget {
  const _SousTitre({
    required this.texte,
    required this.compte,
    this.montant,
    this.onVoirTout,
  });

  final String texte;
  final int compte;
  final double? montant;
  final VoidCallback? onVoirTout;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        children: [
          Text(
            '$texte ($compte)',
            style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600),
          ),
          if (montant != null && montant! > 0) ...[
            const SizedBox(width: 6),
            Text(
              formatMoney(montant),
              style: const TextStyle(fontSize: 12, color: AppTheme.textMuted),
            ),
          ],
          const Spacer(),
          if (onVoirTout != null)
            TextButton(
              onPressed: onVoirTout,
              style: TextButton.styleFrom(
                visualDensity: VisualDensity.compact,
                padding: const EdgeInsets.symmetric(horizontal: 6),
              ),
              child: const Text('Tout voir', style: TextStyle(fontSize: 12)),
            ),
        ],
      ),
    );
  }
}

class _LigneJour extends StatelessWidget {
  const _LigneJour({
    required this.titre,
    required this.sousTitre,
    required this.montant,
    this.note,
    this.couleurNote,
    this.onTap,
  });

  final String titre;
  final String sousTitre;
  final double montant;
  final String? note;
  final Color? couleurNote;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 2),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    titre,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500),
                  ),
                  Text(
                    sousTitre,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  formatMoney(montant),
                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                ),
                if (note != null)
                  Text(
                    note!,
                    style: TextStyle(fontSize: 10.5, color: couleurNote ?? AppTheme.textFaint),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _LigneVide extends StatelessWidget {
  const _LigneVide({required this.texte});

  final String texte;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Text(
        texte,
        style: const TextStyle(fontSize: 12, color: AppTheme.textFaint),
      ),
    );
  }
}

// ── Ventes ─────────────────────────────────────────────────────────────────

class _VentesDuJour extends StatelessWidget {
  const _VentesDuJour({required this.jour, required this.mois});

  final VentesPeriode jour;
  final VentesPeriode mois;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _Tuile(
            libelle: 'Ventes du jour',
            valeur: formatMoney(jour.chiffre),
            // Le cumul du mois donne l'échelle : 800 DH dans la journée se
            // lit autrement selon qu'on en est à 5 000 ou à 90 000.
            detail: '${jour.nombre} vente${jour.nombre > 1 ? 's' : ''} · '
                'mois ${formatMoney(mois.chiffre)}',
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _Tuile(
            libelle: 'Encaissé',
            valeur: formatMoney(jour.encaisse),
            // Ce qui reste dû saute aux yeux : c'est le chiffre qui dit si
            // l'on vend à crédit sans s'en rendre compte.
            detail: jour.aCredit > 0
                ? '${formatMoney(jour.aCredit)} à crédit'
                : 'tout encaissé',
            couleurDetail: jour.aCredit > 0 ? AppTheme.warning : AppTheme.success,
          ),
        ),
      ],
    );
  }
}

class _Tuile extends StatelessWidget {
  const _Tuile({
    required this.libelle,
    required this.valeur,
    this.detail,
    this.couleurDetail,
    this.icone,
  });

  final String libelle;
  final String valeur;
  final String? detail;
  final Color? couleurDetail;
  final IconData? icone;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppTheme.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              if (icone != null) ...[
                Icon(icone, size: 14, color: AppTheme.textMuted),
                const SizedBox(width: 5),
              ],
              Expanded(
                child: Text(
                  libelle,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                ),
              ),
            ],
          ),
          const SizedBox(height: 3),
          Text(
            valeur,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              letterSpacing: -0.3,
            ),
          ),
          if (detail != null) ...[
            const SizedBox(height: 2),
            Text(
              detail!,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(fontSize: 10.5, color: couleurDetail ?? AppTheme.textFaint),
            ),
          ],
        ],
      ),
    );
  }
}

// ── À traiter ──────────────────────────────────────────────────────────────

class _ATraiterCarte extends StatelessWidget {
  const _ATraiterCarte({required this.aTraiter});

  final ATraiter aTraiter;

  @override
  Widget build(BuildContext context) {
    final lignes = <(IconData, String, int, Color)>[
      (Icons.swap_horiz, 'Demandes à accorder', aTraiter.demandesTransfert, AppTheme.brand),
      (Icons.local_shipping_outlined, 'Transferts à recevoir', aTraiter.transfertsEntrants, AppTheme.warning),
      (Icons.fact_check_outlined, 'Inventaires en cours', aTraiter.inventairesOuverts, AppTheme.textMuted),
      (Icons.receipt_long_outlined, 'Ventes impayées', aTraiter.ventesImpayees, AppTheme.danger),
    ].where((l) => l.$3 > 0).toList();

    return _Carte(
      titre: 'À traiter',
      badge: '${aTraiter.total}',
      enfant: Column(
        children: [
          for (final (icone, libelle, nb, couleur) in lignes)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 6),
              child: Row(
                children: [
                  Icon(icone, size: 18, color: couleur),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(libelle, style: const TextStyle(fontSize: 13)),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: couleur.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      '$nb',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: couleur,
                      ),
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

// ── Santé du stock ─────────────────────────────────────────────────────────

class _SanteStock extends StatelessWidget {
  const _SanteStock({required this.stock});

  final StockResume stock;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _Tuile(
            libelle: 'Sous le seuil',
            valeur: '${stock.sousSeuil}',
            detail: 'à réapprovisionner',
            couleurDetail: stock.sousSeuil > 0 ? AppTheme.warning : AppTheme.textFaint,
            icone: Icons.trending_down,
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _Tuile(
            libelle: 'En rupture',
            valeur: '${stock.ruptures}',
            detail: stock.ruptures > 0 ? 'invendables' : 'aucune',
            couleurDetail: stock.ruptures > 0 ? AppTheme.danger : AppTheme.success,
            icone: Icons.remove_shopping_cart_outlined,
          ),
        ),
      ],
    );
  }
}

// ── Créances et charges ────────────────────────────────────────────────────

class _CarteCreances extends StatelessWidget {
  const _CarteCreances({required this.creances, required this.chargesMois});

  final Creances creances;
  final double chargesMois;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _Tuile(
            libelle: 'Encours clients',
            valeur: formatMoney(creances.total),
            detail: creances.horsPlafond > 0
                ? '${creances.horsPlafond} hors plafond'
                : '${creances.clients} client${creances.clients > 1 ? 's' : ''}',
            couleurDetail: creances.horsPlafond > 0 ? AppTheme.danger : AppTheme.textFaint,
            icone: Icons.account_balance_wallet_outlined,
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _Tuile(
            libelle: 'Charges du mois',
            valeur: formatMoney(chargesMois),
            icone: Icons.receipt_outlined,
          ),
        ),
      ],
    );
  }
}

// ── Courbe des ventes ──────────────────────────────────────────────────────

class _CourbeVentes extends StatelessWidget {
  const _CourbeVentes({required this.serie});

  final List<PointJour> serie;

  @override
  Widget build(BuildContext context) {
    final max = serie.map((p) => p.chiffre).reduce((a, b) => a > b ? a : b);

    return _Carte(
      titre: 'Ventes des 14 derniers jours',
      enfant: SizedBox(
        height: 90,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            for (final p in serie)
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 1.5),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      // Hauteur proportionnelle au meilleur jour ; une barre
                      // minimale reste visible pour un jour à zéro, sinon la
                      // journée disparaîtrait du graphique.
                      Container(
                        height: max > 0 ? (p.chiffre / max * 68).clamp(2.0, 68.0) : 2,
                        decoration: BoxDecoration(
                          color: p.chiffre > 0 ? AppTheme.brand : AppTheme.border,
                          borderRadius: const BorderRadius.vertical(top: Radius.circular(3)),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        p.libelle.split('/').first,
                        style: const TextStyle(fontSize: 8, color: AppTheme.textFaint),
                      ),
                    ],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

// ── Meilleures ventes ──────────────────────────────────────────────────────

class _MeilleuresVentes extends StatelessWidget {
  const _MeilleuresVentes({required this.articles});

  final List<ArticleVendu> articles;

  @override
  Widget build(BuildContext context) {
    final max = articles.first.chiffre;

    return _Carte(
      titre: 'Meilleures ventes du mois',
      enfant: Column(
        children: [
          for (final a in articles)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 5),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          a.nom,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontSize: 12.5),
                        ),
                      ),
                      Text(
                        formatMoney(a.chiffre),
                        style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(3),
                    child: LinearProgressIndicator(
                      value: max > 0 ? a.chiffre / max : 0,
                      minHeight: 4,
                      backgroundColor: AppTheme.border,
                      valueColor: const AlwaysStoppedAnimation(AppTheme.brand),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${formatQuantity(a.quantite)} unité${a.quantite > 1 ? 's' : ''}',
                    style: const TextStyle(fontSize: 10, color: AppTheme.textFaint),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

// ── Carte générique ────────────────────────────────────────────────────────

class _Carte extends StatelessWidget {
  const _Carte({required this.titre, required this.enfant, this.badge});

  final String titre;
  final Widget enfant;
  final String? badge;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppTheme.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  titre,
                  style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600),
                ),
              ),
              if (badge != null)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: AppTheme.brandSoft,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    badge!,
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: AppTheme.brand,
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 10),
          enfant,
        ],
      ),
    );
  }
}
