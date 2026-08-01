<?php

declare(strict_types=1);

namespace App\Support\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Applique un tri « DataTable » borné à une liste blanche de colonnes.
 */
final class Sortable
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, string>  $allowed  clé exposée => colonne SQL
     * @return Builder<TModel>
     */
    public static function apply(Builder $query, Request $request, array $allowed, string $default): Builder
    {
        $sort = (string) $request->string('sort')->value();
        $column = array_key_exists($sort, $allowed) ? $allowed[$sort] : $allowed[$default];

        $direction = strtolower((string) $request->string('direction')->value()) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($column, $direction);
    }
}
