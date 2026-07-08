<div wire:loading.class="cursor-wait" class="bg-slate-50 text-slate-900">
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(59,130,246,0.22),transparent_34%),linear-gradient(135deg,#eef6ff_0%,#ffffff_52%,#ecfdf5_100%)]"></div>
        <div class="relative mx-auto grid min-h-[620px] max-w-7xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-8">
            <div>
                <div class="mb-6 inline-flex items-center rounded-full border border-blue-200 bg-white/80 px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm">
                    Versicherungsbewertungen transparent vergleichen
                </div>

                <h1 class="max-w-3xl text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">
                    Wie gut reguliert deine Versicherung wirklich?
                </h1>

                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
                    Teile deine Erfahrung zur Schadenregulierung, sieh nachvollziehbare Bewertungen und hilf anderen dabei, Versicherungen fairer einzuschätzen.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="/login" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        Bewertung einreichen
                    </a>
                    <a href="/register" wire:navigate class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-100">
                        Konto erstellen
                    </a>
                </div>

                <div class="mt-10 grid max-w-2xl grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-white bg-white/75 p-4 shadow-sm">
                        <p class="text-2xl font-bold text-blue-600">3 Min.</p>
                        <p class="mt-1 text-sm text-slate-600">Bewertung erfassen</p>
                    </div>
                    <div class="rounded-lg border border-white bg-white/75 p-4 shadow-sm">
                        <p class="text-2xl font-bold text-emerald-600">KI + Admin</p>
                        <p class="mt-1 text-sm text-slate-600">Plausibilitaet pruefen</p>
                    </div>
                    <div class="rounded-lg border border-white bg-white/75 p-4 shadow-sm">
                        <p class="text-2xl font-bold text-slate-900">Fair</p>
                        <p class="mt-1 text-sm text-slate-600">Scores vergleichen</p>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="absolute -inset-6 rounded-[2rem] bg-blue-200/35 blur-3xl"></div>
                <div class="relative rounded-2xl border border-white bg-white/90 p-5 shadow-2xl">
                    <div class="mb-5 flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Regulierungs-Score</p>
                            <p class="text-xs text-slate-500">Beispielhafte Auswertung</p>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Verifiziert</span>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-slate-700">Kommunikation</span>
                                <span class="text-sm font-bold text-slate-950">4.2 / 5</span>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-slate-200">
                                <div class="h-2 w-[84%] rounded-full bg-blue-500"></div>
                            </div>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-slate-700">Regulierungsdauer</span>
                                <span class="text-sm font-bold text-slate-950">3.6 / 5</span>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-slate-200">
                                <div class="h-2 w-[72%] rounded-full bg-amber-400"></div>
                            </div>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-slate-700">Fairness</span>
                                <span class="text-sm font-bold text-slate-950">4.6 / 5</span>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-slate-200">
                                <div class="h-2 w-[92%] rounded-full bg-emerald-500"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">
                        Bewertungen werden strukturiert erfasst, nachvollziehbar gewichtet und koennen mit Nachweisen verifiziert werden.
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
