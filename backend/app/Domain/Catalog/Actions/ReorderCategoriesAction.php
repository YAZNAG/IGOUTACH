<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Exceptions\CategoryReorderException;
use App\Domain\Catalog\Models\Category;
use Illuminate\Support\Facades\DB;

/**
 * Réorganise l'arbre des catégories en une seule transaction.
 * Profondeur maximale de 2 niveaux ; pas de cycle.
 */
final class ReorderCategoriesAction
{
    /**
     * @param  list<array{id: int, position: int, parent_id: int|null}>  $items
     */
    public function execute(array $items): void
    {
        // Parent visé pour chaque catégorie de la charge utile.
        $desiredParent = [];
        foreach ($items as $item) {
            $desiredParent[$item['id']] = $item['parent_id'];
        }

        DB::transaction(function () use ($items, $desiredParent): void {
            foreach ($items as $item) {
                $this->assertValid($item['id'], $item['parent_id'], $desiredParent);
            }

            foreach ($items as $item) {
                Category::query()->whereKey($item['id'])->update([
                    'position' => $item['position'],
                    'parent_id' => $item['parent_id'],
                ]);
            }
        });
    }

    /**
     * @param  array<int, int|null>  $desiredParent
     */
    private function assertValid(int $id, ?int $parentId, array $desiredParent): void
    {
        if ($parentId === null) {
            return;
        }

        if ($parentId === $id) {
            throw CategoryReorderException::selfParent();
        }

        // Le parent doit être une racine dans l'arbre final (profondeur ≤ 2).
        $parentFinalParent = array_key_exists($parentId, $desiredParent)
            ? $desiredParent[$parentId]
            : Category::query()->whereKey($parentId)->value('parent_id');

        if ($parentFinalParent !== null) {
            throw CategoryReorderException::depthExceeded();
        }

        // Une catégorie qui a des enfants ne peut pas devenir sous-catégorie.
        if (Category::query()->where('parent_id', $id)->exists()) {
            throw CategoryReorderException::parentHasChildren();
        }
    }
}
