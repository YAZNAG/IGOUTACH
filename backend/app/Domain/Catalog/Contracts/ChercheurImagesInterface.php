<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Contracts;

/**
 * Un moteur capable de proposer des photos pour un article.
 *
 * @phpstan-type Candidate array{url: string, thumbnail: string, title: string, source: string, width: int|null, height: int|null}
 */
interface ChercheurImagesInterface
{
    /** Identifiant court, montré à l'écran : « google », « claude ». */
    public function nom(): string;

    public function disponible(): bool;

    /** Articles traités au plus par lancement, selon le coût d'une recherche. */
    public function maxParLancement(): int;

    /**
     * @param  string  $requete  ce que l'on cherche (« télécommande Echolink RX5 »)
     * @param  string|null  $contexte  précisions utiles (référence, marque, catégorie)
     * @return list<Candidate>
     *
     * @throws \RuntimeException message lisible : clé refusée, quota, service muet
     */
    public function chercher(string $requete, ?string $contexte = null): array;
}
