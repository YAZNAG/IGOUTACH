<?php

declare(strict_types=1);

namespace App\Support\Documents;

interface DocumentNumberGeneratorInterface
{
    /**
     * Génère le prochain numéro de document pour une clé donnée
     * (ex. 'transfer' → 'TRF-000001').
     */
    public function next(string $key): string;
}
