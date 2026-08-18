@props([
    'field',
    'label',
    'id' => null,
    'hint' => null,
    'required' => false,
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
            'block text-sm font-extrabold leading-5',
            'text-teal-100' => $isDark,
            'text-slate-800' => ! $isDark,
        ])
    >
        {{ $label }}
        @if ($required)
            <span class="{{ $isDark ? 'text-amber-300' : 'text-teal-700' }}" aria-hidden="true">*</span>
            <span class="sr-only">(Pflichtfeld)</span>
        @endif
    </label>

    <textarea
        id="{{ $controlId }}"
        aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        @if ($required) required aria-required="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        data-promotion-control="textarea"
        {{ $attributes->class([
            'mt-2 block w-full resize-y rounded-2xl border px-4 py-3 text-sm leading-6 shadow-sm outline-none transition duration-150 disabled:cursor-not-allowed disabled:opacity-60',
            'border-red-300 bg-red-50 text-slate-950 placeholder:text-red-300 focus:border-red-500 focus:ring-4 focus:ring-red-100' => $hasError && ! $isDark,
            'border-red-300/80 bg-red-400/10 text-white placeholder:text-red-200/50 focus:border-red-300 focus:ring-4 focus:ring-red-300/20' => $hasError && $isDark,
            'border-white/15 bg-white/10 text-white placeholder:text-white/35 hover:border-white/25 focus:border-teal-300 focus:ring-4 focus:ring-teal-300/20' => ! $hasError && $isDark,
            'border-slate-300 bg-white text-slate-950 placeholder:text-slate-400 hover:border-slate-400 focus:border-teal-600 focus:ring-4 focus:ring-teal-100' => ! $hasError && ! $isDark,
        ]) }}
    >{{ $slot }}</textarea>

    @if ($hint)
        <p id="{{ $hintId }}" class="mt-2 text-xs leading-5 {{ $isDark ? 'text-teal-50/65' : 'text-slate-500' }}">{{ $hint }}</p>
    @endif

    @error($field)
        <p id="{{ $errorId }}" class="mt-2 text-sm font-semibold {{ $isDark ? 'text-red-200' : 'text-red-700' }}" role="alert">{{ $message }}</p>
    @enderror
</div>
