<div class="container-fluid">
    <div class="min-h-screen">
        <div class="grid grid-cols-1 md:grid-cols-12 ">
            <div class="col-span-12 md:col-span-5 lg:col-span-4 xl:col-span-3 relative z-50">
                <div class="w-full bg-white xl:p-12 p-10 dark:bg-zinc-800">
                    <div class="flex min-h-[90vh] flex-col gap-6">
                        <div class="mx-auto">
                            <a href="/"
                                class="flex items-center text-lg flex-shrink-0 font-bold dark:text-white leading-[69px]">
                                    <img src="{{ asset('site-images/logo/logo-icon.png') }}" alt="Regulierungs-CHECK" width="64" height="64" class="h-16 w-16 object-contain">
                            </a>
                        </div>
                            @yield('content')
                        <div class=" text-center">
                            <p class="text-gray-500 dark:text-gray-100 relative  select-none">©
                                <script>
                                    document.write(new Date().getFullYear())
                                </script> Regulierungs-CHECK · Adminbereich<br> Made by LMZ
                            </p>
                            <p class="text-gray-500 dark:text-gray-100 relative mb-5  select-none">
                                <small>v.0.1</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 md:col-span-7 lg:col-span-8 xl:col-span-9">
                    <x-auth-section-image-anim />
            </div>
        </div>
    </div>
</div>
