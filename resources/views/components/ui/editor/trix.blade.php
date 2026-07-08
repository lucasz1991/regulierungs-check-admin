@props([
    'wireModel',
    'value' => '',
    'placeholder' => 'Text schreiben...',
])

@php
    $safeWireKey = str_replace(['.', ':'], '-', $wireModel);
    $inputId = 'trix-input-' . $safeWireKey . '-' . uniqid();
@endphp

@script
<script>
    window.trixEditorFactory = window.trixEditorFactory || function (opts) {
        return {
            model: opts.model,
            initialValue: opts.initialValue || '',
            placeholder: opts.placeholder || '',
            internalChange: false,

            init() {
                this.$nextTick(() => {
                    const editor = this.$refs.editor;
                    const value = this.model || this.initialValue || '';

                    if (value && editor.editor) {
                        editor.editor.loadHTML(value);
                    }

                    editor.setAttribute('placeholder', this.placeholder);

                    editor.addEventListener('trix-change', () => {
                        this.internalChange = true;
                        this.model = editor.innerHTML || '';
                        this.$nextTick(() => this.internalChange = false);
                    });

                    editor.addEventListener('trix-attachment-add', (event) => {
                        event.preventDefault();
                        event.attachment.remove();
                    });

                    this.$watch('model', (next) => {
                        if (this.internalChange || !editor.editor) return;
                        const html = next || '';
                        if ((editor.innerHTML || '') !== html) {
                            editor.editor.loadHTML(html);
                        }
                    });
                });
            },
        };
    };
</script>
@endscript

<div
    x-data="trixEditorFactory({
        model: $wire.entangle(@js($wireModel)),
        initialValue: @js($value),
        placeholder: @js($placeholder),
    })"
    x-init="init()"
    class="trix-editor-wrapper"
    wire:ignore
>
    <input id="{{ $inputId }}" type="hidden" x-ref="input" :value="model || initialValue || ''">
    <trix-editor
        input="{{ $inputId }}"
        x-ref="editor"
        class="min-h-[280px] rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm shadow-sm"
    ></trix-editor>
</div>
