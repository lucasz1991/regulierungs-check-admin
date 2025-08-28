<div  {!! $attributes->merge(['class' => 'relative w-full overflow-hidden rounded-md border border-sky-500 bg-sky-50 text-on-surface md:w-fit ']) !!}  role="alert">
    <div class="text-left flex w-full items-start gap-2 bg-info/10 p-4">
        <div class="bg-sky-500/15 text-sky-500 rounded-full p-1" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-6" aria-hidden="true">
                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-2">
            <span class="sr-only">Info</span>
            <p class="text-xs font-medium sm:text-sm">
                <span class="block sm:inline">{{ $slot }} </span>
            </p>
        </div>
    </div>
</div>
