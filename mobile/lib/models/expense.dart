/// Catégorie de charge (GET /expense-categories).
class ExpenseCategory {
  const ExpenseCategory({required this.id, required this.name});

  final int id;
  final String name;

  factory ExpenseCategory.fromJson(Map<String, dynamic> json) =>
      ExpenseCategory(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
      );
}

/// Charge (GET /expenses).
///
/// Le backend n'expose qu'un seul champ texte (`label`) : il tient lieu de
/// description. Le « lieu » est le `warehouse_id` (code du lieu en retour).
class Expense {
  const Expense({
    required this.id,
    required this.label,
    this.category,
    this.warehouse,
    this.user,
    required this.amount,
    this.expenseDate,
    required this.hasReceipt,
    required this.status,
    this.paymentStatus = 'paid',
    this.paidAt,
  });

  final int id;
  final String label;
  final String? category;
  final String? warehouse;
  final String? user;
  final double amount;

  /// Format `Y-m-d`.
  final String? expenseDate;
  final bool hasReceipt;

  /// `pending`, `approved` ou `rejected`.
  final String status;

  /// `paid` (réglée) ou `unpaid` (portée au crédit, encore due).
  final String paymentStatus;

  /// Date du règlement, au format `Y-m-d`.
  final String? paidAt;

  bool get isPending => status == 'pending';

  bool get estDue => paymentStatus == 'unpaid';

  factory Expense.fromJson(Map<String, dynamic> json) => Expense(
        id: json['id'] as int,
        label: json['label'] as String? ?? '',
        category: json['category'] as String?,
        warehouse: json['warehouse'] as String?,
        user: json['user'] as String?,
        amount: (json['amount'] as num?)?.toDouble() ?? 0,
        expenseDate: json['expense_date'] as String?,
        hasReceipt: json['has_receipt'] == true,
        status: json['status'] as String? ?? 'pending',
        paymentStatus: json['payment_status'] as String? ?? 'paid',
        paidAt: json['paid_at'] as String?,
      );
}
