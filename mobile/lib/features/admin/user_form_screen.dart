import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/admin_user.dart';
import '../../models/role.dart';
import '../../models/warehouse.dart';

/// Création (POST /users) et modification (PUT /users/{id}) d'un utilisateur.
///
/// Nuance serveur : à la création, `StoreUserRequest` accepte
/// `name, email, password, phone, warehouse_id, role_ids, is_active`.
/// À la modification, `UpdateUserRequest` n'accepte que
/// `name, email, phone, warehouse_id` — les rôles passent par
/// PUT /users/{id}/roles et l'activation par PATCH /users/{id}/toggle.
class UserFormScreen extends StatefulWidget {
  const UserFormScreen({super.key, this.user});

  /// `null` = création.
  final AdminUser? user;

  bool get isEdit => user != null;

  @override
  State<UserFormScreen> createState() => _UserFormScreenState();
}

class _UserFormScreenState extends State<UserFormScreen> {
  final _api = ApiClient.instance;
  final _formKey = GlobalKey<FormState>();

  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _phone = TextEditingController();

  List<Warehouse> _warehouses = [];
  List<Role> _roles = [];
  final Set<int> _selectedRoleIds = {};
  int? _warehouseId;
  bool _isActive = true;
  bool _obscurePassword = true;

  bool _loadingRefs = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final user = widget.user;
    if (user != null) {
      _name.text = user.name;
      _email.text = user.email;
      _phone.text = user.phone ?? '';
      _warehouseId = user.warehouseId;
      _isActive = user.isActive;
      _selectedRoleIds.addAll(user.roleIds);
    }
    _loadReferences();
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _password.dispose();
    _phone.dispose();
    super.dispose();
  }

  /// Lieux et rôles : chargements indépendants, échec silencieux (403).
  Future<void> _loadReferences() async {
    final warehouses = await _fetchWarehouses();
    final roles = await _fetchRoles();
    if (!mounted) return;
    setState(() {
      _warehouses = warehouses;
      _roles = roles;
      _loadingRefs = false;
    });
  }

  Future<List<Warehouse>> _fetchWarehouses() async {
    try {
      final res = await _api.dio.get<Map<String, dynamic>>('/warehouses');
      final data = res.data!['data'] as List<dynamic>? ?? [];
      return data
          .map((e) => Warehouse.fromJson(e as Map<String, dynamic>))
          .where((w) => w.isActive)
          .toList();
    } catch (_) {
      // Permission `warehouse.view` absente : le champ est masqué.
      return const [];
    }
  }

  Future<List<Role>> _fetchRoles() async {
    try {
      final res = await _api.dio.get<Map<String, dynamic>>('/roles');
      final data = res.data!['data'] as List<dynamic>? ?? [];
      return data.map((e) => Role.fromJson(e as Map<String, dynamic>)).toList();
    } catch (_) {
      // Permission `role.view` absente : les rôles seront attribués plus tard.
      return const [];
    }
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() {
      _saving = true;
      _error = null;
    });

    final phone = _phone.text.trim();

    try {
      if (widget.isEdit) {
        await _api.dio.put<Map<String, dynamic>>(
          '/users/${widget.user!.id}',
          data: {
            'name': _name.text.trim(),
            'email': _email.text.trim(),
            'phone': phone.isEmpty ? null : phone,
            'warehouse_id': _warehouseId,
          },
        );
      } else {
        await _api.dio.post<Map<String, dynamic>>(
          '/users',
          data: {
            'name': _name.text.trim(),
            'email': _email.text.trim(),
            'password': _password.text,
            'phone': phone.isEmpty ? null : phone,
            'warehouse_id': _warehouseId,
            'role_ids': _selectedRoleIds.toList(),
            'is_active': _isActive,
          },
        );
      }
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
    final auth = context.watch<AuthProvider>();
    final permission = widget.isEdit ? 'user.update' : 'user.create';
    if (!auth.can(permission)) return const NotAllowedView();

    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.isEdit ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur',
        ),
      ),
      body: _loadingRefs
          ? const LoadingView()
          : Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
                children: [
                  TextFormField(
                    controller: _name,
                    textCapitalization: TextCapitalization.words,
                    decoration: const InputDecoration(
                      labelText: 'Nom complet *',
                      prefixIcon: Icon(Icons.person_outline),
                    ),
                    validator: (value) => (value ?? '').trim().isEmpty
                        ? 'Le nom est obligatoire.'
                        : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _email,
                    keyboardType: TextInputType.emailAddress,
                    decoration: const InputDecoration(
                      labelText: 'E-mail *',
                      prefixIcon: Icon(Icons.mail_outline),
                    ),
                    validator: (value) {
                      final email = (value ?? '').trim();
                      if (email.isEmpty) {
                        return 'L\'e-mail est obligatoire.';
                      }
                      return email.contains('@') && email.contains('.')
                          ? null
                          : 'Adresse e-mail invalide.';
                    },
                  ),
                  if (!widget.isEdit) ...[
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _password,
                      obscureText: _obscurePassword,
                      decoration: InputDecoration(
                        labelText: 'Mot de passe *',
                        prefixIcon: const Icon(Icons.lock_outline),
                        helperText: '8 caractères minimum.',
                        suffixIcon: IconButton(
                          icon: Icon(
                            _obscurePassword
                                ? Icons.visibility_outlined
                                : Icons.visibility_off_outlined,
                          ),
                          onPressed: () => setState(
                            () => _obscurePassword = !_obscurePassword,
                          ),
                        ),
                      ),
                      validator: (value) => (value ?? '').length < 8
                          ? 'Le mot de passe doit faire au moins 8 caractères.'
                          : null,
                    ),
                  ],
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _phone,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(
                      labelText: 'Téléphone',
                      prefixIcon: Icon(Icons.phone_outlined),
                    ),
                  ),
                  if (_warehouses.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int?>(
                      initialValue: _warehouses.any((w) => w.id == _warehouseId)
                          ? _warehouseId
                          : null,
                      isExpanded: true,
                      decoration: const InputDecoration(
                        labelText: 'Lieu de rattachement',
                        prefixIcon: Icon(Icons.warehouse_outlined),
                      ),
                      items: [
                        const DropdownMenuItem<int?>(
                          value: null,
                          child: Text('Aucun (accès global)'),
                        ),
                        ..._warehouses.map(
                          (w) => DropdownMenuItem<int?>(
                            value: w.id,
                            child: Text(
                              w.label,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ),
                      ],
                      onChanged: (value) =>
                          setState(() => _warehouseId = value),
                    ),
                  ],
                  if (!widget.isEdit) ...[
                    const SectionTitle('Rôles'),
                    if (_roles.isEmpty)
                      Text(
                        'Liste des rôles indisponible (permission role.view). '
                        'Les rôles pourront être attribués ensuite.',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey.shade600,
                        ),
                      )
                    else
                      ..._roles.map(
                        (role) => CheckboxListTile(
                          value: _selectedRoleIds.contains(role.id),
                          dense: true,
                          contentPadding: EdgeInsets.zero,
                          controlAffinity: ListTileControlAffinity.leading,
                          title: Text(role.displayName),
                          subtitle: Text(
                            'Niveau ${role.level}',
                            style: const TextStyle(fontSize: 12),
                          ),
                          onChanged: (checked) => setState(() {
                            if (checked == true) {
                              _selectedRoleIds.add(role.id);
                            } else {
                              _selectedRoleIds.remove(role.id);
                            }
                          }),
                        ),
                      ),
                    const SizedBox(height: 8),
                    SwitchListTile(
                      value: _isActive,
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Compte actif'),
                      subtitle: const Text(
                        'Un compte inactif ne peut pas se connecter.',
                        style: TextStyle(fontSize: 12),
                      ),
                      onChanged: (value) => setState(() => _isActive = value),
                    ),
                  ] else
                    Padding(
                      padding: const EdgeInsets.only(top: 16),
                      child: Text(
                        'Les rôles et l\'activation se modifient depuis la '
                        'fiche de l\'utilisateur.',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey.shade600,
                        ),
                      ),
                    ),
                  if (_error != null) ...[
                    const SizedBox(height: 16),
                    Text(
                      _error!,
                      style: const TextStyle(
                        color: AppTheme.danger,
                        fontSize: 13,
                      ),
                    ),
                  ],
                  const SizedBox(height: 24),
                  FilledButton.icon(
                    onPressed: _saving ? null : _submit,
                    icon: _saving
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Icon(Icons.check),
                    label: Text(
                      widget.isEdit
                          ? 'Enregistrer les modifications'
                          : 'Créer l\'utilisateur',
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}
