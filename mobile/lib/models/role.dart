/// Rôle (GET /roles, RoleResource).
class Role {
  const Role({
    required this.id,
    required this.name,
    required this.displayName,
    this.description,
    required this.isSystem,
    required this.level,
    this.usersCount,
    this.permissionsCount,
    required this.permissionIds,
  });

  final int id;

  /// Identifiant technique (`admin`, `magasinier`…).
  final String name;
  final String displayName;
  final String? description;

  /// Rôle système : non supprimable côté serveur.
  final bool isSystem;
  final int level;
  final int? usersCount;
  final int? permissionsCount;
  final List<int> permissionIds;

  factory Role.fromJson(Map<String, dynamic> json) => Role(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        displayName: json['display_name'] as String? ?? '',
        description: json['description'] as String?,
        isSystem: json['is_system'] == true,
        level: (json['level'] as num?)?.toInt() ?? 0,
        usersCount: (json['users_count'] as num?)?.toInt(),
        permissionsCount: (json['permissions_count'] as num?)?.toInt(),
        permissionIds: (json['permission_ids'] as List<dynamic>? ?? [])
            .map((e) => (e as num).toInt())
            .toList(),
      );
}
