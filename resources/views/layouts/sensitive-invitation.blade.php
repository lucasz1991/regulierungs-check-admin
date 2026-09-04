<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>@yield('title') | Regulierungs-CHECK</title>
    <link rel="stylesheet" href="{{ URL::asset('build/css/tailwind.min.css') }}">
</head>
<body class="min-h-screen bg-slate-100 text-slate-950 antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-xl items-center px-4 py-8 sm:py-10">
        <section class="w-full rounded-3xl border border-slate-200 bg-white p-4 shadow-xl sm:p-7">
            @yield('content')
        </section>
    </main>
</body>
</html>
