<?php

declare(strict_types=1);

namespace App\Domain\Access\DTOs;

/**
 * Données d'entrée pour créer ou mettre à jour un utilisateur.
 */
final readonly class UserData
{
    /**
     * @param  list<int>  $roleIds
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public ?int $warehouseId,
        public array $roleIds,
        public bool $isActive = true,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var list<int> $roleIds */
        $roleIds = array_map('intval', $data['role_ids'] ?? []);

        return new self(
            name: (string) $data['name'],
            email: (string) $data['email'],
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            warehouseId: isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null,
            roleIds: $roleIds,
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }
}
