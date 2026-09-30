/// Configuration globale de l'application.
class AppConfig {
  AppConfig._();

  /// URL de base de l'API Laravel.
  ///
  /// `10.0.2.2` est l'adresse spéciale qui, depuis l'émulateur Android,
  /// pointe vers le `localhost` de la machine hôte (là où tourne
  /// `php artisan serve --port=8001`).
  ///
  /// Le défaut est le serveur de production : un APK compilé sans préciser
  /// l'adresse est un APK distribué, et il doit fonctionner. Le défaut était
  /// l'adresse de l'émulateur — une compilation sans `--dart-define` livrait
  /// alors une application qui ne joignait aucun serveur, avec pour seul
  /// symptôme « le serveur n'a pas répondu ».
  ///
  /// Pour développer en local, préciser l'adresse à la compilation :
  /// - Émulateur Android :
  ///   flutter run --dart-define=API_URL=http://10.0.2.2:8001/api/v1
  /// - Appareil physique : l'IP locale du PC, ex. http://192.168.1.10:8001/api/v1
  static const String baseUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'https://igoutech.optizaworks.com/api/v1',
  );

  /// Nom d'appareil envoyé au login (un jeton Sanctum par appareil).
  static const String deviceName = 'mobile-flutter';
}
