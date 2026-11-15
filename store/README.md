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

## Paliers et devis

La boutique vend au palier : le prix suit la quantité, selon les seuils des
tarifs de la gestion (`price_types.min_quantity`, qu'une ligne de prix peut
surcharger pour un article donné — c'est le « seuil propre à l'article »).

Au-delà d'un palier, la vente ne se conclut plus en ligne : le client demande
un devis, la boutique chiffre, le client accepte, et la commande naît de cette
acceptation aux prix négociés. Le palier de bascule se règle dans
`/admin/reglages` (`quote_from` : `none`, `semi_gros` ou `gros`).

- Le prix appliqué vient de `PriceResolver` (gestion) : la vitrine en fait une
  copie d'affichage dans `src/lib/paliers.ts`, le serveur reste seul juge.
- Une commande directe dont la quantité relève du devis est **refusée côté
  serveur** : passer par l'API en direct ne contourne rien.
- À l'envoi, le devis est aussi créé dans la gestion (`sales.type = quote`)
  quand un lieu d'expédition est indiqué — il apparaît alors dans le web et
  dans le mobile.
- Le client répond par un lien public porteur d'un jeton
  (`/devis/{public_token}`) ; il peut aussi le retrouver depuis `/suivi`.

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
