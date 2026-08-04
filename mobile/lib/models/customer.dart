/// Client (CustomerResource).
class Customer {
  const Customer({
    required this.id,
    required this.code,
    required this.name,
    this.phone,
    this.city,
    this.email,
    this.address,
    required this.creditLimit,
    required this.balance,
    required this.availableCredit,
    required this.isBlocked,
    required this.isActive,
  });

  final int id;
  final String code;
  final String name;
  final String? phone;
  final String? city;
  final String? email;
  final String? address;
  final double creditLimit;

  /// Encours (solde dû par le client).
  final double balance;
  final double availableCredit;
  final bool isBlocked;
  final bool isActive;

  bool get isOverLimit => creditLimit > 0 && balance > creditLimit;

  factory Customer.fromJson(Map<String, dynamic> json) => Customer(
        id: json['id'] as int,
        code: json['code'] as String? ?? '',
        name: json['name'] as String? ?? '',
        phone: json['phone'] as String?,
        city: json['city'] as String?,
        email: json['email'] as String?,
        address: json['address'] as String?,
        creditLimit: (json['credit_limit'] as num?)?.toDouble() ?? 0,
        balance: (json['balance'] as num?)?.toDouble() ?? 0,
        availableCredit: (json['available_credit'] as num?)?.toDouble() ?? 0,
        isBlocked: json['is_blocked'] == true,
        isActive: json['is_active'] != false,
      );
}

/// Écriture du relevé client (GET /customers/{id}/statement).
class StatementEntry {
  const StatementEntry({
    this.date,
    required this.type,
    required this.amount,
    required this.balanceAfter,
    this.note,
  });

  final String? date;
  final String type;
  final double amount;
  final double balanceAfter;
  final String? note;

  factory StatementEntry.fromJson(Map<String, dynamic> json) => StatementEntry(
        date: json['date'] as String?,
        type: json['type'] as String? ?? '',
        amount: (json['amount'] as num?)?.toDouble() ?? 0,
        balanceAfter: (json['balance_after'] as num?)?.toDouble() ?? 0,
        note: json['note'] as String?,
      );
}
