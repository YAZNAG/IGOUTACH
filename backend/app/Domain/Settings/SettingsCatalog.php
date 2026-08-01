<?php

declare(strict_types=1);

namespace App\Domain\Settings;

/**
 * Catalogue des paramètres généraux : clé => {group, type, default}.
 * Ajouter un paramètre = ajouter une ligne ici (jamais un ENUM en dur ailleurs).
 */
final class SettingsCatalog
{
    /**
     * @var array<string, array{group: string, type: string, default: string}>
     */
    public const DEFINITIONS = [
        // Société
        'company_name' => ['group' => 'company', 'type' => 'string', 'default' => 'IGOUTECH'],
        'company_ice' => ['group' => 'company', 'type' => 'string', 'default' => ''],
        'company_rc' => ['group' => 'company', 'type' => 'string', 'default' => ''],
        'company_if' => ['group' => 'company', 'type' => 'string', 'default' => ''],
        'company_patente' => ['group' => 'company', 'type' => 'string', 'default' => ''],
        'company_address' => ['group' => 'company', 'type' => 'string', 'default' => ''],
        'company_city' => ['group' => 'company', 'type' => 'string', 'default' => ''],
        'company_phone' => ['group' => 'company', 'type' => 'string', 'default' => ''],
        'company_email' => ['group' => 'company', 'type' => 'string', 'default' => ''],

        // Règles de gestion
        'stock_valuation_method' => ['group' => 'rules', 'type' => 'string', 'default' => 'cmup'],
        'allow_negative_stock' => ['group' => 'rules', 'type' => 'bool', 'default' => '0'],
        'max_discount_percent' => ['group' => 'rules', 'type' => 'int', 'default' => '0'],

        // Modèles d'impression / en-têtes
        'print_header' => ['group' => 'print', 'type' => 'string', 'default' => ''],
        'print_footer' => ['group' => 'print', 'type' => 'string', 'default' => 'Merci de votre confiance.'],
        'print_show_logo' => ['group' => 'print', 'type' => 'bool', 'default' => '1'],

        // Général
        'locale' => ['group' => 'general', 'type' => 'string', 'default' => 'fr'],
        'currency' => ['group' => 'general', 'type' => 'string', 'default' => 'MAD'],
    ];

    /**
     * Groupes exposés (dans l'ordre d'affichage).
     *
     * @var list<string>
     */
    public const GROUPS = ['company', 'rules', 'print', 'general'];

    public static function isKnown(string $key): bool
    {
        return array_key_exists($key, self::DEFINITIONS);
    }

    /**
     * Convertit une valeur brute (string en base) vers son type déclaré.
     */
    public static function cast(string $key, ?string $raw): string|int|bool
    {
        $type = self::DEFINITIONS[$key]['type'] ?? 'string';
        $value = $raw ?? self::DEFINITIONS[$key]['default'] ?? '';

        return match ($type) {
            'bool' => $value === '1' || $value === 'true',
            'int' => (int) $value,
            default => $value,
        };
    }
}
