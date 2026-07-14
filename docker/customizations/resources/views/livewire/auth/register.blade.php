<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <div class="dot-login-header">
            <span class="dot-enterprise-badge">{{ __('Initial setup') }}</span>
            <h1>{{ __('Create the first operator account') }}</h1>
            <p>{{ __('Set up controlled access for DOT Database Tools.') }}</p>
        </div>

        @if (session('status'))
            <x-alert class="alert-success" icon="o-check-circle">{{ session('status') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            <x-input
                name="name"
                label="{{ __('Name') }}"
                type="text"
                required
                autofocus
                autocomplete="name"
                placeholder="{{ __('Full name') }}"
            />

            <x-input
                name="email"
                label="{{ __('Email address') }}"
                type="email"
                required
                autocomplete="email"
                placeholder="operator@example.com"
            />

            <x-password
                name="password"
                label="{{ __('Password') }}"
                required
                autocomplete="new-password"
                placeholder="{{ __('Password') }}"
            />

            <x-password
                name="password_confirmation"
                label="{{ __('Confirm password') }}"
                required
                autocomplete="new-password"
                placeholder="{{ __('Confirm password') }}"
            />

            @if(\App\Models\User::count() === 0)
                <x-checkbox
                    name="create_demo_backup"
                    :label="__('Add this application database as a demo backup')"
                    :hint="__('Creates a local backup volume and schedules daily backups of this application database')"
                    checked
                />
            @endif

            <div class="flex items-center justify-end">
                <x-button type="submit" class="btn-primary w-full" label="{{ __('Create operator account') }}" data-test="register-user-button" />
            </div>
        </form>
    </div>
</x-layouts::auth>
