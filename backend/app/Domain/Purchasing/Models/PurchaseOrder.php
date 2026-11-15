<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Models;

use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Bon de commande fournisseur.
 *
 * @property int $id
 * @property string $number
 * @property int $supplier_id
 * @property int $warehouse_id
 * @property Carbon|null $ordered_at
 * @property Carbon|null $expected_at
 * @property int $status_id
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'supplier_id',
        'warehouse_id',
        'ordered_at',
        'expected_at',
        'status_id',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'expected_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<PurchaseOrderStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderStatus::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<PurchaseOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    /**
     * Filtrer par numéro.
     *
     * @param  Builder<self>  $query
     */
    public function scopeByNumber(Builder $query, string $number): Builder
    {
        return $query->where('number', $number);
    }

    /**
     * Filtrer par fournisseur.
     *
     * @param  Builder<self>  $query
     */
    public function scopeBySupplier(Builder $query, int $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Filtrer par statut.
     *
     * @param  Builder<self>  $query
     */
    public function scopeByStatus(Builder $query, string $statusCode): Builder
    {
        return $query->whereHas('status', fn (Builder $q) => $q->where('code', $statusCode));
    }

    /**
     * Filtrer les brouillons.
     *
     * @param  Builder<self>  $query
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->byStatus('draft');
    }

    /**
     * Filtrer les envoyés.
     *
     * @param  Builder<self>  $query
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->byStatus('sent');
    }

    /**
     * Vérifier si le bon peut être envoyé.
     *
     * Un brouillon sans ligne n'est pas une commande : on le garde pour que
     * son auteur le reprenne, mais on ne l'envoie pas au fournisseur.
     */
    public function canSend(): bool
    {
        return $this->status()->first()?->code === 'draft' && $this->nombreDeLignes() > 0;
    }

    /**
     * Vérifier si le bon peut être supprimé : seulement ce qui n'a laissé
     * aucune trace, c'est-à-dire un brouillon ou un bon annulé. Les
     * empêchements de détail (réception rattachée, articles reçus) sont
     * vérifiés à la suppression.
     */
    public function canDelete(): bool
    {
        return in_array($this->status()->first()?->code, ['draft', 'cancelled'], true);
    }

    /**
     * Le nombre de lignes, sans requête quand la liste l'a déjà compté.
     */
    private function nombreDeLignes(): int
    {
        $compte = $this->getAttribute('lines_count');

        return $compte !== null ? (int) $compte : $this->lines()->count();
    }

    /**
     * Vérifier si le bon peut être approuvé.
     */
    public function canApprove(): bool
    {
        return $this->status()->first()?->code === 'pending_approval';
    }

    /**
     * Vérifier si le bon peut recevoir des articles.
     */
    public function canReceive(): bool
    {
        $code = $this->status()->first()?->code;

        return in_array($code, ['sent', 'pending_approval', 'partially_received'], true);
    }

    /**
     * Vérifier si le bon peut être annulé.
     */
    public function canCancel(): bool
    {
        $code = $this->status()->first()?->code;

        return in_array($code, ['draft', 'sent', 'pending_approval'], true);
    }

    /**
     * @return Factory<self>
     */
    protected static function newFactory(): Factory
    {
        return PurchaseOrderFactory::new();
    }
}
