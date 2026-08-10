import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/ui/blocks.dart';
import '../../core/ui/skeletons.dart';
import '../../core/ui/states.dart';
import '../../core/ui/feedback.dart';
import '../../models/customer_overview.dart';
import '../sales/sales_screen.dart' show downloadSalePdf;
import '../shared/payment_sheet.dart';

/// Fiche client : identité, crédit, achats et règlements.
///
/// Tout vient d'un seul appel (`/customers/{id}/overview`) : la fiche s'ouvre
/// d'un coup au lieu d'enchaîner quatre requêtes sur un réseau lent.
class CustomerDetailScreen extends StatefulWidget {
  const CustomerDetailScreen({super.key, required this.customerId, this.customerName});

  final int customerId;
  final String? customerName;

  @override
  State<CustomerDetailScreen> createState() => _CustomerDetailScreenState();
}

class _CustomerDetailScreenState extends State<CustomerDetailScreen> {
  final _api = ApiClient.instance;

  CustomerOverview? _data;
  bool _loading = true;
  String? _error;
  bool _offline = false;

  /// Vrai si l'encours a bougé : l'écran appelant doit se rafraîchir.
  bool _modifie = false;

  @override
  void initState() {
    super.initState();
    _charger();
  }

  Future<void> _charger() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/customers/${widget.customerId}/overview',
      );
      if (!mounted) return;
      setState(() {
        _data = CustomerOverview.fromJson(res.data!['data'] as Map<String, dynamic>);
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = friendlyError(e);
        _offline = isNetworkError(e);
        _loading = false;
      });
    }
  }

  Future<void> _encaisser() async {
    final d = _data;
    if (d == null) return;

    final messenger = ScaffoldMessenger.of(context);
    final saved = await showPaymentSheet(
      context,
      customerId: d.fiche.id,
      customerName: d.fiche.nom,
      dueAmount: d.credit.encours > 0 ? d.credit.encours : null,
    );

    if (!mounted || !saved) return;
    _modifie = true;
    showSuccessSnack(messenger, 'Encaissement enregistré.');
    _charger();
  }

  @override
  Widget build(BuildContext context) {
    final peutEncaisser = context.watch<AuthProvider>().can('payment.create');
    final d = _data;

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) Navigator.of(context).pop(_modifie);
      },
      child: Scaffold(
        backgroundColor: AppTheme.background,
        appBar: AppBar(title: Text(d?.fiche.nom ?? widget.customerName ?? 'Client')),
        floatingActionButton: peutEncaisser && d != null && d.credit.encours > 0
            ? FloatingActionButton.extended(
                onPressed: _encaisser,
                icon: const Icon(Icons.payments_outlined),
                label: const Text('Encaisser'),
              )
            : null,
        body: _loading
            ? const ListSkeleton(itemCount: 5, hasLeading: true)
            : _error != null
                ? ErrorView(message: _error!, offline: _offline, onRetry: _charger)
                : RefreshIndicator(
                    onRefresh: _charger,
                    child: ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(14, 12, 14, 96),
                      children: [
                        _CarteCredit(credit: d!.credit),
                        const SizedBox(height: 12),
                        _CarteStats(stats: d.stats),
                        const SizedBox(height: 12),
                        _CarteIdentite(fiche: d.fiche),
                        const SizedBox(height: 12),
                        _CarteVentes(ventes: d.ventes),
                        const SizedBox(height: 12),
                        _CarteReglements(reglements: d.reglements),
                      ],
                    ),
                  ),
      ),
    );
  }
}

// ── Crédit ─────────────────────────────────────────────────────────────────

class _CarteCredit extends StatelessWidget {
  const _CarteCredit({required this.credit});

  final CreditClient credit;

