@props([
    'columns' => 'md:grid-cols-12',
    'item' => null,
])

<div
    {{ $attributes->merge(['class' => 'grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-white px-4 py-4 text-sm shadow-sm transition hover:border-blue-200 hover:bg-blue-50 md:rounded-none md:border-0 md:shadow-none md:hover:border-transparent md:hover:bg-gray-50 ' . $columns . ' md:items-center md:gap-3 md:px-4 md:py-3']) }}
    data-role="datatable-item"
    @if(! is_null($item)) data-item="{{ $item }}" @endif
    role="row"
>
    {{ $slot }}
</div>
