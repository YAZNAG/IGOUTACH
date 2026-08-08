<?php

declare(strict_types=1);

namespace App\Support\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filtre automatiquement tout modèle rattaché à un lieu selon le lieu de
 * l'utilisateur connecté. Le filtre repose sur une PERMISSION, jamais sur un
 * nom de rôle, et ne dépend jamais d'un paramètre client.
 */
final class WarehouseScope implements Scope
{
    public const DEFAULT_GLOBAL_PERMISSION = 'stock.view_global';

    /**
     * @param  bool  $includeNull  Conserve aussi les lignes sans lieu. Utile
     *                             pour ce qui relève de la société entière —
     *                             une charge fixe non imputée à un lieu doit
     *                             rester visible de tous, pas disparaître.
     */
    public function __construct(private readonly bool $includeNull = false) {}

    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        // Contexte console / seeding : aucun utilisateur, aucun filtre.
        if ($user === null) {
            return;
        }

        $permission = method_exists($model, 'globalViewPermission')
            ? $model->globalViewPermission()
            : self::DEFAULT_GLOBAL_PERMISSION;

        if ($user->can($permission)) {
            return;
        }

        $colonne = $model->getTable().'.warehouse_id';
        $lieu = $user->getAttribute('warehouse_id');

        // getQuery() : la contrainte porte sur une colonne dynamique qualifiée.
        if ($this->includeNull) {
            $builder->getQuery()->where(function ($q) use ($colonne, $lieu): void {
                $q->where($colonne, '=', $lieu)->orWhereNull($colonne);
            });

            return;
        }

        $builder->getQuery()->where($colonne, '=', $lieu);
    }
}
