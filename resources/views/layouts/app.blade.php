<!DOCTYPE html>
<html lang="de" dir="ltr">
<head>
    @include('layouts.metahead')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Regulierungs-Check') }}</title>
    @include('layouts.head-css')
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="font-notosans antialiased">
    {{ $slot }}

    @include('layouts.vendor-scripts')
    @vite(['resources/js/app.js'])
    @livewireScripts
</body>
</html>
