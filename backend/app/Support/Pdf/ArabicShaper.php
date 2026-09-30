<?php

declare(strict_types=1);

namespace App\Support\Pdf;

/**
 * Prépare du texte arabe pour DomPDF.
 *
 * DomPDF ne sait ni lier les lettres arabes ni écrire de droite à gauche : un
 * nom de client sortait en lettres isolées, dans le mauvais sens — ou en
 * « ????? » avec une police sans glyphes arabes. On fait donc ici les deux
 * travaux qu'il ne fait pas :
 *   1. la mise en forme : chaque lettre prend sa forme isolée, initiale,
 *      médiane ou finale (formes de présentation Unicode, présentes dans
 *      DejaVu Sans), avec les ligatures lam-alif ;
 *   2. l'ordre visuel : le texte est remis dans l'ordre d'affichage, les
 *      passages latins et les nombres gardant leur sens de lecture.
 *
 * Un texte sans lettre arabe ressort strictement inchangé.
 */
final class ArabicShaper
{
    /** Formes [isolée, finale, initiale, médiane] ; deux formes = lettre qui ne se lie pas à la suivante. */
    private const FORMES = [
        0x0621 => [0xFE80],
        0x0622 => [0xFE81, 0xFE82],
        0x0623 => [0xFE83, 0xFE84],
        0x0624 => [0xFE85, 0xFE86],
        0x0625 => [0xFE87, 0xFE88],
        0x0626 => [0xFE89, 0xFE8A, 0xFE8B, 0xFE8C],
        0x0627 => [0xFE8D, 0xFE8E],
        0x0628 => [0xFE8F, 0xFE90, 0xFE91, 0xFE92],
        0x0629 => [0xFE93, 0xFE94],
        0x062A => [0xFE95, 0xFE96, 0xFE97, 0xFE98],
        0x062B => [0xFE99, 0xFE9A, 0xFE9B, 0xFE9C],
        0x062C => [0xFE9D, 0xFE9E, 0xFE9F, 0xFEA0],
        0x062D => [0xFEA1, 0xFEA2, 0xFEA3, 0xFEA4],
        0x062E => [0xFEA5, 0xFEA6, 0xFEA7, 0xFEA8],
        0x062F => [0xFEA9, 0xFEAA],
        0x0630 => [0xFEAB, 0xFEAC],
        0x0631 => [0xFEAD, 0xFEAE],
        0x0632 => [0xFEAF, 0xFEB0],
        0x0633 => [0xFEB1, 0xFEB2, 0xFEB3, 0xFEB4],
        0x0634 => [0xFEB5, 0xFEB6, 0xFEB7, 0xFEB8],
        0x0635 => [0xFEB9, 0xFEBA, 0xFEBB, 0xFEBC],
        0x0636 => [0xFEBD, 0xFEBE, 0xFEBF, 0xFEC0],
        0x0637 => [0xFEC1, 0xFEC2, 0xFEC3, 0xFEC4],
        0x0638 => [0xFEC5, 0xFEC6, 0xFEC7, 0xFEC8],
        0x0639 => [0xFEC9, 0xFECA, 0xFECB, 0xFECC],
        0x063A => [0xFECD, 0xFECE, 0xFECF, 0xFED0],
        0x0640 => [0x0640, 0x0640, 0x0640, 0x0640],
        0x0641 => [0xFED1, 0xFED2, 0xFED3, 0xFED4],
        0x0642 => [0xFED5, 0xFED6, 0xFED7, 0xFED8],
        0x0643 => [0xFED9, 0xFEDA, 0xFEDB, 0xFEDC],
        0x0644 => [0xFEDD, 0xFEDE, 0xFEDF, 0xFEE0],
        0x0645 => [0xFEE1, 0xFEE2, 0xFEE3, 0xFEE4],
        0x0646 => [0xFEE5, 0xFEE6, 0xFEE7, 0xFEE8],
        0x0647 => [0xFEE9, 0xFEEA, 0xFEEB, 0xFEEC],
        0x0648 => [0xFEED, 0xFEEE],
        0x0649 => [0xFEEF, 0xFEF0],
        0x064A => [0xFEF1, 0xFEF2, 0xFEF3, 0xFEF4],
        0x067E => [0xFB56, 0xFB57, 0xFB58, 0xFB59],
        0x0686 => [0xFB7A, 0xFB7B, 0xFB7C, 0xFB7D],
        0x06A4 => [0xFB6A, 0xFB6B, 0xFB6C, 0xFB6D],
        0x06A9 => [0xFB8E, 0xFB8F, 0xFB90, 0xFB91],
        0x06AF => [0xFB92, 0xFB93, 0xFB94, 0xFB95],
        0x06CC => [0xFBFC, 0xFBFD, 0xFBFE, 0xFBFF],
    ];

    /** Lam suivi d'un alif : ligature [isolée, finale]. */
    private const LAM_ALIF = [
        0x0622 => [0xFEF5, 0xFEF6],
        0x0623 => [0xFEF7, 0xFEF8],
        0x0625 => [0xFEF9, 0xFEFA],
        0x0627 => [0xFEFB, 0xFEFC],
    ];

    private const MIROIRS = ['(' => ')', ')' => '(', '[' => ']', ']' => '[', '{' => '}', '}' => '{', '<' => '>', '>' => '<', '«' => '»', '»' => '«'];

