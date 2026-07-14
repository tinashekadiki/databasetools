<div class="dot-db-page">
    <x-header :title="__('Database Browser')" separator class="mb-3">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <x-button
                    :label="__('Manage servers')"
                    icon="o-server-stack"
                    link="{{ route('database-servers.index') }}"
                    class="btn-outline btn-sm"
                    wire:navigate
                />
                @if ($activeServer)
                    <x-button
                        :label="__('Reload')"
                        icon="o-arrow-path"
                        wire:click="openAdminer('{{ $activeServer->id }}')"
                        class="btn-ghost btn-sm"
                        spinner
                    />
                @endif
            </div>
        </x-slot:actions>
    </x-header>

    <div class="dot-db-workbench dot-db-workbench-{{ $sidebarSize }}">
        <aside class="dot-db-sidebar">
            <div class="dot-db-sidebar-header">
                <div>
                    <div class="dot-db-sidebar-title">{{ __('Connections') }}</div>
                    <div class="dot-db-sidebar-subtitle">{{ trans_choice('{0} No servers|{1} 1 server|[2,*] :count servers', $servers->count(), ['count' => $servers->count()]) }}</div>
                </div>
                <x-button
                    icon="o-plus"
                    link="{{ route('database-servers.create') }}"
                    class="btn-ghost btn-xs"
                    :tooltip-left="__('Add server')"
                    wire:navigate
                />
            </div>

            <div class="dot-db-search">
                <x-input
                    placeholder="{{ __('Filter connections') }}"
                    wire:model.live.debounce="search"
                    clearable
                    icon="o-magnifying-glass"
                    class="!input-sm"
                />
            </div>

            <nav class="dot-db-tree" aria-label="{{ __('Database connections') }}">
                @forelse ($servers as $server)
                    <button
                        type="button"
                        wire:key="browser-tree-server-{{ $server->id }}"
                        wire:click="openAdminer('{{ $server->id }}')"
                        wire:loading.attr="disabled"
                        wire:target="openAdminer"
                        class="dot-db-tree-item {{ $activeServer?->id === $server->id ? 'is-active' : '' }}"
                    >
                        <span class="dot-db-tree-icon">
                            <x-icon :name="$server->database_type->icon()" class="h-4 w-4" />
                        </span>
                        <span class="dot-db-tree-copy">
                            <span class="dot-db-tree-name">{{ $server->name }}</span>
                            <span class="dot-db-tree-meta">{{ $server->database_type->label() }} · {{ $server->getConnectionLabel() }}</span>
                        </span>
                        <span
                            class="dot-db-tree-loader"
                            wire:loading.inline-flex
                            wire:target="openAdminer('{{ $server->id }}')"
                            aria-label="{{ __('Loading connection') }}"
                        >
                            <span class="dot-db-mini-spinner"></span>
                        </span>
                    </button>
                @empty
                    <div class="dot-db-empty">
                        <x-icon name="o-table-cells" class="h-6 w-6" />
                        <p class="font-semibold">{{ __('No compatible servers') }}</p>
                        <p>{{ __('Add a MySQL, PostgreSQL, or SQLite server without SSH or agent routing.') }}</p>
                    </div>
                @endforelse
            </nav>
        </aside>

        <section class="dot-db-main">
            <div class="dot-db-toolbar">
                <div class="dot-db-tabstrip">
                    @if ($activeServer)
                        <div class="dot-db-tab is-active">
                            <x-icon :name="$activeServer->database_type->icon()" class="h-4 w-4" />
                            <span>{{ $activeServer->name }}</span>
                        </div>
                    @else
                        <div class="dot-db-tab">
                            <x-icon name="o-table-cells" class="h-4 w-4" />
                            <span>{{ __('No connection selected') }}</span>
                        </div>
                    @endif
                </div>

                <div class="dot-db-toolbar-actions">
                    <div class="dot-db-size-controls" aria-label="{{ __('Connection sidebar width') }}">
                        <span>{{ __('Sidebar') }}</span>
                        <button type="button" wire:click="setSidebarSize('narrow')" class="{{ $sidebarSize === 'narrow' ? 'is-active' : '' }}">S</button>
                        <button type="button" wire:click="setSidebarSize('standard')" class="{{ $sidebarSize === 'standard' ? 'is-active' : '' }}">M</button>
                        <button type="button" wire:click="setSidebarSize('wide')" class="{{ $sidebarSize === 'wide' ? 'is-active' : '' }}">L</button>
                    </div>
                    @if ($activeServer)
                        <span class="dot-db-connection-label">{{ $activeServer->getConnectionLabel() }}</span>
                        <x-button
                            :label="__('Query Console')"
                            icon="o-command-line"
                            wire:click="openQueryConsole"
                            class="btn-primary btn-xs"
                            spinner
                        />
                        <x-button
                            :label="__('Server details')"
                            icon="o-eye"
                            link="{{ route('database-servers.show', $activeServer) }}"
                            class="btn-ghost btn-xs"
                            wire:navigate
                        />
                    @endif
                </div>
            </div>

            <div class="dot-db-query-panel">
                <div class="dot-db-query-head">
                    <div class="dot-db-query-title">
                        <label for="dot-db-query">{{ __('SQL Query') }}</label>
                        <span>{{ $activeServer ? __('Editable query console for the selected connection') : __('Select a connection to run SQL') }}</span>
                    </div>
                    <div class="dot-db-query-actions">
                        <x-button
                            :label="__('Run Query')"
                            icon="o-play"
                            wire:click="openQueryConsole"
                            class="btn-primary btn-sm"
                            :disabled="!$activeServer"
                            spinner
                        />
                        <x-button
                            :label="__('Open Console')"
                            icon="o-command-line"
                            wire:click="openQueryConsole"
                            class="btn-outline btn-sm"
                            :disabled="!$activeServer"
                            spinner
                        />
                    </div>
                </div>
                <div class="dot-db-query-body">
                    <textarea
                        id="dot-db-query"
                        wire:model="queryText"
                        class="dot-db-query-input"
                        placeholder="SELECT * FROM table_name LIMIT 100;"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                        data-gramm="false"
                        data-gramm_editor="false"
                        data-enable-grammarly="false"
                        wire:loading.attr="disabled"
                        wire:target="openAdminer,openQueryConsole"
                        @disabled(! $activeServer)
                    ></textarea>
                </div>
            </div>

            <div
                class="dot-db-content {{ $activeServer && $adminerUrl ? 'is-frame-loading' : '' }}"
                wire:loading.class="is-loading"
                wire:target="openAdminer,openQueryConsole"
            >
                @if ($activeServer && $adminerUrl)
                    <iframe
                        wire:key="adminer-frame-{{ $activeServer->id }}-{{ $browserFrameVersion }}"
                        src="{{ $adminerUrl }}"
                        class="dot-db-frame"
                        title="{{ __('Adminer database browser for :server', ['server' => $activeServer->name]) }}"
                        onload="this.closest('.dot-db-content')?.classList.remove('is-frame-loading')"
                    ></iframe>
                @else
                    <div class="dot-db-start">
                        <x-icon name="o-circle-stack" class="h-10 w-10 text-base-content/30" />
                        <h2>{{ __('Select a connection') }}</h2>
                        <p>{{ __('Choose a database server from the left sidebar to open Adminer in this workspace.') }}</p>
                    </div>
                @endif

                <div
                    class="dot-db-loading-overlay"
                    role="status"
                    aria-live="polite"
                >
                    <span class="dot-db-spinner"></span>
                    <span>{{ __('Loading database workspace') }}</span>
                </div>
            </div>
        </section>
    </div>
</div>
