<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderLine;
use App\Domain\Purchasing\Models\PurchaseOrderStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Met à jour la received_quantity d'une ligne et synchronise le statut du bon.
 */
final class ReceivePurchaseOrderLineAction
{
    /**
     * @param  array<int, int>  $quantitiesByLineId  Map de line_id => received_quantity
     */
    public function execute(PurchaseOrder $order, array $quantitiesByLineId): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $quantitiesByLineId): PurchaseOrder {
            if (! $order->canReceive()) {
                throw new RuntimeException('Ce bon de commande ne peut pas recevoir d\'articles.');
            }

            $hasPartial = false;
            $allReceived = true;

            foreach ($quantitiesByLineId as $lineId => $receivedQty) {
                $line = PurchaseOrderLine::with('product:id,sku,name')->findOrFail($lineId);

                // Les messages nommaient la ligne par son identifiant — « la
                // ligne 57 » —, un numero absent du bon papier. Celui qui
                // receptionne cherche un article, pas une ligne de base.
                $article = $this->nommer($line);

                if ($line->purchase_order_id !== $order->id) {
                    throw new RuntimeException("{$article} n'appartient pas à ce bon de commande.");
                }

                if ($receivedQty > $line->quantity) {
                    throw new RuntimeException(
                        "Impossible de recevoir {$receivedQty} unite(s) de {$article} : le bon n'en commande que {$line->quantity}."
                    );
                }

                $line->update(['received_quantity' => $receivedQty]);

                // Déterminer l'état global
                if ($receivedQty < $line->quantity && $receivedQty > 0) {
                    $hasPartial = true;
                }
                if ($receivedQty < $line->quantity) {
                    $allReceived = false;
                }
            }

            // Mettre à jour le statut du bon
            if ($allReceived) {
                $newStatus = PurchaseOrderStatus::where('code', 'received')->firstOrFail();
            } elseif ($hasPartial) {
                $newStatus = PurchaseOrderStatus::where('code', 'partially_received')->firstOrFail();
            } else {
                // Aucun article reçu, laisser le statut inchangé
                return $order->refresh();
            }

            $order->update(['status_id' => $newStatus->id]);

            return $order->refresh();
        });
    }

    /**
     * Nom de l'article d'une ligne, sa référence à défaut, son numéro en
     * dernier recours : mieux vaut un numéro qu'un message amputé.
     */
    private function nommer(PurchaseOrderLine $line): string
    {
        $libelle = trim((string) ($line->product?->name ?? ''));

        if ($libelle === '') {
            $libelle = trim((string) ($line->product?->sku ?? ''));
        }

        return $libelle !== '' ? '« '.$libelle.' »' : "la ligne #{$line->id}";
    }
}
