<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catégorie de charge (loyer, carburant, fournitures…).
 *
 * @property int $id
 * @property string $name
 * @property bool $is_active
 */
final class ExpenseCategory extends Model
{
    protected $fillable = ['name', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
