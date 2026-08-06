import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'core/auth_provider.dart';
import 'core/theme.dart';
import 'core/widgets.dart';
import 'features/auth/login_screen.dart';
import 'features/home/home_shell.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const IgoutechApp());
}

class IgoutechApp extends StatelessWidget {
  const IgoutechApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()..init()),
      ],
      child: MaterialApp(
        title: 'IGOUTECH',
        debugShowCheckedModeBanner: false,
        navigatorKey: appNavigatorKey,
        // L'application est conçue pour un usage en extérieur, souvent en
        // plein soleil : le thème clair est imposé, y compris quand le
        // téléphone est en mode sombre, pour garantir des contrastes connus.
        theme: AppTheme.light(),
        darkTheme: AppTheme.light(),
        themeMode: ThemeMode.light,
        home: const _Root(),
      ),
    );
  }
}

/// Racine : splash pendant l'initialisation, puis login ou accueil
/// selon la présence d'une session valide.
class _Root extends StatelessWidget {
  const _Root();

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    if (auth.initializing) return const SplashScreen();

    return auth.isAuthenticated ? const HomeShell() : const LoginScreen();
  }
}

/// Écran de démarrage : logo sur fond marine, cohérent avec l'écran de
/// connexion et avec l'arrière-plan natif Android.
class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: AppTheme.navy,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                AppLogo(size: 96, onDark: true),
                SizedBox(height: 24),
                Text(
                  'IGOUTECH',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 32,
                    fontWeight: FontWeight.bold,
                    letterSpacing: 3,
                  ),
                ),
                SizedBox(height: 6),
                Text(
                  'Gestion de stock',
                  style: TextStyle(color: Colors.white70, fontSize: 16),
                ),
                SizedBox(height: 36),
                SizedBox(
                  width: 28,
                  height: 28,
                  child: CircularProgressIndicator(
                    color: Colors.white,
                    strokeWidth: 3,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
