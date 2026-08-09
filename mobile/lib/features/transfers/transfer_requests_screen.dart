import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../core/ui/states.dart';

/// Demandes de réapprovisionnement du lieu.
///
/// Le responsable demande, la direction accorde : rien ne bouge tant que la
/// demande n'est pas approuvée. L'écran le dit explicitement pour éviter
/// qu'on attende une marchandise déjà considérée comme partie.
class TransferRequestsScreen extends StatefulWidget {
  const TransferRequestsScreen({super.key});

  @override
  State<TransferRequestsScreen> createState() => _TransferRequestsScreenState();
}

class _TransferRequestsScreenState extends State<TransferRequestsScreen> {
  late Future<List<_Demande>> _futur;

  @override
  void initState() {
    super.initState();
    _futur = _charger();
  }

  Future<List<_Demande>> _charger() async {
    final res = await ApiClient.instance.dio.get<Map<String, dynamic>>('/transfers');
    final lignes = (res.data?['data'] as List<dynamic>? ?? []);

    return lignes
        .map((e) => _Demande.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<void> _rafraichir() async {
    setState(() => _futur = _charger());
    await _futur;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(title: const Text('Demandes de stock')),
      body: RefreshIndicator(
        onRefresh: _rafraichir,
        child: FutureBuilder<List<_Demande>>(
          future: _futur,
          builder: (context, snap) {
            if (snap.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }

            if (snap.hasError) {
              return ErrorView(
                message: 'Impossible de charger les demandes.',
                onRetry: _rafraichir,
              );
            }

            final demandes = snap.data ?? const <_Demande>[];

            if (demandes.isEmpty) {
              return ListView(
                children: const [
                  SizedBox(height: 80),
                  EmptyView(
                    icon: Icons.swap_horiz_rounded,
                    title: 'Aucune demande',
                    message: 'Demandez un réapprovisionnement quand un '
                        'article manque dans votre lieu.',
                  ),
                ],
              );
            }

            return ListView.separated(
              padding: const EdgeInsets.all(14),
              itemCount: demandes.length,
              separatorBuilder: (_, _) => const SizedBox(height: 10),
              itemBuilder: (_, i) => _CarteDemande(demande: demandes[i]),
            );
          },
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => Navigator.of(context)
            .push(MaterialPageRoute<bool>(builder: (_) => const _NouvelleDemande()))
            .then((cree) {
          if (cree == true) _rafraichir();
        }),
        icon: const Icon(Icons.add),
        label: const Text('Demander'),
      ),
    );
  }
}

class _Demande {
  const _Demande({
    required this.reference,
    required this.depuis,
    required this.vers,
    required this.statut,
  });

  final String reference;
  final String depuis;
  final String vers;
  final String statut;

  factory _Demande.fromJson(Map<String, dynamic> j) => _Demande(
        reference: j['reference'] as String? ?? '—',
        depuis: j['from'] as String? ?? '—',
        vers: j['to'] as String? ?? '—',
        statut: j['status'] as String? ?? '',
      );
}

class _CarteDemande extends StatelessWidget {
  const _CarteDemande({required this.demande});

  final _Demande demande;

  ({String texte, Color fond, Color encre}) get _etat => switch (demande.statut) {
        'requested' => (texte: 'En attente', fond: AppTheme.warningSoft, encre: AppTheme.warning),
        'refused' => (texte: 'Refusée', fond: AppTheme.dangerSoft, encre: AppTheme.danger),
        'in_transit' => (texte: 'En transit', fond: AppTheme.brandSoft, encre: AppTheme.brand),
        'received' => (texte: 'Reçue', fond: AppTheme.successSoft, encre: AppTheme.success),
        _ => (texte: demande.statut, fond: AppTheme.skeleton, encre: AppTheme.textMuted),
      };

  @override
  Widget build(BuildContext context) {
    final etat = _etat;

    return Container(
      padding: const EdgeInsets.all(13),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppTheme.radiusCard),
        border: Border.all(color: AppTheme.border),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  demande.reference,
                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 2),
                Text(
                  '${demande.depuis} → ${demande.vers}',
                  style: const TextStyle(fontSize: 12, color: AppTheme.textMuted),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
            decoration: BoxDecoration(
              color: etat.fond,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              etat.texte,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: etat.encre,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Formulaire de demande : lieu source, articles, quantités.
class _NouvelleDemande extends StatefulWidget {
  const _NouvelleDemande();

  @override
  State<_NouvelleDemande> createState() => _NouvelleDemandeState();
}

class _NouvelleDemandeState extends State<_NouvelleDemande> {
  final Map<int, ({String nom, int quantite})> _lignes = {};
  int? _sourceId;
  List<Map<String, dynamic>> _lieux = const [];
  bool _envoi = false;
  String? _erreur;

  @override
  void initState() {
    super.initState();
    _chargerLieux();
  }

  Future<void> _chargerLieux() async {
    try {
      final res = await ApiClient.instance.dio.get<Map<String, dynamic>>('/warehouses');
      setState(() {
        _lieux = (res.data?['data'] as List<dynamic>? ?? [])
            .cast<Map<String, dynamic>>();
      });
    } catch (_) {
      // Le formulaire reste utilisable : la saisie du lieu source est le seul
      // élément manquant, et l'erreur remontera à l'envoi.
    }
  }

  Future<void> _envoyer() async {
    if (_sourceId == null || _lignes.isEmpty) return;

    setState(() {
      _envoi = true;
      _erreur = null;
    });

    try {
      await ApiClient.instance.dio.post<Map<String, dynamic>>(
        '/transfer-requests',
        data: {
          'from_warehouse_id': _sourceId,
          // Le lieu de destination est celui du compte : le serveur refuse
          // toute autre valeur, inutile de le demander à l'écran.
          'to_warehouse_id': _lieuxDestination,
          'lines': _lignes.entries
              .map((e) => {'product_id': e.key, 'quantity': e.value.quantite})
              .toList(),
        },
      );

      if (mounted) Navigator.of(context).pop(true);
    } catch (e) {
      setState(() => _erreur = 'Envoi impossible. Vérifiez votre connexion.');
    } finally {
      if (mounted) setState(() => _envoi = false);
    }
  }

  int? get _lieuxDestination =>
      _lieux.length == 1 ? _lieux.first['id'] as int? : null;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(title: const Text('Nouvelle demande')),
      body: ListView(
        padding: const EdgeInsets.all(14),
        children: [
          const Text(
            'Approvisionner depuis',
            style: TextStyle(fontSize: 12, color: AppTheme.textMuted),
          ),
          const SizedBox(height: 6),
          DropdownButtonFormField<int>(
            initialValue: _sourceId,
            items: [
              for (final l in _lieux)
                DropdownMenuItem(
                  value: l['id'] as int,
                  child: Text('${l['code']} · ${l['name']}'),
                ),
            ],
            onChanged: (v) => setState(() => _sourceId = v),
            decoration: const InputDecoration(hintText: 'Choisir un lieu…'),
          ),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(11),
            decoration: BoxDecoration(
              color: const Color(0xFFF7F7F9),
              borderRadius: BorderRadius.circular(AppTheme.radiusField),
              border: Border.all(color: AppTheme.borderStrong),
            ),
            child: const Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(Icons.info_outline, size: 15, color: AppTheme.textMuted),
                SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Rien ne quitte le dépôt tant que la direction n’a pas '
                    'accordé la demande.',
                    style: TextStyle(fontSize: 12, color: AppTheme.textMuted, height: 1.4),
                  ),
                ),
              ],
            ),
          ),
          if (_erreur != null) ...[
            const SizedBox(height: 12),
            Text(_erreur!, style: const TextStyle(color: AppTheme.danger, fontSize: 13)),
          ],
          const SizedBox(height: 20),
          FilledButton(
            onPressed: _envoi || _sourceId == null || _lignes.isEmpty ? null : _envoyer,
            child: Text(_envoi ? 'Envoi…' : 'Envoyer la demande'),
          ),
        ],
      ),
    );
  }
}
