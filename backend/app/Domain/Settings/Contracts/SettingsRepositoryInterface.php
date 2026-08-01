<?php

declare(strict_types=1);

namespace App\Domain\Settings\Contracts;

interface SettingsRepositoryInterface
{
    public function get(string $key): string|int|bool;

    /**
     * Toutes les valeurs effectives (défauts fusionnés avec la base), par groupe.
     *
     * @return array<string, array<string, string|int|bool>>
     */
    public function allGrouped(): array;

    /**
     * Enregistre un lot de paramètres (clés inconnues ignorées).
     *
     * @param  array<string, scalar|null>  $values
     */
    public function setMany(array $values): void;
}
