import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/product.dart';
import '../shared/product_picker.dart';

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
class _LigneDemande {
  _LigneDemande({
    required this.productId,
    required this.nom,
    this.sku,
    this.stockSource,
  });

  final int productId;
  final String nom;
  final String? sku;

  /// Stock du lieu source au moment du choix. Demander davantage n'est pas
  /// interdit — la direction peut compléter — mais doit se voir.
  final int? stockSource;
  int quantite = 1;

  bool get depasse => stockSource != null && quantite > stockSource!;
}

class _NouvelleDemande extends StatefulWidget {
  const _NouvelleDemande();

  @override
  State<_NouvelleDemande> createState() => _NouvelleDemandeState();
}

class _NouvelleDemandeState extends State<_NouvelleDemande> {
  final List<_LigneDemande> _lignes = [];
  final _note = TextEditingController();

  List<Map<String, dynamic>> _lieux = const [];
  Map<String, dynamic>? _maDestination;
  int? _sourceId;
  int? _destinationId;
  bool _envoi = false;
  String? _erreur;

  @override
  void initState() {
    super.initState();
    // La destination est le lieu du compte : on demande pour soi. Un compte
    // sans lieu (la direction) la choisit dans la liste.
    _destinationId = context.read<AuthProvider>().user?.warehouseId;
    _chargerLieux();
  }

  @override
  void dispose() {
    _note.dispose();
    super.dispose();
  }

  /// Les lieux sollicitables. La liste generale (/warehouses) est cloisonnee :
  /// un responsable n'y voit que le sien — celui-la meme qu'il ne peut pas
  /// choisir comme source. D'ou ce point d'acces dedie, qui renvoie les autres.
  Future<void> _chargerLieux() async {
    try {
      final res = await ApiClient.instance.dio
          .get<Map<String, dynamic>>('/transfer-requests/sources');
      if (!mounted) return;
      final meta = res.data?['meta'] as Map<String, dynamic>?;
      setState(() {
        _lieux = (res.data?['data'] as List<dynamic>? ?? []).cast<Map<String, dynamic>>();
        _destinationId ??= (meta?['destination_id'] as num?)?.toInt();
        _maDestination = meta?['destination'] as Map<String, dynamic>?;
      });
    } catch (e) {
      if (mounted) setState(() => _erreur = friendlyError(e));
    }
  }

  /// Le choix d'une autre source vide la liste : le stock affiché à côté de
  /// chaque article était celui de l'ancienne source, il ne voudrait plus rien.
  void _changerSource(int? id) {
    if (id == _sourceId) return;
    setState(() {
      _sourceId = id;
      _lignes.clear();
      _erreur = null;
    });
  }

  void _ajouter(Product p) {
    setState(() {
      final existante = _lignes.where((l) => l.productId == p.id).firstOrNull;
      if (existante != null) {
        existante.quantite += 1;
        return;
      }
      _lignes.insert(
        0,
        _LigneDemande(productId: p.id, nom: p.name, sku: p.sku, stockSource: p.currentStock),
      );
    });
  }

  /// Ce qui empêche l'envoi, dit en clair. Un bouton grisé sans explication
  /// laissait le responsable deviner ce qui manquait.
  String? get _obstacle {
    if (_destinationId == null) return 'Choisissez le lieu à approvisionner.';
    if (_sourceId == null) return 'Choisissez le lieu d’où vient la marchandise.';
    if (_sourceId == _destinationId) return 'La source et la destination doivent différer.';
    if (_lignes.isEmpty) return 'Ajoutez au moins un article.';
    return null;
  }

  Future<void> _envoyer() async {
    if (_obstacle != null) return;

    setState(() {
      _envoi = true;
      _erreur = null;
    });

    try {
      await ApiClient.instance.dio.post<Map<String, dynamic>>(
        '/transfer-requests',
        data: {
          'from_warehouse_id': _sourceId,
          'to_warehouse_id': _destinationId,
          if (_note.text.trim().isNotEmpty) 'note': _note.text.trim(),
          'lines': [
            for (final l in _lignes) {'product_id': l.productId, 'quantity': l.quantite},
          ],
        },
      );
      if (mounted) Navigator.of(context).pop(true);
    } catch (e) {
      // Le message du serveur dit ce qui cloche ; « vérifiez votre
      // connexion » faisait chercher une panne réseau qui n'existait pas.
      if (mounted) setState(() => _erreur = friendlyError(e));
    } finally {
      if (mounted) setState(() => _envoi = false);
    }
  }

  List<DropdownMenuItem<int>> _options({int? exclure}) => [
        for (final l in _lieux)
          if (l['id'] != exclure)
            DropdownMenuItem(
              value: l['id'] as int,
              child: Text('${l['code']} · ${l['name']}', overflow: TextOverflow.ellipsis),
            ),
      ];

