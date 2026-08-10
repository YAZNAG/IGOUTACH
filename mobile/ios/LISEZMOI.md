# Compilation iOS d'iGouTech

## Ce qui bloque, et pourquoi

Un fichier `.ipa` **ne peut être produit que sur macOS avec Xcode**. Apple ne
publie pas sa chaîne de compilation pour Windows ou Linux : aucun outil ne
contourne cette contrainte. Le poste de travail actuel étant sous Windows, la
compilation se fait ailleurs — sur un Mac, ou sur un serveur macOS loué à la
minute.

Le projet iOS est prêt : il ne manque que la machine qui compile et, pour
distribuer sur iPhone, un compte Apple Developer.

## Ce qui est déjà fait

| Élément | État |
|---|---|
| Projet Xcode (`ios/Runner.xcodeproj`) | présent |
| Identifiant d'application | `ma.igoutech.igoutechMobile` |
| Version minimale d'iOS | 13.0 |
| Nom affiché sur l'écran d'accueil | iGouTech |
| Langue déclarée | français |
| Icônes (15 tailles, sans transparence) | logo iGouTech |
| Conformité export (`ITSAppUsesNonExemptEncryption`) | déclarée |
| Mise à jour intégrée adaptée à iOS | faite |
| Pipeline de compilation | `.github/workflows/ios.yml` |

## La mise à jour automatique ne fonctionne pas pareil sur iOS

Sur Android, l'application télécharge l'APK et ouvre l'installateur du système.
**iOS l'interdit** : un iPhone n'installe que ce qui vient de l'App Store ou de
TestFlight.

L'application le gère désormais seule : sur iPhone, la boîte de mise à jour
affiche « Ouvrir l'App Store » au lieu de « Mettre à jour », et n'affiche ni
barre de téléchargement ni poids de fichier.

Côté serveur, il faut ajouter l'adresse de la fiche dans
`storage/app/public/app/version.json` :

```json
{
  "version": "1.5.0",
  "build": 6,
  "file": "app/igoutech.apk",
  "ios_url": "https://apps.apple.com/app/idVOTRE_ID",
  "notes": "..."
}
```

Tant que `ios_url` est absente, l'application iPhone **ne propose aucune mise à
jour** — plutôt que d'en proposer une qu'elle ne saurait pas installer.

## Compiler dès maintenant, sans compte Apple

Le dépôt GitHub `YAZNAG/IGOUTACH` déclenche la compilation sur un runner macOS
fourni par GitHub :

> Actions → **Application iOS** → *Run workflow* → choisir le profil
> (`manager` ou `admin`) → *Run*

Sans certificat configuré, le workflow produit un build **non signé**. Il prouve
que le projet compile, mais **ne s'installe sur aucun iPhone**. C'est l'étape
utile avant d'ouvrir un compte Apple.

## Distribuer sur de vrais iPhone

Il faut un **compte Apple Developer** : 99 USD par an, souscrit sur
[developer.apple.com](https://developer.apple.com). Aucune alternative pour
installer sur des appareils qui ne sont pas physiquement branchés à un Mac.

Ensuite, quatre secrets à déposer dans le dépôt
(*Settings → Secrets and variables → Actions*) :

| Secret | Contenu |
|---|---|
| `IOS_CERTIFICAT_P12` | certificat de distribution `.p12`, encodé en base64 |
| `IOS_CERTIFICAT_MOT_DE_PASSE` | mot de passe du `.p12` |
| `IOS_PROFIL_PROVISION` | profil `.mobileprovision`, encodé en base64 |

Encodage sous Windows :

```bash
certutil -encode certificat.p12 certificat.txt
```

Il reste à remplacer `VOTRE_TEAM_ID` dans `ios/ExportOptions.plist` par
l'identifiant d'équipe (10 caractères, visible sur developer.apple.com >
Membership). Le workflow bascule alors tout seul en mode signé et produit un
`.ipa` téléversable sur TestFlight.

## Deux profils, une seule application iOS

Sur Android, deux APK sont produits (`admin` et `manager`). Sur iOS, deux
applications distinctes exigeraient deux identifiants et deux fiches App Store.
Le workflow compile donc **un profil à la fois**, choisi au lancement. Pour
distribuer les deux en parallèle, il faudra un second identifiant
(`ma.igoutech.igoutechMobile.admin`) et une seconde fiche.

## Point à trancher, sans rapport avec iOS

L'icône Android est incohérente : bleu marine sur Android 8 et suivants (icône
adaptative héritée d'une version antérieure), icône Flutter par défaut sur les
versions plus anciennes. Aucune des deux n'est le logo rouge iGouTech. L'icône
iOS, elle, porte bien le logo.
