<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}/">
    @include('layouts._theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('dot-enterprise.css') }}?v={{ filemtime(public_path('dot-enterprise.css')) }}">
</head>
<body class="dot-app-shell min-h-screen font-sans antialiased bg-base-200">

{{-- NAVBAR mobile only --}}
<x-nav sticky class="lg:hidden">
    <x-slot:brand>
        <x-app-brand />
    </x-slot:brand>
    <x-slot:actions>
        <label for="main-drawer" class="lg:hidden me-3">
            <x-icon name="o-bars-3" class="cursor-pointer" />
        </label>
    </x-slot:actions>
</x-nav>

{{-- MAIN --}}
<x-main full-width>
    {{-- SIDEBAR --}}
    <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">
        <div class="flex flex-col h-full">
            {{-- BRAND --}}
            <x-app-brand class="px-5 pt-4" />
            <div class="dot-sidebar-kicker">
                Backup management
            </div>

            {{-- ORG SWITCHER --}}
            @if($user = auth()->user())
                @php
                    $showSwitcher = $user->isSuperAdmin() || $user->organizations()->count() > 1;
                @endphp
                @if($showSwitcher)
                    <div class="px-4 pt-3">
                        <livewire:organization-switcher />
                    </div>
                @endif
            @endif

            {{-- MAIN MENU --}}
            <x-menu activate-by-route class="flex-1">
                <x-menu-separator />
                <x-menu-item title="{{ __('Dashboard') }}" icon="o-home" link="{{ route('dashboard') }}" wire:navigate />
                <x-menu-item title="{{ __('Database Servers') }}" icon="o-server-stack" link="{{ route('database-servers.index') }}" wire:navigate />
                @can('adminer', \App\Models\DatabaseServer::class)
                    <x-menu-item title="{{ __('Database Browser') }}" icon="o-table-cells" link="{{ route('database-browser.index') }}" wire:navigate />
                @endcan
                <livewire:menu.snapshots-menu-item />
                <livewire:menu.restores-menu-item />
                <x-menu-item title="{{ __('Volumes') }}" icon="o-circle-stack" link="{{ route('volumes.index') }}" wire:navigate />
                @can('viewAny', \App\Models\User::class)
                    <x-menu-item title="{{ __('Users') }}" icon="o-users" link="{{ route('users.index') }}" wire:navigate />
                @endcan
                <x-menu-item :title="__('Agents')" icon="o-cpu-chip" :link="route('agents.index')" wire:navigate />
                <x-menu-separator />
                <x-menu-item :title="__('Configuration')" icon="o-cog-6-tooth" :link="route('configuration.application')" wire:navigate />
                <x-menu-item title="{{ __('API Docs') }}" no-wire-navigate="true" icon="o-document-text" link="{{ route('scramble.docs.ui') }}" />
                <x-menu-item title="{{ __('API Tokens') }}" icon="o-key" link="{{ route('api-tokens.index') }}" wire:navigate />
            </x-menu>

            {{-- USER SECTION AT BOTTOM --}}
            @if($user = auth()->user())
                <x-menu activate-by-route class="mt-auto" title="">
                    <x-menu-sub title="{{ $user->name }}"  icon="o-user">
                        <x-menu-item title="{{ __('Preferences') }}" icon="o-paint-brush" link="{{ route('preferences.edit') }}" wire:navigate />
                        @unless($user->isDemo())
                            <x-menu-item title="{{ __('Profile') }}" icon="o-user" link="{{ route('profile.edit') }}" wire:navigate />
                            @unless($user->isOAuth())
                                <x-menu-item title="{{ __('Password') }}" icon="o-key" link="{{ route('user-password.edit') }}" wire:navigate />
                                @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                                    <x-menu-item title="{{ __('Two-Factor Auth') }}" icon="o-shield-check" link="{{ route('two-factor.show') }}" wire:navigate />
                                @endif
                            @endunless
                        @endunless
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <x-button type="submit" class="w-full" icon="o-power">
                                {{ __('Logout') }}
                            </x-button>
                        </form>
                    </x-menu-sub>
                </x-menu>
            @endif
        </div>
    </x-slot:sidebar>

    {{-- The `$slot` goes here --}}
    <x-slot:content class="max-md:p-3">
        {{-- Demo mode banner --}}
        @if (config('app.demo_mode') && auth()->user()?->isDemo())
            <x-alert :title="__('You\'re in demo mode. Some data modifications are disabled.')" class="alert-warning mb-4" icon="o-eye" />
        @endif

        {{ $slot }}

        {{-- FOOTER --}}
        @php
            $githubRepo = config('app.github_repo');
            $githubRepoShort = trim(str_replace('https://', '', $githubRepo), '/');
        @endphp
        <footer class="dot-footer py-3">
            <div class="flex flex-col gap-2 text-sm text-base-content/60 sm:flex-row sm:items-center sm:justify-between">
                <span>
                    Developed by
                    <a href="https://dots.co.zw" target="_blank" rel="noopener" class="link link-hover font-semibold">DOT</a>
                    <span class="text-base-content/40">(dots.co.zw)</span>
                </span>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <a href="https://david-crty.github.io/databasement/" target="_blank" rel="noopener" class="link link-hover">
                        Documentation
                    </a>
                    <a href="{{ $githubRepo }}/issues/new" target="_blank" rel="noopener" class="link link-hover">
                        Report an issue
                    </a>
                    <a href="{{ route('licenses') }}" class="link link-hover" wire:navigate>
                        Licenses
                    </a>
                    @persist('version-status')
                        <livewire:version-status />
                    @endpersist
                </div>
            </div>
        </footer>
    </x-slot:content>
</x-main>

{{--  TOAST area --}}
<x-toast />
</body>
</html>
