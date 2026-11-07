<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use Anthropic\Client;
use Anthropic\Messages\WebFetchTool20260209;
use Anthropic\Messages\WebSearchTool20260209;
use App\Domain\Catalog\Contracts\ChercheurImagesInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Recherche d'images par Claude : le modèle cherche sur le web (outil de
 * recherche hébergé par Anthropic), ouvre les pages produit trouvées et en
 * relève les adresses d'images. Il rend une liste JSON, que l'agent vérifie
 * ensuite une à une avant de la proposer.
 *
 * Plus lent et plus coûteux qu'un moteur d'images (vingt à soixante
 * secondes par article) : cinq articles au plus par lancement.
 *
 * Non finale : les tests la remplacent par un double.
 */
class RechercheImagesClaude implements ChercheurImagesInterface
{
    private const CONSIGNES = <<<'TXT'
Tu aides un distributeur marocain de matériel électronique, satellite, câblage et accessoires à illustrer son catalogue. On te donne un article ; trouve des photos de CE produit (même marque, même modèle quand ils sont connus ; sinon le type de produit exact).

Méthode : cherche sur le web (sites de fabricants, boutiques en ligne marocaines, françaises ou internationales), ouvre les pages produit les plus pertinentes et relève les adresses des images principales (balises img, og:image, galeries). Ne renvoie que des adresses d'images DIRECTES (se terminant par .jpg, .jpeg, .png ou .webp, ou servies comme telles), jamais des pages HTML, jamais des adresses inventées ou tronquées. Préfère les photos sur fond blanc, de bonne taille. Entre quatre et huit candidates, les meilleures d'abord.

Réponds UNIQUEMENT avec le JSON demandé.
TXT;

    public function nom(): string
    {
        return 'claude';
    }

    public function disponible(): bool
    {
        return (string) config('services.anthropic.key') !== '';
    }

    public function maxParLancement(): int
    {
        return 5;
    }

    public function modele(): string
    {
        return (string) (config('services.anthropic.model') ?: 'claude-opus-5-5');
    }

    public function chercher(string $requete, ?string $contexte = null): array
    {
        if ($requete === '') {
            return [];
        }

        $client = new Client(apiKey: (string) config('services.anthropic.key'));

        try {
            $message = $client->messages->create(
                maxTokens: 6000,
                messages: [[
                    'role' => 'user',
                    'content' => 'Article à illustrer : '.$requete.($contexte !== null ? "\nPrécisions : ".$contexte : ''),
                ]],
                model: $this->modele(),
                system: self::CONSIGNES,
                // Une recherche d'images n'appelle pas un long raisonnement.
                outputConfig: [
                    'effort' => 'low',
                    'format' => [
                        'type' => 'json_schema',
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'candidates' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'url' => ['type' => 'string'],
                                            'title' => ['type' => 'string'],
                                            'source' => ['type' => 'string'],
                                        ],
                                        'required' => ['url', 'title', 'source'],
                                        'additionalProperties' => false,
                                    ],
                                ],
                            ],
                            'required' => ['candidates'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                tools: [
                    WebSearchTool20260209::with(maxUses: 5),
                    WebFetchTool20260209::with(maxUses: 6),
                ],
                requestOptions: ['timeout' => 150],
            );
        } catch (Throwable $e) {
            Log::warning('Agent images : Claude en erreur', ['erreur' => $e->getMessage(), 'modele' => $this->modele()]);

            throw new RuntimeException($this->traduire($e));
        }

        if (($message->stopReason ?? null) === 'refusal') {
            return [];
        }

        $texte = '';
        foreach ($message->content as $bloc) {
            if (($bloc->type ?? null) === 'text') {
                $texte .= $bloc->text;
            }
        }

        return $this->extraire($texte);
    }

    /**
     * Les candidates lues dans la réponse (JSON, éventuellement entouré de texte).
     *
     * @return list<array{url: string, thumbnail: string, title: string, source: string, width: int|null, height: int|null}>
     */
    public function extraire(string $texte): array
    {
        $json = json_decode($texte, true);

        if (! is_array($json) && preg_match('/\{.*\}/su', $texte, $m) === 1) {
            $json = json_decode($m[0], true);
        }

        $liste = is_array($json) ? ($json['candidates'] ?? []) : [];

        return collect(is_array($liste) ? $liste : [])
            ->filter(fn ($c) => is_array($c) && isset($c['url']) && is_string($c['url']) && preg_match('~^https?://~i', $c['url']) === 1)
            ->map(fn (array $c): array => [
                'url' => trim($c['url']),
                'thumbnail' => trim($c['url']),
                'title' => Str::limit(trim((string) ($c['title'] ?? '')), 120, '…'),
                'source' => (string) (parse_url(trim($c['url']), PHP_URL_HOST) ?: ($c['source'] ?? '')),
                'width' => null,
                'height' => null,
            ])
            ->unique('url')
            ->take(8)
            ->values()
            ->all();
    }

    private function traduire(Throwable $e): string
    {
        $m = $e->getMessage();

        return match (true) {
            str_contains($m, '401') || stripos($m, 'authentication') !== false => 'clé Anthropic refusée : vérifiez ANTHROPIC_API_KEY.',
            str_contains($m, '429') || stripos($m, 'rate limit') !== false => 'quota Anthropic atteint pour le moment : réessayez dans une minute.',
            stripos($m, 'credit') !== false || stripos($m, 'billing') !== false => 'crédit Anthropic épuisé : rechargez le compte.',
            default => 'Claude n’a pas pu chercher : '.Str::limit($m, 160, '…'),
        };
    }
}
