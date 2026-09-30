import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/theme.dart';

/// Déclaration d'un transfert vers la caisse générale.
///
/// Montant, motif et — c'est le point — une photo : reçu signé, billets
/// comptés, bordereau. L'administration regarde cette image avant de
/// confirmer, et elle reste attachée au transfert dans l'historique : le
/// désaccord sur « combien as-tu remis » se tranche alors sur une pièce, pas
/// sur deux souvenirs.
class TransfertCaisseSheet extends StatefulWidget {
  const TransfertCaisseSheet({
    super.key,
    required this.warehouseId,
    required this.solde,
  });

  final int warehouseId;

  /// Solde actuel du tiroir : plafond du montant transférable.
  final double solde;

  /// Ouvre le formulaire ; retourne `true` si un transfert a été déclaré.
  static Future<bool> ouvrir(
    BuildContext context, {
    required int warehouseId,
    required double solde,
  }) async {
    final cree = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (_) => Padding(
        padding: EdgeInsets.only(
          bottom: MediaQuery.of(context).viewInsets.bottom,
        ),
        child: TransfertCaisseSheet(warehouseId: warehouseId, solde: solde),
      ),
    );
    return cree ?? false;
  }

  @override
  State<TransfertCaisseSheet> createState() => _TransfertCaisseSheetState();
}

class _TransfertCaisseSheetState extends State<TransfertCaisseSheet> {
  final _formKey = GlobalKey<FormState>();
  final _montant = TextEditingController();
  final _note = TextEditingController();

  XFile? _photo;
  bool _envoi = false;
  String? _erreur;

  @override
  void initState() {
    super.initState();
    // Remettre tout le tiroir est le geste courant du soir : on le propose.
    if (widget.solde > 0) {
      _montant.text = widget.solde.toStringAsFixed(2);
    }
  }

  @override
  void dispose() {
    _montant.dispose();
    _note.dispose();
    super.dispose();
  }

  Future<void> _choisirPhoto(ImageSource source) async {
    try {
      final fichier = await ImagePicker().pickImage(
        source: source,
        // Une photo de reçu n'a pas besoin de la pleine résolution : un envoi
        // de 8 Mo depuis un magasin en 3G n'aboutit pas.
        maxWidth: 1600,
        imageQuality: 80,
      );
      if (fichier == null || !mounted) return;
      setState(() => _photo = fichier);
    } catch (e) {
      if (!mounted) return;
      setState(() => _erreur = 'Photo indisponible : ${friendlyError(e)}');
    }
  }

  Future<void> _envoyer() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() {
      _envoi = true;
      _erreur = null;
    });

    try {
      final montant = double.parse(_montant.text.trim().replaceAll(',', '.'));
      final corps = FormData.fromMap({
        'warehouse_id': widget.warehouseId,
        'amount': montant,
        if (_note.text.trim().isNotEmpty) 'note': _note.text.trim(),
        if (_photo != null)
          'proof': await MultipartFile.fromFile(
            _photo!.path,
            filename: _photo!.name,
          ),
      });

      await ApiClient.instance.dio.post<Map<String, dynamic>>(
        '/cash-remittances',
        data: corps,
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
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 20),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text(
                'Transfert vers la caisse générale',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: AppTheme.navy,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Solde actuel de la caisse : ${formatMoney(widget.solde)}. '
                'L\'administration confirmera la réception.',
                style: const TextStyle(fontSize: 12.5, color: AppTheme.textMuted),
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _montant,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                inputFormatters: [
                  FilteringTextInputFormatter.allow(RegExp(r'[0-9.,]')),
                ],
                decoration: const InputDecoration(
                  labelText: 'Montant transféré (DH)',
                  prefixIcon: Icon(Icons.payments_outlined),
                ),
                validator: (value) {
                  final montant =
                      double.tryParse((value ?? '').trim().replaceAll(',', '.'));
                  if (montant == null || montant <= 0) {
                    return 'Saisissez le montant transféré.';
                  }
                  if (montant > widget.solde + 0.001) {
                    return 'La caisse ne contient que ${formatMoney(widget.solde)}.';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _note,
                maxLength: 255,
                decoration: const InputDecoration(
                  labelText: 'Note (facultatif)',
                  helperText: 'Qui emporte l\'argent, quand, par quel moyen.',
                  prefixIcon: Icon(Icons.sticky_note_2_outlined),
                ),
              ),
              const SizedBox(height: 6),
              _blocPhoto(),
              if (_erreur != null) ...[
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppTheme.danger.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    _erreur!,
                    style: const TextStyle(fontSize: 12.5, color: AppTheme.danger),
                  ),
                ),
              ],
              const SizedBox(height: 14),
              FilledButton.icon(
                onPressed: _envoi ? null : _envoyer,
                icon: _envoi
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.upload_outlined),
                label: Text(_envoi ? 'Envoi…' : 'Déclarer le transfert'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _blocPhoto() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: _envoi ? null : () => _choisirPhoto(ImageSource.camera),
                icon: const Icon(Icons.photo_camera_outlined, size: 18),
                label: const Text('Photo'),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: OutlinedButton.icon(
                onPressed: _envoi ? null : () => _choisirPhoto(ImageSource.gallery),
                icon: const Icon(Icons.image_outlined, size: 18),
                label: const Text('Galerie'),
              ),
            ),
          ],
        ),
        if (_photo != null)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: Row(
              children: [
                const Icon(Icons.attach_file, size: 16, color: AppTheme.success),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    _photo!.name,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 12.5),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close, size: 18),
                  tooltip: 'Retirer la photo',
                  onPressed: _envoi ? null : () => setState(() => _photo = null),
                ),
              ],
            ),
          )
        else
          const Padding(
            padding: EdgeInsets.only(top: 6),
            child: Text(
              'Joignez une photo du reçu ou des billets : l\'administration la '
              'voit avant de confirmer, et elle reste dans l\'historique.',
              style: TextStyle(fontSize: 11.5, color: AppTheme.textMuted),
            ),
          ),
      ],
    );
  }
}
