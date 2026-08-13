@extends('layouts.sensitive-invitation')

@section('title', 'Mitarbeitereinladung')

@section('content')
    <div class="my-auto">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-gray-800">Promotion-Zugang einrichten</h1>
            <p class="mt-2 text-sm text-gray-500">Team {{ $invitation->team->name }} · {{ $invitation->email }}</p>
        </div>

        <form method="POST" action="{{ route('staff-invitations.store') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Name</label>
                <input id="name" name="name" value="{{ old('name') }}" required autofocus class="w-full rounded-lg border border-gray-300 px-3 py-2">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Passwort</label>
                <input id="password" type="password" name="password" required class="w-full rounded-lg border border-gray-300 px-3 py-2">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Passwort wiederholen</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required class="w-full rounded-lg border border-gray-300 px-3 py-2">
            </div>
            <button class="w-full rounded-lg bg-teal-700 px-4 py-2.5 font-semibold text-white hover:bg-teal-800">Konto erstellen</button>
        </form>
    </div>
@endsection
