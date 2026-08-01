<?php

declare(strict_types=1);

namespace App\Domain\Access\Contracts;

use App\Domain\Access\Models\AuditLog;

interface AuditLoggerInterface
{
    /**
     * Enregistre une entrée dans le journal d'audit.
     *
     * @param  array<string, mixed>|null  $changes
     */
    public function log(
        string $action,
        ?string $description = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $changes = null,
        ?string $module = null,
    ): AuditLog;
}
