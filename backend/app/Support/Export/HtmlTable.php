<?php

declare(strict_types=1);

namespace App\Support\Export;

/**
 * Génère un tableau HTML autonome (sans Blade) pour l'export PDF via dompdf.
 */
final class HtmlTable
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, scalar|null>>  $rows
     */
    public static function render(string $title, array $headings, array $rows): string
    {
        $head = implode('', array_map(
            static fn (string $h): string => '<th>'.e($h).'</th>',
            $headings,
        ));

        $body = '';
        foreach ($rows as $row) {
            $cells = implode('', array_map(
                static fn ($c): string => '<td>'.e((string) ($c ?? '')).'</td>',
                $row,
            ));
            $body .= "<tr>{$cells}</tr>";
        }

        $date = now()->format('d/m/Y H:i');
        $count = count($rows);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr"><head><meta charset="utf-8"><style>
            body { font-family: helvetica, sans-serif; color: #0f1b2d; font-size: 11px; }
            h1 { color: #0b2a5b; font-size: 16px; margin: 0 0 2px; }
            .meta { color: #647a99; font-size: 10px; margin-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #0b2a5b; color: #fff; text-align: left; padding: 5px 7px; font-size: 10px; }
            td { padding: 4px 7px; font-size: 10px; }
        </style></head><body>
            <h1>{$title}</h1>
            <div class="meta">IGOUTECH — {$count} ligne(s) — {$date}</div>
            <table><thead><tr>{$head}</tr></thead><tbody>{$body}</tbody></table>
        </body></html>
        HTML;
    }
}
