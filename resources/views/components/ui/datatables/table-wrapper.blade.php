@props([
    'columns' => 'md:grid-cols-12',
    'label' => null,
])

<div
    {{ $attributes->merge(['class' => 'w-full']) }}
    data-role="datatable"
    role="table"
    @if($label) aria-label="{{ $label }}" @endif
>
    <div data-role="datatable-frame" class="overflow-visible rounded-lg border border-gray-200 bg-white shadow-sm">
        @isset($header)
            <div
                data-role="datatable-header"
                role="row"
                class="hidden {{ $columns }} items-center gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 md:grid"
            >
                {{ $header }}
            </div>
        @endisset

        <div
            data-role="datatable-body"
            role="rowgroup"
            class="space-y-3 bg-gray-50 p-3 md:space-y-0 md:divide-y md:divide-gray-100 md:bg-white md:p-0"
        >
            {{ $slot }}
        </div>
    </div>

    @isset($footer)
        <div data-role="datatable-footer" class="mt-4">
            {{ $footer }}
        </div>
    @endisset
</div>
