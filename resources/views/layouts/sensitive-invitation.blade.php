<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>@yield('title') | Regulierungs-Check</title>
    <link rel="stylesheet" href="{{ URL::asset('build/css/tailwind.min.css') }}">
</head>
<body class="min-h-screen bg-slate-100 text-slate-950 antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-lg items-center px-4 py-10">
        <section class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-lg sm:p-8">
            @yield('content')
        </section>
    </main>
</body>
</html>
