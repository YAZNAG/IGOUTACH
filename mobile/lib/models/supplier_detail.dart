/// Fiche fournisseur complète (GET /suppliers, SupplierResource).
///
/// Le modèle `Supplier` (models/supplier.dart) est volontairement allégé pour
/// les sélecteurs d'achats : celui-ci porte tous les champs du formulaire.
class SupplierDetail {
  const SupplierDetail({
    required this.id,
    required this.code,
    required this.name,
    this.contactName,
    this.phone,
    this.email,
    this.address,
    this.city,
    this.ice,
    this.rc,
    this.paymentTermsDays,
    this.notes,
    required this.isActive,
  });

  final int id;
  final String code;
  final String name;
  final String? contactName;
  final String? phone;
  final String? email;
  final String? address;
  final String? city;
  final String? ice;
  final String? rc;

  /// Délai de règlement accordé, en jours.
  final int? paymentTermsDays;
  final String? notes;
  final bool isActive;

  String get label => code.isEmpty ? name : '$code — $name';

  factory SupplierDetail.fromJson(Map<String, dynamic> json) => SupplierDetail(
        id: json['id'] as int,
        code: json['code'] as String? ?? '',
        name: json['name'] as String? ?? '',
        contactName: json['contact_name'] as String?,
        phone: json['phone'] as String?,
        email: json['email'] as String?,
        address: json['address'] as String?,
        city: json['city'] as String?,
        ice: json['ice'] as String?,
        rc: json['rc'] as String?,
        paymentTermsDays: (json['payment_terms_days'] as num?)?.toInt(),
        notes: json['notes'] as String?,
        isActive: json['is_active'] != false,
      );
}

/// Crédit en cours : bon de réception non entièrement réglé
/// (GET /supplier-credits?supplier_id= → `data.rows`).
class SupplierCreditLine {
  const SupplierCreditLine({
    required this.id,
    required this.number,
    this.receivedAt,
    this.invoiceNumber,
    required this.paymentStatus,
    required this.totalAmount,
    required this.amountPaid,
    required this.remainingAmount,
  });

  final int id;
  final String number;

  /// `Y-m-d`.
  final String? receivedAt;
  final String? invoiceNumber;

  /// `unpaid`, `partial` ou `paid`.
  final String paymentStatus;
  final double totalAmount;
  final double amountPaid;
  final double remainingAmount;

  factory SupplierCreditLine.fromJson(Map<String, dynamic> json) =>
      SupplierCreditLine(
        id: json['id'] as int,
        number: json['number'] as String? ?? '',
        receivedAt: json['received_at'] as String?,
        invoiceNumber: json['invoice_number'] as String?,
        paymentStatus: json['payment_status'] as String? ?? 'unpaid',
        totalAmount: (json['total_amount'] as num?)?.toDouble() ?? 0,
        amountPaid: (json['amount_paid'] as num?)?.toDouble() ?? 0,
        remainingAmount: (json['remaining_amount'] as num?)?.toDouble() ?? 0,
      );
}

/// Règlement versé au fournisseur (GET /suppliers/{id}/payments → `data.rows`).
class SupplierSettlement {
  const SupplierSettlement({
    required this.id,
    this.goodsReceiptNumber,
    required this.amount,
    this.paidAt,
    this.paymentMethod,
    this.notes,
    this.createdBy,
  });

  final int id;

  /// Numéro du bon de réception réglé.
  final String? goodsReceiptNumber;
  final double amount;

  /// `Y-m-d`.
  final String? paidAt;
  final String? paymentMethod;
  final String? notes;
  final String? createdBy;

  factory SupplierSettlement.fromJson(Map<String, dynamic> json) {
    final receipt = json['goods_receipt'] as Map<String, dynamic>?;
    return SupplierSettlement(
      id: json['id'] as int,
      goodsReceiptNumber: receipt?['number'] as String?,
      amount: (json['amount'] as num?)?.toDouble() ?? 0,
      paidAt: json['paid_at'] as String?,
      paymentMethod: json['payment_method'] as String?,
      notes: json['notes'] as String?,
      createdBy: json['created_by'] as String?,
    );
  }
}
