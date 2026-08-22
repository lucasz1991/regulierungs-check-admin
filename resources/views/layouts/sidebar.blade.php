<aside
    id="admin-sidebar"
    aria-label="Admin-Navigation"
    class="admin-sidebar fixed bottom-0 z-10 overflow-hidden ltr:border-r rtl:border-l vertical-menu rtl:right-0 ltr:left-0 top-[70px] bg-slate-50 border-gray-50 print:hidden"
>
    <div
        data-simplebar
        data-simplebar-auto-hide="false"
        data-simplebar-aria-label="Admin-Navigation"
        class="admin-sidebar-scroll h-full min-h-0 w-full"
    >
        @include('layouts.admin-sidebar')
    </div>
</aside>

<button
    type="button"
    class="admin-sidebar-backdrop print:hidden"
    data-sidebar-dismiss
    aria-label="Navigation schließen"
    aria-hidden="true"
    tabindex="-1"
></button>
