@props([
    'mode' => 'info', // Standard: info (blau)
])

@php
    $colors = [
        'info' => [
            'border' => 'border-sky-500',
            'bg' => 'bg-sky-50',
            'iconBg' => 'bg-sky-500/15 text-sky-500',
        ],
        'warning' => [
            'border' => 'border-yellow-500',
            'bg' => 'bg-yellow-50',
            'iconBg' => 'bg-yellow-500/15 text-yellow-600',
        ],
        'success' => [
            'border' => 'border-green-500',
            'bg' => 'bg-green-50',
            'iconBg' => 'bg-green-500/15 text-green-600',
        ],
        'error' => [
            'border' => 'border-red-500',
            'bg' => 'bg-red-50',
            'iconBg' => 'bg-red-500/15 text-red-600',
        ],
    ];

    $c = $colors[$mode] ?? $colors['info'];
@endphp

<div {!! $attributes->merge(['class' => "relative w-full overflow-hidden rounded-md border {$c['border']} {$c['bg']} text-on-surface md:w-fit"]) !!} role="alert">
    <div class="text-left flex w-full items-start gap-2 p-4">
        <div class="{{ $c['iconBg'] }} rounded-full p-1" aria-hidden="true">
            {{-- Info-Icon --}}
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-6">
                <path fill-rule="evenodd"
                      d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z"
                      clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-2">
            <p class="text-xs font-medium sm:text-sm">
                <span class="block sm:inline">{{ $slot }}</span>
            </p>
        </div>
    </div>
</div>
