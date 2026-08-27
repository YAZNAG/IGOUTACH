import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../sales/sales_screen.dart' show downloadSaleDocument;
import 'transfers_screen.dart' show etatTransfert;

/// Une ligne du transfert, avec ce qui a été expédié et ce qui est arrivé.
class _Ligne {
  _Ligne({
    required this.id,
    required this.nom,
    required this.envoyee,
    required this.recue,
    this.sku,
  });

  final int id;
  final String nom;
  final String? sku;
  final int envoyee;
  final int recue;

  /// Quantité saisie à la réception, initialisée à ce qui a été expédié.
  late int aRecevoir = envoyee;

  factory _Ligne.fromJson(Map<String, dynamic> j) => _Ligne(
        id: (j['id'] as num?)?.toInt() ?? 0,
        nom: j['name'] as String? ?? '',
        sku: j['sku'] as String?,
        envoyee: (j['quantity_sent'] as num?)?.toInt() ?? 0,
        recue: (j['quantity_received'] as num?)?.toInt() ?? 0,
      );
}

/// Détail d'un transfert et suite du circuit : approuver, refuser, recevoir.
///
/// Les actions offertes suivent l'état du document et les droits du compte.
/// Montrer un bouton qui sera refusé par le serveur ferait perdre un aller-
/// retour et laisserait croire à une panne.
class TransferDetailScreen extends StatefulWidget {
  const TransferDetailScreen({
    super.key,
    required this.transferId,
    required this.reference,
  });

  final int transferId;
  final String reference;

  @override
  State<TransferDetailScreen> createState() => _TransferDetailScreenState();
}

class _TransferDetailScreenState extends State<TransferDetailScreen> {
  final _api = ApiClient.instance;

  bool _chargement = true;
  String? _erreur;
  bool _horsLigne = false;
  bool _action = false;
  bool _modifie = false;

  String _reference = '';
  String? _etat;
  String? _depuis;
  String? _vers;
  String? _note;
  String? _envoyeLe;
  String? _recuLe;
  List<_Ligne> _lignes = [];

  @override
  void initState() {
    super.initState();
    _reference = widget.reference;
    _charger();
  }

  Future<void> _charger() async {
    setState(() {
      _chargement = true;
      _erreur = null;
    });
    try {
      final res = await _api.dio.get<Map<String, dynamic>>('/transfers/${widget.transferId}');
      final d = res.data!['data'] as Map<String, dynamic>;
      if (!mounted) return;
      setState(() {
        _reference = d['reference'] as String? ?? _reference;
        _etat = d['status'] as String?;
        _depuis = d['from'] as String?;
        _vers = d['to'] as String?;
        _note = d['note'] as String?;
        _envoyeLe = d['sent_at'] as String?;
        _recuLe = d['received_at'] as String?;
        _lignes = (d['lines'] as List<dynamic>? ?? [])
            .map((e) => _Ligne.fromJson(e as Map<String, dynamic>))
            .toList();
        _chargement = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _erreur = friendlyError(e);
        _horsLigne = isNetworkError(e);
        _chargement = false;
      });
    }
  }

