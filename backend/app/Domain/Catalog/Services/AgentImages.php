<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use App\Domain\Assistant\Exceptions\AssistantIndisponibleException;
use App\Domain\Assistant\Services\GeminiClient;
use App\Domain\Catalog\Contracts\ChercheurImagesInterface;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductImage;
use App\Domain\Catalog\Models\ProductImageProposal;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * L'agent images : trouve des photos pour les articles qui n'en ont pas,
 * les propose, et n'enregistre que ce que l'administrateur a validé.
 *
 * La recherche passe par l'API officielle Google Custom Search (moteur
 * programmable, mode images). Gemini, quand il est configuré, reformule le
 * nom de l'article en requête de recherche : « TC ECHOLINK RX5 / IG125 »
 * devient « télécommande Echolink RX5 ».
 */
final class AgentImages
{
    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly RechercheImagesGoogle $google,
        private readonly RechercheImagesClaude $claude,
    ) {}

    /**
     * Le moteur en service : Google Custom Search s'il est configuré (le
     * plus rapide et le moins cher), sinon Claude, sinon rien.
     */
    public function moteur(): ?ChercheurImagesInterface
    {
        foreach ([$this->google, $this->claude] as $moteur) {
            if ($moteur->disponible()) {
                return $moteur;
            }
        }

        return null;
    }

    public function configure(): bool
    {
        return $this->moteur() !== null;
    }

    public function maxParLancement(): int
    {
        return $this->moteur()?->maxParLancement() ?? 25;
    }

    /**
     * Articles actifs sans image ni proposition en attente.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Product>
     */
    public function sansImage()
    {
        return Product::query()
            ->where('is_active', true)
            ->whereDoesntHave('images')
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('product_image_proposals as p')
                ->whereColumn('p.product_id', 'products.id')->where('p.status', ProductImageProposal::STATUS_PENDING))
            ->orderBy('name');
    }

    /**
     * Lance l'agent sur des articles : une proposition par article trouvé.
     *
     * @param  list<int>|null  $productIds  articles choisis ; null = les premiers sans image
     * @return array{proposees: int, sans_resultat: int, erreurs: list<string>}
     */
    public function lancer(int $limite, ?array $productIds, ?int $userId): array
    {
        if (! $this->configure()) {
            throw new RuntimeException('Agent images non configuré : renseignez ANTHROPIC_API_KEY, ou GOOGLE_CSE_KEY et GOOGLE_CSE_CX, dans le fichier .env.');
        }

        $limite = max(1, min($limite, $this->maxParLancement()));

        $articles = $productIds !== null
            ? Product::query()->whereIn('id', $productIds)->orderBy('name')->get()
            : $this->sansImage()->limit($limite)->get();

        $resultat = ['proposees' => 0, 'sans_resultat' => 0, 'erreurs' => []];

        foreach ($articles->take($limite) as $article) {
            try {
                $proposition = $this->proposer($article, $userId);
                $proposition !== null ? $resultat['proposees']++ : $resultat['sans_resultat']++;
            } catch (RuntimeException $e) {
                $resultat['erreurs'][] = $article->sku.' : '.$e->getMessage();
                // Le quota ou la clé en cause : inutile d'insister sur les suivants.
                if (str_contains($e->getMessage(), 'quota') || str_contains($e->getMessage(), 'clé')) {
                    break;
                }
            }
        }

        return $resultat;
    }

    /**
     * Cherche des images pour un article et enregistre la proposition.
     * Une proposition en attente existante est remplacée (nouvelle requête).
     */
    public function proposer(Product $article, ?int $userId, ?string $requete = null): ?ProductImageProposal
    {
        $requete = trim((string) ($requete ?? $this->requetePour($article)));
        $candidates = $this->chercher($requete, $this->contextePour($article));

        ProductImageProposal::query()
            ->where('product_id', $article->id)
            ->where('status', ProductImageProposal::STATUS_PENDING)
            ->delete();

        if ($candidates === []) {
            return null;
        }

        return ProductImageProposal::query()->create([
            'product_id' => $article->id,
            'query' => Str::limit($requete, 200, ''),
            'candidates' => $candidates,
            'status' => ProductImageProposal::STATUS_PENDING,
            'created_by' => $userId,
        ]);
    }

    /**
     * La requête de recherche : Gemini si disponible, sinon le nom nettoyé.
     */
    public function requetePour(Product $article): string
    {
        $article->loadMissing(['brand:id,name', 'category:id,name']);
        $repli = $this->nettoyer($article->name);

        if (! $this->gemini->configure()) {
            return $repli;
        }

        try {
            $reponse = $this->gemini->repondre(
                "Tu aides un distributeur marocain de matériel électronique, satellite et câblage à trouver des photos de ses articles. "
                ."On te donne le nom d'un article tel qu'il est saisi dans le stock (abréviations : TC = télécommande, RLT = rouleau, PIL = pile, AFF = afficheur, FICH = fiche, RECEP = récepteur). "
                ."Réponds UNIQUEMENT par une requête de recherche d'images de 2 à 6 mots, en français ou avec la marque et le modèle, sans codes internes (IG125, CL-…), sans guillemets, sans ponctuation.",
                [['role' => 'user', 'content' => sprintf(
                    'Article : %s (référence %s%s%s)',
                    $article->name,
                    $article->sku,
                    $article->brand?->name ? ', marque '.$article->brand->name : '',
                    $article->category?->name ? ', catégorie '.$article->category->name : '',
                )]],
            );
            $requete = trim(preg_replace('/[\"«»\n\r]+/u', ' ', $reponse) ?? '');
            $mots = preg_split('/\s+/u', $requete) ?: [];

            return count($mots) >= 1 && count($mots) <= 8 ? $requete : $repli;
        } catch (AssistantIndisponibleException) {
            return $repli;
        }
    }

    /**
     * Le nom saisi, débarrassé des codes internes et des abréviations.
     */
    private function nettoyer(string $nom): string
    {
        $n = preg_replace('~/\s*IG\d+~i', '', $nom) ?? $nom;
        $n = preg_replace('/\b(TC)\b/i', 'télécommande', $n) ?? $n;
        $n = preg_replace('/\b(RLT)\b/i', 'rouleau', $n) ?? $n;
        $n = preg_replace('/\b(PIL)\b/i', 'pile', $n) ?? $n;
        $n = preg_replace('/\b(FICH)\b/i', 'fiche', $n) ?? $n;
        $n = preg_replace('/[\/()]+/', ' ', $n) ?? $n;

        return trim(preg_replace('/\s+/', ' ', $n) ?? $n);
    }

    /**
     * Les candidates pour une requête, par le moteur en service.
     *
     * Les adresses rendues par Claude sont relevées sur des pages web : on
     * vérifie que chacune sert bien une image avant de la proposer. Celles
     * de Google viennent d'un index d'images, elles se montrent telles quelles.
     *
     * @return list<array{url: string, thumbnail: string, title: string, source: string, width: int|null, height: int|null}>
     */
    public function chercher(string $requete, ?string $contexte = null): array
    {
        $moteur = $this->moteur();

        if ($moteur === null || $requete === '') {
            return [];
        }

        $candidates = $moteur->chercher($requete, $contexte);

        if ($moteur->nom() === 'google') {
            return $candidates;
        }

        return array_values(array_filter($candidates, fn (array $c): bool => $this->estUneImage($c['url'])));
    }

    /**
     * Précisions passées au moteur : référence, marque, catégorie.
     */
    private function contextePour(Product $article): string
    {
        $article->loadMissing(['brand:id,name', 'category:id,name']);

        return implode(', ', array_filter([
            'référence '.$article->sku,
            $article->brand?->name ? 'marque '.$article->brand->name : null,
            $article->category?->name ? 'catégorie '.$article->category->name : null,
        ]));
    }

    /**
     * L'adresse sert-elle une image ? Un en-tête suffit, le contenu ne se
     * télécharge qu'à la validation.
     */
    private function estUneImage(string $url): bool
    {
        try {
            $r = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->withHeaders(['Accept' => 'image/*'])
                ->timeout(10)
                ->head($url);

            if (! $r->successful()) {
                $r = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                    ->withHeaders(['Accept' => 'image/*', 'Range' => 'bytes=0-64'])
                    ->timeout(10)
                    ->get($url);
            }
        } catch (ConnectionException) {
            return false;
        }

        return $r->successful() && str_starts_with((string) $r->header('Content-Type'), 'image/');
    }

    /**
     * L'administrateur retient une candidate : elle est téléchargée, rangée
     * avec les images de l'article, et devient la principale s'il n'en a pas.
     */
    public function approuver(ProductImageProposal $proposition, int $index, ?int $userId): ProductImage
    {
        if ($proposition->status !== ProductImageProposal::STATUS_PENDING) {
            throw new RuntimeException('Cette proposition a déjà été traitée.');
        }

        $candidate = $proposition->candidates[$index] ?? null;

        if ($candidate === null) {
            throw new RuntimeException('Candidate inconnue.');
        }

        [$octets, $extension] = $this->telecharger($candidate['url'])
            ?? $this->telecharger($candidate['thumbnail'])
            ?? throw new RuntimeException('Image introuvable au téléchargement : choisissez-en une autre.');

        /** @var Product $article */
        $article = $proposition->product()->firstOrFail();
        $chemin = sprintf('products/agent-%d-%s.%s', $article->id, Str::lower(Str::random(8)), $extension);
        Storage::disk('public')->put($chemin, $octets);

        return DB::transaction(function () use ($article, $chemin, $proposition, $index, $userId): ProductImage {
            $premiere = $article->images()->count() === 0;

            $image = $article->images()->create([
                'path' => $chemin,
                'is_main' => $premiere,
                'position' => $article->images()->count(),
            ]);

            $proposition->update([
                'status' => ProductImageProposal::STATUS_APPROVED,
                'chosen_index' => $index,
                'image_id' => $image->id,
                'reviewed_by' => $userId,
                'reviewed_at' => now(),
            ]);

            return $image;
        });
    }

    public function rejeter(ProductImageProposal $proposition, ?int $userId): void
    {
        if ($proposition->status !== ProductImageProposal::STATUS_PENDING) {
            throw new RuntimeException('Cette proposition a déjà été traitée.');
        }

        $proposition->update([
            'status' => ProductImageProposal::STATUS_REJECTED,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * @return array{0: string, 1: string}|null  octets et extension, ou null si ce n'est pas une image exploitable
     */
    private function telecharger(string $url): ?array
    {
        try {
            $r = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->withHeaders(['Accept' => 'image/*'])
                ->timeout(25)
                ->get($url);
        } catch (ConnectionException) {
            return null;
        }

        if (! $r->successful() || strlen($r->body()) < 1024 || strlen($r->body()) > 8 * 1024 * 1024) {
            return null;
        }

        $extension = match (true) {
            str_starts_with($r->body(), "\xFF\xD8") => 'jpg',
            str_starts_with($r->body(), "\x89PNG") => 'png',
            str_starts_with($r->body(), 'RIFF') && substr($r->body(), 8, 4) === 'WEBP' => 'webp',
            default => null,
        };

        return $extension !== null ? [$r->body(), $extension] : null;
    }

    /**
     * Chiffres de la page : combien d'articles attendent un visuel.
     *
     * @return array{sans_image: int, en_attente: int, validees: int, rejetees: int}
     */
    public function compteurs(): array
    {
        $parStatut = ProductImageProposal::query()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return [
            'sans_image' => Product::query()->where('is_active', true)->whereDoesntHave('images')->count(),
            'en_attente' => (int) ($parStatut[ProductImageProposal::STATUS_PENDING] ?? 0),
            'validees' => (int) ($parStatut[ProductImageProposal::STATUS_APPROVED] ?? 0),
            'rejetees' => (int) ($parStatut[ProductImageProposal::STATUS_REJECTED] ?? 0),
        ];
    }

    /**
     * @param  Collection<int, ProductImageProposal>  $propositions
     * @return list<array<string, mixed>>
     */
    public function serialiser(Collection $propositions): array
    {
        return $propositions->map(fn (ProductImageProposal $p): array => [
            'id' => $p->id,
            'status' => $p->status,
            'query' => $p->query,
            'candidates' => $p->candidates,
            'chosen_index' => $p->chosen_index,
            'product' => [
                'id' => $p->product_id,
                'sku' => $p->product?->sku,
                'name' => $p->product?->name,
                'category' => $p->product?->category?->name,
                'brand' => $p->product?->brand?->name,
            ],
            'image_url' => $p->image?->path ? Storage::disk('public')->url($p->image->path) : null,
            'reviewed_by' => $p->reviewer?->name,
            'reviewed_at' => $p->reviewed_at?->toIso8601String(),
            'created_at' => $p->created_at?->toIso8601String(),
        ])->values()->all();
    }
}
