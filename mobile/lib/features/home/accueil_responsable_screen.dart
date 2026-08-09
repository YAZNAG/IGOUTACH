import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/ui/states.dart';
import '../shared/update_prompt.dart';

/// Accueil du responsable : son lieu, rien d'autre.
///
/// La vue consolidée (`/dashboard`) lui est refusée — et c'est voulu. Cet
/// écran se construit donc à partir de ce qu'il a le droit de lire : le
/// résumé de son lieu et ses alertes.
class AccueilResponsableScreen extends StatefulWidget {
  const AccueilResponsableScreen({super.key});

  @override
  State<AccueilResponsableScreen> createState() =>
      _AccueilResponsableScreenState();
}

class _AccueilResponsableScreenState extends State<AccueilResponsableScreen> {
  late Future<_Accueil> _futur;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) UpdatePrompt.checkAndShow(context);
    });
    _futur = _charger();
  }

  Future<_Accueil> _charger() async {
    final lieuId = context.read<AuthProvider>().user?.warehouseId;
    final api = ApiClient.instance.dio;

    // Les appels partent ensemble : l'accueil s'ouvre en une attente, pas
    // trois à la file.
    final resultats = await Future.wait([
      api.get<Map<String, dynamic>>('/warehouses'),
      api.get<Map<String, dynamic>>('/alerts'),
      if (lieuId != null)
        api.get<Map<String, dynamic>>('/warehouses/$lieuId/summary'),
    ]);

    final lieux = (resultats[0].data?['data'] as List<dynamic>? ?? [])
        .cast<Map<String, dynamic>>();
    final alertes = (resultats[1].data?['data'] as List<dynamic>? ?? [])
        .cast<Map<String, dynamic>>()
        .where((a) => (a['count'] as int? ?? 0) > 0)
        .toList();
    final resume = resultats.length > 2
        ? (resultats[2].data?['data'] as Map<String, dynamic>? ?? const {})
        : const <String, dynamic>{};

    return _Accueil(
      lieuCode: lieux.isNotEmpty ? lieux.first['code'] as String? ?? '' : '',
      lieuNom: lieux.isNotEmpty ? lieux.first['name'] as String? ?? '' : '',
      valeurStock: (resume['stock_value'] as num?)?.toDouble() ?? 0,
      references: resume['references'] as int? ?? 0,
      sousSeuil: resume['below_threshold'] as int? ?? 0,
      ruptures: resume['ruptures'] as int? ?? 0,
      alertes: alertes,
    );
  }

  Future<void> _rafraichir() async {
    setState(() => _futur = _charger());
    await _futur;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.background,
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _rafraichir,
          child: FutureBuilder<_Accueil>(
            future: _futur,
            builder: (context, snap) {
              if (snap.connectionState != ConnectionState.done) {
                return const LoadingView();
              }

              if (snap.hasError) {
                return ListView(
                  children: [
                    const SizedBox(height: 60),
                    ErrorView(
                      message: 'Impossible de charger votre lieu.',
                      onRetry: _rafraichir,
                    ),
                  ],
                );
              }

              final a = snap.data!;

              return ListView(
                padding: const EdgeInsets.all(14),
                children: [
                  _EnTete(code: a.lieuCode, nom: a.lieuNom),
                  const SizedBox(height: 14),
                  _CarteValeur(
                    valeur: a.valeurStock,
                    references: a.references,
                    sousSeuil: a.sousSeuil,
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: _Indicateur(
                          libelle: 'Sous le seuil',
                          valeur: '${a.sousSeuil}',
                          detail: 'à réapprovisionner',
                          teinte: AppTheme.warning,
                        ),
                      ),
                      const SizedBox(width: 9),
                      Expanded(
                        child: _Indicateur(
                          libelle: 'En rupture',
                          valeur: '${a.ruptures}',
                          detail: 'articles à zéro',
                          teinte: AppTheme.danger,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  _BlocAlertes(alertes: a.alertes),
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}

class _Accueil {
  const _Accueil({
    required this.lieuCode,
    required this.lieuNom,
    required this.valeurStock,
    required this.references,
    required this.sousSeuil,
    required this.ruptures,
    required this.alertes,
  });

  final String lieuCode;
  final String lieuNom;
  final double valeurStock;
  final int references;
  final int sousSeuil;
  final int ruptures;
  final List<Map<String, dynamic>> alertes;
}

class _EnTete extends StatelessWidget {
  const _EnTete({required this.code, required this.nom});

  final String code;
  final String nom;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 38,
          height: 38,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            border: Border.all(color: AppTheme.brand, width: 2.5),
          ),
          child: const Text(
            'iG',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: AppTheme.brand,
            ),
          ),
        ),
        const SizedBox(width: 11),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                code.isEmpty ? 'Mon lieu' : code,
                style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w700),
              ),
              Text(
                nom,
                style: const TextStyle(fontSize: 12, color: AppTheme.textMuted),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

/// Bandeau rouge de la maquette : la valeur du stock détenu.
class _CarteValeur extends StatelessWidget {
  const _CarteValeur({
    required this.valeur,
    required this.references,
    required this.sousSeuil,
  });

  final double valeur;
  final int references;
  final int sousSeuil;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppTheme.brand, AppTheme.brandDeep],
        ),
        borderRadius: BorderRadius.circular(AppTheme.radiusCard),
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
            formatMoney(valeur),
            style: const TextStyle(
              fontSize: 26,
              fontWeight: FontWeight.w700,
              color: Colors.white,
              fontFeatures: AppTheme.tabularFigures,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            '$references références · $sousSeuil sous seuil',
            style: const TextStyle(fontSize: 12, color: Colors.white70),
          ),
        ],
      ),
    );
  }
}

