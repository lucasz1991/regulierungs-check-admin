@props([
    'href',
    'icon',
    'active' => false,
    'navigate' => true,
])

@php
    $classes = 'sidebar-nav-link';
    $label = html_entity_decode(trim(strip_tags((string) $slot)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
@endphp

<li class="sidebar-nav__item">
    <a
        href="{{ $href }}"
        data-menu-active="{{ $active ? 'true' : 'false' }}"
        title="{{ $label }}"
        @if($active) aria-current="page" @endif
        {{ $attributes->class([$classes, 'active' => $active]) }}
        @if($navigate) wire:navigate @endif
    >
        <span class="sidebar-nav-link__icon" aria-hidden="true">
            <i data-feather="{{ $icon }}" fill="#545a6d33"></i>
        </span>
        <span class="sidebar-nav-link__label">{{ $slot }}</span>
    </a>
</li>
