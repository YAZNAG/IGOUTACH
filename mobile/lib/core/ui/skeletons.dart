import 'package:flutter/material.dart';

import '../theme.dart';

/// Rectangle gris animé (« squelette ») affiché pendant un chargement.
///
/// Reprendre la forme du contenu attendu plutôt qu'afficher une roue de
/// chargement donne le sentiment d'une application deux fois plus rapide :
/// l'écran est déjà dessiné, il ne manque que les valeurs.
class SkeletonBox extends StatefulWidget {
  const SkeletonBox({
    super.key,
    this.width,
    required this.height,
    this.radius = 8,
  });

  final double? width;
  final double height;
  final double radius;

  @override
  State<SkeletonBox> createState() => _SkeletonBoxState();
}

class _SkeletonBoxState extends State<SkeletonBox>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1100),
  )..repeat(reverse: true);

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: Tween<double>(begin: 0.45, end: 1).animate(
        CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
      ),
      child: Container(
        width: widget.width,
        height: widget.height,
        decoration: BoxDecoration(
          color: AppTheme.skeleton,
          borderRadius: BorderRadius.circular(widget.radius),
        ),
      ),
    );
  }
}

/// Carte « squelette » reprenant la forme d'une ligne de liste :
/// un titre, une ligne secondaire et un montant à droite.
class SkeletonCard extends StatelessWidget {
  const SkeletonCard({
    super.key,
    this.hasLeading = false,
    this.lines = 2,
  });

  /// Réserve la place d'une pastille/avatar à gauche.
  final bool hasLeading;

  /// Nombre de lignes de texte simulées (1 à 3).
  final int lines;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (hasLeading) ...[
              const SkeletonBox(width: 44, height: 44, radius: 22),
              const SizedBox(width: 12),
            ],
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  const SkeletonBox(height: 15, radius: 6),
                  if (lines >= 2) ...[
                    const SizedBox(height: 10),
                    const FractionallySizedBox(
                      alignment: Alignment.centerLeft,
                      widthFactor: 0.6,
                      child: SkeletonBox(height: 12, radius: 6),
                    ),
                  ],
                  if (lines >= 3) ...[
                    const SizedBox(height: 10),
                    const FractionallySizedBox(
                      alignment: Alignment.centerLeft,
                      widthFactor: 0.4,
                      child: SkeletonBox(height: 12, radius: 6),
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(width: 12),
            const Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                SkeletonBox(width: 74, height: 16, radius: 6),
                SizedBox(height: 8),
                SkeletonBox(width: 50, height: 12, radius: 6),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

/// Liste de cartes « squelette » : remplace la roue de chargement au
/// premier affichage d'une liste.
class ListSkeleton extends StatelessWidget {
  const ListSkeleton({
    super.key,
    this.itemCount = 6,
    this.hasLeading = false,
    this.lines = 2,
    this.padding = const EdgeInsets.only(top: 4, bottom: 24),
  });

  final int itemCount;
  final bool hasLeading;
  final int lines;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      padding: padding,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: itemCount,
      itemBuilder: (context, index) => SkeletonCard(
        hasLeading: hasLeading,
        lines: lines,
      ),
    );
  }
}

/// Squelette d'un formulaire (bandeau + champs) pendant le chargement
/// des données de référence.
class FormSkeleton extends StatelessWidget {
  const FormSkeleton({super.key, this.fieldCount = 4});

  final int fieldCount;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 20, 16, 32),
      children: [
        for (var i = 0; i < fieldCount; i++) ...[
          const SkeletonBox(width: 120, height: 13, radius: 6),
          const SizedBox(height: 8),
          const SkeletonBox(height: 56, radius: AppTheme.radiusField),
          const SizedBox(height: 20),
        ],
      ],
    );
  }
}
