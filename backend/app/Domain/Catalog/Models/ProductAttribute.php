<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne de fiche technique d'un article (nom → valeur).
 *
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $value
 * @property int $position
 */
final class ProductAttribute extends Model
{
    protected $fillable = ['product_id', 'name', 'value', 'position'];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }
}
