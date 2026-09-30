<?php

declare(strict_types=1);

namespace App\Support\Pdf;

/**
 * Nombre de lignes de tableau qu'un PDF peut tenir, déduit de la mémoire
 * accordée à PHP (mesuré sur le serveur : environ 0,6 Mo par ligne au-delà
 * d'une base de 25 Mo, sur 75 % de la mémoire).
 */
final class PdfLimit
{
    public static function lignes(): int
    {
        $brut = trim((string) ini_get('memory_limit'));
        if ($brut === '' || $brut === '-1') {
            return 1000;
        }

        $valeur = (int) $brut;
        $mo = match (strtoupper(substr($brut, -1))) {
            'G' => $valeur * 1024,
            'M' => $valeur,
            'K' => intdiv($valeur, 1024),
            default => intdiv($valeur, 1048576),
        };

        return max(50, (int) floor(($mo * 0.75 - 25) / 0.6));
    }
}
