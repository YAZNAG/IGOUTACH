<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property float $rate
 * @property string $label
 * @property bool $is_default
 * @property int $position
 * @property bool $is_active
 */
class TaxRate extends Model
{
    protected $fillable = [
        'rate',
        'label',
        'is_default',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
