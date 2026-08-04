<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Purchasing\Actions\CreatePurchaseOrderAction;
use App\Domain\Purchasing\Actions\PaySupplierCreditAction;
use App\Domain\Purchasing\Actions\ReceiveGoodsAction;
use App\Domain\Purchasing\Actions\SendPurchaseOrderAction;
use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Sales\Actions\ConfirmSaleAction;
use App\Domain\Sales\Actions\RecordPaymentAction;
use App\Domain\Sales\Models\Sale;
use App\Domain\Settings\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Données transactionnelles de démonstration : bons de commande à tous les
 * stades, réceptions (payées / partielles / à crédit), devis (dont un
 * converti), ventes confirmées avec règlements variés et crédits clients.
 *
 * À lancer APRÈS le seed de base + DemoPricingSeeder + DemoStockSeeder :
 *   php artisan db:seed --class=Database\\Seeders\\TestDataSeeder
 */
final class DemoTransactionsSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent : ne recrée rien si les données de démo existent déjà.
        if (Sale::query()->where('reference', 'like', '%-DEMO-%')->exists()) {
            $this->command?->info('Données de démonstration déjà présentes — rien à refaire.');

            return;
        }

        $admin = User::query()->where('email', config('igoutech.admin.email'))->firstOrFail();
        $warehouseId = 1; // DEP-01 Dépôt principal
        $suppliers = Supplier::query()->take(3)->get();
        $customers = Customer::query()->where('is_blocked', false)->take(6)->get();
        $products = Product::query()->where('cost_price', '>', 0)->take(12)->get();

        if ($suppliers->isEmpty() || $customers->isEmpty() || $products->count() < 6) {
            $this->command?->warn('Référentiels insuffisants — lancez d\'abord le seed de base.');

            return;
        }

        $cash = PaymentMethod::firstOrCreate(['code' => 'especes'], ['name' => 'Espèces', 'is_active' => true]);
        $transfer = PaymentMethod::firstOrCreate(['code' => 'virement'], ['name' => 'Virement', 'is_active' => true]);

        $this->purchases($admin, $warehouseId, $suppliers, $products, $transfer);
        $this->sales($admin, $warehouseId, $customers, $products, $cash);

        $this->command?->info('Données de démonstration créées (achats, réceptions, crédits, devis, ventes).');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Supplier>  $suppliers
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     */
    private function purchases(User $admin, int $warehouseId, $suppliers, $products, PaymentMethod $method): void
    {
        $create = app(CreatePurchaseOrderAction::class);
        $send = app(SendPurchaseOrderAction::class);
        $receive = app(ReceiveGoodsAction::class);
        $pay = app(PaySupplierCreditAction::class);

        $lines = fn (int $offset, int $count) => $products->slice($offset, $count)
            ->map(fn (Product $p) => ['product_id' => $p->id, 'quantity' => random_int(10, 40)])
            ->values()
            ->all();

        // 1. Un bon en brouillon.
        $create->execute($suppliers[0]->id, $warehouseId, new \DateTime('+7 days'), 'Brouillon de démonstration', $admin->id, $lines(0, 3));

        // 2. Un bon envoyé, en attente de réception.
        $sent = $create->execute($suppliers[1]->id, $warehouseId, new \DateTime('+3 days'), null, $admin->id, $lines(3, 3));
        $send->execute($sent);

        // 3. Un bon partiellement reçu, réception NON payée (crédit fournisseur).
        $partial = $create->execute($suppliers[2]->id, $warehouseId, new \DateTime('-5 days'), null, $admin->id, $lines(6, 3));
        $send->execute($partial);
        $receive->execute(
            $partial->refresh(),
            now()->subDays(4)->format('Y-m-d'),
            'FA-DEMO-001',
            'Livraison partielle',
            $partial->lines()->get()->take(2)->map(fn ($l) => [
                'purchase_order_line_id' => $l->id,
                'quantity' => max(1, (int) floor($l->quantity / 2)),
                'unit_price' => round((float) ($l->product->cost_price ?? 10), 2),
            ])->values()->all(),
            $admin->id,
        );

        // 4. Un bon entièrement reçu ; réception réglée partiellement (reste en crédit).
        $full = $create->execute($suppliers[0]->id, $warehouseId, new \DateTime('-10 days'), null, $admin->id, $lines(9, 3));
        $send->execute($full);
        $receipt = $receive->execute(
            $full->refresh(),
            now()->subDays(9)->format('Y-m-d'),
            'FA-DEMO-002',
            null,
            $full->lines()->get()->map(fn ($l) => [
                'purchase_order_line_id' => $l->id,
                'quantity' => (int) $l->quantity,
                'unit_price' => round((float) ($l->product->cost_price ?? 10) * 1.05, 2),
            ])->values()->all(),
            $admin->id,
        );

        $receipt->refresh()->load('lines');
        $half = round($receipt->totalAmount() / 2, 2);
        if ($half > 0) {
            $pay->execute($receipt, $half, $method->id, now()->subDays(7)->format('Y-m-d'), 'Acompte 50 % (démo)', $admin->id);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Customer>  $customers
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     */
    private function sales(User $admin, int $warehouseId, $customers, $products, PaymentMethod $method): void
    {
        $confirm = app(ConfirmSaleAction::class);
        $payment = app(RecordPaymentAction::class);

        // Indexation circulaire : fonctionne même avec peu de clients.
        $cust = fn (int $i): Customer => $customers[$i % $customers->count()];

        $makeSale = function (string $type, ?int $customerId, int $offset, int $count) use ($admin, $warehouseId, $products): Sale {
            static $seq = 0;
            $seq++;

            $sale = Sale::create([
                'reference' => sprintf('%s-DEMO-%04d', $type === Sale::TYPE_QUOTE ? 'DV' : 'VT', $seq),
                'type' => $type,
                'status' => Sale::STATUS_DRAFT,
                'customer_id' => $customerId,
                'warehouse_id' => $warehouseId,
                'user_id' => $admin->id,
                'subtotal' => 0,
                'discount_percent' => 0,
                'total' => 0,
            ]);

            $subtotal = 0.0;
            foreach ($products->slice($offset, $count) as $product) {
                $qty = random_int(1, 8);
                $price = round((float) $product->cost_price * 1.3, 2);
                $sale->lines()->create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'price_type_code' => 'detail',
                    'line_total' => round($qty * $price, 2),
                ]);
                $subtotal += $qty * $price;
            }

            $sale->update(['subtotal' => round($subtotal, 2), 'total' => round($subtotal, 2)]);

            return $sale->refresh();
        };

        // Devis : un simple, un converti en vente confirmée.
        $makeSale(Sale::TYPE_QUOTE, $cust(0)->id, 0, 2);

        $quote = $makeSale(Sale::TYPE_QUOTE, $cust(1)->id, 2, 2);
        $converted = $makeSale(Sale::TYPE_INVOICE, $cust(1)->id, 2, 2);
        $converted->update(['quote_id' => $quote->id, 'note' => 'Issu du devis '.$quote->reference]);
        $confirm->execute($converted, $admin->id, true);
        $payment->execute([
            'customer_id' => $cust(1)->id,
            'amount' => (float) $converted->total,
            'payment_method_id' => $method->id,
            'sale_id' => $converted->id,
            'received_at' => now()->format('Y-m-d'),
            'note' => 'Règlement complet (démo)',
        ], $admin->id);

        // Vente confirmée payée partiellement → crédit client.
        $partialSale = $makeSale(Sale::TYPE_INVOICE, $cust(2)->id, 4, 3);
        $confirm->execute($partialSale, $admin->id, true);
        $payment->execute([
            'customer_id' => $cust(2)->id,
            'amount' => round((float) $partialSale->total / 3, 2),
            'payment_method_id' => $method->id,
            'sale_id' => $partialSale->id,
            'received_at' => now()->format('Y-m-d'),
            'note' => 'Acompte 1/3 (démo)',
        ], $admin->id);

        // Vente confirmée non payée → crédit client complet.
        $credit = $makeSale(Sale::TYPE_INVOICE, $cust(3)->id, 7, 2);
        $confirm->execute($credit, $admin->id, true);

        // Vente client de passage : payée comptant automatiquement.
        $walkIn = $makeSale(Sale::TYPE_INVOICE, null, 9, 2);
        $confirm->execute($walkIn, $admin->id, true);

        // Vente en brouillon (à confirmer depuis l'app).
        $makeSale(Sale::TYPE_INVOICE, $cust(4)->id, 5, 2);
    }
}
