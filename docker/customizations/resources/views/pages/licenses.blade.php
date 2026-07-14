<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Licenses') }} - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}/">
    @include('layouts._theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('dot-enterprise.css') }}?v={{ filemtime(public_path('dot-enterprise.css')) }}">
</head>
<body class="dot-app-shell min-h-screen font-sans antialiased bg-base-200">
<main class="min-h-screen px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <div class="flex items-center justify-between gap-4">
            <x-app-brand />
            <a href="{{ route('home') }}" class="btn btn-ghost btn-sm" wire:navigate>
                {{ __('Back') }}
            </a>
        </div>

        <x-card shadow>
            <div class="space-y-6">
                <div>
                    <p class="text-sm font-semibold uppercase text-primary">{{ __('Licenses') }}</p>
                    <h1 class="mt-2 text-3xl font-bold text-base-content">{{ config('app.name') }}</h1>
                    <p class="mt-3 text-base text-base-content/70">
                        {{ __('Developed by DOT (dots.co.zw) while preserving the upstream Databasement project attribution and license references.') }}
                    </p>
                </div>

                <div class="rounded-lg border border-base-300 bg-base-200 p-5">
                    <p class="text-base text-base-content">
                        Made with <span class="text-error">&#10084;</span> by
                        <a href="https://crty.dev" target="_blank" rel="noopener" class="link link-hover font-semibold">David-Crty</a>
                    </p>
                    <a href="{{ config('app.github_repo') }}" target="_blank" rel="noopener" class="link link-hover mt-3 inline-flex items-center gap-2">
                        <x-bi-github class="h-4 w-4" />
                        github.com/David-Crty/databasement
                    </a>
                </div>

                <div class="space-y-3 text-sm text-base-content/70">
                    <p>
                        {{ __('Databasement is distributed under the MIT License. Review the upstream license for the original project terms.') }}
                    </p>
                    <a href="{{ config('app.github_repo') }}/blob/main/LICENSE" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                        {{ __('View MIT License') }}
                    </a>
                </div>
            </div>
        </x-card>
    </div>
</main>
<x-toast />
@livewireScripts
</body>
</html>
