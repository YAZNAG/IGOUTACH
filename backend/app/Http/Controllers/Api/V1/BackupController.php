<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Access\Contracts\AuditLoggerInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;

/**
 * Sauvegarde de la base : génère un dump SQL via mysqldump et le renvoie en
 * téléchargement. La restauration se fait par import de ce fichier (opération
 * sensible, réalisée hors ligne par un administrateur).
 */
final class BackupController extends Controller
{
    public function download(AuditLoggerInterface $audit): StreamedResponse|JsonResponse
    {
        /** @var array<string, mixed> $db */
        $db = Config::get('database.connections.'.Config::get('database.default'));

        $binary = (string) (Config::get('backup.mysqldump_path') ?? 'mysqldump');

        $arguments = [
            $binary,
            '--host='.(string) ($db['host'] ?? '127.0.0.1'),
            '--port='.(string) ($db['port'] ?? '3306'),
            '--user='.(string) ($db['username'] ?? 'root'),
            '--single-transaction',
            '--skip-lock-tables',
            (string) ($db['database'] ?? ''),
        ];

        $env = [];
        $password = (string) ($db['password'] ?? '');
        if ($password !== '') {
            $env['MYSQL_PWD'] = $password;
        }

        $process = new Process($arguments, null, $env === [] ? null : $env);
        $process->setTimeout(300.0);
        $process->run();

        if (! $process->isSuccessful()) {
            return response()->json([
                'message' => 'Sauvegarde impossible : mysqldump introuvable ou en erreur. '
                    .'Renseignez BACKUP_MYSQLDUMP_PATH le cas échéant.',
            ], 500);
        }

        $audit->log(action: 'system.backup', description: 'Sauvegarde de la base téléchargée', module: 'settings');

        $filename = 'igoutech-backup-'.now()->format('Ymd-His').'.sql';
        $sql = $process->getOutput();

        return response()->streamDownload(function () use ($sql): void {
            echo $sql;
        }, $filename, ['Content-Type' => 'application/sql']);
    }
}
