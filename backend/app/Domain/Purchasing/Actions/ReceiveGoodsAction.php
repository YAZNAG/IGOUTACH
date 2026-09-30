<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderLine;
use App\Domain\Purchasing\Models\PurchaseOrderStatus;
use App\Domain\Stock\Contracts\StockWriterInterface;
use App\Domain\Stock\DTOs\StockMovementData;
use App\Support\Stock\MovementInstant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Réceptionne des marchandises sur un bon de commande :
 * crée le bon de réception (BR-YYYY-0001), incrémente le stock au prix
 * d'achat réel (recalcul CMUP par le mécanisme existant) et synchronise
 * les reliquats + le statut du bon de commande. Transaction unique.
 */
final class ReceiveGoodsAction
{
    public function __construct(
        private readonly StockWriterInterface $stockWriter,
    ) {}

    /**
     * @param  list<array{purchase_order_line_id: int, quantity: int, unit_price: float, over_receipt_reason?: string|null}>  $lines
     */
    public function execute(
        PurchaseOrder $order,
        string $receivedAt,
        ?string $invoiceNumber,
        ?string $notes,
        array $lines,
        ?int $createdBy,
        string $paymentStatus = GoodsReceipt::PAYMENT_UNPAID,
        float $amountPaid = 0.0
    ): GoodsReceipt {
        return DB::transaction(function () use ($order, $receivedAt, $invoiceNumber, $notes, $lines, $createdBy, $paymentStatus, $amountPaid): GoodsReceipt {
            if (! $order->canReceive()) {
                throw new RuntimeException('Ce bon de commande ne peut pas recevoir d\'articles.');
            }

            if ($lines === []) {
                throw new RuntimeException('Aucune ligne à réceptionner.');
            }

            $receivedAtDate = new \DateTimeImmutable($receivedAt);

            // 1. En-tête du bon de réception
            $receipt = GoodsReceipt::create([
                'number' => $this->generateNumber($receivedAtDate),
                'purchase_order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'warehouse_id' => $order->warehouse_id,
                'received_at' => $receivedAtDate->format('Y-m-d H:i:s'),
                'invoice_number' => $invoiceNumber,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            // 2. Lignes + entrées de stock + reliquats
            foreach ($lines as $position => $line) {
                $quantity = (int) $line['quantity'];
                $unitPrice = (float) ($line['unit_price'] ?? 0);
                $reason = $line['over_receipt_reason'] ?? null;

                if ($quantity <= 0) {
                    throw new RuntimeException('La quantité reçue doit être supérieure à zéro.');
                }

                if ($unitPrice <= 0) {
                    throw new RuntimeException('Le prix unitaire est obligatoire (supérieur à zéro) pour toute quantité reçue.');
                }

                /** @var PurchaseOrderLine $orderLine */
                $orderLine = PurchaseOrderLine::query()
                    ->whereKey((int) $line['purchase_order_line_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($orderLine->purchase_order_id !== $order->id) {
                    throw new RuntimeException("La ligne {$orderLine->id} n'appartient pas à ce bon de commande.");
                }

                // Sur-réception : au-delà du reliquat, un motif est exigé.
                if ($quantity > $orderLine->remaining() && ($reason === null || trim($reason) === '')) {
                    throw new RuntimeException(
                        "Sur-réception sur la ligne {$orderLine->id} (reliquat: {$orderLine->remaining()}) : un motif est obligatoire."
                    );
                }

                $receiptLine = $receipt->lines()->create([
                    'product_id' => $orderLine->product_id,
                    'purchase_order_line_id' => $orderLine->id,
                    'quantity' => $quantity,
                    'unit_price' => number_format($unitPrice, 2, '.', ''),
                    'over_receipt_reason' => $reason,
                    'position' => $position,
                ]);

                // Entrée de stock au prix réel, datée de la réception (CMUP recalculé).
                $this->stockWriter->increase(new StockMovementData(
                    warehouseId: $order->warehouse_id,
                    productId: $orderLine->product_id,
                    quantity: $quantity,
                    movementTypeCode: 'in',
                    unitCost: $unitPrice,
                    referenceType: 'goods_receipt',
                    referenceId: $receipt->id,
                    userId: $createdBy,
                    note: "Réception {$receipt->number}",
                    // `received_at` est la date metier de la reception ;
                    // le mouvement, lui, porte l'heure a laquelle il a ete
                    // ecrit, faute de quoi il se range a minuit et passe
                    // avant tout ce qui s'est produit dans la journee.
                    occurredAt: MovementInstant::resolve($receivedAtDate->format('Y-m-d H:i:s')),
                ));

                $orderLine->update([
                    'received_quantity' => $orderLine->received_quantity + $quantity,
                ]);

                $this->alignerPrixAchat($orderLine->product_id);

                unset($receiptLine);
            }

            // 3. Paiement fournisseur : payé / partiel (reste en crédit) / non payé.
            $this->applyPayment($receipt, $paymentStatus, $amountPaid);

            // 4. Statut du bon de commande (partially_received / received)
            $this->syncOrderStatus($order);

            return $receipt->refresh();
        });
    }

    /**
     * Reporte sur la fiche article le prix de la dernière réception.
     *
     * Le prix saisi au bon de réception EST le prix d'achat, que la facture
     * fournisseur soit réglée ou non : la marchandise a été reçue à ce
     * prix-là. Sans ce report, `products.cost_price` restait figé sur une
     * saisie manuelle — nul sur un article jamais renseigné — pendant que le
     * CMUP, lui, suivait la réalité ; les deux chiffres finissaient par se
     * contredire sur la fiche comme au tableau de bord.
     *
     * On relit la dernière réception en base plutôt que de prendre le prix
     * de la ligne courante : une réception saisie après coup pour une date
     * ancienne ne doit pas écraser un achat plus récent.
     */
    private function alignerPrixAchat(int $productId): void
    {
        $dernier = DB::table('goods_receipt_lines as l')
            ->join('goods_receipts as r', 'r.id', '=', 'l.goods_receipt_id')
            ->where('l.product_id', $productId)
            ->orderByDesc('r.received_at')
            ->orderByDesc('l.id')
            ->value('l.unit_price');

        if ($dernier === null) {
            return;
        }

        $prix = number_format((float) $dernier, 2, '.', '');

        DB::table('products')->where('id', $productId)->update([
            'cost_price' => $prix,
            'updated_at' => now(),
        ]);

        // Le stock des AUTRES lieux suit le même prix. Sans cela, le dépôt qui
        // n'a pas reçu garderait l'ancien coût, et la même marchandise vaudrait
        // deux prix selon l'endroit où elle se trouve.
        DB::table('stocks')->where('product_id', $productId)->update([
            'average_cost' => $prix,
            'updated_at' => now(),
        ]);
    }

    private function applyPayment(GoodsReceipt $receipt, string $paymentStatus, float $amountPaid): void
    {
        $receipt->load('lines');
        $total = $receipt->totalAmount();

        switch ($paymentStatus) {
            case GoodsReceipt::PAYMENT_PAID:
                // Payé intégralement à la réception.
                $amountPaid = $total;
                break;
            case GoodsReceipt::PAYMENT_PARTIAL:
                if ($amountPaid <= 0 || $amountPaid >= $total) {
                    throw new RuntimeException(
                        'Paiement partiel : le montant payé doit être supérieur à 0 et inférieur au total ('.number_format($total, 2, '.', '').' DH).'
                    );
                }
                break;
            case GoodsReceipt::PAYMENT_UNPAID:
                $amountPaid = 0.0;
                break;
            default:
                throw new RuntimeException('Statut de paiement invalide.');
        }

        $receipt->update([
            'payment_status' => $paymentStatus,
            'amount_paid' => number_format($amountPaid, 2, '.', ''),
        ]);
    }

    private function syncOrderStatus(PurchaseOrder $order): void
    {
        $orderLines = $order->lines()->get();

        $allReceived = $orderLines->every(
            fn (PurchaseOrderLine $line): bool => $line->received_quantity >= $line->quantity
        );
        $anyReceived = $orderLines->contains(
            fn (PurchaseOrderLine $line): bool => $line->received_quantity > 0
        );

        if ($allReceived) {
            $code = 'received';
        } elseif ($anyReceived) {
            $code = 'partially_received';
        } else {
            return;
        }

        $status = PurchaseOrderStatus::where('code', $code)->firstOrFail();
        $order->update(['status_id' => $status->id]);
    }

    private function generateNumber(\DateTimeImmutable $receivedAt): string
    {
        $year = (int) $receivedAt->format('Y');
        $prefix = "BR-{$year}-";

        $last = GoodsReceipt::where('number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if ($last !== null) {
            $lastNumber = (int) substr($last->number, strlen($prefix));
            $next = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $next = '0001';
        }

        return "{$prefix}{$next}";
    }
}
