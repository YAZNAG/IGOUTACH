<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Version publiée de l'application Android.
 *
 * L'application interroge ce point au démarrage et se propose de se mettre à
 * jour toute seule : plus besoin de transmettre l'APK à chaque correction.
 *
 * La description de la version vit dans un fichier déposé à côté de l'APK
 * plutôt qu'en base : publier une version se résume alors à copier deux
 * fichiers, sans migration ni écriture en base.
 */
final class AppReleaseController extends Controller
{
    private const MANIFESTE = 'app/version.json';

    public function show(): JsonResponse
    {
        $disque = Storage::disk('public');

        if (! $disque->exists(self::MANIFESTE)) {
            // Aucune version publiée : l'application doit continuer de
            // fonctionner normalement, pas afficher une erreur.
            return response()->json(['data' => null]);
        }

        $manifeste = json_decode((string) $disque->get(self::MANIFESTE), true);

        if (! is_array($manifeste) || ! isset($manifeste['version'])) {
            return response()->json(['data' => null]);
        }

        $fichier = (string) ($manifeste['file'] ?? 'app/igoutech.apk');
        $existe = $disque->exists($fichier);

        return response()->json(['data' => [
            'version' => (string) $manifeste['version'],
            'build' => (int) ($manifeste['build'] ?? 0),
            // URL absolue : l'application la passe telle quelle au
            // téléchargement, sans avoir à reconstruire le domaine.
            'url' => $existe ? $disque->url($fichier) : null,
            'size' => $existe ? $disque->size($fichier) : null,
            'notes' => $manifeste['notes'] ?? null,
            // Une version obligatoire bloque l'usage tant qu'elle n'est pas
            // installée : réservé aux corrections qui faussent les données.
            'mandatory' => (bool) ($manifeste['mandatory'] ?? false),
            'published_at' => $manifeste['published_at'] ?? null,
        ]]);
    }
}
