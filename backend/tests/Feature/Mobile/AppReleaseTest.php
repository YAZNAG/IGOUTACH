<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;

it('renvoie null quand aucune version n\'est publiée', function (): void {
    Storage::fake('public');

    // L'application doit continuer de fonctionner, pas afficher une erreur.
    $this->getJson('/api/v1/app/version')->assertOk()->assertJsonPath('data', null);
});

it('publie la version, son poids et son lien de téléchargement', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('app/igoutech.apk', str_repeat('x', 2048));
    Storage::disk('public')->put('app/version.json', json_encode([
        'version' => '1.1.0',
        'build' => 4,
        'file' => 'app/igoutech.apk',
        'notes' => 'Contrôle du stock à la saisie.',
        'mandatory' => false,
    ]));

    $data = $this->getJson('/api/v1/app/version')->assertOk()->json('data');

    expect($data['version'])->toBe('1.1.0')
        ->and($data['build'])->toBe(4)
        ->and($data['size'])->toBe(2048)
        ->and($data['url'])->toContain('app/igoutech.apk')
        ->and($data['mandatory'])->toBeFalse();
});

it('n\'annonce aucun lien quand le fichier manque', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('app/version.json', json_encode([
        'version' => '2.0.0', 'build' => 9,
    ]));

    // Annoncer une version sans fichier enverrait l'application télécharger
    // du vide : mieux vaut ne rien proposer.
    expect($this->getJson('/api/v1/app/version')->json('data.url'))->toBeNull();
});

it('reste accessible sans être connecté', function (): void {
    Storage::fake('public');

    // La mise à jour doit rester possible même si la session a expiré ou si
    // le compte a été désactivé entre-temps.
    $this->getJson('/api/v1/app/version')->assertOk();
});

it('signale une version obligatoire', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('app/igoutech.apk', 'x');
    Storage::disk('public')->put('app/version.json', json_encode([
        'version' => '1.2.0', 'build' => 5, 'mandatory' => true,
    ]));

    expect($this->getJson('/api/v1/app/version')->json('data.mandatory'))->toBeTrue();
});
