<?php

declare(strict_types=1);

namespace App\Domain\Settings\Repositories;

use App\Domain\Settings\Contracts\SettingsRepositoryInterface;
use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\SettingsCatalog;
use Illuminate\Support\Facades\Cache;

final class SettingsRepository implements SettingsRepositoryInterface
{
    private const CACHE_KEY = 'settings:all';

    public function get(string $key): string|int|bool
    {
        return SettingsCatalog::cast($key, $this->raw()[$key] ?? null);
    }

    /**
     * {@inheritDoc}
     */
    public function allGrouped(): array
    {
        $raw = $this->raw();
        $grouped = [];

        foreach (SettingsCatalog::DEFINITIONS as $key => $def) {
            $grouped[$def['group']][$key] = SettingsCatalog::cast($key, $raw[$key] ?? null);
        }

        return $grouped;
    }

    /**
     * {@inheritDoc}
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! SettingsCatalog::isKnown($key)) {
                continue;
            }

            $def = SettingsCatalog::DEFINITIONS[$key];
            $stored = is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? '');

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $stored, 'group' => $def['group'], 'type' => $def['type']],
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Valeurs brutes stockées (clé => valeur string), cachées.
     *
     * @return array<string, string|null>
     */
    private function raw(): array
    {
        /** @var array<string, string|null> $raw */
        $raw = Cache::rememberForever(self::CACHE_KEY, fn (): array => Setting::query()->pluck('value', 'key')->all());

        return $raw;
    }
}
