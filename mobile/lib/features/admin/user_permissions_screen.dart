import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/admin_user.dart';
import '../../models/permission.dart';
import '../shared/warehouse_scope.dart' show InfoBanner;
import 'admin_common.dart';

/// Permissions individuelles d'un utilisateur.
///
/// GET  /users/{id}/permissions               (permission `user.view`)
/// POST /users/{id}/permissions               (permission `user.manage_permissions`)
/// DELETE /users/{id}/permissions/{permission} (idem)
class UserPermissionsScreen extends StatefulWidget {
  const UserPermissionsScreen({super.key, required this.user});

  final AdminUser user;

  @override
  State<UserPermissionsScreen> createState() => _UserPermissionsScreenState();
}

class _UserPermissionsScreenState extends State<UserPermissionsScreen> {
  final _api = ApiClient.instance;

  UserPermissions? _data;
  List<PermissionGroup> _catalogue = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/users/${widget.user.id}/permissions',
      );
      final catalogue = await _fetchCatalogue();
      if (!mounted) return;
      setState(() {
        _data = UserPermissions.fromJson(
          res.data!['data'] as Map<String, dynamic>,
        );
        _catalogue = catalogue;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = friendlyError(e);
        _loading = false;
      });
    }
  }

  /// Catalogue complet des permissions (échec silencieux si `permission.view`
  /// n'est pas accordée : on ne pourra alors pas ajouter de dérogation).
  Future<List<PermissionGroup>> _fetchCatalogue() async {
    try {
      final res = await _api.dio.get<Map<String, dynamic>>('/permissions');
      final data = res.data!['data'] as List<dynamic>? ?? [];
      return data
          .map((e) => PermissionGroup.fromJson(e as Map<String, dynamic>))
          .toList();
    } catch (_) {
      return const [];
    }
  }

  Future<void> _addOverride() async {
    final result = await showModalBottomSheet<_OverrideDraft>(
      context: context,
      isScrollControlled: true,
      builder: (_) => _AddOverrideSheet(groups: _catalogue),
    );
    if (result == null) return;

    try {
      final res = await _api.dio.post<Map<String, dynamic>>(
        '/users/${widget.user.id}/permissions',
        data: {
          'permission': result.permission,
          'is_granted': result.isGranted,
          'reason': result.reason,
          'expires_at': result.expiresAt?.toUtc().toIso8601String(),
        },
      );
      if (!mounted) return;
      setState(() {
        _data = UserPermissions.fromJson(
          res.data!['data'] as Map<String, dynamic>,
        );
      });
      showSuccessMessage(context, 'Dérogation enregistrée.');
    } catch (e) {
      if (!mounted) return;
      showFailureMessage(context, friendlyError(e));
    }
  }

  Future<void> _removeOverride(PermissionOverride override) async {
    final confirmed = await confirmAction(
      context,
      title: 'Retirer la dérogation',
      message:
          'Supprimer la dérogation « ${override.displayName} » ? '
          'L\'utilisateur reviendra aux permissions de ses rôles.',
      confirmLabel: 'Retirer',
      confirmColor: AppTheme.danger,
    );
    if (!confirmed) return;

    try {
      final res = await _api.dio.delete<Map<String, dynamic>>(
        '/users/${widget.user.id}/permissions/${override.permissionId}',
      );
      if (!mounted) return;
      setState(() {
        _data = UserPermissions.fromJson(
          res.data!['data'] as Map<String, dynamic>,
        );
      });
      showSuccessMessage(context, 'Dérogation retirée.');
    } catch (e) {
      if (!mounted) return;
      showFailureMessage(context, friendlyError(e));
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.can('user.view')) return const NotAllowedView();
    final canManage = auth.can('user.manage_permissions');

    return Scaffold(
      appBar: AppBar(
        title: const Text('Permissions individuelles'),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(28),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
            child: Align(
              alignment: Alignment.centerLeft,
              child: Text(
                widget.user.name,
                style: const TextStyle(color: Colors.white70, fontSize: 13),
              ),
            ),
          ),
        ),
      ),
      floatingActionButton: canManage && _data != null && _catalogue.isNotEmpty
          ? FloatingActionButton.extended(
              onPressed: _addOverride,
              icon: const Icon(Icons.add),
              label: const Text('Dérogation'),
            )
          : null,
      body: _buildBody(canManage),
    );
  }

  Widget _buildBody(bool canManage) {
    if (_loading) return const LoadingView();
    final data = _data;
    if (_error != null || data == null) {
      return ErrorView(
        message: _error ?? 'Chargement impossible.',
        onRetry: _load,
      );
    }

    final grouped = <String, List<EffectivePermission>>{};
    for (final permission in data.effective) {
      grouped.putIfAbsent(permission.module, () => []).add(permission);
    }
    final modules = grouped.keys.toList()..sort();

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(bottom: 96),
        children: [
          if (canManage && _catalogue.isEmpty)
            const InfoBanner(
              message: 'Le catalogue des permissions est inaccessible '
                  '(permission permission.view) : aucune dérogation ne peut '
                  'être ajoutée depuis le mobile.',
            ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
            child: Text(
              'DÉROGATIONS (${data.overrides.length})',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                letterSpacing: 0.6,
                color: Colors.grey.shade600,
              ),
            ),
          ),
          if (data.overrides.isEmpty)
            const Padding(
              padding: EdgeInsets.fromLTRB(16, 12, 16, 4),
              child: Text(
                'Aucune dérogation : l\'utilisateur suit strictement ses rôles.',
                style: TextStyle(fontSize: 13),
              ),
            )
          else
            ...data.overrides.map(
              (override) => Card(
                child: ListTile(
                  leading: Icon(
                    override.isGranted
                        ? Icons.add_moderator_outlined
                        : Icons.remove_moderator_outlined,
                    color: override.isGranted
                        ? AppTheme.success
                        : AppTheme.danger,
                  ),
                  title: Text(
                    override.displayName,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                  subtitle: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        override.permission,
                        style: const TextStyle(fontSize: 11),
                      ),
                      if ((override.reason ?? '').isNotEmpty)
                        Text(
                          'Motif : ${override.reason}',
                          style: const TextStyle(fontSize: 11),
                        ),
                      if (override.expiresAt != null)
                        Text(
                          override.expired
                              ? 'Expirée le ${formatDate(override.expiresAt)}'
                              : 'Expire le ${formatDate(override.expiresAt)}',
                          style: TextStyle(
                            fontSize: 11,
                            color: override.expired
                                ? AppTheme.danger
                                : Colors.grey.shade700,
                          ),
                        ),
                    ],
                  ),
                  trailing: canManage
                      ? IconButton(
                          icon: const Icon(
                            Icons.delete_outline,
                            color: AppTheme.danger,
                          ),
                          tooltip: 'Retirer',
                          onPressed: () => _removeOverride(override),
                        )
                      : null,
                ),
              ),
            ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 24, 16, 0),
            child: Text(
              'PERMISSIONS EFFECTIVES (${data.effective.length})',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                letterSpacing: 0.6,
                color: Colors.grey.shade600,
              ),
            ),
          ),
          if (data.effective.isEmpty)
            const Padding(
              padding: EdgeInsets.all(16),
              child: Text(
                'Aucune permission effective : cet utilisateur ne peut rien '
                'faire dans l\'application.',
                style: TextStyle(fontSize: 13),
              ),
            )
          else
            ...modules.map(
              (module) => Card(
                child: ExpansionTile(
                  title: Text(
                    moduleLabel(module),
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                  subtitle: Text(
                    '${grouped[module]!.length} permission(s)',
                    style: const TextStyle(fontSize: 12),
                  ),
                  children: grouped[module]!
                      .map(
                        (permission) => ListTile(
                          dense: true,
                          title: Text(
                            permission.displayName,
                            style: const TextStyle(fontSize: 13),
                          ),
                          subtitle: Text(
                            permission.name,
                            style: const TextStyle(fontSize: 11),
                          ),
                          trailing: StatusBadge(
                            label: permission.sourceLabel,
                            color: permission.fromOverride
                                ? AppTheme.warning
                                : AppTheme.navy,
                          ),
                        ),
                      )
                      .toList(),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

/// Saisie d'une dérogation avant envoi.
class _OverrideDraft {
  const _OverrideDraft({
    required this.permission,
    required this.isGranted,
    this.reason,
    this.expiresAt,
  });

  final String permission;
  final bool isGranted;
  final String? reason;
  final DateTime? expiresAt;
}

/// Feuille de saisie d'une dérogation (permission + sens + motif + expiration).
class _AddOverrideSheet extends StatefulWidget {
  const _AddOverrideSheet({required this.groups});

  final List<PermissionGroup> groups;

  @override
  State<_AddOverrideSheet> createState() => _AddOverrideSheetState();
}

class _AddOverrideSheetState extends State<_AddOverrideSheet> {
  final _reason = TextEditingController();
  final _search = TextEditingController();

  String? _permission;
  bool _isGranted = true;
  DateTime? _expiresAt;

  @override
  void dispose() {
    _reason.dispose();
    _search.dispose();
    super.dispose();
  }

  List<PermissionItem> get _filtered {
    final query = _search.text.trim().toLowerCase();
    final all = widget.groups.expand((g) => g.permissions).toList();
    if (query.isEmpty) return all;
    return all
        .where(
          (p) =>
              p.name.toLowerCase().contains(query) ||
              p.displayName.toLowerCase().contains(query),
        )
        .toList();
  }

  Future<void> _pickExpiry() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _expiresAt ?? now.add(const Duration(days: 7)),
      firstDate: now.add(const Duration(days: 1)),
      lastDate: now.add(const Duration(days: 365 * 3)),
      helpText: 'Date de fin de la dérogation',
    );
    if (picked == null) return;
    // Fin de journée : la validation serveur exige une date future.
    setState(
      () => _expiresAt = DateTime(picked.year, picked.month, picked.day, 23, 59),
    );
  }

  @override
  Widget build(BuildContext context) {
    final items = _filtered;

    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: DraggableScrollableSheet(
        expand: false,
        initialChildSize: 0.85,
        builder: (context, scrollController) => Column(
          children: [
            const Padding(
              padding: EdgeInsets.fromLTRB(16, 16, 16, 8),
              child: Text(
                'Nouvelle dérogation',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: SegmentedButton<bool>(
                segments: const [
                  ButtonSegment(
                    value: true,
                    icon: Icon(Icons.add),
                    label: Text('Accorder'),
                  ),
                  ButtonSegment(
                    value: false,
                    icon: Icon(Icons.remove),
                    label: Text('Retirer'),
                  ),
                ],
                selected: {_isGranted},
                onSelectionChanged: (values) =>
                    setState(() => _isGranted = values.first),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
              child: TextField(
                controller: _search,
                onChanged: (_) => setState(() {}),
                decoration: const InputDecoration(
                  hintText: 'Rechercher une permission…',
                  prefixIcon: Icon(Icons.search),
                ),
              ),
            ),
            Expanded(
              child: items.isEmpty
                  ? const EmptyView(message: 'Aucune permission trouvée.')
                  : ListView.builder(
                      controller: scrollController,
                      itemCount: items.length,
                      itemBuilder: (context, index) {
                        final item = items[index];
                        final selected = _permission == item.name;
                        return ListTile(
                          dense: true,
                          selected: selected,
                          leading: Icon(
                            selected
                                ? Icons.radio_button_checked
                                : Icons.radio_button_unchecked,
                            color: selected ? AppTheme.navy : Colors.grey,
                          ),
                          title: Text(
                            item.displayName,
                            style: const TextStyle(fontSize: 13),
                          ),
                          subtitle: Text(
                            item.name,
                            style: const TextStyle(fontSize: 11),
                          ),
                          onTap: () =>
                              setState(() => _permission = item.name),
                        );
                      },
                    ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
              child: TextField(
                controller: _reason,
                decoration: const InputDecoration(
                  labelText: 'Motif (facultatif)',
                  prefixIcon: Icon(Icons.notes_outlined),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      _expiresAt == null
                          ? 'Sans expiration'
                          : 'Expire le ${formatDate(_expiresAt)}',
                      style: const TextStyle(fontSize: 13),
                    ),
                  ),
                  if (_expiresAt != null)
                    TextButton(
                      onPressed: () => setState(() => _expiresAt = null),
                      child: const Text('Effacer'),
                    ),
                  TextButton.icon(
                    onPressed: _pickExpiry,
                    icon: const Icon(Icons.event_outlined),
                    label: const Text('Expiration'),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
              child: FilledButton.icon(
                onPressed: _permission == null
                    ? null
                    : () => Navigator.of(context).pop(
                        _OverrideDraft(
                          permission: _permission!,
                          isGranted: _isGranted,
                          reason: _reason.text.trim().isEmpty
                              ? null
                              : _reason.text.trim(),
                          expiresAt: _expiresAt,
                        ),
                      ),
                icon: const Icon(Icons.check),
                label: const Text('Enregistrer'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
