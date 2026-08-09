import 'package:flutter/material.dart';

import '../../core/theme.dart';
import '../../core/update_service.dart';

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
      await _service.downloadAndInstall(
        widget.release,
        onProgress: (p) {
          if (mounted) setState(() => _progression = p);
        },
      );
      // L'installateur système prend la main : la boîte peut se refermer.
      if (mounted) Navigator.of(context).maybePop();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _enCours = false;
        _erreur = e is StateError ? e.message : 'Téléchargement impossible. Vérifiez le réseau.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final r = widget.release;

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
              'Version ${r.version}${r.sizeLabel.isEmpty ? '' : ' · ${r.sizeLabel}'}',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            if (r.notes != null && r.notes!.trim().isNotEmpty) ...[
              const SizedBox(height: 10),
              Text(r.notes!, style: const TextStyle(fontSize: 13)),
            ],
            if (_enCours) ...[
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
            const Text(
              'Android vous demandera de confirmer l’installation.',
              style: TextStyle(fontSize: 11.5, color: AppTheme.textMuted),
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
            child: Text(_enCours ? 'En cours…' : 'Mettre à jour'),
          ),
        ],
      ),
    );
  }
}
