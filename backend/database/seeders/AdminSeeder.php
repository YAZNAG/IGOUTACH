<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Access\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Lu via config (compatible config:cache).
        $email = config('igoutech.admin.email');
        $password = config('igoutech.admin.password');

        if (! is_string($email) || ! is_string($password) || $email === '' || $password === '') {
            throw new RuntimeException('ADMIN_EMAIL et ADMIN_PASSWORD doivent être définis dans le .env.');
        }

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrateur IGOUTECH',
                'password' => Hash::make($password),
                'warehouse_id' => null, // accès non restreint à un lieu
                'is_active' => true,
            ],
        );

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $admin->roles()->syncWithoutDetaching([
            $adminRole->id => ['assigned_at' => now()],
        ]);
    }
}
