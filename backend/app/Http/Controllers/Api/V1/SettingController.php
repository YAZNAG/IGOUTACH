<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Access\Contracts\AuditLoggerInterface;
use App\Domain\Settings\Contracts\SettingsRepositoryInterface;
use App\Domain\Settings\SettingsCatalog;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SettingController extends Controller
{
    public function __construct(private readonly SettingsRepositoryInterface $settings) {}

    /**
     * @return array{data: array<string, array<string, string|int|bool>>, groups: list<string>}
     */
    public function index(): array
    {
        return [
            'data' => $this->settings->allGrouped(),
            'groups' => SettingsCatalog::GROUPS,
        ];
    }

    public function update(Request $request, AuditLoggerInterface $audit): JsonResponse
    {
        /** @var array<string, scalar|null> $values */
        $values = $request->validate(
            $this->rules(),
        );

        $this->settings->setMany($values);

        $audit->log(
            action: 'settings.updated',
            description: 'Paramètres généraux mis à jour : '.implode(', ', array_keys($values)),
            module: 'settings',
        );

        return response()->json(['data' => $this->settings->allGrouped()]);
    }

    /**
     * Règles de validation dérivées du catalogue (types).
     *
     * @return array<string, list<string>>
     */
    private function rules(): array
    {
        $rules = [];

        foreach (SettingsCatalog::DEFINITIONS as $key => $def) {
            $rules[$key] = match ($def['type']) {
                'bool' => ['sometimes', 'boolean'],
                'int' => ['sometimes', 'integer', 'min:0'],
                default => ['sometimes', 'nullable', 'string', 'max:1000'],
            };
        }

        return $rules;
    }
}
