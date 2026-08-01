<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Access\Contracts\AuditLoggerInterface;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sessions actives (store de session en base) + déconnexion forcée.
 */
final class SessionController extends Controller
{
    public function __construct(private readonly AuditLoggerInterface $audit) {}

    /**
     * @return array{data: array<int, array<string, mixed>>}
     */
    public function index(Request $request): array
    {
        $currentId = $request->session()->getId();

        $sessions = DB::table('sessions')
            ->leftJoin('users', 'users.id', '=', 'sessions.user_id')
            ->select([
                'sessions.id',
                'sessions.user_id',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
                'users.name as user_name',
                'users.email as user_email',
            ])
            ->orderByDesc('sessions.last_activity')
            ->get()
            ->map(fn (\stdClass $row): array => [
                'id' => (string) $row->id,
                'user_id' => $row->user_id !== null ? (int) $row->user_id : null,
                'user_name' => $row->user_name,
                'user_email' => $row->user_email,
                'ip_address' => $row->ip_address,
                'user_agent' => $row->user_agent,
                'last_activity' => Carbon::createFromTimestamp((int) $row->last_activity)->toIso8601String(),
                'is_current' => (string) $row->id === $currentId,
            ])
            ->values()
            ->all();

        return ['data' => $sessions];
    }

    /**
     * Révoque une session précise (déconnexion forcée).
     */
    public function destroy(string $session): JsonResponse
    {
        $deleted = DB::table('sessions')->where('id', $session)->delete();

        if ($deleted > 0) {
            $this->audit->log(
                action: 'session.revoked',
                description: 'Session révoquée : '.$session,
                module: 'access',
            );
        }

        return response()->json(['message' => 'Session révoquée.']);
    }

    /**
     * Déconnecte toutes les sessions d'un utilisateur et révoque ses jetons API.
     */
    public function destroyForUser(User $user): JsonResponse
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->tokens()->delete();

        $this->audit->log(
            action: 'session.revoked_all',
            description: 'Toutes les sessions révoquées pour : '.$user->email,
            entityType: $user::class,
            entityId: $user->id,
            module: 'access',
        );

        return response()->json(['message' => 'Sessions de l\'utilisateur révoquées.']);
    }
}
