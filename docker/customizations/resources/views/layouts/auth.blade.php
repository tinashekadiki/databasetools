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
<body class="dot-auth-shell min-h-screen antialiased">
<div class="dot-auth-grid">
    <main class="dot-auth-card-wrap">
        <div class="dot-auth-card">
            <div class="mb-6">
                <x-app-brand />
            </div>
            {{ $slot }}
        </div>
    </main>
</div>
<x-toast />
@livewireScripts
</body>
</html>
