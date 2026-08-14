@extends('layouts.sensitive-invitation')

@section('title', 'E-Mail-Adresse bestätigen')

@section('content')
    <div class="text-center">
        <img
            src="{{ asset('site-images/logo/logo-icon.png') }}"
            alt="Regulierungs-CHECK"
            class="mx-auto h-20 w-20"
        >

        <p class="mt-5 text-xs font-semibold uppercase tracking-widest text-teal-700">
            Regulierungs-CHECK Zugang
        </p>
        <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">
            E-Mail-Adresse bestätigen
        </h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">
            Bevor du fortfährst, muss deine E-Mail-Adresse einmal bestätigt werden.
            Wenn dir noch kein aktueller Link vorliegt, kannst du ihn hier neu anfordern.
        </p>

        @if (auth()->user()?->email)
            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-left">
                <span class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Bestätigungsadresse
                </span>
                <strong class="mt-1 block break-all text-sm text-slate-900">
                    {{ auth()->user()->email }}
                </strong>
            </div>
        @endif
    </div>

    @if (session('error'))
        <div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-5 text-red-800">
            {{ session('error') }}
        </div>
    @elseif (session('status') === 'verification-link-sent')
        <div role="status" class="mt-5 rounded-xl border border-green-300 bg-green-50 px-4 py-3 text-sm leading-5 text-green-800">
            Ein neuer Bestätigungslink wurde an deine E-Mail-Adresse gesendet.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
        @csrf
        <button
            type="submit"
            class="w-full rounded-xl bg-teal-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2"
        >
            Bestätigungs-E-Mail erneut senden
        </button>
    </form>

    <div class="mt-5 border-t border-slate-200 pt-5 text-center">
        <p class="text-xs leading-5 text-slate-500">
            Der Bestätigungslink ist {{ (int) config('auth.verification.expire', 60) }} Minuten gültig.
        </p>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button
                type="submit"
                class="text-sm font-semibold text-slate-600 underline underline-offset-4 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2"
            >
                Abmelden
            </button>
        </form>
    </div>
@endsection
