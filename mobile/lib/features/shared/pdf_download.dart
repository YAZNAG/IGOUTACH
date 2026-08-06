import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';

import '../../core/api_client.dart';
import '../../core/widgets.dart';

/// Télécharge un PDF de l'API (réponse binaire) puis l'ouvre.
///
/// Même mécanique que la facture de vente : octets → fichier dans le
/// répertoire de documents → `open_filex`. En cas d'échec, un message
/// lisible est affiché.
///
/// [path] est le chemin relatif de l'API (`/purchase-orders/12/pdf`),
/// [fileName] le nom souhaité sans extension (`BC-2026-0001`).
Future<void> downloadPdf(
  BuildContext context, {
  required String path,
  required String fileName,
}) async {
  final messenger = ScaffoldMessenger.of(context);
  try {
    final res = await ApiClient.instance.dio.get<List<int>>(
      path,
      options: Options(responseType: ResponseType.bytes),
    );
    final dir = await getApplicationDocumentsDirectory();
    final safeName = fileName.replaceAll(RegExp(r'[^\w\-]'), '_');
    final file = File('${dir.path}${Platform.pathSeparator}$safeName.pdf');
    await file.writeAsBytes(res.data ?? const []);
    await OpenFilex.open(file.path);
  } catch (e) {
    showErrorSnack(messenger, 'Téléchargement impossible : ${_pdfError(e)}');
  }
}

/// La réponse d'erreur d'un téléchargement arrive en octets : `friendlyError`
/// ne sait pas y lire le champ `message`, on le décode ici quand c'est un
/// refus métier (422).
String _pdfError(Object error) {
  if (error is DioException && error.response?.statusCode == 422) {
    return 'Ce document n\'est pas encore disponible en PDF.';
  }
  return friendlyError(error);
}
