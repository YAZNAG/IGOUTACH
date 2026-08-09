import 'package:flutter/material.dart';

import '../../core/theme.dart';
import '../customers/customers_screen.dart';
import 'accueil_responsable_screen.dart';
import '../sales/create_sale_screen.dart';
import '../stock/stock_screen.dart';
import 'plus_screen.dart';

/// Coquille de navigation du responsable de lieu.
///
/// Cinq onglets, dont un bouton central pour l'action la plus fréquente :
/// vendre. Le reste — inventaire, mouvements, charges, retours, catalogue —
/// vit dans « Plus » plutôt que d'encombrer la barre : cinq destinations est
/// la limite au-delà de laquelle les cibles deviennent trop étroites au pouce.
class ResponsableShell extends StatefulWidget {
  const ResponsableShell({super.key});

  @override
  State<ResponsableShell> createState() => _ResponsableShellState();
}

class _ResponsableShellState extends State<ResponsableShell> {
  int _index = 0;

  /// L'index 2 est le bouton central : il ouvre une page en plein écran au
  /// lieu de changer d'onglet, d'où l'absence de vue correspondante.
  static const int _indexVente = 2;

  final List<Widget> _vues = const [
    AccueilResponsableScreen(),
    StockScreen(),
    SizedBox.shrink(),
    CustomersScreen(),
    PlusScreen(),
  ];

  void _ouvrirVente() {
    Navigator.of(context).push(
      MaterialPageRoute<void>(builder: (_) => const CreateSaleScreen()),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.background,
      body: IndexedStack(index: _index, children: _vues),
      bottomNavigationBar: _BarreOnglets(
        index: _index,
        onChanged: (i) {
          if (i == _indexVente) {
            _ouvrirVente();
            return;
          }
          setState(() => _index = i);
        },
      ),
    );
  }
}

class _BarreOnglets extends StatelessWidget {
  const _BarreOnglets({required this.index, required this.onChanged});

  final int index;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: AppTheme.border)),
      ),
      child: SafeArea(
        top: false,
        child: SizedBox(
          height: 64,
          child: Row(
            children: [
              _Onglet(
                icone: Icons.home_outlined,
                iconeActive: Icons.home_rounded,
                libelle: 'Accueil',
                actif: index == 0,
                onTap: () => onChanged(0),
              ),
              _Onglet(
                icone: Icons.inventory_2_outlined,
                iconeActive: Icons.inventory_2_rounded,
                libelle: 'Stock',
                actif: index == 1,
                onTap: () => onChanged(1),
              ),
              _BoutonVendre(onTap: () => onChanged(2)),
              _Onglet(
                icone: Icons.people_outline,
                iconeActive: Icons.people_rounded,
                libelle: 'Clients',
                actif: index == 3,
                onTap: () => onChanged(3),
              ),
              _Onglet(
                icone: Icons.more_horiz,
                iconeActive: Icons.more_horiz,
                libelle: 'Plus',
                actif: index == 4,
                onTap: () => onChanged(4),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Onglet extends StatelessWidget {
  const _Onglet({
    required this.icone,
    required this.iconeActive,
    required this.libelle,
    required this.actif,
    required this.onTap,
  });

  final IconData icone;
  final IconData iconeActive;
  final String libelle;
  final bool actif;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final couleur = actif ? AppTheme.brand : AppTheme.textFaint;

    return Expanded(
      child: InkWell(
        onTap: onTap,
        child: Semantics(
          selected: actif,
          button: true,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(actif ? iconeActive : icone, size: 24, color: couleur),
              const SizedBox(height: 2),
              Text(
                libelle,
                style: TextStyle(
                  fontSize: 11,
                  color: couleur,
                  fontWeight: actif ? FontWeight.w600 : FontWeight.w400,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Bouton central : la vente est l'action la plus répétée de la journée,
/// elle mérite la cible la plus large et la plus atteignable au pouce.
class _BoutonVendre extends StatelessWidget {
  const _BoutonVendre({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Center(
        child: Transform.translate(
          offset: const Offset(0, -10),
          child: Material(
            color: AppTheme.brand,
            shape: const CircleBorder(),
            elevation: 4,
            shadowColor: AppTheme.brand.withValues(alpha: 0.4),
            child: InkWell(
              customBorder: const CircleBorder(),
              onTap: onTap,
              child: const SizedBox(
                width: 52,
                height: 52,
                child: Icon(Icons.add, color: Colors.white, size: 26),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
