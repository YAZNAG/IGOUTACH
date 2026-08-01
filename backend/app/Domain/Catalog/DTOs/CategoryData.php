<?php

declare(strict_types=1);

namespace App\Domain\Catalog\DTOs;

final readonly class CategoryData
{
    public function __construct(
        public string $name,
        public bool $requiresSerial = false,
        public bool $isActive = true,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            requiresSerial: (bool) ($data['requires_serial'] ?? false),
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'requires_serial' => $this->requiresSerial,
            'is_active' => $this->isActive,
        ];
    }
}
