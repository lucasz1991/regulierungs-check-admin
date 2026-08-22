@props([
    'label' => null,
])

@if(filled($label))
    <li class="sidebar-nav__section" data-key="t-menu">
        {{ $label }}
    </li>
@endif

{{ $slot }}
