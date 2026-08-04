import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'core/auth_provider.dart';
import 'core/theme.dart';
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
        theme: AppTheme.light(),
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

    if (auth.initializing) {
      return const Scaffold(
        backgroundColor: AppTheme.navy,
        body: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'IGOUTECH',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 32,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 2,
                ),
              ),
              SizedBox(height: 24),
              CircularProgressIndicator(color: Colors.white),
            ],
          ),
        ),
      );
    }

    return auth.isAuthenticated ? const HomeShell() : const LoginScreen();
  }
}
