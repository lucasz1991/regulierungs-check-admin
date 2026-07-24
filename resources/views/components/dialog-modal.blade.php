@props(['id' => null, 'maxWidth' => null])

@php
    $dialogId = $id ?? md5($attributes->wire('model'));
    $titleId = "{$dialogId}-title";
@endphp

<x-modal :id="$dialogId" :maxWidth="$maxWidth" :labelledBy="$titleId" {{ $attributes }}>
    <div class="px-6 py-4">
        <div id="{{ $titleId }}" class="text-lg font-medium text-gray-900">
            {{ $title }}
        </div>
        <div class="mt-4 text-sm text-gray-600">
            {{ $content }}
        </div>
    </div>
    <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 text-end rounded-b-lg">
        {{ $footer }}
    </div>
</x-modal>
