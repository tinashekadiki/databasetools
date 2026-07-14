<?php

namespace App\Livewire\DatabaseBrowser;

use App\Enums\DatabaseType;
use App\Models\DatabaseServer;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Database Browser')]
class Index extends Component
{
    use AuthorizesRequests;

    #[Url]
    public string $search = '';

    #[Url(as: 'server')]
    public ?string $activeServerId = null;

    #[Url(as: 'sidebar')]
    public string $sidebarSize = 'standard';

    public string $adminerUrl = '';

    public string $queryText = '';

    public int $browserFrameVersion = 0;

    public function mount(): void
    {
        $this->authorize('adminer', DatabaseServer::class);

        if ($this->activeServerId) {
            $server = DatabaseServer::find($this->activeServerId);

            if ($server?->supportsAdminer()) {
                $this->openWorkspace($server, 'databases');
            } else {
                $this->activeServerId = null;
            }
        }
    }

    public function openAdminer(string $serverId): void
    {
        $server = DatabaseServer::findOrFail($serverId);

        $this->authorize('adminer', DatabaseServer::class);
        abort_unless($server->supportsAdminer(), 403);

        $this->openWorkspace($server, 'databases');
    }

    public function openQueryConsole(): void
    {
        if (! $this->activeServerId) {
            return;
        }

        $server = DatabaseServer::findOrFail($this->activeServerId);

        $this->authorize('adminer', DatabaseServer::class);
        abort_unless($server->supportsAdminer(), 403);

        $this->openWorkspace($server, 'sql', $this->queryText);
    }

    public function setSidebarSize(string $size): void
    {
        if (! in_array($size, ['narrow', 'standard', 'wide'], true)) {
            return;
        }

        $this->sidebarSize = $size;
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    public function render(): View
    {
        $servers = DatabaseServer::query()
            ->whereIn('database_type', [
                DatabaseType::MYSQL->value,
                DatabaseType::POSTGRESQL->value,
                DatabaseType::SQLITE->value,
            ])
            ->whereNull('ssh_config_id')
            ->whereNull('agent_id')
            ->when(
                $this->search !== '',
                fn ($query) => $query->where(function ($query): void {
                    $query
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('host', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                })
            )
            ->orderBy('name')
            ->get();

        $activeServer = $this->activeServerId
            ? $servers->firstWhere('id', $this->activeServerId)
            : null;

        return view('livewire.database-browser.index', [
            'servers' => $servers,
            'activeServer' => $activeServer,
        ]);
    }

    private function openWorkspace(DatabaseServer $server, string $mode, string $query = ''): void
    {
        session()->put('adminer_server_id', $server->id);
        session()->put('adminer_select_database', $mode !== 'databases');

        $this->activeServerId = $server->id;
        $this->browserFrameVersion++;

        $parameters = ['v' => $this->browserFrameVersion];

        if ($mode === 'sql') {
            $parameters['sql'] = $query;
        }

        $this->adminerUrl = url('/adminer').'?'.http_build_query($parameters);
    }
}
