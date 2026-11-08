# Boutique en ligne iGouTech

Vitrine publique et tableau de bord de la boutique, servis sur
`https://store.igoutech.optizaworks.com`. La boutique n'a pas de base à elle :
catalogue, stock, prix, commandes et paiements vivent dans l'API de gestion
(`backend/`, domaine `app/Domain/Store`).

## Pile

- Vite + React 19 + TypeScript, Tailwind 4 (`@tailwindcss/vite`), react-router 7,
  TanStack Query, lucide-react.
- API : `VITE_API_URL` (par défaut `https://igoutech.optizaworks.com/api`).
  - Pages publiques : `/api/store/*` (réglages, catégories, articles, commandes,
    suivi), sans authentification, débit limité.
  - Tableau de bord `/admin` : `/api/v1/store-admin/*`, jeton obtenu via
    `/api/v1/mobile/login` (même compte que la gestion), permission `store.manage`.

## Développement

```bash
npm install
npm run dev
npm run build   # produit dist/
```

## Déploiement

1. `npm run build`.
2. Copier `dist/` dans le docroot cPanel
   `/home/optizaworks/public_html/store.igoutech.optizaworks.com`
   (le `.htaccess` de `public/` assure le repli SPA).
3. Côté API : l'origine de la boutique doit figurer dans `CORS_ALLOWED_ORIGINS`
   du `.env` serveur.

## HTTPS

AutoSSL du compte échoue sur ce sous-domaine : le certificat est un Let's Encrypt
dédié, émis par `certbot certonly --webroot` sur le docroot et installé dans
Apache par le hook `/root/deploy-store-cert.sh` (déclaré en `renew_hook`).
Le hook passe les fichiers PEM **URL-encodés** à `whmapi1 installssl`, sinon
l'API WHM refuse le certificat (« Invalid base64 »). Le renouvellement est
automatique (timer certbot).
