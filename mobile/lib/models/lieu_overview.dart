/// Aperçu du lieu, tel que renvoyé par `GET /me/overview`.
class LieuOverview {
  const LieuOverview({
    required this.lieuCode,
    required this.lieuNom,
    required this.jour,
    required this.mois,
    required this.stock,
    required this.creances,
    required this.chargesMois,
    required this.topArticles,
    required this.serie,
    required this.aTraiter,
  });

  final String lieuCode;
  final String lieuNom;
  final VentesPeriode jour;
  final VentesPeriode mois;
  final StockResume stock;
  final Creances creances;
  final double chargesMois;
  final List<ArticleVendu> topArticles;
  final List<PointJour> serie;
  final ATraiter aTraiter;

  factory LieuOverview.fromJson(Map<String, dynamic> j) {
    final lieu = j['warehouse'] as Map<String, dynamic>?;
    return LieuOverview(
      lieuCode: lieu?['code'] as String? ?? '',
      lieuNom: lieu?['name'] as String? ?? '',
      jour: VentesPeriode.fromJson(j['today'] as Map<String, dynamic>? ?? const {}),
      mois: VentesPeriode.fromJson(j['month'] as Map<String, dynamic>? ?? const {}),
      stock: StockResume.fromJson(j['stock'] as Map<String, dynamic>? ?? const {}),
      creances: Creances.fromJson(j['receivables'] as Map<String, dynamic>? ?? const {}),
      chargesMois: _double(j['expenses_month']),
      topArticles: (j['top_products'] as List<dynamic>? ?? [])
          .map((e) => ArticleVendu.fromJson(e as Map<String, dynamic>))
          .toList(),
      serie: (j['daily'] as List<dynamic>? ?? [])
          .map((e) => PointJour.fromJson(e as Map<String, dynamic>))
          .toList(),
      aTraiter: ATraiter.fromJson(j['pending'] as Map<String, dynamic>? ?? const {}),
    );
  }
}

class VentesPeriode {
  const VentesPeriode({
    required this.nombre,
    required this.chiffre,
    required this.encaisse,
    required this.aCredit,
  });

  final int nombre;
  final double chiffre;
  final double encaisse;

  /// Ce qui reste à encaisser sur la période.
  final double aCredit;

  factory VentesPeriode.fromJson(Map<String, dynamic> j) => VentesPeriode(
        nombre: (j['count'] as num?)?.toInt() ?? 0,
        chiffre: _double(j['revenue']),
        encaisse: _double(j['collected']),
        aCredit: _double(j['on_credit']),
      );
}

class StockResume {
  const StockResume({
    required this.valeur,
    required this.unites,
    required this.references,
    required this.sousSeuil,
    required this.ruptures,
  });

  final double valeur;
  final int unites;
  final int references;
  final int sousSeuil;
  final int ruptures;

  factory StockResume.fromJson(Map<String, dynamic> j) => StockResume(
        valeur: _double(j['value']),
        unites: (j['units'] as num?)?.toInt() ?? 0,
        references: (j['references'] as num?)?.toInt() ?? 0,
        sousSeuil: (j['below_min'] as num?)?.toInt() ?? 0,
        ruptures: (j['out_of_stock'] as num?)?.toInt() ?? 0,
      );
}

class Creances {
  const Creances({required this.total, required this.clients, required this.horsPlafond});

  final double total;
  final int clients;
  final int horsPlafond;

  factory Creances.fromJson(Map<String, dynamic> j) => Creances(
        total: _double(j['total']),
        clients: (j['customers'] as num?)?.toInt() ?? 0,
        horsPlafond: (j['over_limit'] as num?)?.toInt() ?? 0,
      );
}

class ArticleVendu {
  const ArticleVendu({required this.nom, required this.quantite, required this.chiffre});

  final String nom;
  final int quantite;
  final double chiffre;

  factory ArticleVendu.fromJson(Map<String, dynamic> j) => ArticleVendu(
        nom: j['name'] as String? ?? '',
        quantite: (j['quantity'] as num?)?.toInt() ?? 0,
        chiffre: _double(j['revenue']),
      );
}

class PointJour {
  const PointJour({required this.libelle, required this.chiffre});

  final String libelle;
  final double chiffre;

  factory PointJour.fromJson(Map<String, dynamic> j) => PointJour(
        libelle: j['label'] as String? ?? '',
        chiffre: _double(j['revenue']),
      );
}

class ATraiter {
  const ATraiter({
    required this.demandesTransfert,
    required this.transfertsEntrants,
    required this.inventairesOuverts,
    required this.ventesImpayees,
  });

  final int demandesTransfert;
  final int transfertsEntrants;
  final int inventairesOuverts;
  final int ventesImpayees;

  int get total =>
      demandesTransfert + transfertsEntrants + inventairesOuverts + ventesImpayees;

  factory ATraiter.fromJson(Map<String, dynamic> j) => ATraiter(
        demandesTransfert: (j['transfer_requests'] as num?)?.toInt() ?? 0,
        transfertsEntrants: (j['incoming_transfers'] as num?)?.toInt() ?? 0,
        inventairesOuverts: (j['draft_inventories'] as num?)?.toInt() ?? 0,
        ventesImpayees: (j['unpaid_sales'] as num?)?.toInt() ?? 0,
      );
}

double _double(dynamic v) => v == null ? 0 : (v as num).toDouble();
