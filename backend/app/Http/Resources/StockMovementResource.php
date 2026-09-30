<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Stock\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockMovement
 */
class StockMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'product_id' => $this->product_id,
            'movement_type_id' => $this->movement_type_id,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'balance_after' => $this->balance_after,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            // Numero lisible du document d'origine, pose par l'appelant quand
            // il a resolu les references. Absent, l'ecran retombe sur
            // reference_type / reference_id.
            'document' => $this->resource->getAttribute('document'),
            'note' => $this->note,
            // Formate dans le fuseau de l'application (Africa/Casablanca).
            // La serialisation par defaut de Carbon convertit en UTC : un
            // mouvement de 19:28 partait a 18:28Z, et l'ecran, qui lit les
            // chiffres tels quels, affichait une heure de moins.
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
            'movement_type' => $this->whenLoaded('movementType', function () {
                return [
                    'id' => $this->movementType->id,
                    'code' => $this->movementType->code,
                    'name' => $this->movementType->name,
                ];
            }),
            // Le code du type, à plat : l'écran s'en sert pour choisir le
            // signe et la pastille, et le lire à travers l'objet imbriqué
            // obligeait chaque appelant à charger la relation.
            'type' => $this->movementType?->code,
            'warehouse_name' => $this->warehouse?->name,
            'warehouse_code' => $this->warehouse?->code,
            // L'auteur peut manquer : import initial, ou compte supprimé
            // depuis. On renvoie null plutôt que d'omettre la clé, pour que
            // l'absence se lise au lieu de se deviner.
            'user' => $this->user !== null
                ? ['id' => $this->user->id, 'name' => $this->user->name]
                : null,
        ];
    }
}
