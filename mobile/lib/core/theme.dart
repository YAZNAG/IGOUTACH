import 'package:flutter/material.dart';

/// Thème iGouTech : rouge de marque, encre presque noire, fond clair.
///
/// Les valeurs sont celles de la maquette UX validée (#E8112D et sa famille).
/// Les noms `navy` et `sky` sont conservés comme ALIAS : une centaine de
/// références les utilisent déjà, les renommer d'un bloc n'apporterait rien
/// et risquerait d'en manquer.
///
/// Le public visé (magasinier, vendeur, responsable de dépôt) utilise
/// l'application debout, souvent d'une seule main et en plein soleil. Les
/// choix ci-dessous en découlent :
///
/// - texte courant à 15-16 sp minimum (jamais 11-12 sp pour une information
///   utile) ;
/// - toute zone tactile à 48 dp minimum ;
/// - gris de texte suffisamment sombres pour rester lisibles (contraste
///   supérieur à 4.5:1 sur fond clair) ;
/// - chiffres tabulaires pour que les montants s'alignent en colonne.
class AppTheme {
  AppTheme._();

  // ── Couleurs de marque ───────────────────────────────────────────────────

  /// Rouge de marque, relevé sur la maquette.
  static const Color brand = Color(0xFFE8112D);
  static const Color brandDeep = Color(0xFFC50E26);
  static const Color brandLight = Color(0xFFFF3B4E);
  static const Color brandSoft = Color(0xFFFEECEE);

  /// Encre : presque noire, pas bleutée.
  static const Color ink = Color(0xFF141416);
  static const Color inkSoft = Color(0xFF3A3A40);

  // Alias hérités — pointent désormais sur la marque.
  static const Color navy = brand;
  static const Color navyDeep = brandDeep;
  static const Color sky = brand;
  static const Color background = Color(0xFFF4F4F6);

  static const Color success = Color(0xFF0E9F6E);
  static const Color successSoft = Color(0xFFE6F6F0);
  static const Color warning = Color(0xFFB7791F);
  static const Color warningSoft = Color(0xFFFDF3E2);
  static const Color danger = Color(0xFFC53B3B);
  static const Color dangerSoft = Color(0xFFFBEBEB);

  // ── Accents de section (accueil) ─────────────────────────────────────────

  /// Stock et mouvements.
  static const Color accentStock = sky;

  /// Commerce (clients, ventes, crédits).
  static const Color accentCommerce = success;

  /// Gestion (charges, tarifs).
  static const Color accentAdmin = warning;

  // ── Texte ────────────────────────────────────────────────────────────────

  /// Texte secondaire (contraste ≈ 6:1 sur blanc).
  static const Color textMuted = Color(0xFF5E5E68);

  /// Texte tertiaire, le plus clair encore lisible sur fond clair.
  static const Color textFaint = Color(0xFF7A7A85);

  static const Color border = Color(0xFFE6E6EA);
  static const Color borderStrong = Color(0xFFD4D4D9);

  /// Fond des blocs « squelette » pendant le chargement.
  static const Color skeleton = Color(0xFFEDEDF0);

  // ── Mesures ──────────────────────────────────────────────────────────────

  static const double radiusCard = 16;
  static const double radiusField = 12;

  /// Taille minimale d'une cible tactile (recommandation Material).
  static const double minTapTarget = 48;

  /// Chiffres à chasse fixe : les montants restent alignés d'une ligne
  /// à l'autre et ne « sautent » pas quand la valeur change.
  static const List<FontFeature> tabularFigures = [
    FontFeature.tabularFigures(),
  ];

  /// Style d'un montant (gras, chiffres tabulaires, jamais tronqué).
  static TextStyle amountStyle({
    double fontSize = 16,
    Color color = navy,
    FontWeight weight = FontWeight.w700,
  }) =>
      TextStyle(
        fontSize: fontSize,
        fontWeight: weight,
        color: color,
        fontFeatures: tabularFigures,
      );

  /// Style d'une référence / SKU (chasse fixe, lisible).
  static const TextStyle codeStyle = TextStyle(
    fontFamily: 'monospace',
    fontSize: 13,
    height: 1.2,
    fontWeight: FontWeight.w600,
    color: navy,
  );

  // ── Thème ────────────────────────────────────────────────────────────────

