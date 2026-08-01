<?php

declare(strict_types=1);

namespace App\Domain\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Paramètre clé/valeur groupé.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string $group
 * @property string $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'type'];
}
