<a href="/" wire:navigate {{ $attributes->merge(['class' => 'dot-brand']) }}>
    <!-- Hidden when collapsed -->
    <div class="hidden-when-collapsed">
        <div class="dot-brand">
            <x-logo-icon class="w-10 h-10" />
            <span class="dot-brand-copy">
                <span class="dot-brand-name">DOT</span>
                <span class="dot-brand-product">Database Tools</span>
            </span>
        </div>
    </div>

    <!-- Display when collapsed -->
    <div class="display-when-collapsed hidden mx-5 mt-5 mb-1 h-[28px]">
        <x-logo-icon class="w-7 h-7" />
    </div>
</a>
