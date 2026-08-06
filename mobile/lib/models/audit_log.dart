/// Entrée du journal d'audit (GET /audit, AuditLogResource).
class AuditLog {
  const AuditLog({
    required this.id,
    required this.action,
    this.module,
    this.description,
    this.entityType,
    this.entityId,
    this.ipAddress,
    this.userName,
    this.userEmail,
    this.createdAt,
  });

  final int id;
  final String action;
  final String? module;
  final String? description;
  final String? entityType;
  final int? entityId;
  final String? ipAddress;
  final String? userName;
  final String? userEmail;
  final DateTime? createdAt;

  /// Nom court de l'entité concernée : `App\Models\User` → `User #12`.
  String? get entityLabel {
    final type = entityType;
    if (type == null || type.isEmpty) return null;
    final short = type.split('\\').last;
    return entityId == null ? short : '$short #$entityId';
  }

  factory AuditLog.fromJson(Map<String, dynamic> json) {
    final user = json['user'] as Map<String, dynamic>?;

    return AuditLog(
      id: json['id'] as int,
      action: json['action'] as String? ?? '',
      module: json['module'] as String?,
      description: json['description'] as String?,
      entityType: json['entity_type'] as String?,
      entityId: (json['entity_id'] as num?)?.toInt(),
      ipAddress: json['ip_address'] as String?,
      userName: user?['name'] as String?,
      userEmail: user?['email'] as String?,
      createdAt: DateTime.tryParse(json['created_at'] as String? ?? ''),
    );
  }
}

/// Valeurs distinctes disponibles pour les filtres (GET /audit/filters).
class AuditFilters {
  const AuditFilters({required this.actions, required this.modules});

  final List<String> actions;
  final List<String> modules;

  static const AuditFilters empty = AuditFilters(actions: [], modules: []);

  factory AuditFilters.fromJson(Map<String, dynamic> json) => AuditFilters(
        actions: (json['actions'] as List<dynamic>? ?? [])
            .map((e) => e.toString())
            .toList(),
        modules: (json['modules'] as List<dynamic>? ?? [])
            .map((e) => e.toString())
            .toList(),
      );
}
