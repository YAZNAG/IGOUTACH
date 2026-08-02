<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nom d'attribut suggéré pour tous les articles d'une catégorie
 * (pré-remplissage de la fiche technique).
 *
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property int $position
 */
final class AttributeTemplate extends Model
{
    protected $fillable = ['category_id', 'name', 'position'];

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }
}
