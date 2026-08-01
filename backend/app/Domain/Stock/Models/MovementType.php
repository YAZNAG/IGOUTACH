<?php

declare(strict_types=1);

namespace App\Domain\Stock\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $sign
 * @property bool $affects_valuation
 */
class MovementType extends Model
{
    public const IN = 'in';

    public const OUT = 'out';

    public const TRANSFER_IN = 'transfer_in';

    public const TRANSFER_OUT = 'transfer_out';

    public const ADJUSTMENT = 'adjustment';

    public const RETURN_IN = 'return_in';

    public const RETURN_OUT = 'return_out';

    protected $fillable = [
        'code',
        'name',
        'sign',
        'affects_valuation',
    ];

    protected function casts(): array
    {
        return [
            'sign' => 'integer',
            'affects_valuation' => 'boolean',
        ];
    }
}
