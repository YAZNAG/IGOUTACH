<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Models;

use App\Domain\Warehouses\Models\Warehouse;
use App\Support\Scopes\WarehouseScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Charge fixe : saisie une fois, due chaque mois.
 */
final class RecurringExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'expense_category_id',
        'warehouse_id',
        'amount',
        'day_of_month',
        'start_period',
        'end_period',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'day_of_month' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Une charge propre à un lieu ne regarde que ce lieu. Les charges de
        // société (warehouse_id nul) échappent au filtre : elles concernent
        // tout le monde et ne doivent pas disparaître pour un responsable.
        static::addGlobalScope(new WarehouseScope(includeNull: true));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(RecurringExpenseOccurrence::class);
    }

    /**
     * Date d'échéance du mois donné.
     *
     * Le jour est ramené au dernier jour du mois quand il le dépasse : « le
     * 31 » doit tomber le 28 en février, pas déborder sur mars.
     */
    public function dueDateFor(string $period): Carbon
    {
        $debut = Carbon::createFromFormat('Y-m-d', $period.'-01')->startOfDay();

        return $debut->copy()->day(min($this->day_of_month, $debut->daysInMonth));
    }
}
