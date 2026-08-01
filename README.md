# IGOUTECH — Gestion de stock multi-sites

Deux applications séparées :

- **`backend/`** — API REST Laravel 11 / PHP 8.3 (aucune vue Blade), architecture
  SOLID, MySQL/MariaDB, auth Breeze API + Sanctum. Voir [backend/README.md](backend/README.md).
- **`frontend/`** — SPA React 18 + TypeScript + Vite. Voir [frontend/README.md](frontend/README.md).

Aucun code frontend dans le backend, aucune logique métier dans le frontend.

## Démarrage rapide

```bash
# 1) Backend (port 8000)
cd backend
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve

# 2) Frontend (port 5173) — dans un autre terminal
cd frontend
npm install
cp .env.example .env
npm run dev
```

Ouvrir http://localhost:5173. Le compte administrateur est créé par seeder à
partir de `backend/.env` (`ADMIN_EMAIL` / `ADMIN_PASSWORD`).

## Contexte

Distribution de matériel informatique, 5 lieux de stock (2 dépôts, 1 point de
vente, 2 véhicules vendeurs). Chaque lieu a son stock ; seul l'admin voit le
stock consolidé. Rôles : admin (global), responsable (un lieu), vendeur (son
véhicule). RBAC entièrement normalisé — aucun rôle en dur, tout repose sur des
permissions `module.action`.

## Avancement

- [x] Étape 1 — Auth (Breeze API + Sanctum) + RBAC + seeder admin
- [x] Étape 2 — Warehouses + Global Scope + tests d'isolation
- [x] Étape 3 — Catalogue produits
- [x] Étape 4 — Mouvements de stock + transferts (le cœur)
- [x] Frontend — écran de connexion + vue globale + navigation RBAC
- [ ] Étapes 5+ — Achats, ventes/caisse, clients & crédits, charges, alertes,
      rapports, application mobile vendeur (hors ligne)

# IGOUTACH
