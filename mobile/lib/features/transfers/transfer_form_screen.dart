import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/product.dart';
import '../../models/warehouse.dart';
import '../shared/product_picker.dart';

/// Une ligne du transfert en préparation.
class _Ligne {
  _Ligne({
    required this.productId,
    required this.sku,
    required this.nom,
    required this.quantite,
    this.stockSource,
  });

  final int productId;
  final String? sku;
  final String nom;
  int quantite;

  /// Stock disponible au lieu source au moment de la sélection.
  final int? stockSource;

  /// Demander plus que ce que le lieu détient sera refusé par le serveur :
  /// le signaler à la saisie évite d'aller au bout pour rien.
  bool get depasseLeStock => stockSource != null && quantite > stockSource!;
}

/// Création d'un transfert : lieu source, lieu destination, note et lignes.
class TransferFormScreen extends StatefulWidget {
  const TransferFormScreen({super.key});

  @override
  State<TransferFormScreen> createState() => _TransferFormScreenState();
}

class _TransferFormScreenState extends State<TransferFormScreen> {
  final _api = ApiClient.instance;
  final _note = TextEditingController();

  List<Warehouse> _lieux = const [];
  int? _sourceId;
  int? _destinationId;
  final List<_Ligne> _lignes = [];

  bool _chargement = true;
  String? _erreurChargement;
  bool _envoi = false;
  String? _erreur;

  @override
  void initState() {
    super.initState();
    _chargerLieux();
  }

  @override
  void dispose() {
    _note.dispose();
    super.dispose();
  }

  Future<void> _chargerLieux() async {
    try {
      final res = await _api.dio.get<Map<String, dynamic>>('/warehouses');
      final donnees = res.data?['data'] as List<dynamic>? ?? [];
      if (!mounted) return;
      setState(() {
        _lieux = donnees
            .map((e) => Warehouse.fromJson(e as Map<String, dynamic>))
            .toList();
        _chargement = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _erreurChargement = friendlyError(e);
        _chargement = false;
      });
    }
  }

  /// Ajoute un article, ou incrémente celui déjà présent en le remontant.
  void _ajouter(Product p) {
    final existante = _lignes.where((l) => l.productId == p.id).firstOrNull;
    setState(() {
      if (existante != null) {
        _lignes.remove(existante);
        existante.quantite += 1;
        _lignes.insert(0, existante);

        return;
      }
      _lignes.insert(0, _Ligne(
        productId: p.id,
        sku: p.sku,
        nom: p.name,
        quantite: 1,
        stockSource: p.currentStock,
      ));
    });
  }

  /// Message bloquant, ou null si le transfert peut partir.
  String? get _obstacle {
    if (_sourceId == null) return 'Choisissez le lieu source.';
    if (_destinationId == null) return 'Choisissez le lieu de destination.';
    if (_sourceId == _destinationId) {
      return 'Les deux lieux doivent être différents.';
    }
    if (_lignes.isEmpty) return 'Ajoutez au moins un article.';
    if (_lignes.any((l) => l.depasseLeStock)) {
      return 'Une ligne dépasse le stock disponible au lieu source.';
    }

    return null;
  }

  Future<void> _envoyer() async {
    final obstacle = _obstacle;
    if (obstacle != null) {
      setState(() => _erreur = obstacle);

      return;
    }

    setState(() {
      _envoi = true;
      _erreur = null;
    });

    try {
      await _api.dio.post<Map<String, dynamic>>(
        '/transfers',
        data: {
          'from_warehouse_id': _sourceId,
          'to_warehouse_id': _destinationId,
          if (_note.text.trim().isNotEmpty) 'note': _note.text.trim(),
          'lines': _lignes
              .map((l) => {'product_id': l.productId, 'quantity': l.quantite})
              .toList(),
        },
      );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _envoi = false;
        _erreur = friendlyError(e);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(title: const Text('Nouveau transfert')),
      body: _corps(),
      bottomNavigationBar: _chargement || _erreurChargement != null
          ? null
          : BottomActionBar(
              label: 'Créer le transfert',
              loading: _envoi,
              summaryLabel: 'Articles',
              summaryValue:
                  '${_lignes.length} · ${_lignes.fold<int>(0, (s, l) => s + l.quantite)} u.',
              onPressed: _lignes.isEmpty ? null : _envoyer,
            ),
    );
  }

