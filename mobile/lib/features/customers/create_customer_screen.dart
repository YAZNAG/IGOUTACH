import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../shared/warehouse_scope.dart';

/// Création d'un client (POST /customers).
///
/// Le code client (CL-0001) est généré par le serveur : il n'est pas envoyé.
///
/// Le fichier client est séparé par lieu : la fiche va dans le lieu de
/// l'utilisateur, détecté depuis son profil, sans rien lui demander. Seul un
/// administrateur sans lieu choisit celui de la fiche.
class CreateCustomerScreen extends StatefulWidget {
  const CreateCustomerScreen({super.key});

  @override
  State<CreateCustomerScreen> createState() => _CreateCustomerScreenState();
}

class _CreateCustomerScreenState extends State<CreateCustomerScreen> {
  final _api = ApiClient.instance;
  final _formKey = GlobalKey<FormState>();

  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _city = TextEditingController();
  final _address = TextEditingController();
  final _ice = TextEditingController();
  final _creditLimit = TextEditingController();

  bool _saving = false;
  String? _error;

  /// Lieu de la fiche : imposé par le profil, ou choisi par un admin global.
  WarehouseScope? _scope;

  /// La validation ne se déclenche à la volée qu'après une première
  /// tentative : on ne souligne pas en rouge un champ jamais touché.
  bool _submitted = false;

  /// Un champ au moins a été rempli : on confirme avant d'abandonner.
  bool get _isDirty => [
        _name,
        _phone,
        _email,
        _city,
        _address,
        _ice,
        _creditLimit,
      ].any((c) => c.text.trim().isNotEmpty);

  @override
  void initState() {
    super.initState();
    _chargerLieu();
  }