  Future<void> _appeler(String chemin, String succes, {Map<String, dynamic>? corps}) async {
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _action = true);
    try {
      await _api.dio.post<Map<String, dynamic>>(chemin, data: corps);
      if (!mounted) return;
      _modifie = true;
      showSuccessSnack(messenger, succes);
      await _charger();
    } catch (e) {
      if (!mounted) return;
      showErrorSnack(messenger, friendlyError(e));
    } finally {
      if (mounted) setState(() => _action = false);
    }
  }

  Future<void> _approuver() async {
    final ok = await confirmAction(
      context,
      icon: Icons.check_circle_outline,
      title: 'Approuver le transfert',
      message: '$_reference\n$_depuis → $_vers\n\n'
          'La marchandise sortira du lieu source et partira en transit.',
      confirmLabel: 'Approuver',
      confirmColor: AppTheme.success,
    );
    if (!ok || !mounted) return;
    await _appeler('/transfers/${widget.transferId}/approve', 'Transfert approuvé.');
  }

  Future<void> _refuser() async {
    final ok = await confirmAction(
      context,
      icon: Icons.cancel_outlined,
      title: 'Refuser le transfert',
      message: '$_reference\n\nRien ne sortira du stock.',
      confirmLabel: 'Refuser',
      confirmColor: AppTheme.danger,
    );
    if (!ok || !mounted) return;
    await _appeler('/transfers/${widget.transferId}/refuse', 'Transfert refusé.');
  }

  /// Réception : on peut recevoir moins qu'expédié, la différence reste
  /// visible sur la ligne. C'est ainsi qu'on constate une casse ou un manque.
  Future<void> _recevoir() async {
    final ok = await confirmAction(
      context,
      icon: Icons.move_to_inbox_outlined,
      title: 'Réceptionner',
      message: '$_reference\n\n'
          'Les quantités saisies entreront au stock de $_vers. '
          'Un écart avec ce qui a été expédié restera visible sur le document.',
      confirmLabel: 'Réceptionner',
      confirmColor: AppTheme.success,
    );
    if (!ok || !mounted) return;

    await _appeler(
      '/transfers/${widget.transferId}/receive',
      'Transfert réceptionné.',
      corps: {
        'quantities': {
          for (final l in _lignes) l.id.toString(): l.aRecevoir,
        },
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) Navigator.of(context).pop(_modifie);
      },
      child: Scaffold(
        backgroundColor: AppTheme.background,
        appBar: AppBar(
          title: Text(_reference),
          actions: [
            if (!_chargement && _erreur == null)
              IconButton(
                icon: const Icon(Icons.picture_as_pdf_outlined),
                tooltip: 'Bon de transfert',
                onPressed: () => downloadSaleDocument(
                  context,
                  widget.transferId,
                  'pdf',
                  'TR-$_reference',
                  base: '/transfers',
                ),
              ),
          ],
        ),
        body: _corps(),
        bottomNavigationBar: _chargement || _erreur != null ? null : _actions(auth),
      ),
    );
  }

  Widget _corps() {
    if (_chargement) return const ListSkeleton(itemCount: 4, lines: 2);
    if (_erreur != null) {
      return ErrorView(message: _erreur!, offline: _horsLigne, onRetry: _charger);
    }

    final (libelle, couleur) = etatTransfert(_etat, null);
    final enReception = _etat == 'in_transit';

    return ListView(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 96),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        '$_depuis → $_vers',
                        style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
                      ),
                    ),
                    StatusBadge(label: libelle, color: couleur),
                  ],
                ),
                if (_envoyeLe != null || _recuLe != null) ...[
                  const SizedBox(height: 6),
                  Text(
                    [
                      if (_envoyeLe != null) 'Expédié le $_envoyeLe',
                      if (_recuLe != null) 'Reçu le $_recuLe',
                    ].join(' · '),
                    style: const TextStyle(fontSize: 12.5, color: AppTheme.textMuted),
                  ),
                ],
                if ((_note ?? '').isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(_note!, style: const TextStyle(fontSize: 13)),
                ],
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        SectionTitle(
          enReception ? 'Quantités reçues' : 'Articles',
          padding: const EdgeInsets.only(bottom: 6),
        ),
        ..._lignes.map((l) => _carteLigne(l, enReception)),
      ],
    );
  }

  Widget _carteLigne(_Ligne l, bool enReception) {
    final ecart = l.recue > 0 && l.recue != l.envoyee;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
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
            const SizedBox(height: 8),
            if (enReception)
              Row(
                children: [
                  QuantityStepper(
                    quantity: l.aRecevoir,
                    onChanged: (q) => setState(() => l.aRecevoir = q),
                  ),
                  const Spacer(),
                  Text(
                    'Expédié : ${formatQuantity(l.envoyee)}',
                    style: TextStyle(
                      fontSize: 12.5,
                      color: l.aRecevoir == l.envoyee ? AppTheme.textMuted : AppTheme.warning,
                      fontWeight:
                          l.aRecevoir == l.envoyee ? FontWeight.w400 : FontWeight.w600,
                    ),
                  ),
                ],
              )
            else
              Row(
                children: [
                  Text(
                    'Expédié : ${formatQuantity(l.envoyee)}',
                    style: const TextStyle(fontSize: 13),
                  ),
                  if (l.recue > 0) ...[
                    const SizedBox(width: 14),
                    Text(
                      'Reçu : ${formatQuantity(l.recue)}',
                      style: TextStyle(
                        fontSize: 13,
                        color: ecart ? AppTheme.danger : AppTheme.success,
                        fontWeight: ecart ? FontWeight.w600 : FontWeight.w400,
                      ),
                    ),
                  ],
                ],
              ),
          ],
        ),
      ),
    );
  }

  Widget? _actions(AuthProvider auth) {
    final boutons = <Widget>[];

    if (_etat == 'requested' && auth.can('transfer.approve')) {
      boutons.addAll([
        Expanded(
          child: OutlinedButton(
            onPressed: _action ? null : _refuser,
            style: OutlinedButton.styleFrom(
              foregroundColor: AppTheme.danger,
              side: const BorderSide(color: AppTheme.danger),
            ),
            child: const Text('Refuser'),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: FilledButton(
            onPressed: _action ? null : _approuver,
            child: Text(_action ? 'En cours…' : 'Approuver'),
          ),
        ),
      ]);
    } else if (_etat == 'in_transit' && auth.can('transfer.receive')) {
      boutons.add(
        Expanded(
          child: FilledButton.icon(
            onPressed: _action ? null : _recevoir,
            icon: const Icon(Icons.move_to_inbox_outlined, size: 18),
            label: Text(_action ? 'En cours…' : 'Réceptionner'),
          ),
        ),
      );
    }

    if (boutons.isEmpty) return null;

    return SafeArea(
      child: Container(
        padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: AppTheme.border)),
        ),
        child: Row(children: boutons),
      ),
    );
  }
}
