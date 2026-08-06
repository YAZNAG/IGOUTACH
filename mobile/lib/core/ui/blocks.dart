import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../theme.dart';

/// Logo de l'application : pastille arrondie marine (ou blanche sur fond
/// marine) portant l'icône de gestion de stock.
class AppLogo extends StatelessWidget {
  const AppLogo({super.key, this.size = 84, this.onDark = false});

  final double size;

  /// `true` sur fond marine : la pastille devient claire et l'icône marine.
  final bool onDark;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: onDark ? Colors.white : AppTheme.navy,
        borderRadius: BorderRadius.circular(size * 0.28),
        boxShadow: onDark
            ? null
            : [
                BoxShadow(
                  color: AppTheme.navy.withValues(alpha: 0.25),
                  blurRadius: 18,
                  offset: const Offset(0, 8),
                ),
              ],
      ),
      child: Icon(
        Icons.inventory_2_rounded,
        color: onDark ? AppTheme.navy : Colors.white,
        size: size * 0.52,
      ),
    );
  }
}

/// Badge de statut : fond teinté + texte de la même famille de couleur.
///
/// Jamais du texte coloré sur fond blanc : en plein soleil, un mot orange
/// sur blanc devient illisible, alors qu'une pastille teintée reste visible.
class StatusBadge extends StatelessWidget {
  const StatusBadge({
    super.key,
    required this.label,
    required this.color,
    this.icon,
  });

  final String label;
  final Color color;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    final text = Text(
      label,
      maxLines: 1,
      softWrap: false,
      overflow: TextOverflow.ellipsis,
      style: TextStyle(
        color: color,
        fontSize: 13,
        height: 1.15,
        fontWeight: FontWeight.w700,
      ),
    );

    // Un libellé long (« En attente d'approbation ») ne doit jamais faire
    // déborder la ligne : quand la largeur disponible est connue, le texte
    // devient flexible et se termine par « … ».
    return LayoutBuilder(
      builder: (context, constraints) => Container(
        padding: EdgeInsets.fromLTRB(icon == null ? 10 : 8, 5, 10, 5),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.13),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: color.withValues(alpha: 0.28)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (icon != null) ...[
              Icon(icon, size: 14, color: color),
              const SizedBox(width: 4),
            ],
            if (constraints.hasBoundedWidth) Flexible(child: text) else text,
          ],
        ),
      ),
    );
  }
}

/// Encadré d'erreur affiché dans un formulaire (message renvoyé par l'API).
class ErrorBox extends StatelessWidget {
  const ErrorBox({super.key, required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppTheme.danger.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(AppTheme.radiusField),
        border: Border.all(color: AppTheme.danger.withValues(alpha: 0.3)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.error_outline, color: AppTheme.danger),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(color: AppTheme.danger, fontSize: 15),
            ),
          ),
        ],
      ),
    );
  }
}

/// Montant en chiffres tabulaires, gras, aligné à droite et jamais tronqué.
class AmountText extends StatelessWidget {
  const AmountText(
    this.value, {
    super.key,
    this.fontSize = 16,
    this.color = AppTheme.navy,
    this.label,
  });

  /// Texte déjà formaté (`formatMoney` / `formatQuantity`).
  final String value;
  final double fontSize;
  final Color color;

  /// Légende affichée sous le montant (ex. « Encours »).
  final String? label;

  @override
  Widget build(BuildContext context) {
    // La largeur est bornée : même un montant inhabituellement long ne peut
    // pas écraser le texte voisin ni provoquer de débordement.
    return ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 170),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          FittedBox(
            fit: BoxFit.scaleDown,
            alignment: Alignment.centerRight,
            child: Text(
              value,
              textAlign: TextAlign.right,
              maxLines: 1,
              style: AppTheme.amountStyle(fontSize: fontSize, color: color),
            ),
          ),
          if (label != null)
            Text(
              label!,
              maxLines: 1,
              textAlign: TextAlign.right,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 13, color: AppTheme.textMuted),
            ),
        ],
      ),
    );
  }
}

/// Petit titre de section, discret : majuscules, gris, interlettrage léger.
class SectionTitle extends StatelessWidget {
  const SectionTitle(
    this.title, {
    super.key,
    this.padding = const EdgeInsets.fromLTRB(4, 24, 4, 12),
  });

  final String title;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: padding,
      child: Text(
        title.toUpperCase(),
        style: const TextStyle(
          fontSize: 13,
          fontWeight: FontWeight.w700,
          letterSpacing: 1.2,
          color: AppTheme.textMuted,
        ),
      ),
    );
  }
}

