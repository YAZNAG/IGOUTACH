<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\Sale;
use App\Domain\Warehouses\Models\Warehouse;

function makeQuote(): Sale
{
    $quote = Sale::create([
        'reference' => 'DV-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
        'type' => Sale::TYPE_QUOTE,
        'status' => Sale::STATUS_DRAFT,
        'customer_id' => Customer::factory()->create()->id,
        'warehouse_id' => Warehouse::factory()->create()->id,
        'subtotal' => 200,
        'discount_percent' => 0,
        'total' => 200,
    ]);

    $quote->lines()->create([
        'product_id' => Product::factory()->create()->id,
        'quantity' => 4,
        'unit_price' => 50,
        'line_total' => 200,
    ]);

    return $quote;
}

it('convertit un devis en vente avec les mêmes lignes', function (): void {
    $user = grantUser(['sale.create']);
    $quote = makeQuote();

    $response = $this->actingAs($user)->postJson("/api/v1/sales/{$quote->id}/convert")->assertCreated();

    $invoiceId = $response->json('data.id');
    $invoice = Sale::findOrFail($invoiceId);

    expect($invoice->type)->toBe(Sale::TYPE_INVOICE)
        ->and($invoice->status)->toBe(Sale::STATUS_DRAFT)
        ->and($invoice->quote_id)->toBe($quote->id)
        ->and($invoice->customer_id)->toBe($quote->customer_id)
        ->and((float) $invoice->total)->toBe(200.0)
        ->and($invoice->lines()->count())->toBe(1)
        ->and($invoice->lines()->first()?->quantity)->toBe(4);
});

it('refuse de convertir deux fois le même devis', function (): void {
    $user = grantUser(['sale.create']);
    $quote = makeQuote();

    $this->actingAs($user)->postJson("/api/v1/sales/{$quote->id}/convert")->assertCreated();
    $this->actingAs($user)->postJson("/api/v1/sales/{$quote->id}/convert")->assertStatus(422);
});

it('refuse de convertir une facture', function (): void {
    $user = grantUser(['sale.create']);
    $quote = makeQuote();
    $quote->update(['type' => Sale::TYPE_INVOICE]);

    $this->actingAs($user)->postJson("/api/v1/sales/{$quote->id}/convert")->assertStatus(422);
});

it('signale les devis convertis dans la liste', function (): void {
    $user = grantUser(['sale.create']);
    $quote = makeQuote();
    $this->actingAs($user)->postJson("/api/v1/sales/{$quote->id}/convert")->assertCreated();

    $response = $this->actingAs($user)->getJson('/api/v1/sales?type=quote')->assertOk();
    $row = collect($response->json('data'))->firstWhere('id', $quote->id);

    expect($row['converted'])->toBeTrue();
});
