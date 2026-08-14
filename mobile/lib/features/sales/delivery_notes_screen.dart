import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/sale.dart';
import '../shared/period_export.dart';

/// Bons de livraison : le document de sortie des ventes confirmées.
///
/// Le bon de livraison n'est pas un objet à part : c'est la face « quantités »
/// d'une facture confirmée. On liste donc les ventes qui en ont produit un,
/// et on ouvre le PDF correspondant.
class DeliveryNotesScreen extends StatefulWidget {
  const DeliveryNotesScreen({super.key});

  @override
  State<DeliveryNotesScreen> createState() => _DeliveryNotesScreenState();
}

class _DeliveryNotesScreenState extends State<DeliveryNotesScreen> {
  final _api = ApiClient.instance;
  final _scrollController = ScrollController();

  Periode _periode = Periode.moisEnCours();
  final List<SaleSummary> _ventes = [];
  int _page = 0;
  int _lastPage = 1;
  bool _loading = false;
  bool _premierChargementFait = false;
  String? _erreur;
  bool _horsLigne = false;
  int? _telechargement;

  bool get _encore => _page < _lastPage;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_auDefilement);
    _charger(reset: true);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _auDefilement() {
    if (!_scrollController.hasClients) return;
    final position = _scrollController.position;
    if (position.pixels > position.maxScrollExtent - 300 && _encore && !_loading) {
      _charger();
    }
  }

  Future<void> _charger({bool reset = false}) async {
    if (_loading) return;
    setState(() {
      _loading = true;
      if (reset) {
        _erreur = null;
        _page = 0;
        _lastPage = 1;
        _ventes.clear();
        _premierChargementFait = false;
      }
    });

    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/sales',
        queryParameters: {
          'page': _page + 1,
          // Seules les ventes confirmées ont sorti du stock, donc produit un
          // bon de livraison. Un brouillon n'a rien livré.
          'type': 'invoice',
          'status': 'confirmed',
          'date_from': _periode.duIso,
          'date_to': _periode.auIso,
        },
      );
      final body = res.data!;
      final data = body['data'] as List<dynamic>? ?? [];
      final meta = body['meta'] as Map<String, dynamic>? ?? {};
      if (!mounted) return;
      setState(() {
        _page = (meta['current_page'] as num?)?.toInt() ?? _page + 1;
        _lastPage = (meta['last_page'] as num?)?.toInt() ?? _page;
        _ventes.addAll(data.map((e) => SaleSummary.fromJson(e as Map<String, dynamic>)));
        _premierChargementFait = true;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _erreur = friendlyError(e);
        _horsLigne = isNetworkError(e);
        _loading = false;
        _premierChargementFait = true;
      });
    }
  }

  /// Ouvre le bon de livraison d'une vente.
  Future<void> _ouvrirBon(SaleSummary vente) async {
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _telechargement = vente.id);
    try {
      final res = await _api.dio.get<List<int>>(
        '/sales/${vente.id}/exit-pdf',
        options: Options(responseType: ResponseType.bytes),
      );
      final dossier = await getApplicationDocumentsDirectory();
      final nom = 'BL-${vente.reference}'.replaceAll(RegExp(r'[^\w\-]'), '_');
      final fichier = File('${dossier.path}${Platform.pathSeparator}$nom.pdf');
      await fichier.writeAsBytes(res.data ?? const []);
      await OpenFilex.open(fichier.path);
    } catch (e) {
      showErrorSnack(messenger, 'Téléchargement impossible : ${friendlyError(e)}');
    } finally {
      if (mounted) setState(() => _telechargement = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.can('sale.create')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Bons de livraison')),
      body: Column(
        children: [
          PeriodBar(
            periode: _periode,
            journal: 'sales',
            exportLabel: 'Exporter le journal',
            onChanged: (p) {
              setState(() => _periode = p);
              _charger(reset: true);
            },
          ),
          Expanded(child: _corps()),
        ],
      ),
    );
  }

  Widget _corps() {
    if (!_premierChargementFait && _loading) {
      return const ListSkeleton(itemCount: 5, lines: 2);
    }

    if (_erreur != null && _ventes.isEmpty) {
      return ErrorView(
        message: _erreur!,
        offline: _horsLigne,
        onRetry: () => _charger(reset: true),
      );
    }

    if (_ventes.isEmpty) {
      return const EmptyView(
        icon: Icons.local_shipping_outlined,
        title: 'Aucun bon de livraison',
        message: 'Les ventes confirmées de la période apparaîtront ici.',
      );
    }

    return RefreshIndicator(
      onRefresh: () => _charger(reset: true),
      child: ListView.builder(
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(top: 8, bottom: 88),
        itemCount: _ventes.length + (_encore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index >= _ventes.length) return const SkeletonCard(lines: 2);
          final vente = _ventes[index];
          return Card(
            child: ListTile(
              leading: const Icon(Icons.local_shipping_outlined, color: AppTheme.sky),
              title: Text(
                vente.reference,
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
              subtitle: Text(
                '${vente.customer ?? 'Client de passage'} · ${formatMoney(vente.total)}',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
              trailing: _telechargement == vente.id
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : IconButton(
                      icon: const Icon(Icons.download_outlined),
                      tooltip: 'Ouvrir le bon de livraison',
                      onPressed: () => _ouvrirBon(vente),
                    ),
              onTap: _telechargement == null ? () => _ouvrirBon(vente) : null,
            ),
          );
        },
      ),
    );
  }
}
