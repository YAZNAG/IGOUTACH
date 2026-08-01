<?php

declare(strict_types=1);

namespace App\Domain\Stock\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 */
class TransferStatus extends Model
{
    public const IN_TRANSIT = 'in_transit';

    public const RECEIVED = 'received';

    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'code',
        'name',
    ];
}