    /**
     * Applique la mise en forme aux seuls textes d'un document HTML : balises,
     * attributs et feuilles de style ne sont jamais touchés.
     */
    public static function html(string $html): string
    {
        if (! self::contientArabe($html)) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/>([^<]+)</u',
            static function (array $m): string {
                if (! self::contientArabe($m[1])) {
                    return $m[0];
                }

                // Les entités (&amp;…) sont décodées avant le retournement, sans
                // quoi « &amp; » reviendrait en « ;pma& ».
                $texte = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                return '>'.htmlspecialchars(self::texte($texte), ENT_NOQUOTES, 'UTF-8').'<';
            },
            $html,
        ) ?: $html;
    }

    public static function texte(string $texte): string
    {
        if (! self::contientArabe($texte)) {
            return $texte;
        }

        // Ligne par ligne : l'ordre visuel ne doit pas mélanger deux lignes.
        return implode("\n", array_map(
            static fn (string $ligne): string => self::ordonner(self::lier(self::points($ligne))),
            explode("\n", $texte),
        ));
    }

    private static function contientArabe(string $s): bool
    {
        return preg_match('/[\x{0600}-\x{06FF}]/u', $s) === 1;
    }

    /** @return list<int> */
    private static function points(string $s): array
    {
        return array_values(array_map('mb_ord', mb_str_split($s, 1, 'UTF-8')));
    }

    /** Signes (voyelles courtes, chadda…) : transparents pour la liaison. */
    private static function estSigne(int $c): bool
    {
        return ($c >= 0x064B && $c <= 0x065F) || $c === 0x0670;
    }

    /**
     * @param  list<int>  $p
     * @return list<int>
     */
    private static function lier(array $p): array
    {
        $n = count($p);
        $sortie = [];

        for ($i = 0; $i < $n; $i++) {
            $c = $p[$i];
            if (! isset(self::FORMES[$c])) {
                $sortie[] = $c;

                continue;
            }

            // La lettre précédente (signes ignorés) se lie-t-elle vers l'avant ?
            $j = $i - 1;
            while ($j >= 0 && self::estSigne($p[$j])) {
                $j--;
            }
            $lieeAvant = $j >= 0 && isset(self::FORMES[$p[$j]]) && count(self::FORMES[$p[$j]]) === 4;

            $k = $i + 1;
            while ($k < $n && self::estSigne($p[$k])) {
                $k++;
            }
            $suivante = $k < $n ? $p[$k] : null;

            if ($c === 0x0644 && $suivante !== null && isset(self::LAM_ALIF[$suivante])) {
                $sortie[] = self::LAM_ALIF[$suivante][$lieeAvant ? 1 : 0];
                // Les signes posés sur le lam restent, l'alif est absorbé.
                for ($s = $i + 1; $s < $k; $s++) {
                    $sortie[] = $p[$s];
                }
                $i = $k;

                continue;
            }

            $formes = self::FORMES[$c];
            $lieeApres = count($formes) === 4 && $suivante !== null && isset(self::FORMES[$suivante]);

            $sortie[] = match (true) {
                count($formes) === 1 => $formes[0],
                $lieeAvant && $lieeApres => $formes[3],
                $lieeAvant => $formes[1],
                $lieeApres => $formes[2],
                default => $formes[0],
            };
        }

        return $sortie;
    }

    /**
     * Ordre visuel d'une ligne à dominante arabe : les segments sont inversés,
     * les lettres arabes aussi, les segments latins et les nombres gardent
     * leur ordre (« 3 », « H-1 », « 06 61 34 »).
     *
     * @param  list<int>  $p
     */
    private static function ordonner(array $p): string
    {
        $segments = [];
        $n = count($p);
        $i = 0;

        while ($i < $n) {
            if (self::estLatin($p[$i])) {
                // Un segment latin court jusqu'au dernier caractère latin : les
                // espaces et signes pris entre deux mots latins lui appartiennent.
                $fin = $i;
                for ($k = $i; $k < $n && ! self::estArabe($p[$k]); $k++) {
                    if (self::estLatin($p[$k])) {
                        $fin = $k;
                    }
                }
                $segments[] = [false, array_slice($p, $i, $fin - $i + 1)];
                $i = $fin + 1;

                continue;
            }

            $debut = $i;
            while ($i < $n && ! self::estLatin($p[$i])) {
                $i++;
            }
            $segments[] = [true, array_slice($p, $debut, $i - $debut)];
        }

        $sortie = '';
        foreach (array_reverse($segments) as [$droiteAGauche, $points]) {
            if ($droiteAGauche) {
                $points = array_reverse($points);
            }
            foreach ($points as $c) {
                $car = mb_chr($c, 'UTF-8');
                $sortie .= $droiteAGauche ? (self::MIROIRS[$car] ?? $car) : $car;
            }
        }

        return $sortie;
    }

    private static function estArabe(int $c): bool
    {
        return ($c >= 0x0600 && $c <= 0x06FF && ! ($c >= 0x0660 && $c <= 0x0669))
            || ($c >= 0xFB50 && $c <= 0xFDFF)
            || ($c >= 0xFE70 && $c <= 0xFEFF);
    }

    /** Lettres latines et chiffres : ils se lisent de gauche à droite. */
    private static function estLatin(int $c): bool
    {
        return ($c >= 0x30 && $c <= 0x39)
            || ($c >= 0x41 && $c <= 0x5A)
            || ($c >= 0x61 && $c <= 0x7A)
            || ($c >= 0x0660 && $c <= 0x0669)
            || ($c >= 0xC0 && $c <= 0x024F);
    }
}
