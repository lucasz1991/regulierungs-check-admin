<!DOCTYPE html>
<html lang="de">
<head>
    @include('layouts.metahead')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Promotion | Regulierungs-Check</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        :root { --console-ink:#062b31; --console-teal:#0d9187; --console-gold:#ffd166; }
        .promotion-console-bg { background-color:#edf4f3; background-image:linear-gradient(rgba(255,255,255,.45) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.45) 1px,transparent 1px); background-size:28px 28px; }
        .console-enter { animation:console-enter .5s cubic-bezier(.22,1,.36,1) both; }
        .console-panel { box-shadow:0 20px 60px -42px rgba(6,43,49,.48),inset 0 0 0 1px rgba(255,255,255,.68); }
        .console-scan-glow { animation:console-scan-glow 2.4s cubic-bezier(.22,1,.36,1) infinite; }
        @keyframes console-enter { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
        @keyframes console-scan-glow { 0%,100% { box-shadow:0 18px 36px -20px rgba(255,209,102,.5); } 50% { box-shadow:0 22px 52px -16px rgba(255,209,102,.72); } }
        @media (prefers-reduced-motion:reduce) { .console-enter,.console-scan-glow { animation:none!important; } }
    </style>
</head>
<body class="promotion-console-bg min-h-[100dvh] font-notosans text-slate-900">
    <header class="border-b border-white/80 bg-white/90 shadow-[0_1px_20px_rgba(6,43,49,.05)] backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a href="{{ route('promotion.console') }}" class="flex items-center gap-3 font-bold text-[#062b31]"><span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#062b31] text-white"><x-application-icon /></span><span>Promotion-Konsole</span></a>
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
