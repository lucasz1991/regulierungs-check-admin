<div 
    x-data="{ focused: false, value: '' }" 
    x-cloak 
    class="relative"
    @click.away="focused = false"
>
    <table class="table-auto w-full mb-6">
        <thead>
            {{ $header }}
        </thead>
        <tbody>
            {{ $body }}
        </tbody>
    </table>
</div>