  Future<void> _chargerLieu() async {
    final lieuUtilisateur = context.read<AuthProvider>().user?.warehouseId;
    final scope = await WarehouseScope.load(lieuUtilisateur);
    if (!mounted) return;
    setState(() => _scope = scope);
  }

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _email.dispose();
    _city.dispose();
    _address.dispose();
    _ice.dispose();
    _creditLimit.dispose();
    super.dispose();
  }

  String? _trimmedOrNull(TextEditingController controller) {
    final value = controller.text.trim();
    return value.isEmpty ? null : value;
  }

  /// Interception du retour arrière : confirmation si la fiche est entamée.
  Future<void> _handlePop(bool didPop) async {
    if (didPop) return;
    final navigator = Navigator.of(context);
    if (!_isDirty || await confirmDiscard(context)) {
      navigator.pop();
    }
  }

  Future<void> _submit() async {
    setState(() => _submitted = true);
    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() {
      _saving = true;
      _error = null;
    });

    final rawLimit = _creditLimit.text.trim().replaceAll(',', '.');

    try {
      await _api.dio.post<Map<String, dynamic>>(
        '/customers',
        data: {
          'name': _name.text.trim(),
          'warehouse_id': ?_scope?.selectedId,
          'phone': ?_trimmedOrNull(_phone),
          'email': ?_trimmedOrNull(_email),
          'city': ?_trimmedOrNull(_city),
          'address': ?_trimmedOrNull(_address),
          'ice': ?_trimmedOrNull(_ice),
          if (rawLimit.isNotEmpty) 'credit_limit': double.tryParse(rawLimit),
        },
      );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _saving = false;
        _error = friendlyError(e);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('customer.create')) {
      return const NotAllowedView();
    }
    final scope = _scope;

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) => _handlePop(didPop),
      child: Scaffold(
        appBar: AppBar(title: const Text('Nouveau client')),
        body: Form(
          key: _formKey,
          autovalidateMode: _submitted
              ? AutovalidateMode.onUserInteraction
              : AutovalidateMode.disabled,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 24),
            children: [
              // Le lieu d'abord : c'est lui qui dit où la fiche vivra.
              if (scope != null &&
                  scope.canChoose &&
                  scope.warehouses.isNotEmpty) ...[
                DropdownButtonFormField<int>(
                  initialValue: scope.selectedId,
                  isExpanded: true,
                  decoration: const InputDecoration(
                    labelText: 'Lieu de la fiche *',
                    prefixIcon: Icon(Icons.warehouse_outlined),
                  ),
                  items: scope.warehouses
                      .map(
                        (w) => DropdownMenuItem(
                          value: w.id,
                          child: Text(w.label, overflow: TextOverflow.ellipsis),
                        ),
                      )
                      .toList(),
                  onChanged: (value) => setState(
                    () => _scope = scope.copyWith(selectedId: value),
                  ),
                ),
                const SizedBox(height: 18),
              ] else if (scope?.selectedLabel != null) ...[
                InfoBanner(
                  icon: Icons.warehouse_outlined,
                  message: 'Lieu de la fiche : ${scope!.selectedLabel}',
                ),
                const SizedBox(height: 18),
              ],
              TextFormField(
                controller: _name,
                textCapitalization: TextCapitalization.words,
                textInputAction: TextInputAction.next,
                decoration: const InputDecoration(
                  labelText: 'Nom *',
                  hintText: 'Nom du client ou de la société',
                  prefixIcon: Icon(Icons.person_outline),
                ),
                validator: (value) => (value ?? '').trim().isEmpty
                    ? 'Le nom est obligatoire.'
                    : null,
              ),
              const SizedBox(height: 18),
              TextFormField(
                controller: _phone,
                keyboardType: TextInputType.phone,
                textInputAction: TextInputAction.next,
                decoration: const InputDecoration(
                  labelText: 'Téléphone',
                  hintText: '06 12 34 56 78',
                  prefixIcon: Icon(Icons.phone_outlined),
                ),
              ),
              const SizedBox(height: 18),
              TextFormField(
                controller: _email,
                keyboardType: TextInputType.emailAddress,
                autocorrect: false,
                textInputAction: TextInputAction.next,
                decoration: const InputDecoration(
                  labelText: 'E-mail',
                  prefixIcon: Icon(Icons.mail_outline),
                ),
                validator: (value) {
                  final email = (value ?? '').trim();
                  if (email.isEmpty) return null;
                  return email.contains('@') && email.contains('.')
                      ? null
                      : 'Adresse e-mail invalide.';
                },
              ),
              const SizedBox(height: 18),
              TextFormField(
                controller: _city,
                textCapitalization: TextCapitalization.words,
                textInputAction: TextInputAction.next,
                decoration: const InputDecoration(
                  labelText: 'Ville',
                  prefixIcon: Icon(Icons.location_city_outlined),
                ),
              ),
              const SizedBox(height: 18),
              TextFormField(
                controller: _address,
                maxLines: 2,
                textCapitalization: TextCapitalization.sentences,
                decoration: const InputDecoration(
                  labelText: 'Adresse',
                  prefixIcon: Icon(Icons.home_outlined),
                ),
              ),
              const SizedBox(height: 18),
              TextFormField(
                controller: _ice,
                keyboardType: TextInputType.number,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                decoration: const InputDecoration(
                  labelText: 'ICE',
                  helperText: 'Identifiant commun de l\'entreprise.',
                  prefixIcon: Icon(Icons.badge_outlined),
                ),
              ),
              const SizedBox(height: 18),
              TextFormField(
                controller: _creditLimit,
                keyboardType:
                    const TextInputType.numberWithOptions(decimal: true),
                textInputAction: TextInputAction.done,
                style: TextStyle(
                  fontSize: 16,
                  fontFeatures: AppTheme.tabularFigures,
                ),
                inputFormatters: [
                  FilteringTextInputFormatter.allow(RegExp(r'[0-9.,]')),
                ],
                decoration: const InputDecoration(
                  labelText: 'Plafond de crédit (DH)',
                  prefixIcon: Icon(Icons.credit_card_outlined),
                  helperText: 'Laisser vide pour aucun crédit autorisé.',
                ),
                onFieldSubmitted: (_) => _saving ? null : _submit(),
                validator: (value) {
                  final raw = (value ?? '').trim().replaceAll(',', '.');
                  if (raw.isEmpty) return null;
                  final parsed = double.tryParse(raw);
                  if (parsed == null || parsed < 0) {
                    return 'Montant invalide.';
                  }
                  return null;
                },
              ),
              if (_error != null) ...[
                const SizedBox(height: 20),
                ErrorBox(message: _error!),
              ],
            ],
          ),
        ),
        bottomNavigationBar: BottomActionBar(
          label: 'Créer le client',
          loading: _saving,
          onPressed: _submit,
        ),
      ),
    );
  }
}
