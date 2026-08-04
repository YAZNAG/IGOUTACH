import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'config.dart';

/// Client HTTP unique (singleton) :
/// - ajoute le jeton Sanctum (`Authorization: Bearer …`) depuis le stockage
///   sécurisé sur chaque requête ;
/// - sur une réponse 401 (jeton révoqué/expiré), déclenche la déconnexion
///   via [onUnauthorized] (branché par AuthProvider).
class ApiClient {
  ApiClient._() {
    dio = Dio(
      BaseOptions(
        baseUrl: AppConfig.baseUrl,
        headers: {'Accept': 'application/json'},
        connectTimeout: const Duration(seconds: 15),
        receiveTimeout: const Duration(seconds: 45),
      ),
    );

    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await readToken();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) {
          final isLogin = error.requestOptions.path.contains('/mobile/login');
          if (error.response?.statusCode == 401 && !isLogin) {
            onUnauthorized?.call();
          }
          handler.next(error);
        },
      ),
    );
  }

  static final ApiClient instance = ApiClient._();

  static const String _tokenKey = 'igoutech_token';

  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  late final Dio dio;

  /// Appelé quand le serveur répond 401 : AuthProvider force la déconnexion.
  void Function()? onUnauthorized;

  Future<String?> readToken() => _storage.read(key: _tokenKey);

  Future<void> saveToken(String token) =>
      _storage.write(key: _tokenKey, value: token);

  Future<void> clearToken() => _storage.delete(key: _tokenKey);
}

/// Message d'erreur lisible (en français) pour une exception réseau.
String friendlyError(Object error) {
  if (error is DioException) {
    final data = error.response?.data;
    if (data is Map && data['message'] is String) {
      return data['message'] as String;
    }
    final status = error.response?.statusCode;
    if (status == 401) return 'Session expirée. Reconnectez-vous.';
    if (status == 403) return 'Accès non autorisé.';
    if (status == 404) return 'Ressource introuvable.';
    if (status != null) return 'Erreur serveur ($status).';
    return 'Impossible de contacter le serveur. Vérifiez le réseau.';
  }
  return 'Une erreur inattendue est survenue.';
}
