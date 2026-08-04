/// Configuration globale de l'application.
class AppConfig {
  AppConfig._();

  /// URL de base de l'API Laravel.
  ///
  /// `10.0.2.2` est l'adresse spéciale qui, depuis l'émulateur Android,
  /// pointe vers le `localhost` de la machine hôte (là où tourne
  /// `php artisan serve --port=8001`).
  ///
  /// - Émulateur Android : http://10.0.2.2:8001/api/v1 (défaut)
  /// - Appareil physique : IP locale du PC, ex. http://192.168.1.10:8001/api/v1
  /// - Production (APK lié au serveur) : passer l'URL à la compilation :
  ///   flutter build apk --release --dart-define=API_URL=http://SERVEUR/api/v1
  static const String baseUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'http://10.0.2.2:8001/api/v1',
  );

  /// Nom d'appareil envoyé au login (un jeton Sanctum par appareil).
  static const String deviceName = 'mobile-flutter';
}
