<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contact d'un fournisseur (nom, fonction, téléphone, e-mail).
 *
 * @property int $id
 * @property int $supplier_id
 * @property string $name
 * @property string|null $role
 * @property string|null $phone
 * @property string|null $email
 */
final class SupplierContact extends Model
{
    protected $fillable = ['supplier_id', 'name', 'role', 'phone', 'email'];

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
