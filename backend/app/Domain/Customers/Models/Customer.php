<?php

declare(strict_types=1);

namespace App\Domain\Customers\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_company
 * @property string|null $contact_name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $city
 * @property string|null $ice
 * @property float $credit_limit
 * @property float $balance
 * @property bool $is_blocked
 * @property string|null $notes
 * @property bool $is_active
 */
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'is_company',
        'contact_name',
        'phone',
        'email',
        'address',
        'city',
        'ice',
        'credit_limit',
        'balance',
        'is_blocked',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_company' => 'boolean',
            'credit_limit' => 'decimal:2',
            'balance' => 'decimal:2',
            'is_blocked' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return CustomerFactory::new();
    }

    /**
     * Crédit disponible = plafond − encours.
     */
    public function availableCredit(): float
    {
        return round((float) $this->credit_limit - (float) $this->balance, 2);
    }
}
