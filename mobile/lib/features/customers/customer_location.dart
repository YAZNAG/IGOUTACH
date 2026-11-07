import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../core/ui/feedback.dart';
import '../../models/customer_overview.dart';

/// Centre par défaut de la carte quand rien d'autre n'est connu : Agadir,
/// là où sont les lieux. On ne part pas d'un planisphère.
const _centreParDefaut = LatLng(30.4278, -9.5981);

/// Fond de carte OpenStreetMap, sans clé ni compte.
TileLayer _fondDeCarte() => TileLayer(
      urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
      userAgentPackageName: 'ma.igoutech.app',
    );

Marker _repere(LatLng point) => Marker(
      point: point,
      width: 44,
      height: 44,
      alignment: Alignment.topCenter,
      child: const Icon(Icons.location_on, size: 44, color: AppTheme.brand),
    );

/// Carte « Localisation » de la fiche client.
///
/// La localisation est facultative : sans elle, la carte propose de l'ajouter
/// (position du téléphone, ou point choisi sur la carte). Avec, elle montre
/// un aperçu et permet d'ouvrir l'itinéraire, de la déplacer ou de la retirer.
class CarteLocalisation extends StatefulWidget {
  const CarteLocalisation({
    super.key,
    required this.fiche,
    required this.peutModifier,
    required this.onChange,
  });

  final FicheClient fiche;
  final bool peutModifier;

  /// Appelé après un enregistrement réussi : la fiche se recharge.
  final VoidCallback onChange;

  @override
  State<CarteLocalisation> createState() => _CarteLocalisationState();
}

class _CarteLocalisationState extends State<CarteLocalisation> {
  bool _enregistrement = false;

  LatLng? get _point => widget.fiche.aUneLocalisation
      ? LatLng(widget.fiche.latitude!, widget.fiche.longitude!)
      : null;

  Future<void> _enregistrer(LatLng? point) async {
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _enregistrement = true);
    try {
      await ApiClient.instance.dio.patch<Map<String, dynamic>>(
        '/customers/${widget.fiche.id}/location',
        data: {
          'latitude': point?.latitude,
          'longitude': point?.longitude,
        },
      );
      if (!mounted) return;
      showSuccessSnack(
        messenger,
        point == null ? 'Localisation retirée.' : 'Localisation enregistrée.',
      );
      widget.onChange();
    } catch (e) {
      if (!mounted) return;
      showErrorSnack(messenger, friendlyError(e));
    } finally {
      if (mounted) setState(() => _enregistrement = false);
    }
  }

  /// Position courante du téléphone, avec la demande d'autorisation.
  Future<void> _utiliserMaPosition() async {
    final messenger = ScaffoldMessenger.of(context);
    try {
      if (!await Geolocator.isLocationServiceEnabled()) {
        showErrorSnack(messenger, 'Activez la localisation du téléphone (GPS).');
        return;
      }
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        showErrorSnack(
          messenger,
          'Autorisation de localisation refusée : autorisez-la dans les réglages.',
        );
        return;
      }
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 20),
        ),
      );
      await _enregistrer(LatLng(position.latitude, position.longitude));
    } catch (_) {
      if (!mounted) return;
      showErrorSnack(messenger, 'Position introuvable : réessayez à l’extérieur.');
    }
  }

  Future<void> _choisirSurLaCarte() async {
    final choisi = await Navigator.of(context).push<LatLng>(
      MaterialPageRoute<LatLng>(
        builder: (_) => ChoixSurCarteScreen(
          initial: _point,
          titre: widget.fiche.nom,
        ),
      ),
    );
    if (choisi != null) await _enregistrer(choisi);
  }

  Future<void> _retirer() async {
    final ok = await confirmAction(
      context,
      title: 'Retirer la localisation',
      message: 'La position de ${widget.fiche.nom} sera effacée.',
      confirmLabel: 'Retirer',
      icon: Icons.location_off_outlined,
    );
    if (ok) await _enregistrer(null);
  }

  Future<void> _ouvrirItineraire() async {
    final p = _point;
    if (p == null) return;
    final uri = Uri.parse(
      'https://www.google.com/maps/search/?api=1&query=${p.latitude},${p.longitude}',
    );
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      if (!mounted) return;
      showErrorSnack(ScaffoldMessenger.of(context), 'Aucune application de cartes.');
    }
  }

  /// Les deux façons d'ajouter : la position du téléphone, ou la carte.
  Future<void> _ajouter() async {
    final choix = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.my_location, color: AppTheme.brand),
              title: const Text('Ma position actuelle'),
              subtitle: const Text('Vous êtes chez le client : le téléphone relève sa position.'),
              onTap: () => Navigator.pop(ctx, 'gps'),
            ),
            ListTile(
              leading: const Icon(Icons.map_outlined, color: AppTheme.brand),
              title: const Text('Choisir sur la carte'),
              subtitle: const Text('Déplacez la carte et posez le repère à l’endroit voulu.'),
              onTap: () => Navigator.pop(ctx, 'carte'),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
    if (!mounted) return;
    if (choix == 'gps') await _utiliserMaPosition();
    if (choix == 'carte') await _choisirSurLaCarte();
  }

  @override
  Widget build(BuildContext context) {
    final point = _point;

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
              const Expanded(
                child: Text(
                  'Localisation',
                  style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600),
                ),
              ),
              if (_enregistrement)
                const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
            ],
          ),
          const SizedBox(height: 8),
          if (point == null) ...[
            const Text(
              'Aucune localisation. Facultative : elle sert à retrouver le client en tournée.',
              style: TextStyle(fontSize: 12.5, color: AppTheme.textMuted),
            ),
            if (widget.peutModifier) ...[
              const SizedBox(height: 10),
              FilledButton.tonalIcon(
                onPressed: _enregistrement ? null : _ajouter,
                icon: const Icon(Icons.add_location_alt_outlined),
                label: const Text('Ajouter une localisation'),
              ),
            ],
          ] else ...[
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: SizedBox(
                height: 150,
                child: IgnorePointer(
                  child: FlutterMap(
                    options: MapOptions(
                      initialCenter: point,
                      initialZoom: 15,
                      interactionOptions: const InteractionOptions(flags: InteractiveFlag.none),
                    ),
                    children: [
                      _fondDeCarte(),
                      MarkerLayer(markers: [_repere(point)]),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: 6),
            Text(
              '${point.latitude.toStringAsFixed(5)}, ${point.longitude.toStringAsFixed(5)}',
              style: const TextStyle(
                fontSize: 11.5,
                color: AppTheme.textFaint,
                fontFeatures: AppTheme.tabularFigures,
              ),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 4,
              children: [
                FilledButton.tonalIcon(
                  onPressed: _ouvrirItineraire,
                  icon: const Icon(Icons.directions_outlined, size: 18),
                  label: const Text('Itinéraire'),
                ),
                if (widget.peutModifier) ...[
                  OutlinedButton.icon(
                    onPressed: _enregistrement ? null : _ajouter,
                    icon: const Icon(Icons.edit_location_alt_outlined, size: 18),
                    label: const Text('Modifier'),
                  ),
                  TextButton.icon(
                    onPressed: _enregistrement ? null : _retirer,
                    icon: const Icon(Icons.location_off_outlined, size: 18),
                    label: const Text('Retirer'),
                  ),
                ],
              ],
            ),
          ],
        ],
      ),
    );
  }
}

