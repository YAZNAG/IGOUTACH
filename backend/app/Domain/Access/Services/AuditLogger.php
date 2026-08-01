<?php

declare(strict_types=1);

namespace App\Domain\Access\Services;

use App\Domain\Access\Contracts\AuditLoggerInterface;
use App\Domain\Access\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Écrit dans le journal d'audit en capturant l'acteur et le contexte HTTP courants.
 */
final class AuditLogger implements AuditLoggerInterface
{
    public function __construct(private readonly Request $request) {}

    /**
     * {@inheritDoc}
     */
    public function log(
        string $action,
        ?string $description = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $changes = null,
        ?string $module = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module ?? $this->moduleFromAction($action),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'changes' => $changes,
            'ip_address' => $this->request->ip(),
            'user_agent' => substr((string) $this->request->userAgent(), 0, 1000),
        ]);
    }

    private function moduleFromAction(string $action): string
    {
        return str_contains($action, '.') ? explode('.', $action)[0] : 'system';
    }
}
