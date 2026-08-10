import 'package:flutter/material.dart';

import '../../core/theme.dart';
import '../../core/update_service.dart';

/// Surveille en continu la publication d'une nouvelle version.
///
/// Vérifier une seule fois au lancement ne suffit pas : sur un téléphone de
/// terrain, l'application reste des jours en arrière-plan sans jamais être
/// relancée. On revérifie donc à chaque retour au premier plan, espacé pour
/// ne pas interroger le serveur à chaque coup d'œil à l'écran.
class UpdateWatcher extends StatefulWidget {
  const UpdateWatcher({super.key, required this.child});

  final Widget child;

  /// Délai minimal entre deux interrogations du serveur.
  static const Duration intervalle = Duration(minutes: 30);

  @override
  State<UpdateWatcher> createState() => _UpdateWatcherState();
}

class _UpdateWatcherState extends State<UpdateWatcher> with WidgetsBindingObserver {
  DateTime? _derniereVerification;
  bool _boiteOuverte = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) => _verifier());
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) _verifier();
  }

  Future<void> _verifier() async {
    // Une boîte déjà affichée ne doit pas être doublée par une seconde.
    if (_boiteOuverte) return;

    final derniere = _derniereVerification;
    if (derniere != null &&
        DateTime.now().difference(derniere) < UpdateWatcher.intervalle) {
      return;
    }
    _derniereVerification = DateTime.now();

    final release = await UpdateService().checkForUpdate();
    if (release == null || !mounted) return;

    _boiteOuverte = true;
    try {
      await showDialog<void>(
        context: context,
        barrierDismissible: !release.mandatory,
        builder: (_) => UpdatePrompt(release: release),
      );
    } finally {
      _boiteOuverte = false;
    }
  }

  @override
  Widget build(BuildContext context) => widget.child;
}

/// Propose la mise à jour au démarrage.
///
/// Une version facultative se referme d'un tap sur « Plus tard » ; une version
/// obligatoire ne se referme pas, sinon elle ne servirait à rien.
class UpdatePrompt extends StatefulWidget {
  const UpdatePrompt({super.key, required this.release, this.service});

  final AppRelease release;
  final UpdateService? service;

  /// Affiche la boîte si une version plus récente existe.
  static Future<void> checkAndShow(BuildContext context) async {
    final release = await UpdateService().checkForUpdate();
    if (release == null || !context.mounted) return;

    await showDialog<void>(
      context: context,
      barrierDismissible: !release.mandatory,
      builder: (_) => UpdatePrompt(release: release),
    );
  }

  @override
  State<UpdatePrompt> createState() => _UpdatePromptState();
}

class _UpdatePromptState extends State<UpdatePrompt> {
  late final UpdateService _service = widget.service ?? UpdateService();

  bool _enCours = false;
  double? _progression;
  String? _erreur;

  Future<void> _installer() async {
    setState(() {
      _enCours = true;
      _erreur = null;
      _progression = 0;
    });

    try {
      if (UpdateService.installationDirecte) {
        await _service.downloadAndInstall(
          widget.release,
          onProgress: (p) {
            if (mounted) setState(() => _progression = p);
          },
        );
      } else {
        // iOS : rien à télécharger, l'App Store fait la mise à jour.
        await _service.ouvrirPageMiseAJour(widget.release);
      }
      // L'installateur système ou l'App Store prend la main : la boîte peut
      // se refermer.
      if (mounted) Navigator.of(context).maybePop();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _enCours = false;
        _erreur = e is StateError
            ? e.message
            : UpdateService.installationDirecte
                ? 'Téléchargement impossible. Vérifiez le réseau.'
                : 'Impossible d’ouvrir l’App Store.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final r = widget.release;
    // Sur iOS il n'y a ni téléchargement à suivre ni poids de fichier à
    // annoncer : l'App Store s'en charge une fois la fiche ouverte.
    final directe = UpdateService.installationDirecte;

    return PopScope(
      // Une version obligatoire ne se contourne pas par le bouton retour.
      canPop: !r.mandatory,
      child: AlertDialog(
        title: Row(
          children: [
            const Icon(Icons.system_update, color: AppTheme.brand),
            const SizedBox(width: 10),
            Expanded(child: Text(r.mandatory ? 'Mise à jour requise' : 'Mise à jour disponible')),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Version ${r.version}'
              '${directe && r.sizeLabel.isNotEmpty ? ' · ${r.sizeLabel}' : ''}',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            if (r.notes != null && r.notes!.trim().isNotEmpty) ...[
              const SizedBox(height: 10),
              Text(r.notes!, style: const TextStyle(fontSize: 13)),
            ],
            if (_enCours && directe) ...[
              const SizedBox(height: 16),
              LinearProgressIndicator(value: _progression),
              const SizedBox(height: 6),
              Text(
                _progression == null
                    ? 'Téléchargement…'
                    : 'Téléchargement ${(_progression! * 100).round()} %',
                style: const TextStyle(fontSize: 12),
              ),
            ],
            if (_erreur != null) ...[
              const SizedBox(height: 12),
              Text(_erreur!, style: const TextStyle(fontSize: 12.5, color: AppTheme.danger)),
            ],
            const SizedBox(height: 12),
            Text(
              directe
                  ? 'Android vous demandera de confirmer l’installation.'
                  : 'L’App Store s’ouvrira pour installer la mise à jour.',
              style: const TextStyle(fontSize: 11.5, color: AppTheme.textMuted),
            ),
          ],
        ),
        actions: [
          if (!r.mandatory && !_enCours)
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Plus tard'),
            ),
          FilledButton(
            onPressed: _enCours ? null : _installer,
            child: Text(
              _enCours
                  ? 'En cours…'
                  : directe
                      ? 'Mettre à jour'
                      : 'Ouvrir l’App Store',
            ),
          ),
        ],
      ),
    );
  }
}