  Widget _corps() {
    if (_chargement) return const FormSkeleton();
    if (_erreurChargement != null) {
      return ErrorView(message: _erreurChargement!, onRetry: _chargerLieux);
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(14, 16, 14, 24),
      children: [
        _selecteurLieu(
          libelle: 'Lieu source *',
          valeur: _sourceId,
          icone: Icons.outbox_outlined,
          onChange: (id) => setState(() {
            _sourceId = id;
            // Le stock affiché sur chaque ligne appartient au lieu source :
            // en changer rendrait ces chiffres faux.
            _lignes.clear();
          }),
        ),
        const SizedBox(height: 14),
        _selecteurLieu(
          libelle: 'Lieu destination *',
          valeur: _destinationId,
          icone: Icons.move_to_inbox_outlined,
          exclure: _sourceId,
          onChange: (id) => setState(() => _destinationId = id),
        ),
        const SizedBox(height: 14),
        TextField(
          controller: _note,
          maxLength: 255,
          textCapitalization: TextCapitalization.sentences,
          decoration: const InputDecoration(
            labelText: 'Note (facultatif)',
            prefixIcon: Icon(Icons.notes_outlined),
            counterText: '',
          ),
        ),
        const SizedBox(height: 18),
        const SectionTitle('Articles', padding: EdgeInsets.only(bottom: 8)),
        if (_sourceId == null)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 10),
            child: Text(
              'Choisissez d’abord le lieu source : les articles proposés '
              'porteront son stock disponible.',
              style: TextStyle(fontSize: 13, color: AppTheme.textMuted),
            ),
          )
        else
          ProductPickerField(
            key: ValueKey('articles-$_sourceId'),
            warehouseId: _sourceId,
            onSelected: _ajouter,
          ),
        const SizedBox(height: 10),
        ..._lignes.map(_carteLigne),
        if (_erreur != null) ...[
          const SizedBox(height: 14),
          ErrorBox(message: _erreur!),
        ],
      ],
    );
  }

  Widget _selecteurLieu({
    required String libelle,
    required int? valeur,
    required IconData icone,
    required ValueChanged<int?> onChange,
    int? exclure,
  }) {
    final options = _lieux.where((l) => l.id != exclure).toList();

    return DropdownButtonFormField<int>(
      initialValue: options.any((l) => l.id == valeur) ? valeur : null,
      isExpanded: true,
      decoration: InputDecoration(labelText: libelle, prefixIcon: Icon(icone)),
      items: options
          .map((l) => DropdownMenuItem(
                value: l.id,
                child: Text('${l.code} · ${l.name}', overflow: TextOverflow.ellipsis),
              ))
          .toList(),
      onChanged: onChange,
    );
  }

  Widget _carteLigne(_Ligne l) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
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
                      Text(
                        l.nom,
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.delete_outline, color: AppTheme.danger),
                  tooltip: 'Retirer',
                  onPressed: () => setState(() => _lignes.remove(l)),
                ),
              ],
            ),
            const SizedBox(height: 6),
            Row(
              children: [
                QuantityStepper(
                  quantity: l.quantite,
                  onChanged: (q) => setState(() => l.quantite = q),
                ),
                const Spacer(),
                if (l.stockSource != null)
                  Text(
                    'Stock source : ${formatQuantity(l.stockSource!)}',
                    style: TextStyle(
                      fontSize: 12,
                      color: l.depasseLeStock ? AppTheme.danger : AppTheme.textMuted,
                      fontWeight: l.depasseLeStock ? FontWeight.w600 : FontWeight.w400,
                    ),
                  ),
              ],
            ),
            if (l.depasseLeStock)
              const Padding(
                padding: EdgeInsets.only(top: 6),
                child: Text(
                  'La quantité demandée dépasse le stock disponible.',
                  style: TextStyle(fontSize: 12, color: AppTheme.danger),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
