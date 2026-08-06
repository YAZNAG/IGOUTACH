/// Session active (GET /sessions — table `sessions` du store de session).
class UserSession {
  const UserSession({
    required this.id,
    this.userId,
    this.userName,
    this.userEmail,
    this.ipAddress,
    this.userAgent,
    this.lastActivity,
    required this.isCurrent,
  });

  final String id;
  final int? userId;
  final String? userName;
  final String? userEmail;
  final String? ipAddress;
  final String? userAgent;
  final DateTime? lastActivity;
  final bool isCurrent;

  /// Nom lisible de l'appareil déduit de l'agent utilisateur.
  String get deviceLabel {
    final agent = userAgent ?? '';
    if (agent.isEmpty) return 'Appareil inconnu';

    final system = agent.contains('Android')
        ? 'Android'
        : agent.contains('iPhone') || agent.contains('iPad')
            ? 'iOS'
            : agent.contains('Windows')
                ? 'Windows'
                : agent.contains('Mac OS')
                    ? 'macOS'
                    : agent.contains('Linux')
                        ? 'Linux'
                        : null;

    final browser = agent.contains('Edg/')
        ? 'Edge'
        : agent.contains('Chrome')
            ? 'Chrome'
            : agent.contains('Firefox')
                ? 'Firefox'
                : agent.contains('Safari')
                    ? 'Safari'
                    : null;

    final parts = [?browser, ?system];
    return parts.isEmpty ? agent : parts.join(' · ');
  }

  factory UserSession.fromJson(Map<String, dynamic> json) => UserSession(
        id: json['id'] as String? ?? '',
        userId: (json['user_id'] as num?)?.toInt(),
        userName: json['user_name'] as String?,
        userEmail: json['user_email'] as String?,
        ipAddress: json['ip_address'] as String?,
        userAgent: json['user_agent'] as String?,
        lastActivity: DateTime.tryParse(json['last_activity'] as String? ?? ''),
        isCurrent: json['is_current'] == true,
      );
}
