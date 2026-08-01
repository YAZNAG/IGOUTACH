# IGOUTECH — Gestion de stock multi-sites

API de gestion de stock pour un distributeur de matériel informatique : 2 dépôts,
1 point de vente, N vendeurs itinérants avec stock embarqué. Chaque lieu est suivi
séparément, avec une vue consolidée réservée à la direction.

Architecture **SOLID** (condition de recette), backend **Laravel 11 / PHP 8.3**,
base **MySQL/MariaDB**, authentification **Sanctum**.

## Stack

- Laravel 11, PHP 8.3 (`declare(strict_types=1)` partout)
- MySQL 8 / MariaDB 10.11+ (moteur **InnoDB** requis : transactions + verrous)
- Laravel Sanctum (cookie SPA web + token mobile)
- Qualité : PHPStan niveau 8 (Larastan), Laravel Pint, Pest
- Redis prévu en production ; en développement, cache/queues sur `database`

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
# Renseigner DB_* et ADMIN_EMAIL / ADMIN_PASSWORD dans .env
php artisan migrate:fresh --seed
php artisan serve   # http://localhost:8000
```

Le compte administrateur est créé par `AdminSeeder` à partir de `ADMIN_EMAIL` /
`ADMIN_PASSWORD` (jamais en dur dans le code).

## Structure SOLID

```
app/
├── Domain/
│   ├── Access/        RBAC : rôles, permissions, résolution + cache
│   ├── Warehouses/    Lieux et types de lieux
│   ├── Catalog/       Articles, catégories, marques, unités, n° de série
│   └── Stock/         Mouvements, transferts, inventaires, valorisation
│       ├── Actions/       StockIn, StockOut, CreateTransfer, ReceiveTransfer
│       ├── Contracts/     StockReaderInterface, StockWriterInterface, StockValuationInterface
│       ├── DTOs/          StockMovementData, TransferData…
│       ├── Repositories/  StockRepository (lock + transaction + CMUP)
│       ├── Services/      AverageCostValuation (CMUP)
│       └── Models/        Stock, StockMovement, Transfer, Inventory…
├── Http/Controllers/Api/V1/
├── Http/Requests/     validation uniquement
├── Http/Resources/    sérialisation uniquement (masque le prix d'achat)
├── Providers/Domain/  liaisons interface → implémentation par domaine
└── Support/
    ├── Concerns/BelongsToWarehouse.php   isolation par lieu
    ├── Scopes/WarehouseScope.php         filtre global basé sur permission
    └── Documents/                        générateur de n° de documents
```

### Principes appliqués

- **S** — Controllers minces ; logique métier dans Actions/Services ; aucun accès
  Eloquent depuis un controller.
- **O** — Rôles/permissions/types en base (jamais d'ENUM ni de rôle en dur). La
  valorisation est une implémentation de `StockValuationInterface` : en changer =
  une ligne dans `StockServiceProvider`.
- **L** — Toute implémentation d'interface est substituable (mêmes contrats).
- **I** — Interfaces ségrégées : un écran « Mon stock » ne dépend que de
  `StockReaderInterface` et ne peut structurellement pas écrire de stock.
- **D** — Les Actions reçoivent leurs dépendances par le constructeur, sous forme
  d'interfaces, liées dans les `ServiceProvider` de domaine.

## RBAC

Permissions nommées `module.action`. Résolution :

```
effectives = (permissions des rôles ∪ accordées) − refusées
```

Le refus explicite (`user_permission.is_granted = false`) l'emporte toujours.
Le résultat est mis en cache par utilisateur (invalidation via `PermissionResolver::forget`).
Toute autorisation passe par les permissions (`$user->can('stock.view_global')`),
jamais par un nom de rôle. Les routes API sont protégées par le middleware `can:`.

Rôles système au seeding : `admin`, `manager`, `seller` (matrice dans
`RolePermissionSeeder`, conforme au brief §9).

## Isolation par lieu

`WarehouseScope` filtre automatiquement les modèles rattachés à un lieu (`Stock`,
`StockMovement`, …) selon `users.warehouse_id`, **sauf** si l'utilisateur détient
`stock.view_global`. Le filtre ne dépend jamais d'un paramètre client.

## Stock

- `stocks.quantity` n'est modifié qu'à travers un mouvement, en transaction avec
  `lockForUpdate()`.
- `stock_movements` est **append-only** (ni UPDATE ni DELETE) : une correction se
  fait par un mouvement inverse.
- Valorisation **CMUP** par défaut.
- Transfert à double validation : expédition (`transfer_out`, statut *en transit*)
  puis réception (`transfer_in`, statut *reçu*). Pendant le transit, la marchandise
  reste comptée dans le stock global. Tout écart de réception est enregistré et un
  événement `TransferDiscrepancyDetected` est émis.

## Qualité & tests

```bash
composer qa          # Pint (test) + PHPStan niveau 8 + Pest
composer stan        # PHPStan niveau 8
composer pint        # formatage
php artisan test     # suite Pest
```

Tests couverts : résolution des permissions, isolation des données par lieu,
mouvements de stock (CMUP, stock insuffisant, append-only), transferts (transit,
stock global constant, réception, écarts), API catalogue (permissions, masquage
du prix d'achat).

## Décisions (brief §16)

1. Le point de vente peut recevoir directement d'un fournisseur (`allows_purchase_receipt`).
2. Numéros de série : par catégorie (`categories.requires_serial`).
3. Valorisation : **CMUP** en v1.
4. Facturation marocaine (ICE, TVA) : prévue, **TVA par défaut 0**.
5. Langue : **français**.

## État d'avancement

- [x] Étape 1 — RBAC complet, Global Scope, seeders
- [x] Étape 2 — Warehouses + types de lieux + tests d'isolation
- [x] Étape 3 — Catalogue produits (API v1, masquage prix d'achat)
- [x] Étape 4 — Cœur stock : mouvements, transferts, inventaires
- [ ] Étapes 5+ — Achats, ventes/caisse, clients & crédits, charges, alertes, rapports, mobile
