@extends('layouts.master-without-nav')
@section('title', 'Passwort vergessen')
@section('content')
<div class="my-auto">
    <div class="text-center mb-8">
        <h1 class="text-xl font-semibold text-gray-600 dark:text-gray-100">Passwort vergessen?</h1>
        <p class="text-gray-500 mt-2 dark:text-zinc-100/60">Fordere einen Reset-Link für deinen Mitarbeiterzugang bei Regulierungs-CHECK an.</p>
    </div>
    @if (session('status'))
        <p role="status" class="mb-4 text-sm text-green-600">{{ session('status') }}</p>
    @endif
    <form method="POST" action="{{ route('password.email') }}" class="mt-4 pt-2">
        @csrf
        <div class="mb-6">
            <label for="email" class="text-gray-600 font-medium mb-2 block dark:text-gray-100">E-Mail-Adresse</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                class="w-full border border-gray-300 rounded px-3 py-2 dark:bg-zinc-700/50 dark:border-zinc-600 dark:text-gray-100" placeholder="name@beispiel.de">
            @error('email') <p role="alert" class="text-sm text-red-600 mt-2">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="w-full rounded bg-blue-200 py-2.5 text-blue-500 font-semibold">Reset-Link anfordern</button>
    </form>
    <p class="mt-8 text-center"><a href="{{ route('login') }}" class="text-blue-500 underline">Zurück zur Anmeldung</a></p>
</div>
@endsection
