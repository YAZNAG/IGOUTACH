<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Models;

use App\Domain\Settings\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Échéance mensuelle d'une charge fixe.
 */
final class RecurringExpenseOccurrence extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'recurring_expense_id',
        'period',
        'due_date',
        'amount',
        'status',
        'paid_at',
        'payment_method_id',
        'expense_id',
        'paid_by',
        'note',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'date',
        'amount' => 'decimal:2',
    ];

    public function recurringExpense(): BelongsTo
    {
        return $this->belongsTo(RecurringExpense::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /** Une échéance échue et non réglée doit rester signalée. */
    public function estEnRetard(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->due_date !== null
            && $this->due_date->isPast();
    }
}
