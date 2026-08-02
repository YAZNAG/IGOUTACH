<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Access\Models\Permission;
use App\Domain\Access\Models\Role;
use Illuminate\Database\Seeder;

final class RolePermissionSeeder extends Seeder
{
    /**
     * Matrice rôles → permissions (brief §9).
     * 'admin' reçoit toutes les permissions existantes.
     *
     * @var array<string, list<string>>
     */
    public const MATRIX = [
        'manager' => [
            'stock.view',
            'stock.issue',
            'stock.entry',
            'stock.adjust',
            'inventory.create',
            'inventory.approve',
            'user.view',
            'warehouse.view',
            'category.view',
            'product.view',
            'price.view',
            'unit.view',
            'brand.view',
            'tax_rate.view',
            'serial.view',
            'product.labels_print',
            'supplier.view',
            'supplier.create',
            'supplier.update',
            'customer.view',
            'customer.create',
            'customer.update',
            'customer.set_credit_limit',
            'purchase.create',
            'receipt.create',
            'transfer.create',
            'transfer.receive',
            'sale.create',
            'sale.cancel',
            'payment.view',
            'payment.create',
            'cash.manage',
            'expense.create',
            'expense.approve',
            'inventory.create',
            'audit.view',
            'settings.view',
            'payment_method.view',
        ],
        'seller' => [
            'stock.view',
            'serial.view',
            'customer.view',
            'customer.create',
            'sale.create',
            'payment.create',
            'expense.create',
        ],
    ];

    public function run(): void
    {
        $permissionIds = Permission::pluck('id', 'name');

        // admin : toutes les permissions
        $admin = Role::where('name', 'admin')->firstOrFail();
        $admin->permissions()->sync($permissionIds->values()->all());

        foreach (self::MATRIX as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->firstOrFail();
            $ids = collect($permissionNames)
                ->map(fn (string $name) => $permissionIds[$name] ?? null)
                ->filter()
                ->all();
            $role->permissions()->sync($ids);
        }
    }
}