  static ThemeData light() {
    final scheme = ColorScheme.fromSeed(
      seedColor: navy,
      primary: navy,
      secondary: sky,
      surface: Colors.white,
      error: danger,
    );

    final textTheme = const TextTheme(
      titleLarge: TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
      titleMedium: TextStyle(fontSize: 17, fontWeight: FontWeight.w600),
      titleSmall: TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
      bodyLarge: TextStyle(fontSize: 16, height: 1.35),
      bodyMedium: TextStyle(fontSize: 15, height: 1.35),
      bodySmall: TextStyle(fontSize: 13.5, height: 1.3, color: textMuted),
      labelLarge: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
      labelMedium: TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
    ).apply(bodyColor: ink, displayColor: ink);

    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      scaffoldBackgroundColor: background,
      textTheme: textTheme,
      splashFactory: InkSparkle.splashFactory,
      pageTransitionsTheme: const PageTransitionsTheme(
        builders: {
          TargetPlatform.android: FadeForwardsPageTransitionsBuilder(),
          TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
          TargetPlatform.macOS: CupertinoPageTransitionsBuilder(),
          TargetPlatform.windows: FadeForwardsPageTransitionsBuilder(),
          TargetPlatform.linux: FadeForwardsPageTransitionsBuilder(),
        },
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: navy,
        foregroundColor: Colors.white,
        centerTitle: false,
        elevation: 0,
        scrolledUnderElevation: 0,
        titleTextStyle: TextStyle(
          color: Colors.white,
          fontSize: 19,
          fontWeight: FontWeight.w600,
        ),
        iconTheme: IconThemeData(color: Colors.white, size: 24),
        actionsIconTheme: IconThemeData(color: Colors.white, size: 24),
      ),
      cardTheme: CardThemeData(
        color: Colors.white,
        elevation: 0,
        shadowColor: navy.withValues(alpha: 0.10),
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusCard),
          side: const BorderSide(color: border),
        ),
        margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      ),
      dividerTheme: const DividerThemeData(
        color: border,
        thickness: 1,
        space: 1,
      ),
      listTileTheme: const ListTileThemeData(
        minVerticalPadding: 12,
        iconColor: navy,
        titleTextStyle: TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w600,
          color: ink,
        ),
        subtitleTextStyle: TextStyle(fontSize: 14, color: textMuted),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        floatingLabelBehavior: FloatingLabelBehavior.always,
        labelStyle: const TextStyle(fontSize: 15, color: textMuted),
        floatingLabelStyle: const TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w600,
          color: navy,
        ),
        hintStyle: const TextStyle(fontSize: 15, color: textFaint),
        helperStyle: const TextStyle(fontSize: 13, color: textMuted),
        errorStyle: const TextStyle(fontSize: 13.5, color: danger),
        prefixIconColor: textMuted,
        suffixIconColor: textMuted,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusField),
          borderSide: const BorderSide(color: border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusField),
          borderSide: const BorderSide(color: border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusField),
          borderSide: const BorderSide(color: sky, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusField),
          borderSide: const BorderSide(color: danger),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusField),
          borderSide: const BorderSide(color: danger, width: 2),
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 18,
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: navy,
          foregroundColor: Colors.white,
          disabledBackgroundColor: navy.withValues(alpha: 0.35),
          disabledForegroundColor: Colors.white70,
          minimumSize: const Size.fromHeight(52),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusField),
          ),
          textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: navy,
          minimumSize: const Size(64, minTapTarget),
          side: const BorderSide(color: border),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusField),
          ),
          textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: navy,
          minimumSize: const Size(64, minTapTarget),
          textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
        ),
      ),
      iconButtonTheme: IconButtonThemeData(
        style: IconButton.styleFrom(
          minimumSize: const Size(minTapTarget, minTapTarget),
        ),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: sky,
        foregroundColor: Colors.white,
        elevation: 3,
        extendedTextStyle: TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w600,
        ),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: Colors.white,
        selectedColor: navy,
        checkmarkColor: Colors.white,
        labelStyle: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
        secondaryLabelStyle: const TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w600,
          color: Colors.white,
        ),
        side: const BorderSide(color: border),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
        ),
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
        titleTextStyle: const TextStyle(
          fontSize: 19,
          fontWeight: FontWeight.w700,
          color: navy,
        ),
        contentTextStyle: const TextStyle(
          fontSize: 15,
          height: 1.4,
          color: ink,
        ),
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
      ),
      snackBarTheme: const SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        insetPadding: EdgeInsets.fromLTRB(12, 12, 12, 16),
        contentTextStyle: TextStyle(
          fontSize: 15,
          color: Colors.white,
          fontWeight: FontWeight.w500,
        ),
      ),
      progressIndicatorTheme: const ProgressIndicatorThemeData(
        color: sky,
        linearTrackColor: skeleton,
      ),
      datePickerTheme: DatePickerThemeData(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
      ),
      switchTheme: SwitchThemeData(
        thumbColor: WidgetStateProperty.resolveWith(
          (states) =>
              states.contains(WidgetState.selected) ? Colors.white : null,
        ),
        trackColor: WidgetStateProperty.resolveWith(
          (states) => states.contains(WidgetState.selected) ? sky : null,
        ),
      ),
    );
  }
}
