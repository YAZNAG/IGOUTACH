/// Point d'entrée unique des composants d'interface partagés.
///
/// Les écrans importent `core/widgets.dart` et disposent ainsi de toute la
/// bibliothèque : états (vide, erreur, chargement), squelettes, badges,
/// montants, barres de recherche et d'action, messages courts.
///
/// Le détail vit dans `core/ui/` afin qu'aucun écran n'ait à redéfinir un
/// squelette, une pastille de statut ou une SnackBar.
library;

export 'ui/blocks.dart';
export 'ui/feedback.dart';
export 'ui/skeletons.dart';
export 'ui/states.dart';
