import 'dart:io';

import 'package:dio/dio.dart';
import 'package:open_filex/open_filex.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:path_provider/path_provider.dart';

import 'config.dart';

/// Version publiée sur le serveur.
class AppRelease {
  const AppRelease({
    required this.version,
    required this.build,
    required this.url,
    this.size,
    this.notes,
    this.mandatory = false,
  });

  final String version;
  final int build;
  final String? url;
  final int? size;
  final String? notes;

  /// Bloque l'usage tant qu'elle n'est pas installée. Réservé aux corrections
  /// qui fausseraient les données si l'ancienne version continuait de tourner.
  final bool mandatory;

  static AppRelease? fromJson(Map<String, dynamic>? json) {
    if (json == null || json['version'] == null) return null;
    return AppRelease(
      version: json['version'] as String,
      build: (json['build'] as num?)?.toInt() ?? 0,
      url: json['url'] as String?,
      size: (json['size'] as num?)?.toInt(),
      notes: json['notes'] as String?,
      mandatory: json['mandatory'] == true,
    );
  }

  String get sizeLabel {
    if (size == null) return '';
    final mo = size! / (1024 * 1024);
    return '${mo.toStringAsFixed(1)} Mo';
  }
}

/// Mise à jour de l'application sans repasser par un transfert de fichier.
///
/// Android n'autorise pas l'installation silencieuse sur un téléphone
/// ordinaire : on télécharge l'APK puis on ouvre l'installateur du système,
/// que l'utilisateur confirme d'un tap. C'est la limite de la plateforme,
/// pas un choix de conception.
class UpdateService {
  UpdateService({Dio? dio}) : _dio = dio ?? Dio();

  final Dio _dio;

  /// Version publiée si elle est plus récente que celle installée, sinon null.
  Future<AppRelease?> checkForUpdate() async {
    try {
      final info = await PackageInfo.fromPlatform();
      final res = await _dio.get<Map<String, dynamic>>(
        '${AppConfig.baseUrl}/app/version',
        options: Options(
          // Une mise à jour n'est jamais urgente au point de faire attendre
          // l'écran d'accueil : on abandonne vite en cas de réseau lent.
          sendTimeout: const Duration(seconds: 6),
          receiveTimeout: const Duration(seconds: 6),
        ),
      );

      final release = AppRelease.fromJson(
        res.data?['data'] as Map<String, dynamic>?,
      );

      if (release == null || release.url == null) return null;

      final installe = int.tryParse(info.buildNumber) ?? 0;

      // Le numéro de build tranche, pas la chaîne de version : « 1.10.0 »
      // est postérieur à « 1.9.0 » alors qu'il lui est inférieur en ordre
      // alphabétique.
      return release.build > installe ? release : null;
    } catch (_) {
      // Serveur injoignable ou version illisible : l'application continue
      // normalement. Une mise à jour ratée ne doit pas bloquer le travail.
      return null;
    }
  }

  /// Télécharge l'APK puis ouvre l'installateur Android.
  ///
  /// [onProgress] reçoit une valeur entre 0 et 1, ou null tant que la taille
  /// totale est inconnue.
  Future<void> downloadAndInstall(
    AppRelease release, {
    void Function(double? progress)? onProgress,
  }) async {
    if (release.url == null) {
      throw StateError('Aucun fichier à télécharger pour cette version.');
    }

    final dossier = await getTemporaryDirectory();
    final chemin = '${dossier.path}/igoutech-${release.version}.apk';
    final fichier = File(chemin);

    // Un téléchargement interrompu laisse un fichier tronqué que
    // l'installateur refuserait : on repart d'un fichier propre.
    if (await fichier.exists()) {
      await fichier.delete();
    }

    await _dio.download(
      release.url!,
      chemin,
      onReceiveProgress: (recu, total) {
        onProgress?.call(total > 0 ? recu / total : null);
      },
    );

    final resultat = await OpenFilex.open(chemin);

    if (resultat.type != ResultType.done) {
      throw StateError(
        'Impossible d’ouvrir l’installateur : ${resultat.message}. '
        'Autorisez « Installer des applications inconnues » pour iGouTech.',
      );
    }
  }
}
