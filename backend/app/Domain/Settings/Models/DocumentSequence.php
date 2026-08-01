<?php

declare(strict_types=1);

namespace App\Domain\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Compteur de numérotation d'un type de document (BL, facture, inventaire…).
 *
 * @property int $id
 * @property string $key
 * @property string $prefix
 * @property int $current
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class DocumentSequence extends Model
{
    protected $fillable = ['key', 'prefix', 'current'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current' => 'integer',
        ];
    }
}
