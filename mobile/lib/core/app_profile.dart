import 'auth_provider.dart';

/// Profil d'interface de l'application.
///
/// Deux APK sont produits depuis la même base de code :
///   flutter build apk --dart-define=APP_PROFILE=admin
///   flutter build apk --dart-define=APP_PROFILE=manager
///
/// Le profil ne donne AUCUN droit supplémentaire : les permissions restent
/// décidées par le serveur. Il choisit seulement la présentation (navigation,
/// modules mis en avant, page d'accueil).
enum AppProfile {
  /// Direction : navigation complète par onglets, pilotage multi-lieux.
  admin,

  /// Responsable d'un lieu : parcours court centré sur son dépôt.
  manager;

  static const String _defined = String.fromEnvironment('APP_PROFILE');

  /// Profil compilé dans l'APK. Sans `--dart-define`, il est déduit des
  /// permissions de l'utilisateur connecté (vue globale = direction).
  static AppProfile resolve(AuthProvider auth) {
    switch (_defined) {
      case 'admin':
        return AppProfile.admin;
      case 'manager':
        return AppProfile.manager;
      default:
        return auth.can('stock.view_global') || auth.can('user.view')
            ? AppProfile.admin
            : AppProfile.manager;
    }
  }

  /// Nom affiché sous le titre sur l'écran de connexion.
  String get label => switch (this) {
        AppProfile.admin => 'Administration',
        AppProfile.manager => 'Responsable de lieu',
      };
}
