# IGOUTECH — Frontend

SPA React 18 + TypeScript (strict) + Vite. Consomme l'API `backend/` via Sanctum
(cookie SPA). Aucune logique métier ici : l'interface n'est que présentation, le
backend reste la source de vérité des autorisations.

## Démarrage

```bash
npm install
cp .env.example .env   # VITE_API_URL=http://localhost:8000/api/v1
npm run dev            # http://localhost:5173
```

Le backend doit tourner sur `http://localhost:8000` et déclarer `localhost:5173`
dans `SANCTUM_STATEFUL_DOMAINS` / `FRONTEND_URL`.

## Stack

- React 18, TypeScript strict (aucun `any`)
- Vite, TanStack Query, React Router, React Hook Form + Zod
- Tailwind CSS (tokens de la charte) + primitives type shadcn/ui
- axios (`withCredentials` + `withXSRFToken` pour le flux Sanctum cross-origin)

## Organisation

```
src/
├── assets/logo/      igoutech-full · igoutech-mark · igoutech-white (SVG)
├── styles/           tokens.css (charte) · global.css
├── components/
│   ├── ui/           Button, Input, Field, Card (couleurs via tokens only)
│   └── layout/       AppLayout, Sidebar, Topbar, nav
├── features/
│   ├── auth/         api/ components/ pages/ hooks · index.ts
│   └── dashboard/    api/ components/ pages/ hooks · index.ts
├── hooks/            usePermission
├── lib/              api (axios), queryClient, utils
├── routes/           AppRouter, ProtectedRoute, PublicOnlyRoute, HomePage
└── types/
```

Règles d'architecture :

- Une feature ne s'importe qu'à travers son `index.ts`.
- Aucun `fetch`/axios hors du dossier `api/` d'une feature (client partagé `lib/api`).
- Aucune couleur en dur dans un composant : tout passe par `tokens.css` /
  classes Tailwind mappées (`bg-navy`, `text-ink`, `border-line`…).
- `VITE_API_URL` est la seule source d'URL ; aucun port/chemin en dur.

## Charte

Marine `--navy #0B2A5B` / bleu clair `--sky #0EA5E9`. Inter 400/500/600,
IBM Plex Mono pour chiffres/codes/horodatages (`tabular-nums`, alignés à droite).
Aplats, traits 1 px, pas d'ombres ni de dégradés — sauf le dégradé marine → bleu
du panneau gauche de l'écran de connexion.

## Navigation & permissions

Menu groupé : Pilotage · Exploitation · Gestion · Système. Chaque entrée porte une
permission ; `usePermission()` masque (jamais grise) les entrées non autorisées.
Confort d'affichage seulement — le backend refuse de toute façon.

## Scripts

```bash
npm run dev      # serveur de développement
npm run build    # typecheck (tsc) + build de production
npm run preview  # prévisualiser le build
```
