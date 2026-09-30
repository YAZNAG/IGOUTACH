<?php

declare(strict_types=1);

use App\Support\Auth\IdentifiantConnexion;

it('reconnait une adresse a son arobase', function (): void {
    expect(IdentifiantConnexion::estEmail('admin@igoutech.ma'))->toBeTrue()
        ->and(IdentifiantConnexion::estEmail('0612345678'))->toBeFalse();
});

it('rapproche les ecritures d un meme numero', function (): void {
    $attendu = '612345678';

    foreach ([
        '0612345678',
        '06 12 34 56 78',
        '06-12-34-56-78',
        '+212612345678',
        '212 612 345 678',
        '00212612345678',
        ' 0612345678 ',
    ] as $ecriture) {
        expect(IdentifiantConnexion::normaliserTelephone($ecriture))
            ->toBe($attendu, "echec sur « {$ecriture} »");
    }
});

it('distingue deux lignes differentes', function (): void {
    expect(IdentifiantConnexion::normaliserTelephone('0612345678'))
        ->not->toBe(IdentifiantConnexion::normaliserTelephone('0612345679'));
});

it('refuse ce qui est trop court pour designer une ligne', function (): void {
    expect(IdentifiantConnexion::normaliserTelephone('12345'))->toBeNull()
        ->and(IdentifiantConnexion::normaliserTelephone(''))->toBeNull()
        ->and(IdentifiantConnexion::normaliserTelephone(null))->toBeNull()
        ->and(IdentifiantConnexion::normaliserTelephone('abcdefgh'))->toBeNull();
});

it('ne retient que les chiffres, quels que soient les separateurs', function (): void {
    expect(IdentifiantConnexion::normaliserTelephone('(+212) 6.12.34.56.78'))
        ->toBe('612345678');
});
