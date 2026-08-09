<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\Scopes\WarehouseScope;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuse un lieu sur lequel l'utilisateur n'a pas la main.
 *
 * La lecture est cloisonnée par WarehouseScope, mais l'écriture ne l'était
 * pas : un responsable pouvait créer un inventaire, une vente ou un mouvement
 * sur le lieu d'un autre en changeant simplement `warehouse_id` dans la
 * requête. Le cloisonnement doit valoir dans les deux sens.
 *
 * La règle s'appuie sur une PERMISSION, jamais sur un nom de rôle.
 */
final class WarehouseAccessible implements ValidationRule
{
    public function __construct(
        private readonly string $permissionGlobale = WarehouseScope::DEFAULT_GLOBAL_PERMISSION,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = auth()->user();

        // Console, seeding, tâche planifiée : aucun utilisateur, aucun filtre.
        if ($user === null) {
            return;
        }

        // Vue multi-lieux : libre de désigner n'importe quel lieu.
        if ($user->can($this->permissionGlobale)) {
            return;
        }

        $sien = $user->getAttribute('warehouse_id');

        // Sans lieu de rattachement, on ne peut agir sur aucun lieu : il vaut
        // mieux bloquer que laisser passer par défaut.
        if ($sien === null) {
            $fail('Aucun lieu ne vous est rattaché : impossible d’enregistrer cette opération.');

            return;
        }

        if ((int) $value !== (int) $sien) {
            $fail('Vous ne pouvez agir que sur le lieu qui vous est rattaché.');
        }
    }
}
