/// Rôle attaché à un utilisateur (bloc `roles` d'AdminUserResource).
class UserRoleRef {
  const UserRoleRef({
    required this.id,
    required this.name,
    required this.displayName,
    required this.level,
  });

  final int id;
  final String name;
  final String displayName;
  final int level;

  factory UserRoleRef.fromJson(Map<String, dynamic> json) => UserRoleRef(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        displayName: json['display_name'] as String? ?? '',
        level: (json['level'] as num?)?.toInt() ?? 0,
      );
}

/// Utilisateur de l'administration (GET /users, AdminUserResource).
class AdminUser {
  const AdminUser({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.warehouseId,
    this.warehouseLabel,
    required this.isActive,
    this.lastLoginAt,
    required this.invited,
    required this.roles,
  });

  final int id;
  final String name;
  final String email;
  final String? phone;
  final int? warehouseId;

  /// Libellé du lieu (`code — nom`) quand la relation est chargée.
  final String? warehouseLabel;
  final bool isActive;
  final DateTime? lastLoginAt;

  /// `true` quand l'adresse e-mail n'a jamais été vérifiée (compte invité).
  final bool invited;
  final List<UserRoleRef> roles;

  List<int> get roleIds => roles.map((r) => r.id).toList();

  String get rolesLabel =>
      roles.isEmpty ? 'Aucun rôle' : roles.map((r) => r.displayName).join(', ');

  factory AdminUser.fromJson(Map<String, dynamic> json) {
    final warehouse = json['warehouse'] as Map<String, dynamic>?;
    final code = warehouse?['code'] as String? ?? '';
    final name = warehouse?['name'] as String? ?? '';

    return AdminUser(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      email: json['email'] as String? ?? '',
      phone: json['phone'] as String?,
      warehouseId: (json['warehouse_id'] as num?)?.toInt(),
      warehouseLabel: warehouse == null
          ? null
          : (code.isEmpty ? name : '$code — $name'),
      isActive: json['is_active'] != false,
      lastLoginAt: DateTime.tryParse(json['last_login_at'] as String? ?? ''),
      invited: json['invited'] == true,
      roles: (json['roles'] as List<dynamic>? ?? [])
          .map((e) => UserRoleRef.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}
