<?php

declare(strict_types=1);

namespace App\Domain\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Mode de paiement paramétrable.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property bool $is_active
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class PaymentMethod extends Model
{
    /** Types de règlement supportés. */
    public const TYPES = ['cash', 'cheque', 'transfer', 'card', 'other'];

    protected $fillable = ['code', 'name', 'type', 'is_active', 'position'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }
}
