<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Contracts\ChercheurImagesInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Recherche d'images par l'API officielle Google Custom Search (moteur
 * programmable en mode images). Cent requêtes par jour en offre gratuite.
 */
class RechercheImagesGoogle implements ChercheurImagesInterface
{
    private const POINT_D_ENTREE = 'https://www.googleapis.com/customsearch/v1';

    /** Candidates demandées par article (le maximum de l'API est 10). */
    public const CANDIDATES = 8;

    public function nom(): string
    {
        return 'google';
    }

    public function disponible(): bool
    {
        return (string) config('services.google_cse.key') !== '' && (string) config('services.google_cse.cx') !== '';
    }

    public function maxParLancement(): int
    {
        return 25;
    }

    public function chercher(string $requete, ?string $contexte = null): array
    {
        if ($requete === '') {
            return [];
        }

        try {
            $reponse = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->timeout(30)
                ->get(self::POINT_D_ENTREE, [
                    'key' => (string) config('services.google_cse.key'),
                    'cx' => (string) config('services.google_cse.cx'),
                    'q' => $requete,
                    'searchType' => 'image',
                    'num' => self::CANDIDATES,
                    'safe' => 'active',
                    'imgType' => 'photo',
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Agent images : Google injoignable', ['erreur' => $e->getMessage()]);

            throw new RuntimeException('Google ne répond pas : réessayez dans un instant.');
        }

        if ($reponse->status() === 429) {
            throw new RuntimeException('quota Google Custom Search atteint pour aujourd’hui (100 requêtes/jour en offre gratuite).');
        }

        if (! $reponse->successful()) {
            $detail = (string) ($reponse->json('error.message') ?? $reponse->body());
            Log::warning('Agent images : refus de Google', ['status' => $reponse->status(), 'detail' => mb_substr($detail, 0, 300)]);

            throw new RuntimeException('clé ou moteur de recherche refusé par Google : '.mb_substr($detail, 0, 160));
        }

        $items = $reponse->json('items') ?? [];

        return collect(is_array($items) ? $items : [])
            ->filter(fn ($i) => is_array($i) && isset($i['link']))
            ->map(fn (array $i): array => [
                'url' => (string) $i['link'],
                'thumbnail' => (string) ($i['image']['thumbnailLink'] ?? $i['link']),
                'title' => Str::limit((string) ($i['title'] ?? ''), 120, '…'),
                'source' => (string) ($i['displayLink'] ?? ''),
                'width' => isset($i['image']['width']) ? (int) $i['image']['width'] : null,
                'height' => isset($i['image']['height']) ? (int) $i['image']['height'] : null,
            ])
            ->values()
            ->all();
    }
}
