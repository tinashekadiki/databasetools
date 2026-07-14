@props(['class' => 'w-8 h-8'])

<span {{ $attributes->merge(['class' => 'dot-brand-mark '.$class]) }}>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" role="img" aria-label="DOT Database Tools">
        <rect x="7" y="7" width="26" height="26" rx="3" fill="#00563f" />
        <path d="M14 13h7c5.5 0 9 3.1 9 7s-3.5 7-9 7h-7V13Zm4 3.5v7h3c3 0 4.7-1.3 4.7-3.5s-1.7-3.5-4.7-3.5h-3Z" fill="#ffffff" />
    </svg>
</span>
