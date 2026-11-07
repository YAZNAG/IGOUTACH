<?php

declare(strict_types=1);

namespace App\Domain\Assistant\Exceptions;

use RuntimeException;

/**
 * L'assistant ne peut pas répondre : clé absente, service injoignable, quota.
 * Le message est destiné à l'écran, en français, sans détail technique inutile.
 */
final class AssistantIndisponibleException extends RuntimeException {}