  @override
  Widget build(BuildContext context) {
    final monLieu = context.watch<AuthProvider>().user?.warehouseId;
    final obstacle = _obstacle;
    final destination = _maDestination ??
        _lieux.where((l) => l['id'] == _destinationId).firstOrNull;
    final sourceValide = _options(exclure: _destinationId).any((o) => o.value == _sourceId);

    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(title: const Text('Nouvelle demande')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 32),
        children: [
          // ── Pour qui ────────────────────────────────────────────────
          if (monLieu == null)
            DropdownButtonFormField<int>(
              initialValue: _destinationId,
              isExpanded: true,
              items: _options(),
              onChanged: (v) => setState(() {
                _destinationId = v;
                if (_sourceId == v) _changerSource(null);
              }),
              decoration: const InputDecoration(
                labelText: 'Lieu à approvisionner',
                prefixIcon: Icon(Icons.call_received),
              ),
            )
          else
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppTheme.navy.withValues(alpha: 0.06),
                borderRadius: BorderRadius.circular(AppTheme.radiusField),
              ),
              child: Row(
                children: [
                  const Icon(Icons.call_received, size: 18, color: AppTheme.navy),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      destination != null
                          ? 'Pour ${destination['code']} · ${destination['name']}'
                          : 'Pour votre lieu — la marchandise vous sera envoyée',
                      style: const TextStyle(fontWeight: FontWeight.w600, color: AppTheme.navy),
                    ),
                  ),
                ],
              ),
            ),
          const SizedBox(height: 14),

          // ── D'où ────────────────────────────────────────────────────
          // Son propre lieu n'est pas proposé comme source : se réapprovisionner
          // chez soi n'a pas de sens, et le serveur le refuserait.
          if (_lieux.isNotEmpty && _options(exclure: _destinationId).isEmpty)
            const Padding(
              padding: EdgeInsets.only(bottom: 10),
              child: Text(
                'Aucun autre lieu actif n’est disponible comme source.',
                style: TextStyle(fontSize: 13, color: AppTheme.danger),
              ),
            ),
          DropdownButtonFormField<int>(
            key: ValueKey('source-$_destinationId'),
            initialValue: sourceValide ? _sourceId : null,
            isExpanded: true,
            items: _options(exclure: _destinationId),
            onChanged: _changerSource,
            decoration: const InputDecoration(
              labelText: 'Approvisionner depuis',
              prefixIcon: Icon(Icons.call_made),
            ),
          ),
          const SizedBox(height: 18),

          // ── Quoi ────────────────────────────────────────────────────
          const Text(
            'Articles',
            style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppTheme.textMuted),
          ),
          const SizedBox(height: 8),
          if (_sourceId == null)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 8),
              child: Text(
                'Choisissez d’abord le lieu source : la recherche affichera le '
                'stock qu’il détient pour chaque article.',
                style: TextStyle(fontSize: 13, color: AppTheme.textMuted, height: 1.4),
              ),
            )
          else
            ProductPickerField(
              key: ValueKey('articles-$_sourceId'),
              warehouseId: _sourceId,
              onSelected: _ajouter,
            ),
          const SizedBox(height: 10),
          for (final l in _lignes) _carteLigne(l),

          // ── Note ────────────────────────────────────────────────────
          const SizedBox(height: 8),
          TextField(
            controller: _note,
            maxLength: 255,
            minLines: 1,
            maxLines: 3,
            decoration: const InputDecoration(
              labelText: 'Note pour la direction (facultatif)',
              prefixIcon: Icon(Icons.notes),
              counterText: '',
            ),
          ),
          const SizedBox(height: 14),

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
            ErrorBox(message: _erreur!),
          ],
          const SizedBox(height: 16),
          FilledButton(
            onPressed: _envoi || obstacle != null ? null : _envoyer,
            child: Text(
              _envoi
                  ? 'Envoi…'
                  : _lignes.isEmpty
                      ? 'Envoyer la demande'
                      : 'Envoyer la demande · ${_lignes.length} article(s)',
            ),
          ),
          if (obstacle != null && !_envoi) ...[
            const SizedBox(height: 8),
            Text(
              obstacle,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 12.5, color: AppTheme.textMuted),
            ),
          ],
        ],
      ),
    );
  }

  Widget _carteLigne(_LigneDemande l) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(12, 10, 6, 10),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (l.sku != null)
                        Text(
                          l.sku!,
                          style: const TextStyle(
                            fontFamily: 'monospace',
                            fontSize: 11.5,
                            color: AppTheme.textMuted,
                          ),
                        ),
                      Text(l.nom, style: const TextStyle(fontWeight: FontWeight.w600)),
                      if (l.stockSource != null)
                        Text(
                          '${l.stockSource} en stock à la source',
                          style: TextStyle(
                            fontSize: 12,
                            color: l.depasse ? AppTheme.danger : AppTheme.textMuted,
                          ),
                        ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.remove_circle_outline),
                  tooltip: 'Moins',
                  onPressed: l.quantite > 1 ? () => setState(() => l.quantite -= 1) : null,
                ),
                Text(
                  '${l.quantite}',
                  style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w700),
                ),
                IconButton(
                  icon: const Icon(Icons.add_circle_outline),
                  tooltip: 'Plus',
                  onPressed: () => setState(() => l.quantite += 1),
                ),
                IconButton(
                  icon: const Icon(Icons.delete_outline, color: AppTheme.danger),
                  tooltip: 'Retirer',
                  onPressed: () => setState(() => _lignes.remove(l)),
                ),
              ],
            ),
            if (l.depasse)
              const Padding(
                padding: EdgeInsets.only(top: 4),
                child: Text(
                  'Vous demandez plus que ce que détient la source : la direction '
                  'pourra ne vous accorder qu’une partie.',
                  style: TextStyle(fontSize: 11.5, color: AppTheme.danger),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
