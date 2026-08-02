<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Image d'un article (une principale au plus, secondaires ordonnées).
 *
 * @property int $id
 * @property int $product_id
 * @property string $path
 * @property bool $is_main
 * @property int $position
 */
final class ProductImage extends Model
{
    protected $fillable = ['product_id', 'path', 'is_main', 'position'];

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
        return ['is_main' => 'boolean', 'position' => 'integer'];
    }
}
