<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;

it('réorganise l\'arbre en une seule requête', function () {
    $a = Category::factory()->create(['parent_id' => null, 'position' => 0]);
    $b = Category::factory()->create(['parent_id' => null, 'position' => 1]);

    $this->actingAs(grantUser(['category.update']))
        ->patchJson('/api/v1/categories/reorder', [
            'items' => [
                ['id' => $a->id, 'position' => 0, 'parent_id' => null],
                ['id' => $b->id, 'position' => 0, 'parent_id' => $a->id],
            ],
        ])
        ->assertOk();

    expect($b->refresh()->parent_id)->toBe($a->id);
    expect($a->refresh()->parent_id)->toBeNull();
});

it('refuse une profondeur supérieure à 2 niveaux', function () {
    $a = Category::factory()->create(['parent_id' => null]);
    $b = Category::factory()->create(['parent_id' => $a->id]);
    $c = Category::factory()->create(['parent_id' => null]);

    // Rattacher C sous B (qui a déjà un parent) → profondeur 3 refusée.
    $this->actingAs(grantUser(['category.update']))
        ->patchJson('/api/v1/categories/reorder', [
            'items' => [['id' => $c->id, 'position' => 0, 'parent_id' => $b->id]],
        ])
        ->assertStatus(422);

    expect($c->refresh()->parent_id)->toBeNull();
});

it('refuse qu\'une catégorie devienne son propre parent', function () {
    $a = Category::factory()->create(['parent_id' => null]);

    $this->actingAs(grantUser(['category.update']))
        ->patchJson('/api/v1/categories/reorder', [
            'items' => [['id' => $a->id, 'position' => 0, 'parent_id' => $a->id]],
        ])
        ->assertStatus(422);
});

it('refuse qu\'une famille ayant des enfants devienne sous-catégorie', function () {
    $a = Category::factory()->create(['parent_id' => null]);
    Category::factory()->create(['parent_id' => $a->id]); // A a un enfant
    $c = Category::factory()->create(['parent_id' => null]);

    $this->actingAs(grantUser(['category.update']))
        ->patchJson('/api/v1/categories/reorder', [
            'items' => [['id' => $a->id, 'position' => 0, 'parent_id' => $c->id]],
        ])
        ->assertStatus(422);

    expect($a->refresh()->parent_id)->toBeNull();
});