class _Indicateur extends StatelessWidget {
  const _Indicateur({
    required this.libelle,
    required this.valeur,
    required this.detail,
    required this.teinte,
  });

  final String libelle;
  final String valeur;
  final String detail;
  final Color teinte;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(11),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppTheme.radiusField),
        border: Border.all(color: AppTheme.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            libelle,
            style: const TextStyle(fontSize: 12, color: AppTheme.textMuted),
          ),
          const SizedBox(height: 2),
          Text(
            valeur,
            style: TextStyle(
              fontSize: 21,
              fontWeight: FontWeight.w700,
              color: teinte,
              fontFeatures: AppTheme.tabularFigures,
            ),
          ),
          Text(
            detail,
            style: const TextStyle(fontSize: 11, color: AppTheme.textFaint),
          ),
        ],
      ),
    );
  }
}

class _BlocAlertes extends StatelessWidget {
  const _BlocAlertes({required this.alertes});

  final List<Map<String, dynamic>> alertes;

  Color _teinte(String severite) => switch (severite) {
        'bad' => AppTheme.danger,
        'warn' => AppTheme.warning,
        _ => AppTheme.brand,
      };

  Color _fond(String severite) => switch (severite) {
        'bad' => AppTheme.dangerSoft,
        'warn' => AppTheme.warningSoft,
        _ => AppTheme.brandSoft,
      };

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(13),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppTheme.radiusCard),
        border: Border.all(color: AppTheme.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Text(
                'À traiter',
                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
              ),
              const Spacer(),
              Text(
                '${alertes.length}',
                style: const TextStyle(
                  fontSize: 12,
                  color: AppTheme.brand,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
          const SizedBox(height: 9),
          if (alertes.isEmpty)
            const Text(
              'Rien à signaler sur votre lieu.',
              style: TextStyle(fontSize: 13, color: AppTheme.textMuted),
            )
          else
            for (final a in alertes)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 7),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 3,
                      ),
                      decoration: BoxDecoration(
                        color: _fond(a['severity'] as String? ?? ''),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        '${a['count']}',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: _teinte(a['severity'] as String? ?? ''),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        a['label'] as String? ?? '',
                        style: const TextStyle(fontSize: 13),
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
