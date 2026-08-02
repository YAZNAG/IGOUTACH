<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Statut de bon de commande (lookup).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 */
final class PurchaseOrderStatus extends Model
{
    protected $table = 'purchase_order_statuses';

    protected $fillable = ['code', 'name'];

    public $timestamps = true;
}
