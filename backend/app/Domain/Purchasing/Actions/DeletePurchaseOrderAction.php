<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Domain\Purchasing\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Supprime un bon de commande qui n'a laissé aucune trace ailleurs.
 *
 * Deux cas seulement : le brouillon jamais envoyé, et le bon annulé. Le
 * reste de la vie d'un bon se raconte dans l'historique et ne s'efface pas —
 * un bon envoyé a été vu par le fournisseur, un bon reçu a mouvementé le
 * stock. L'annulation reste donc la porte normale ; la suppression ne sert
 * qu'à faire le ménage de ce qui n'aurait jamais dû exister.
 */
final class DeletePurchaseOrderAction
{
    public function execute(PurchaseOrder $order): void
    {
        $code = $order->status()->first()?->code;

        if (! in_array($code, ['draft', 'cancelled'], true)) {
            throw new RuntimeException(
                'Seul un bon de commande en brouillon ou annulé peut être supprimé. Annulez-le d’abord.'
            );
        }

        $obstacles = [];

        if (GoodsReceipt::query()->where('purchase_order_id', $order->id)->exists()) {
            $obstacles[] = 'une réception y est rattachée';
        }

        if ($order->lines()->where('received_quantity', '>', 0)->exists()) {
            $obstacles[] = 'des articles y ont déjà été reçus';
        }

        if ($obstacles !== []) {
            throw new RuntimeException(
                'Ce bon de commande ne peut pas être supprimé : '.implode(', ', $obstacles)
                .'. Il reste consultable.'
            );
        }

        // Les lignes partent en cascade côté base ; la transaction évite de
        // laisser un bon sans ses lignes si la suppression échoue à mi-chemin.
        DB::transaction(function () use ($order): void {
            $order->lines()->delete();
            $order->delete();
        });
    }
}