  @override
  Widget build(BuildContext context) {
    final alerte = credit.horsPlafond || credit.bloque;
    final couleur = alerte
        ? AppTheme.danger
        : credit.encours > 0
            ? AppTheme.warning
            : AppTheme.success;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: alerte ? AppTheme.dangerSoft : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: alerte ? couleur.withValues(alpha: 0.4) : AppTheme.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Text('Encours', style: TextStyle(fontSize: 12, color: AppTheme.textMuted)),
              const Spacer(),
              if (credit.bloque)
                const StatusBadge(label: 'Bloqué', color: AppTheme.danger)
              else if (credit.horsPlafond)
                const StatusBadge(label: 'Hors plafond', color: AppTheme.danger),
            ],
          ),
          const SizedBox(height: 2),
          Text(
            formatMoney(credit.encours),
            style: TextStyle(fontSize: 26, fontWeight: FontWeight.w700, color: couleur),
          ),
          if (credit.plafond > 0) ...[
            const SizedBox(height: 10),
            ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: LinearProgressIndicator(
                // Bornée à 1 : au-delà du plafond la barre reste pleine, elle
                // ne peut pas déborder de son cadre.
                value: ((credit.partPlafond ?? 0) / 100).clamp(0.0, 1.0),
                minHeight: 6,
                backgroundColor: AppTheme.border,
                valueColor: AlwaysStoppedAnimation(couleur),
              ),
            ),
            const SizedBox(height: 5),
            Text(
              'Plafond ${formatMoney(credit.plafond)}'
              '${credit.partPlafond != null ? ' · ${credit.partPlafond!.toStringAsFixed(0)} % utilisé' : ''}',
              style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
            ),
          ] else ...[
            const SizedBox(height: 6),
            const Text(
              'Aucun plafond fixé — la vente à crédit sera refusée.',
              style: TextStyle(fontSize: 11, color: AppTheme.textMuted),
            ),
          ],
          if (credit.impayees > 0) ...[
            const SizedBox(height: 8),
            Text(
              '${credit.impayees} facture${credit.impayees > 1 ? 's' : ''} non soldée'
              '${credit.impayees > 1 ? 's' : ''}',
              style: const TextStyle(fontSize: 11.5, color: AppTheme.warning),
            ),
          ],
        ],
      ),
    );
  }
}

// ── Statistiques ───────────────────────────────────────────────────────────

class _CarteStats extends StatelessWidget {
  const _CarteStats({required this.stats});

  final StatsClient stats;

  @override
  Widget build(BuildContext context) {
    return _Carte(
      titre: 'Son activité',
      enfant: Column(
        children: [
          Row(
            children: [
              Expanded(child: _Mini('Achats', formatMoney(stats.totalAchete))),
              Expanded(child: _Mini('Règlements', formatMoney(stats.totalRegle))),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(child: _Mini('Ventes', '${stats.nombreVentes}')),
              Expanded(child: _Mini('Panier moyen', formatMoney(stats.panierMoyen))),
            ],
          ),
          if (stats.dernierAchat != null) ...[
            const SizedBox(height: 10),
            Align(
              alignment: Alignment.centerLeft,
              child: Text(
                'Dernier achat : ${stats.dernierAchat}',
                style: const TextStyle(fontSize: 11.5, color: AppTheme.textMuted),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _Mini extends StatelessWidget {
  const _Mini(this.libelle, this.valeur);

  final String libelle;
  final String valeur;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(libelle, style: const TextStyle(fontSize: 10.5, color: AppTheme.textMuted)),
        const SizedBox(height: 2),
        Text(
          valeur,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700),
        ),
      ],
    );
  }
}

// ── Identité ───────────────────────────────────────────────────────────────

class _CarteIdentite extends StatelessWidget {
  const _CarteIdentite({required this.fiche});

  final FicheClient fiche;

  @override
  Widget build(BuildContext context) {
    final lignes = <(String, String?)>[
      ('Code', fiche.code),
      ('Type', fiche.estSociete ? 'Société' : 'Particulier'),
      ('Contact', fiche.contact),
      ('Téléphone', fiche.telephone),
      ('E-mail', fiche.email),
      ('Adresse', [fiche.adresse, fiche.ville].where((e) => e != null && e.isNotEmpty).join(', ')),
      ('ICE', fiche.ice),
      ('Tarif appliqué', fiche.typePrix),
      ('Créé par', fiche.creePar),
      ('Notes', fiche.notes),
    ].where((l) => l.$2 != null && l.$2!.trim().isNotEmpty).toList();

    return _Carte(
      titre: 'Informations',
      enfant: Column(
        children: [
          for (final (libelle, valeur) in lignes)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 5),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SizedBox(
                    width: 108,
                    child: Text(
                      libelle,
                      style: const TextStyle(fontSize: 11.5, color: AppTheme.textMuted),
                    ),
                  ),
                  Expanded(
                    child: Text(valeur!, style: const TextStyle(fontSize: 12.5)),
                  ),
                ],
              ),
            ),
          if (!fiche.actif)
            const Padding(
              padding: EdgeInsets.only(top: 8),
              child: StatusBadge(label: 'Client inactif', color: AppTheme.textFaint),
            ),
        ],
      ),
    );
  }
}

