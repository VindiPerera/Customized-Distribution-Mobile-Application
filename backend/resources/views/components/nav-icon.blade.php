@props(['name'])

@php
    $paths = [
        'grid' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.3"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.3"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.3"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.3"/>',
        'receipt' => '<path d="M6 3h12v18l-2.5-1.5L13 21l-2.5-1.5L8 21l-2-1.5V3z" stroke-linejoin="round"/><path d="M9 8h6M9 12h6M9 16h3" stroke-linecap="round"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3.5 20c0-3.3 2.5-6 5.5-6s5.5 2.7 5.5 6" stroke-linecap="round"/><path d="M16 15c2.4.3 4.2 2.4 4.5 5" stroke-linecap="round"/><circle cx="16" cy="7.5" r="2.3"/>',
        'box' => '<path d="M3.5 8L12 3.5 20.5 8v8L12 20.5 3.5 16V8z" stroke-linejoin="round"/><path d="M3.5 8L12 12.5 20.5 8M12 12.5V20.5" stroke-linejoin="round"/>',
        'chart' => '<path d="M4 20V10M11 20V4M18 20v-7" stroke-linecap="round"/><path d="M3 20h18" stroke-linecap="round"/>',
        'badge' => '<circle cx="12" cy="9" r="3.5"/><path d="M8 12.5l-1.5 8L12 18l5.5 2.5-1.5-8" stroke-linejoin="round"/>',
        'theme' => '<path d="M12 3.5a8.5 8.5 0 100 17c-2-2-3-5-3-8.5s1-6.5 3-8.5z" stroke-linejoin="round"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 3.5v2M12 18.5v2M20.5 12h-2M5.5 12h-2M17.7 6.3l-1.4 1.4M7.7 16.3l-1.4 1.4M17.7 17.7l-1.4-1.4M7.7 7.7L6.3 6.3" stroke-linecap="round"/>',
        'logout' => '<path d="M9 21H5a1 1 0 01-1-1V4a1 1 0 011-1h4" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 17l5-5-5-5M21 12H9" stroke-linecap="round" stroke-linejoin="round"/>',
        'plus' => '<path d="M12 5v14M5 12h14" stroke-linecap="round"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="M20 20l-4.5-4.5" stroke-linecap="round"/>',
        'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/>',
        'tag' => '<path d="M11.5 3.5H5a1.5 1.5 0 00-1.5 1.5v6.5a1.5 1.5 0 00.44 1.06l8 8a1.5 1.5 0 002.12 0l6.5-6.5a1.5 1.5 0 000-2.12l-8-8a1.5 1.5 0 00-1.06-.44z" stroke-linejoin="round"/><circle cx="8.5" cy="8.5" r="1.25"/>',
        'truck' => '<path d="M3 6.5h11v9H3z" stroke-linejoin="round"/><path d="M14 10h3.5l3 3v2.5H14z" stroke-linejoin="round"/><circle cx="7" cy="17.5" r="1.6"/><circle cx="17" cy="17.5" r="1.6"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'w-5 h-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
    {!! $paths[$name] ?? '' !!}
</svg>
