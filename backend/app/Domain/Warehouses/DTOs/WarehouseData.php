<?php

declare(strict_types=1);

namespace App\Domain\Warehouses\DTOs;

/**
 * Données d'entrée pour créer ou mettre à jour un lieu.
 */
final readonly class WarehouseData
{
    public function __construct(
        public string $code,
        public string $name,
        public int $warehouseTypeId,
        public ?int $managerId = null,
        public ?int $parentId = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $phone = null,
        public bool $isActive = true,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: (string) $data['code'],
            name: (string) $data['name'],
            warehouseTypeId: (int) $data['warehouse_type_id'],
            managerId: isset($data['manager_id']) ? (int) $data['manager_id'] : null,
            parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            address: isset($data['address']) ? (string) $data['address'] : null,
            city: isset($data['city']) ? (string) $data['city'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'warehouse_type_id' => $this->warehouseTypeId,
            'manager_id' => $this->managerId,
            'parent_id' => $this->parentId,
            'address' => $this->address,
            'city' => $this->city,
            'phone' => $this->phone,
            'is_active' => $this->isActive,
        ];
    }
}
