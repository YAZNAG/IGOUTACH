<?php

declare(strict_types=1);

namespace App\Domain\Store\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Réglages de la boutique, clé → valeur (nom, téléphone, frais de port…).
 *
 * @property string $key
 * @property string|null $value
 */
final class StoreSetting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    /** Les clés acceptées, et donc exposées : rien d'autre ne sort. */
    public const CLES = [
        'shop_name', 'phone', 'whatsapp', 'email', 'address',
        'shipping_fee', 'free_shipping_from', 'payment_cod', 'payment_transfer', 'bank_details',
        'announcement', 'hero_title', 'hero_subtitle',
    ];

    /** @return array<string, string> */
    public static function tous(): array
    {
        $valeurs = self::query()->whereIn('key', self::CLES)->pluck('value', 'key')->all();

        return array_map(fn ($v) => (string) ($v ?? ''), $valeurs);
    }

    public static function valeur(string $cle, string $defaut = ''): string
    {
        return (string) (self::query()->whereKey($cle)->value('value') ?? $defaut);
    }
}