/// Plein écran : on déplace la carte, le repère reste au centre, et on
/// confirme. Un bouton ramène sur la position du téléphone.
class ChoixSurCarteScreen extends StatefulWidget {
  const ChoixSurCarteScreen({super.key, this.initial, required this.titre});

  final LatLng? initial;
  final String titre;

  @override
  State<ChoixSurCarteScreen> createState() => _ChoixSurCarteScreenState();
}

class _ChoixSurCarteScreenState extends State<ChoixSurCarteScreen> {
  final _controleur = MapController();
  late LatLng _centre = widget.initial ?? _centreParDefaut;
  bool _recherchePosition = false;

  @override
  void initState() {
    super.initState();
    // Sans point connu, on se place d'abord là où est le téléphone : le
    // client est souvent à deux rues de là, pas à l'autre bout de la ville.
    if (widget.initial == null) _allerAMaPosition(silencieux: true);
  }

  Future<void> _allerAMaPosition({bool silencieux = false}) async {
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _recherchePosition = true);
    try {
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        if (!silencieux) {
          showErrorSnack(messenger, 'Autorisation de localisation refusée.');
        }
        return;
      }
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 15),
        ),
      );
      if (!mounted) return;
      final ici = LatLng(position.latitude, position.longitude);
      _controleur.move(ici, 16);
      setState(() => _centre = ici);
    } catch (_) {
      if (!silencieux && mounted) {
        showErrorSnack(messenger, 'Position introuvable pour le moment.');
      }
    } finally {
      if (mounted) setState(() => _recherchePosition = false);
    }
  }

  @override
  void dispose() {
    _controleur.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Localiser ${widget.titre}')),
      body: Stack(
        children: [
          FlutterMap(
            mapController: _controleur,
            options: MapOptions(
              initialCenter: _centre,
              initialZoom: widget.initial == null ? 13 : 16,
              onPositionChanged: (camera, _) {
                _centre = camera.center;
              },
            ),
            children: [_fondDeCarte()],
          ),
          // Le repère est fixe au centre de l'écran : c'est la carte qui bouge
          // sous lui. Plus précis au doigt qu'un marqueur à glisser.
          const IgnorePointer(
            child: Center(
              child: Padding(
                padding: EdgeInsets.only(bottom: 44),
                child: Icon(Icons.location_on, size: 44, color: AppTheme.brand),
              ),
            ),
          ),
          Positioned(
            top: 12,
            left: 12,
            right: 12,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.95),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: AppTheme.border),
              ),
              child: const Text(
                'Déplacez la carte pour placer le repère sur le client, puis confirmez.',
                style: TextStyle(fontSize: 12.5, color: AppTheme.textMuted),
              ),
            ),
          ),
          Positioned(
            right: 12,
            bottom: 96,
            child: FloatingActionButton.small(
              heroTag: 'ma-position',
              backgroundColor: Colors.white,
              foregroundColor: AppTheme.brand,
              onPressed: _recherchePosition ? null : () => _allerAMaPosition(),
              child: _recherchePosition
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.my_location),
            ),
          ),
        ],
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          child: FilledButton.icon(
            onPressed: () => Navigator.of(context).pop(_centre),
            icon: const Icon(Icons.check),
            label: const Text('Enregistrer cette position'),
          ),
        ),
      ),
    );
  }
}
