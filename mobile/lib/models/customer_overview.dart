/// Fiche client complète, telle que renvoyée par
/// `GET /customers/{id}/overview`.
class CustomerOverview {
  const CustomerOverview({
    required this.fiche,
    required this.credit,
    required this.stats,
    required this.ventes,
    required this.reglements,
  });

  final FicheClient fiche;
  final CreditClient credit;
  final StatsClient stats;
  final List<VenteClient> ventes;
  final List<ReglementClient> reglements;

  factory CustomerOverview.fromJson(Map<String, dynamic> j) => CustomerOverview(
        fiche: FicheClient.fromJson(j['customer'] as Map<String, dynamic>? ?? const {}),
        credit: CreditClient.fromJson(j['credit'] as Map<String, dynamic>? ?? const {}),
        stats: StatsClient.fromJson(j['stats'] as Map<String, dynamic>? ?? const {}),
        ventes: (j['sales'] as List<dynamic>? ?? [])
            .map((e) => VenteClient.fromJson(e as Map<String, dynamic>))
            .toList(),
        reglements: (j['payments'] as List<dynamic>? ?? [])
            .map((e) => ReglementClient.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}

class FicheClient {
  const FicheClient({
    required this.id,
    required this.code,
    required this.nom,
    this.estSociete = false,
    this.contact,
    this.telephone,
    this.email,
    this.adresse,
    this.ville,
    this.ice,
    this.typePrix,
    this.lieu,
    this.creePar,
    this.notes,
    this.actif = true,
  });

  final int id;
  final String code;
  final String nom;
  final bool estSociete;
  final String? contact;
  final String? telephone;
  final String? email;
  final String? adresse;
  final String? ville;
  final String? ice;
  final String? typePrix;
  final String? lieu;
  final String? creePar;
  final String? notes;
  final bool actif;

  factory FicheClient.fromJson(Map<String, dynamic> j) => FicheClient(
        id: (j['id'] as num?)?.toInt() ?? 0,
        code: j['code'] as String? ?? '',
        nom: j['name'] as String? ?? '',
        estSociete: j['is_company'] == true,
        contact: j['contact_name'] as String?,
        telephone: j['phone'] as String?,
        email: j['email'] as String?,
        adresse: j['address'] as String?,
        ville: j['city'] as String?,
        ice: j['ice'] as String?,
        typePrix: j['price_type'] as String?,
        lieu: j['warehouse'] as String?,
        creePar: j['created_by'] as String?,
        notes: j['notes'] as String?,
        actif: j['is_active'] != false,
      );
}

class CreditClient {
  const CreditClient({
    required this.encours,
    required this.plafond,
    required this.bloque,
    required this.impayees,
    this.partPlafond,
  });

  final double encours;
  final double plafond;
  final bool bloque;
  final int impayees;

  /// Part du plafond consommée. `null` quand aucun plafond n'est fixé :
  /// afficher une jauge sans plafond ne voudrait rien dire.
  final double? partPlafond;

  bool get horsPlafond => plafond > 0 && encours > plafond;

  factory CreditClient.fromJson(Map<String, dynamic> j) => CreditClient(
        encours: _d(j['balance']),
        plafond: _d(j['limit']),
        bloque: j['is_blocked'] == true,
        impayees: (j['unpaid_count'] as num?)?.toInt() ?? 0,
        partPlafond: j['usage_percent'] == null ? null : _d(j['usage_percent']),
      );
}

class StatsClient {
  const StatsClient({
    required this.nombreVentes,
    required this.totalAchete,
    required this.panierMoyen,
    required this.totalRegle,
    this.dernierAchat,
  });

  final int nombreVentes;
  final double totalAchete;
  final double panierMoyen;
  final double totalRegle;
  final String? dernierAchat;

  factory StatsClient.fromJson(Map<String, dynamic> j) => StatsClient(
        nombreVentes: (j['sales_count'] as num?)?.toInt() ?? 0,
        totalAchete: _d(j['total_purchased']),
        panierMoyen: _d(j['average_basket']),
        totalRegle: _d(j['total_paid']),
        dernierAchat: j['last_purchase'] as String?,
      );
}

class VenteClient {
  const VenteClient({
    required this.id,
    required this.reference,
    required this.date,
    required this.total,
    required this.paye,
    required this.reste,
    required this.statut,
    required this.statutReglement,
  });

  final int id;
  final String reference;
  final String? date;
  final double total;
  final double paye;
  final double reste;
  final String statut;
  final String statutReglement;

  bool get annulee => statut == 'cancelled';

  factory VenteClient.fromJson(Map<String, dynamic> j) => VenteClient(
        id: (j['id'] as num?)?.toInt() ?? 0,
        reference: j['reference'] as String? ?? '',
        date: j['date'] as String?,
        total: _d(j['total']),
        paye: _d(j['paid']),
        reste: _d(j['remaining']),
        statut: j['status'] as String? ?? '',
        statutReglement: j['payment_status'] as String? ?? '',
      );
}

class ReglementClient {
  const ReglementClient({
    required this.id,
    required this.reference,
    required this.montant,
    this.date,
    this.mode,
  });

  final int id;
  final String reference;
  final double montant;
  final String? date;
  final String? mode;

  factory ReglementClient.fromJson(Map<String, dynamic> j) => ReglementClient(
        id: (j['id'] as num?)?.toInt() ?? 0,
        reference: j['reference'] as String? ?? '',
        montant: _d(j['amount']),
        date: j['date'] as String?,
        mode: j['method'] as String?,
      );
}

double _d(dynamic v) => v == null ? 0 : (v as num).toDouble();
