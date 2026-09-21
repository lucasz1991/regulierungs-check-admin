@extends('layouts.master-without-nav')
@section('title', 'Neues Passwort festlegen')
@section('content')
<div class="my-auto">
    <div class="text-center mb-6">
        <h1 class="text-xl font-semibold text-gray-600 dark:text-gray-100">Neues Passwort festlegen</h1>
        <p class="text-gray-500 mt-2 dark:text-gray-100/60">Wähle ein neues Passwort für deinen Mitarbeiterzugang bei Regulierungs-CHECK.</p>
    </div>
    <form method="POST" action="{{ route('password.update') }}" class="mt-4 pt-2">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        @foreach (['email' => 'E-Mail-Adresse', 'password' => 'Neues Passwort', 'password_confirmation' => 'Passwort bestätigen'] as $field => $label)
            <div class="mb-4">
                <label for="{{ $field }}" class="block mb-2 font-medium text-gray-600 dark:text-gray-100">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'password' }}"
                    @if ($field === 'email') value="{{ old('email', $request->email) }}" autocomplete="username"
                    @else autocomplete="new-password" minlength="10" @endif
                    required class="w-full rounded border border-gray-300 px-3 py-2 dark:bg-zinc-700/50 dark:border-zinc-600 dark:text-gray-100">
                @error($field) <p role="alert" class="text-sm text-red-600 mt-2">{{ $message }}</p> @enderror
            </div>
        @endforeach
        @error('token') <p role="alert" class="text-sm text-red-600 mb-4">{{ $message }}</p> @enderror
        <p class="mb-4 text-sm text-gray-500">Mindestens 10 Zeichen, ein Großbuchstabe und ein Sonderzeichen.</p>
        <button type="submit" class="w-full rounded bg-blue-200 py-2.5 text-blue-500 font-semibold">Passwort speichern</button>
    </form>
    <p class="mt-6 text-center"><a href="{{ route('password.request') }}" class="text-blue-500 underline">Neuen Reset-Link anfordern</a></p>
    <p class="mt-4 text-center"><a href="{{ route('login') }}" class="text-blue-500 underline">Zurück zur Anmeldung</a></p>
</div>
@endsection
