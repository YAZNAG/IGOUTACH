<?php

declare(strict_types=1);

namespace App\Support\Documents;

use Illuminate\Support\Facades\DB;

final class SequentialDocumentNumberGenerator implements DocumentNumberGeneratorInterface
{
    /**
     * Préfixes par défaut si la clé n'existe pas encore.
     *
     * @var array<string, string>
     */
    private const DEFAULT_PREFIXES = [
        'transfer' => 'TRF',
        'inventory' => 'INV',
        'sale' => 'VTE',
        'purchase' => 'CMD',
        'receipt' => 'BR',
    ];

    public function next(string $key): string
    {
        return DB::transaction(function () use ($key): string {
            /** @var object{prefix: string, current: int}|null $sequence */
            $sequence = DB::table('document_sequences')
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                $prefix = self::DEFAULT_PREFIXES[$key] ?? strtoupper(substr($key, 0, 3));
                $next = 1;
                DB::table('document_sequences')->insert([
                    'key' => $key,
                    'prefix' => $prefix,
                    'current' => $next,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $prefix = (string) $sequence->prefix;
                $next = (int) $sequence->current + 1;
                DB::table('document_sequences')
                    ->where('key', $key)
                    ->update(['current' => $next, 'updated_at' => now()]);
            }

            return sprintf('%s-%06d', $prefix, $next);
        });
    }
}