/// Barre de recherche arrondie : loupe, croix d'effacement, texte lisible.
///
/// Le rebond (« debounce ») reste géré par l'écran appelant.
class AppSearchField extends StatefulWidget {
  const AppSearchField({
    super.key,
    required this.controller,
    required this.onChanged,
    required this.hintText,
    this.autofocus = false,
    this.padding = const EdgeInsets.fromLTRB(12, 12, 12, 4),
  });

  final TextEditingController controller;
  final ValueChanged<String> onChanged;
  final String hintText;
  final bool autofocus;
  final EdgeInsetsGeometry padding;

  @override
  State<AppSearchField> createState() => _AppSearchFieldState();
}

class _AppSearchFieldState extends State<AppSearchField> {
  @override
  Widget build(BuildContext context) {
    final hasText = widget.controller.text.isNotEmpty;

    return Padding(
      padding: widget.padding,
      child: TextField(
        controller: widget.controller,
        autofocus: widget.autofocus,
        textInputAction: TextInputAction.search,
        style: const TextStyle(fontSize: 16),
        onChanged: (value) {
          setState(() {});
          widget.onChanged(value);
        },
        decoration: InputDecoration(
          hintText: widget.hintText,
          prefixIcon: const Icon(Icons.search, size: 24),
          isDense: true,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 16,
            vertical: 16,
          ),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(28),
            borderSide: const BorderSide(color: AppTheme.border),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(28),
            borderSide: const BorderSide(color: AppTheme.border),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(28),
            borderSide: const BorderSide(color: AppTheme.sky, width: 2),
          ),
          suffixIcon: !hasText
              ? null
              : IconButton(
                  icon: const Icon(Icons.close),
                  tooltip: 'Effacer la recherche',
                  onPressed: () {
                    widget.controller.clear();
                    setState(() {});
                    widget.onChanged('');
                  },
                ),
        ),
      ),
    );
  }
}

/// Barre d'action fixée en bas de l'écran : récapitulatif (total, nombre de
/// lignes…) puis bouton principal avec état de chargement intégré.
class BottomActionBar extends StatelessWidget {
  const BottomActionBar({
    super.key,
    required this.label,
    required this.onPressed,
    this.loading = false,
    this.icon = Icons.check,
    this.summaryLabel,
    this.summaryValue,
    this.summaryColor = AppTheme.navy,
    this.color = AppTheme.navy,
  });

  final String label;

  /// `null` désactive le bouton (règle métier non satisfaite).
  final VoidCallback? onPressed;
  final bool loading;
  final IconData icon;

