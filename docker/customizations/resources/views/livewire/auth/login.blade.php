@php
    $oauthOnlyMode = (bool) config('oauth.only_mode');
    $oauthProviders = app(\App\Services\OAuthService::class)->getEnabledProviders();
@endphp

<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <div class="dot-login-header">
            <span class="dot-enterprise-badge">{{ __('Access') }}</span>
            <h1>{{ __('Sign in') }}</h1>
            <p>
                @if ($oauthOnlyMode)
                    {{ __('Use your approved identity provider to continue.') }}
                @else
                    {{ __('Enter your credentials to continue.') }}
                @endif
            </p>
        </div>

        @if (config('app.demo_mode') && ! $oauthOnlyMode)
            <x-alert class="alert-info" icon="o-information-circle">
                {{ __('Demo mode: credentials are pre-filled. Just click Log in!') }}
            </x-alert>
        @endif

        @if (session('status'))
            <x-alert class="alert-success" icon="o-check-circle">{{ session('status') }}</x-alert>
        @endif

        @if (session('error'))
            <x-alert class="alert-error" icon="o-exclamation-circle">{{ session('error') }}</x-alert>
        @endif

        @error('email')
            <x-alert class="alert-error" icon="o-exclamation-circle">{{ $message }}</x-alert>
        @enderror

        @unless ($oauthOnlyMode)
            <x-form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
                @csrf

                <x-input
                    name="email"
                    label="{{ __('Email address') }}"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="operator@example.com"
                    value="{{ config('app.demo_mode') ? \App\Models\User::DEMO_EMAIL : old('email') }}"
                />

                <div class="relative">
                    <x-password
                        name="password"
                        label="{{ __('Password') }}"
                        required
                        autocomplete="current-password"
                        placeholder="{{ __('Password') }}"
                        value="{{ config('app.demo_mode') ? config('app.demo_user_password') : '' }}"
                    />

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="absolute top-0 text-sm end-0 link link-hover" wire:navigate>
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif
                </div>

                <x-checkbox name="remember" label="{{ __('Remember this device') }}" :checked="old('remember')" />

                <div class="flex items-center justify-end">
                    <x-button type="submit" class="btn-primary w-full" label="{{ __('Sign in') }}" data-test="login-button" />
                </div>
            </x-form>
        @endunless

        @if (count($oauthProviders) > 0)
            @unless ($oauthOnlyMode)
                <div class="divider">{{ __('or continue with') }}</div>
            @endunless

            <div class="flex flex-col gap-3">
                @foreach ($oauthProviders as $key => $provider)
                    <a href="{{ $provider['url'] }}" class="btn btn-outline w-full gap-2" data-test="oauth-{{ $key }}">
                        <x-icon name="{{ $provider['icon'] }}" class="w-5 h-5" />
                        {{ __('Continue with :provider', ['provider' => $provider['label']]) }}
                    </a>
                @endforeach
            </div>
        @elseif ($oauthOnlyMode)
            <x-alert class="alert-warning" icon="o-exclamation-triangle">
                {{ __('OAuth-only mode is enabled but no OAuth providers are configured. Please contact your administrator.') }}
            </x-alert>
        @endif
    </div>
</x-layouts::auth>
