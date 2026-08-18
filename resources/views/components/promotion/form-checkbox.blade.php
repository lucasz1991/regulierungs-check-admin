@props([
    'field',
    'label',
    'id' => null,
    'hint' => null,
    'accent' => false,
    'variant' => 'light',
    'wrapperClass' => '',
])

@php
    $controlId = $id ?: 'promotion-'.str_replace(['.', '[', ']'], '-', $field);
    $hasError = $errors->has($field);
    $hintId = $hint ? $controlId.'-hint' : null;
    $errorId = $hasError ? $controlId.'-error' : null;
    $describedBy = collect([$hintId, $errorId])->filter()->implode(' ');
    $isDark = $variant === 'dark';
@endphp

<div class="{{ $wrapperClass }}" data-promotion-field="{{ $field }}">
    <label
        for="{{ $controlId }}"
        @class([
            'group flex cursor-pointer gap-3 rounded-2xl border p-4 text-left transition duration-150',
            'border-red-300 bg-red-50' => $hasError && ! $isDark,
            'border-red-300/70 bg-red-400/10' => $hasError && $isDark,
            'border-white/15 bg-white/10 hover:border-white/25 hover:bg-white/[.14]' => ! $hasError && $isDark,
            'border-teal-200 bg-teal-50 hover:border-teal-300 hover:bg-teal-100/70' => ! $hasError && ! $isDark && $accent,
            'border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-slate-100' => ! $hasError && ! $isDark && ! $accent,
        ])
    >
        <input
            id="{{ $controlId }}"
            type="checkbox"
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            data-promotion-control="checkbox"
            {{ $attributes->class([
                'mt-0.5 h-5 w-5 shrink-0 rounded border-2 text-teal-700 shadow-sm focus:ring-4 focus:ring-teal-200',
                'border-white/40 bg-white/10' => $isDark,
                'border-slate-300 bg-white' => ! $isDark,
            ]) }}
        >
        <span class="min-w-0">
            <strong class="block text-sm font-extrabold {{ $isDark ? 'text-white' : 'text-slate-900' }}">{{ $label }}</strong>
            @if ($hint)
                <span id="{{ $hintId }}" class="mt-1 block text-xs leading-5 {{ $isDark ? 'text-teal-50/65' : 'text-slate-500' }}">{{ $hint }}</span>
            @endif
        </span>
    </label>

    @error($field)
        <p id="{{ $errorId }}" class="mt-2 text-sm font-semibold {{ $isDark ? 'text-red-200' : 'text-red-700' }}" role="alert">{{ $message }}</p>
    @enderror
</div>
