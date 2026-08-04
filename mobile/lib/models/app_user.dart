/// Utilisateur authentifié, avec ses rôles et permissions effectives.
class AppUser {
  const AppUser({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.warehouseId,
    required this.roles,
    required this.permissions,
  });

  final int id;
  final String name;
  final String email;
  final String? phone;
  final int? warehouseId;
  final List<String> roles;
  final List<String> permissions;

  factory AppUser.fromJson(Map<String, dynamic> json) => AppUser(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        email: json['email'] as String? ?? '',
        phone: json['phone'] as String?,
        warehouseId: json['warehouse_id'] as int?,
        roles: (json['roles'] as List<dynamic>? ?? [])
            .map((e) => e.toString())
            .toList(),
        permissions: (json['permissions'] as List<dynamic>? ?? [])
            .map((e) => e.toString())
            .toList(),
      );
}
