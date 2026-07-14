<?php

namespace App\Http\Controllers\Web;

use App\Enums\DatabaseType;
use App\Http\Controllers\Controller;
use App\Models\DatabaseServer;
use App\Models\User;
use App\Services\AdminerService;
use Illuminate\Support\Facades\Gate;

class AdminerController extends Controller
{
    public function __invoke(AdminerService $adminer)
    {
        Gate::authorize('adminer', DatabaseServer::class);

        $credentials = null;
        $serverId = session()->pull('adminer_server_id');
        $selectDatabase = (bool) session()->pull('adminer_select_database', true);

        if ($serverId) {
            /** @var DatabaseServer $server */
            $server = DatabaseServer::findOrFail($serverId);
            abort_unless($server->supportsAdminer(), 403);

            $credentials = $this->buildCredentials($server, $selectDatabase);
            $_POST['auth'] = $credentials;
        }

        // Adminer can keep a request open while loading sub-resources through
        // the same route. Release Laravel's session lock before rendering it.
        session()->save();

        $content = $adminer->render($credentials);

        if (app()->runningInConsole()) {
            return response($content);
        }

        echo $content;
        exit;
    }

    /**
     * @return array{driver: string, server: string, username: string, password: string, db: string}
     */
    private function buildCredentials(DatabaseServer $server, bool $selectDatabase): array
    {
        $driver = match ($server->database_type) {
            DatabaseType::MYSQL => 'server',
            DatabaseType::POSTGRESQL => 'pgsql',
            DatabaseType::SQLITE => 'sqlite',
        };

        $serverAddress = $server->database_type === DatabaseType::SQLITE
            ? ''
            : $server->host.':'.$server->port;

        $db = '';
        if ($selectDatabase) {
            $databaseNames = $server->resolveDatabaseNames();
            if (count($databaseNames) === 1) {
                $db = $databaseNames[0];
            }
        }

        $user = auth()->user();
        $useDemoCredentials = $user instanceof User && $user->isDemo();

        return [
            'driver' => $driver,
            'server' => $serverAddress,
            'username' => $useDemoCredentials
                ? (string) config('services.adminer.demo_username')
                : ($server->username ?? ''),
            'password' => $useDemoCredentials
                ? (string) config('services.adminer.demo_password')
                : $server->getDecryptedPassword(),
            'db' => $db,
        ];
    }
}