// ── Ventes ─────────────────────────────────────────────────────────────────

class _CarteVentes extends StatelessWidget {
  const _CarteVentes({required this.ventes});

  final List<VenteClient> ventes;

  @override
  Widget build(BuildContext context) {
    return _Carte(
      titre: 'Historique des ventes',
      badge: '${ventes.length}',
      enfant: ventes.isEmpty
          ? const Padding(
              padding: EdgeInsets.symmetric(vertical: 16),
              child: Text(
                'Aucun achat enregistré.',
                style: TextStyle(fontSize: 12.5, color: AppTheme.textMuted),
              ),
            )
          : Column(
              children: [
                for (final v in ventes)
                  InkWell(
                    onTap: () => downloadSalePdf(context, v.id, v.reference),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  v.reference,
                                  style: TextStyle(
                                    fontSize: 12.5,
                                    fontWeight: FontWeight.w600,
                                    // Une vente annulée reste visible mais se
                                    // distingue : elle ne compte plus.
                                    decoration: v.annulee ? TextDecoration.lineThrough : null,
                                    color: v.annulee ? AppTheme.textFaint : AppTheme.ink,
                                  ),
                                ),
                                Text(
                                  v.date ?? '—',
                                  style: const TextStyle(fontSize: 10.5, color: AppTheme.textMuted),
                                ),
                              ],
                            ),
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                formatMoney(v.total),
                                style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                              ),
                              if (!v.annulee && v.reste > 0)
                                Text(
                                  'reste ${formatMoney(v.reste)}',
                                  style: const TextStyle(fontSize: 10, color: AppTheme.warning),
                                )
                              else if (!v.annulee)
                                const Text(
                                  'payée',
                                  style: TextStyle(fontSize: 10, color: AppTheme.success),
                                ),
                            ],
                          ),
                          const SizedBox(width: 6),
                          const Icon(Icons.picture_as_pdf_outlined, size: 16, color: AppTheme.textFaint),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
    );
  }
}

// ── Règlements ─────────────────────────────────────────────────────────────

class _CarteReglements extends StatelessWidget {
  const _CarteReglements({required this.reglements});

  final List<ReglementClient> reglements;

  @override
  Widget build(BuildContext context) {
    return _Carte(
      titre: 'Règlements reçus',
      badge: '${reglements.length}',
      enfant: reglements.isEmpty
          ? const Padding(
              padding: EdgeInsets.symmetric(vertical: 16),
              child: Text(
                'Aucun règlement enregistré.',
                style: TextStyle(fontSize: 12.5, color: AppTheme.textMuted),
              ),
            )
          : Column(
              children: [
                for (final r in reglements)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 7),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(r.reference, style: const TextStyle(fontSize: 12.5)),
                              Text(
                                [r.date, r.mode].where((e) => e != null).join(' · '),
                                style: const TextStyle(fontSize: 10.5, color: AppTheme.textMuted),
                              ),
                            ],
                          ),
                        ),
                        Text(
                          formatMoney(r.montant),
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: AppTheme.success,
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
          const SizedBox(height: 8),
          enfant,
        ],
      ),
    );
  }
}
