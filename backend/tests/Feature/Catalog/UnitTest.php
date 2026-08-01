<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Models\User;

function unitActor(string $permission): User
{
    return grantUser([$permission]);
}

it('liste les unités avec unit.view', function () {
    Unit::factory()->create(['code' => 'PCE', 'name' => 'Pièce']);

    $this->actingAs(unitActor('unit.view'))
        ->getJson('/api/v1/units')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'code', 'name', 'is_decimal', 'products_count']]]);
});

it('crée une unité avec unit.manage', function () {
    $this->actingAs(unitActor('unit.manage'))
        ->postJson('/api/v1/units', ['code' => 'MTR', 'name' => 'Mètre', 'is_decimal' => true])
        ->assertCreated()
        ->assertJsonPath('data.is_decimal', true);

    $this->assertDatabaseHas('units', ['code' => 'MTR', 'is_decimal' => true]);
});

it('refuse la création sans unit.manage', function () {
    $this->actingAs(unitActor('unit.view'))
        ->postJson('/api/v1/units', ['code' => 'MTR', 'name' => 'Mètre'])
        ->assertForbidden();
});

it('bloque la suppression d\'une unité utilisée et propose la désactivation', function () {
    $unit = Unit::factory()->create(['code' => 'PCE', 'name' => 'Pièce']);
    $category = Category::factory()->create();
    Product::factory()->create(['unit_id' => $unit->id, 'category_id' => $category->id]);

    $this->actingAs(unitActor('unit.manage'))
        ->deleteJson("/api/v1/units/{$unit->id}")
        ->assertStatus(422);

    $this->assertDatabaseHas('units', ['id' => $unit->id, 'is_active' => true]);
});

it('désactive une unité non utilisée', function () {
    $unit = Unit::factory()->create(['code' => 'LOT', 'name' => 'Lot']);

    $this->actingAs(unitActor('unit.manage'))
        ->deleteJson("/api/v1/units/{$unit->id}")
        ->assertOk();

    $this->assertDatabaseHas('units', ['id' => $unit->id, 'is_active' => false]);
});

it('autorise le changement is_decimal sans mouvement décimal', function () {
    $unit = Unit::factory()->create(['code' => 'MTR', 'name' => 'Mètre', 'is_decimal' => true]);

    $this->actingAs(unitActor('unit.manage'))
        ->putJson("/api/v1/units/{$unit->id}", ['code' => 'MTR', 'name' => 'Mètre', 'is_decimal' => false])
        ->assertOk()
        ->assertJsonPath('data.is_decimal', false);
});
