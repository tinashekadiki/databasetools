<div>
    <div class="max-w-5xl">
        <x-header
            :title="__('Preferences')"
            :subtitle="__('Manage language preferences for this operator account')"
            size="text-2xl"
            separator
            class="mb-4"
        />

        <x-card title="{{ __('Language') }}" subtitle="{{ __('Choose your preferred language') }}">
            <div class="flex flex-wrap gap-2">
                @foreach($availableLocales as $code => $label)
                    <button
                        wire:click="setLocale('{{ $code }}')"
                        wire:key="locale-{{ $code }}"
                        aria-pressed="{{ $locale === $code ? 'true' : 'false' }}"
                        class="btn btn-sm {{ $locale === $code ? 'btn-primary' : 'btn-outline' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </x-card>

        <x-card title="{{ __('Theme') }}" subtitle="{{ __('DOT Database Tools uses a managed enterprise theme.') }}" class="mt-4">
            <div class="text-sm text-base-content/70">
                {{ __('Theme selection is controlled centrally to keep the interface consistent across deployments.') }}
            </div>
        </x-card>
    </div>
</div>
