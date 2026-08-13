<!DOCTYPE html>
<html lang="de">
<head>
    @include('layouts.metahead')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Promotion | Regulierungs-Check</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 font-notosans text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a href="{{ route('promotion.console') }}" class="flex items-center gap-3 font-bold"><x-application-icon /><span>Promotion-Konsole</span></a>
            <div class="flex items-center gap-4 text-sm">
                <span class="hidden text-slate-500 sm:inline">{{ auth()->user()->name }} · {{ auth()->user()->currentTeam?->name }}</span>
                @if(auth()->user()->isAdmin())<a href="{{ route('admin.index') }}" class="font-semibold text-teal-700">Adminbereich</a>@endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="font-semibold text-slate-700 hover:text-red-700">Abmelden</button></form>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6">{{ $slot }}</main>
    @livewireScripts
</body>
</html>
