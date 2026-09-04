@extends('layouts.sensitive-invitation')

@section('title', 'Mitarbeiterzugang einrichten')

@section('content')
    <div>
        <div class="rounded-2xl bg-[#082f35] px-5 py-6 text-center text-white">
            <img src="{{ asset('site-images/logo/logo-white.png') }}" alt="Regulierungs-CHECK" class="mx-auto h-auto w-48 max-w-full">
            <p class="mt-5 text-xs font-bold uppercase tracking-[0.18em] text-[#8dd7d1]">Mitarbeiterzugang</p>
            <h1 class="mt-2 text-2xl font-bold">Zugang einrichten</h1>
            <p class="mt-2 text-sm leading-5 text-slate-200">Legen Sie einmalig Ihr Passwort fest. Danach werden Sie direkt angemeldet.</p>
        </div>

        <div class="mt-5 rounded-xl border border-teal-100 bg-teal-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">Ihre Zuordnung</p>
            <p class="mt-1 font-bold text-[#082f35]">Team {{ $invitation->team->name }}</p>
            <p class="mt-0.5 break-all text-sm text-slate-600">{{ $invitation->email }}</p>
            @if($invitation->position)
                <p class="mt-1 text-sm text-slate-600">Funktion: {{ $invitation->position }}</p>
            @endif
        </div>

        @if($errors->any())
            <div role="alert" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                Bitte prüfen Sie die markierten Eingaben.
            </div>
        @endif

        <form method="POST" action="{{ route('staff-invitations.store') }}" class="mt-5 space-y-4">
            @csrf
            <div>
                <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700">Vor- und Nachname</label>
                <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus class="min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2 text-slate-950 shadow-sm focus:border-teal-600 focus:ring-teal-600">
                @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Neues Passwort</label>
                <input id="password" type="password" name="password" autocomplete="new-password" required class="min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2 text-slate-950 shadow-sm focus:border-teal-600 focus:ring-teal-600">
                @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">Passwort wiederholen</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required class="min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2 text-slate-950 shadow-sm focus:border-teal-600 focus:ring-teal-600">
            </div>
            <button class="min-h-11 w-full rounded-xl bg-teal-700 px-4 py-2.5 font-semibold text-white transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2">Passwort speichern &amp; direkt anmelden</button>
        </form>

        <div class="mt-5 flex items-start gap-2 rounded-xl bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-600">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-teal-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 0h10.5A2.25 2.25 0 0 1 19.5 12.75v6A2.25 2.25 0 0 1 17.25 21H6.75A2.25 2.25 0 0 1 4.5 18.75v-6a2.25 2.25 0 0 1 2.25-2.25Z"/></svg>
            <p>Der Link ist einmalig und zeitlich begrenzt. Eine weitere E-Mail-Bestätigung ist für Mitarbeiter nicht erforderlich.</p>
        </div>
    </div>
@endsection
