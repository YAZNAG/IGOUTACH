<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Access\Contracts\PermissionResolverInterface;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Supprime les dérogations de permissions expirées et invalide le cache
 * des utilisateurs concernés. Planifiée toutes les heures.
 */
final class PurgeExpiredPermissions extends Command
{
    protected $signature = 'permissions:purge-expired';

    protected $description = 'Purge les dérogations de permissions expirées et invalide le cache';

    public function handle(PermissionResolverInterface $resolver): int
    {
        $expired = DB::table('user_permission')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get(['user_id', 'permission_id']);

        if ($expired->isEmpty()) {
            $this->info('Aucune dérogation expirée.');

            return self::SUCCESS;
        }

        DB::table('user_permission')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();

        /** @var list<int> $userIds */
        $userIds = $expired->pluck('user_id')->unique()->values()->all();

        User::query()->whereIn('id', $userIds)->get()->each(function (User $user) use ($resolver): void {
            $resolver->forget($user);
        });

        $this->info(sprintf('%d dérogation(s) expirée(s) purgée(s) pour %d utilisateur(s).', $expired->count(), count($userIds)));

        return self::SUCCESS;
    }
}
