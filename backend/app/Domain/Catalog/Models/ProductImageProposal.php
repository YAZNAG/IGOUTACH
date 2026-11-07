<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Proposition d'images de l'agent pour un article : des candidates, puis
 * la décision de l'administrateur (une retenue, ou aucune).
 *
 * @property int $id
 * @property int $product_id
 * @property string $query
 * @property list<array{url: string, thumbnail: string, title: string, source: string, width: int|null, height: int|null}> $candidates
 * @property string $status
 * @property int|null $chosen_index
 * @property int|null $image_id
 * @property int|null $created_by
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 */
final class ProductImageProposal extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'product_id', 'query', 'candidates', 'status', 'chosen_index', 'image_id',
        'created_by', 'reviewed_by', 'reviewed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'candidates' => 'array',
            'chosen_index' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductImage, $this> */
    public function image(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'image_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