  /// Récapitulatif affiché au-dessus du bouton (ex. « Total »).
  final String? summaryLabel;
  final String? summaryValue;
  final Color summaryColor;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        border: const Border(top: BorderSide(color: AppTheme.border)),
        boxShadow: [
          BoxShadow(
            color: AppTheme.navy.withValues(alpha: 0.08),
            blurRadius: 12,
            offset: const Offset(0, -3),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (summaryValue != null) ...[
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        summaryLabel ?? '',
                        style: const TextStyle(
                          fontSize: 16,
                          color: AppTheme.textMuted,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Flexible(
                      child: AmountText(
                        summaryValue!,
                        fontSize: 22,
                        color: summaryColor,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
              ],
              FilledButton.icon(
                onPressed: loading ? null : onPressed,
                style: FilledButton.styleFrom(backgroundColor: color),
                icon: loading
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2.5,
                          color: Colors.white,
                        ),
                      )
                    : Icon(icon),
                label: Text(loading ? 'Enregistrement…' : label),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Sélecteur de quantité − / valeur / + avec cibles tactiles de 48 dp.
///
/// La valeur centrale est un vrai champ de saisie : vendre 250 unités se fait
/// au clavier, pas en 249 appuis. Le contenu est sélectionné à la prise de
/// focus, si bien que taper un nombre remplace la valeur au lieu de
/// l'allonger. Un champ vidé revient à [minQuantity] à la sortie du champ.
///
/// [onChanged] est appelé à chaque valeur valide ; c'est à l'écran appelant
/// d'amortir (« debounce ») les requêtes réseau qui en découlent.
class QuantityStepper extends StatefulWidget {
  const QuantityStepper({
    super.key,
    required this.quantity,
    required this.onChanged,
    this.minQuantity = 1,
    this.enabled = true,
  });

  final int quantity;
  final ValueChanged<int> onChanged;
  final int minQuantity;
  final bool enabled;

  @override
  State<QuantityStepper> createState() => _QuantityStepperState();
}

class _QuantityStepperState extends State<QuantityStepper> {
  late final TextEditingController _controller =
      TextEditingController(text: '${widget.quantity}');
  final FocusNode _focusNode = FocusNode();

  @override
  void initState() {
    super.initState();
    _focusNode.addListener(_onFocusChanged);
  }

  @override
  void didUpdateWidget(covariant QuantityStepper oldWidget) {
    super.didUpdateWidget(oldWidget);
    // Synchronisation seulement hors saisie : on ne réécrit jamais sous les
    // doigts de l'utilisateur.
    if (!_focusNode.hasFocus && widget.quantity != _typedQuantity) {
      _controller.text = '${widget.quantity}';
    }
  }

  @override
  void dispose() {
    _focusNode.removeListener(_onFocusChanged);
    _focusNode.dispose();
    _controller.dispose();
    super.dispose();
  }

  int? get _typedQuantity => int.tryParse(_controller.text.trim());

  void _onFocusChanged() {
    if (_focusNode.hasFocus) {
      _controller.selection = TextSelection(
        baseOffset: 0,
        extentOffset: _controller.text.length,
      );
      return;
    }
    // Sortie du champ : une saisie vide ou inférieure au minimum est
    // ramenée au minimum, jamais laissée dans un état invalide.
    final typed = _typedQuantity;
    if (typed == null || typed < widget.minQuantity) {
      _apply(widget.minQuantity);
    }
  }

  /// Écrit la valeur dans le champ et prévient l'écran appelant.
  void _apply(int value) {
    _controller.value = TextEditingValue(
      text: '$value',
      selection: TextSelection.collapsed(offset: '$value'.length),
    );
    widget.onChanged(value);
  }

  void _step(int delta) {
    final current = _typedQuantity ?? widget.quantity;
    final next = current + delta;
    if (next < widget.minQuantity) return;
    _apply(next);
  }

  void _onTextChanged(String value) {
    final typed = int.tryParse(value.trim());
    // Champ vidé en cours de frappe : on attend la sortie du champ plutôt
    // que d'imposer une valeur pendant que l'utilisateur écrit.
    if (typed == null || typed < widget.minQuantity) return;
    if (typed != widget.quantity) widget.onChanged(typed);
  }

  @override
  Widget build(BuildContext context) {
    final canDecrease =
        widget.enabled && (_typedQuantity ?? widget.quantity) > widget.minQuantity;

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: AppTheme.border),
        borderRadius: BorderRadius.circular(AppTheme.radiusField),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          _StepperButton(
            icon: Icons.remove,
            tooltip: 'Diminuer la quantité',
            onPressed: canDecrease ? () => _step(-1) : null,
          ),
          SizedBox(
            width: 64,
            child: TextField(
              controller: _controller,
              focusNode: _focusNode,
              enabled: widget.enabled,
              keyboardType: TextInputType.number,
              textInputAction: TextInputAction.done,
              textAlign: TextAlign.center,
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
              style: AppTheme.amountStyle(fontSize: 18),
              decoration: const InputDecoration(
                isDense: true,
                filled: false,
                border: InputBorder.none,
                enabledBorder: InputBorder.none,
                focusedBorder: InputBorder.none,
                contentPadding: EdgeInsets.symmetric(vertical: 12),
                hintText: '0',
              ),
              onChanged: _onTextChanged,
              onSubmitted: (_) => _focusNode.unfocus(),
            ),
          ),
          _StepperButton(
            icon: Icons.add,
            tooltip: 'Augmenter la quantité',
            onPressed: widget.enabled ? () => _step(1) : null,
          ),
        ],
      ),
    );
  }
}

class _StepperButton extends StatelessWidget {
  const _StepperButton({
    required this.icon,
    required this.tooltip,
    required this.onPressed,
  });

  final IconData icon;
  final String tooltip;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: tooltip,
      child: InkWell(
        onTap: onPressed,
        borderRadius: BorderRadius.circular(AppTheme.radiusField),
        child: SizedBox(
          width: AppTheme.minTapTarget,
          height: AppTheme.minTapTarget,
          child: Icon(
            icon,
            size: 22,
            color: onPressed == null ? AppTheme.border : AppTheme.navy,
          ),
        ),
      ),
    );
  }
}
