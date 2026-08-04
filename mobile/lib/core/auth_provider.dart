import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../models/app_user.dart';
import 'api_client.dart';
import 'config.dart';

/// Clé de navigation globale : permet de revenir à la racine
/// quand la session expire (401) alors que des écrans sont empilés.
final GlobalKey<NavigatorState> appNavigatorKey = GlobalKey<NavigatorState>();

/// État d'authentification : jeton Sanctum + profil + permissions.
class AuthProvider extends ChangeNotifier {
  AuthProvider() {
    _api.onUnauthorized = forceLogout;
  }

  final ApiClient _api = ApiClient.instance;

  AppUser? _user;
  bool _initializing = true;
  bool _loggingIn = false;

  AppUser? get user => _user;
  bool get initializing => _initializing;
  bool get loggingIn => _loggingIn;
  bool get isAuthenticated => _user != null;

  /// L'utilisateur possède-t-il la permission donnée ?
  bool can(String permission) =>
      _user?.permissions.contains(permission) ?? false;

  /// Au démarrage : recharge le jeton stocké puis le profil (/user).
  Future<void> init() async {
    final token = await _api.readToken();
    if (token == null || token.isEmpty) {
      _initializing = false;
      notifyListeners();
      return;
    }
    try {
      final res = await _api.dio.get<Map<String, dynamic>>('/user');
      _user = AppUser.fromJson(res.data!['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) {
        await _api.clearToken();
      }
      _user = null;
    } catch (_) {
      _user = null;
    }
    _initializing = false;
    notifyListeners();
  }

  /// Connexion : retourne `null` si OK, sinon un message d'erreur.
  Future<String?> login(String email, String password) async {
    _loggingIn = true;
    notifyListeners();
    try {
      final res = await _api.dio.post<Map<String, dynamic>>(
        '/mobile/login',
        data: {
          'email': email,
          'password': password,
          'device_name': AppConfig.deviceName,
        },
      );
      final data = res.data!['data'] as Map<String, dynamic>;
      await _api.saveToken(data['token'] as String);
      _user = AppUser.fromJson(data['user'] as Map<String, dynamic>);
      return null;
    } on DioException catch (e) {
      return friendlyError(e);
    } catch (_) {
      return 'Une erreur inattendue est survenue.';
    } finally {
      _loggingIn = false;
      notifyListeners();
    }
  }

  /// Déconnexion volontaire : révoque le jeton côté serveur puis local.
  Future<void> logout() async {
    try {
      await _api.dio.post('/mobile/logout');
    } catch (_) {
      // Le jeton local est supprimé même si le serveur est injoignable.
    }
    await forceLogout();
  }

  /// Déconnexion locale (utilisée aussi sur 401) : retour à l'écran de login.
  Future<void> forceLogout() async {
    await _api.clearToken();
    if (_user != null) {
      _user = null;
      // Dépile les écrans ouverts pour revenir à la racine (login).
      appNavigatorKey.currentState?.popUntil((route) => route.isFirst);
      notifyListeners();
    }
  }
}
