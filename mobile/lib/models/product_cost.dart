/// Coût d'un article (GET /product-costs).
class ProductCostRow {
  const ProductCostRow({
    required this.id,
    required this.sku,
    required this.name,
    this.category,
    required this.totalQuantity,
    required this.cmup,
    required this.stockValue,
    this.purchasePrice,
    this.lastPurchasePrice,
    this.lastPurchaseAt,
    this.lastPurchaseSupplier,
    this.lastPurchaseNumber,
    this.lastPurchasePaymentStatus,
    this.purchaseCount = 0,
    this.appliedPurchasePrice,
    this.detailPrice,
    this.semiGrosPrice,
    this.grosPrice,
    this.marginDetail,
    this.marginSemiGros,
    this.marginGros,
    this.marginPercent,
    required this.belowCost,
  });

  final int id;
  final String sku;
  final String name;
  final String? category;
  final int totalQuantity;

  /// Coût d'achat de l'article, servi sous ce nom par l'API.
  final double cmup;
  final double stockValue;

  /// Prix d'achat porté par la fiche article. Depuis que la réception le met
  /// à jour, il suit le dernier achat ; il peut encore s'en écarter sur un
  /// article dont le prix a été forcé à la main.
  final double? purchasePrice;

  final double? lastPurchasePrice;
  final String? lastPurchaseAt;
  final String? lastPurchaseSupplier;

  /// Numéro du bon de réception (BR-…), pour remonter à la pièce.
  final String? lastPurchaseNumber;

  /// « paid », « partial » ou « unpaid ». Le règlement du fournisseur ne
  /// change pas le prix d'achat : la marchandise a été reçue à ce prix.
  final String? lastPurchasePaymentStatus;

  /// Nombre de réceptions : une seule ne dit rien d'une tendance.
  final int purchaseCount;

  /// Prix d'achat retenu pour les marges : celui du dernier bon s'il existe,
  /// sinon celui de la fiche.
  final double? appliedPurchasePrice;
  final double? detailPrice;
  final double? semiGrosPrice;
  final double? grosPrice;

  /// Marges des trois tarifs, exprimées sur le prix d'achat.
  final double? marginDetail;
  final double? marginSemiGros;
  final double? marginGros;

  final double? marginPercent;

  /// Le prix détail en vigueur est inférieur au coût d'achat : vente à perte.
  final bool belowCost;

  factory ProductCostRow.fromJson(Map<String, dynamic> json) => ProductCostRow(
        id: json['id'] as int,
        sku: json['sku'] as String? ?? '',
        name: json['name'] as String? ?? '',
        category: json['category'] as String?,
        totalQuantity: (json['total_quantity'] as num?)?.toInt() ?? 0,
        cmup: (json['cmup'] as num?)?.toDouble() ?? 0,
        stockValue: (json['stock_value'] as num?)?.toDouble() ?? 0,
        purchasePrice: (json['purchase_price'] as num?)?.toDouble(),
        lastPurchasePrice: (json['last_purchase_price'] as num?)?.toDouble(),
        lastPurchaseAt: json['last_purchase_at'] as String?,
        lastPurchaseSupplier: json['last_purchase_supplier'] as String?,
        lastPurchaseNumber: json['last_purchase_number'] as String?,
        lastPurchasePaymentStatus: json['last_purchase_payment_status'] as String?,
        purchaseCount: (json['purchase_count'] as num?)?.toInt() ?? 0,
        appliedPurchasePrice: (json['applied_purchase_price'] as num?)?.toDouble(),
        detailPrice: (json['detail_price'] as num?)?.toDouble(),
        semiGrosPrice: (json['semi_gros_price'] as num?)?.toDouble(),
        grosPrice: (json['gros_price'] as num?)?.toDouble(),
        marginDetail: (json['margin_detail'] as num?)?.toDouble(),
        marginSemiGros: (json['margin_semi_gros'] as num?)?.toDouble(),
        marginGros: (json['margin_gros'] as num?)?.toDouble(),
        marginPercent: (json['margin_percent'] as num?)?.toDouble(),
        belowCost: json['below_cost'] == true,
      );
}

/// Totaux du bloc `totals` de GET /product-costs (sur l'ensemble filtré).
class ProductCostTotals {
  const ProductCostTotals({
    required this.totalQuantity,
    required this.totalValue,
  });

  final int totalQuantity;
  final double totalValue;

  factory ProductCostTotals.fromJson(Map<String, dynamic> json) =>
      ProductCostTotals(
        totalQuantity: (json['total_quantity'] as num?)?.toInt() ?? 0,
        totalValue: (json['total_value'] as num?)?.toDouble() ?? 0,
      );
}
