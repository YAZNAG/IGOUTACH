<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $rank
 * @property int $min_quantity
 * @property bool $is_active
 */
class PriceType extends Model
{
    public const DETAIL = 'detail';

    public const SEMI_GROS = 'semi_gros';

    public const GROS = 'gros';

    protected $fillable = [
        'code',
        'name',
        'rank',
        'min_quantity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'min_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
