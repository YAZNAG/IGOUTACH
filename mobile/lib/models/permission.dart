/// Une permission du catalogue (GET /permissions).
class PermissionItem {
  const PermissionItem({
    required this.id,
    required this.name,
    required this.displayName,
  });

  final int id;
  final String name;
  final String displayName;

  factory PermissionItem.fromJson(Map<String, dynamic> json) => PermissionItem(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        displayName: json['display_name'] as String? ?? '',
      );
}

/// Permissions regroupées par module (GET /permissions renvoie ce format).
class PermissionGroup {
  const PermissionGroup({required this.module, required this.permissions});

  final String module;
  final List<PermissionItem> permissions;

  factory PermissionGroup.fromJson(Map<String, dynamic> json) =>
      PermissionGroup(
        module: json['module'] as String? ?? 'divers',
        permissions: (json['permissions'] as List<dynamic>? ?? [])
            .map((e) => PermissionItem.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}

/// Permission effective d'un utilisateur, avec son origine
/// (`role:magasinier` ou `granted` pour une dérogation).
class EffectivePermission {
  const EffectivePermission({
    required this.name,
    required this.displayName,
    required this.module,
    required this.source,
  });

  final String name;
  final String displayName;
  final String module;
  final String source;

  bool get fromOverride => source == 'granted';

  String get sourceLabel =>
      source.startsWith('role:') ? 'Rôle ${source.substring(5)}' : 'Dérogation';

  factory EffectivePermission.fromJson(Map<String, dynamic> json) =>
      EffectivePermission(
        name: json['name'] as String? ?? '',
        displayName: json['display_name'] as String? ?? '',
        module: json['module'] as String? ?? 'divers',
        source: json['source'] as String? ?? '',
      );
}

/// Dérogation individuelle posée sur un utilisateur
/// (GET /users/{id}/permissions, bloc `overrides`).
class PermissionOverride {
  const PermissionOverride({
    required this.permissionId,
    required this.permission,
    required this.displayName,
    required this.module,
    required this.isGranted,
    this.reason,
    this.expiresAt,
    required this.expired,
  });

  final int permissionId;
  final String permission;
  final String displayName;
  final String module;

  /// `true` = permission accordée, `false` = permission retirée.
  final bool isGranted;
  final String? reason;
  final DateTime? expiresAt;
  final bool expired;

  factory PermissionOverride.fromJson(Map<String, dynamic> json) =>
      PermissionOverride(
        permissionId: (json['permission_id'] as num?)?.toInt() ?? 0,
        permission: json['permission'] as String? ?? '',
        displayName: json['display_name'] as String? ?? '',
        module: json['module'] as String? ?? 'divers',
        isGranted: json['is_granted'] == true,
        reason: json['reason'] as String?,
        expiresAt: DateTime.tryParse(json['expires_at'] as String? ?? ''),
        expired: json['expired'] == true,
      );
}

/// Réponse complète de GET /users/{id}/permissions.
class UserPermissions {
  const UserPermissions({required this.effective, required this.overrides});

  final List<EffectivePermission> effective;
  final List<PermissionOverride> overrides;

  factory UserPermissions.fromJson(Map<String, dynamic> json) =>
      UserPermissions(
        effective: (json['effective'] as List<dynamic>? ?? [])
            .map((e) => EffectivePermission.fromJson(e as Map<String, dynamic>))
            .toList(),
        overrides: (json['overrides'] as List<dynamic>? ?? [])
            .map((e) => PermissionOverride.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}
