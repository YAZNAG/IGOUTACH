<?php

declare(strict_types=1);

use App\Support\Stock\MovementInstant;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

it('pose l\'heure de saisie quand la date choisie est celle du jour', function (): void {
    Carbon::setTestNow('2026-09-04 20:31:07');

    expect(MovementInstant::resolve('2026-09-04'))->toBe('2026-09-04 20:31:07');
});

it('range le retour après la vente du même jour', function (): void {
    // Le cas réel : une vente à 20:23, puis un retour saisi à 20:31. Avec
    // minuit, le journal affichait le retour avant la vente.
    Carbon::setTestNow('2026-09-01 20:31:00');

    $retour = MovementInstant::resolve('2026-09-01');

    expect($retour)->toBeGreaterThan('2026-09-01 20:23:10');
});

it('laisse un document antidaté en tête de sa journée', function (): void {
    Carbon::setTestNow('2026-09-04 20:31:07');

    // L'heure de l'opération ce jour-là est inconnue : l'inventer placerait
    // le mouvement à un moment où rien ne s'est produit.
    expect(MovementInstant::resolve('2026-08-29'))->toBe('2026-08-29 00:00:00');
});

it('respecte une heure explicitement fournie sur une date passée', function (): void {
    Carbon::setTestNow('2026-09-04 20:31:07');

    expect(MovementInstant::resolve('2026-08-29 14:05:00'))->toBe('2026-08-29 14:05:00');
});

it('laisse le dépôt horodater quand aucune date n\'est donnée', function (): void {
    expect(MovementInstant::resolve(null))->toBeNull()
        ->and(MovementInstant::resolve(''))->toBeNull()
        ->and(MovementInstant::resolve('   '))->toBeNull();
});
