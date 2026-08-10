import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';

/// Période retenue pour consulter et exporter un journal.
class Periode {
  const Periode(this.du, this.au);

  /// Par défaut le mois en cours : c'est la période que l'on consulte le plus.
  factory Periode.moisEnCours() {
    final now = DateTime.now();
    return Periode(DateTime(now.year, now.month, 1), now);
  }

  final DateTime du;
  final DateTime au;

  String get duIso => _iso(du);
  String get auIso => _iso(au);

  String get libelle => '${formatDate(du)} → ${formatDate(au)}';

  static String _iso(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-'
      '${d.month.toString().padLeft(2, '0')}-'
      '${d.day.toString().padLeft(2, '0')}';
}

/// Barre de période : affiche l'intervalle et permet de le changer ou
/// d'exporter le journal correspondant en PDF.
///
/// Le même composant sert aux ventes, entrées, sorties et charges : quatre
/// écrans, une seule barre, un seul comportement à corriger le jour où il
/// faudra le faire.
class PeriodBar extends StatelessWidget {
  const PeriodBar({
    super.key,
    required this.periode,
    required this.onChanged,
    required this.journal,
    this.exportLabel = 'Exporter en PDF',
  });

  final Periode periode;
  final ValueChanged<Periode> onChanged;

  /// Segment de l'URL : `sales`, `stock-entries`, `stock-exits`, `expenses`.
  final String journal;

  final String exportLabel;

  Future<void> _choisir(BuildContext context) async {
    final choix = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 1)),
      initialDateRange: DateTimeRange(start: periode.du, end: periode.au),
      helpText: 'Choisir la période',
      saveText: 'Valider',
    );

    if (choix != null) onChanged(Periode(choix.start, choix.end));
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(12, 8, 8, 8),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(bottom: BorderSide(color: AppTheme.border)),
      ),
      child: Row(
        children: [
          const Icon(Icons.event_outlined, size: 18, color: AppTheme.textMuted),
          const SizedBox(width: 8),
          Expanded(
            child: InkWell(
              onTap: () => _choisir(context),
              borderRadius: BorderRadius.circular(8),
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Text(
                  periode.libelle,
                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                ),
              ),
            ),
          ),
          TextButton.icon(
            onPressed: () => exporterJournal(context, journal, periode),
            icon: const Icon(Icons.picture_as_pdf_outlined, size: 18),
            label: const Text('PDF'),
            style: TextButton.styleFrom(foregroundColor: AppTheme.brand),
          ),
        ],
      ),
    );
  }
}

/// Télécharge le journal de la période et l'ouvre.
///
/// Le fichier est nommé d'après le journal et la période : plusieurs exports
/// successifs cohabitent au lieu de s'écraser, ce qui permet de les comparer.
Future<void> exporterJournal(
  BuildContext context,
  String journal,
  Periode periode,
) async {
  final messenger = ScaffoldMessenger.of(context);
  showInfoSnack(messenger, 'Préparation du document…');

  try {
    final res = await ApiClient.instance.dio.get<List<int>>(
      '/reports/journal/$journal',
      queryParameters: {'date_from': periode.duIso, 'date_to': periode.auIso},
      options: Options(responseType: ResponseType.bytes),
    );

    final dir = await getApplicationDocumentsDirectory();
    final nom = '$journal-${periode.duIso}-${periode.auIso}.pdf';
    final file = File('${dir.path}${Platform.pathSeparator}$nom');
    await file.writeAsBytes(res.data ?? const []);

    final ouverture = await OpenFilex.open(file.path);

    if (ouverture.type != ResultType.done) {
      showErrorSnack(
        messenger,
        'Document enregistré, mais aucune application ne peut l’ouvrir. '
        'Installez un lecteur PDF.',
      );
    }
  } catch (e) {
    showErrorSnack(messenger, 'Export impossible : ${friendlyError(e)}');
  }
}
