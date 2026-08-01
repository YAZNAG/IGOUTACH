<?php

declare(strict_types=1);

namespace App\Providers\Domain;

use App\Domain\Settings\Contracts\SettingsRepositoryInterface;
use App\Domain\Settings\Repositories\SettingsRepository;
use Illuminate\Support\ServiceProvider;

final class SettingsServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        SettingsRepositoryInterface::class => SettingsRepository::class,
    ];
}
