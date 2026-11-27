@section('title', $isEmployeeProfile ? 'Mitarbeiterprofil' : 'Benutzerprofil')
    @php
        $roleLabel = match($user->role) { 'admin' => 'Administrator', 'staff' => 'Mitarbeiter', default => 'Benutzer' };
        $customer = $user->customer;
        $canSeeNewsletter = \Illuminate\Support\Facades\Gate::any(['newsletter.view', 'newsletter.manage'])
            && \Illuminate\Support\Facades\Gate::allows('users.manage') && \Illuminate\Support\Facades\Schema::hasTable('newsletter_subscriptions');
        $profileTabs = ['details' => 'Personendaten', 'ratings' => 'Bewertungen', 'promotion' => 'Promotion'];
        if ($canSeeNewsletter) $profileTabs['newsletter'] = 'Newsletter';
        $canChangeStatus = $canManageUserStatus && (auth()->user()->isAdmin() || ! $user->isAdmin()) && ! $user->is(auth()->user());
    @endphp
<div class="rc-profile" x-data="{ selectedTab: @js($canSeeNewsletter && request('tab') === 'newsletter' ? 'newsletter' : 'details'), selectTab(tab) { this.selectedTab = tab; this.$nextTick(() => this.$refs[tab + 'Tab'].focus()) } }">

    <nav class="rc-profile__breadcrumb" aria-label="Profilnavigation">
        <a href="{{ route($isEmployeeProfile ? 'admin.employees' : 'admin.users') }}" wire:navigate>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14 6-6 6 6 6"/></svg>
            {{ $isEmployeeProfile ? 'Mitarbeiter' : 'Benutzer' }}
        </a>
        <span aria-hidden="true">/</span>
        <span>{{ $isEmployeeProfile ? 'Mitarbeiterprofil' : 'Benutzerprofil' }}</span>
    </nav>

    <header class="rc-profile__header">
        <div class="rc-profile__identity">
            <div class="rc-profile__avatar" aria-hidden="true">
                @if($user->profile_photo_path)
                    <img src="{{ $user->profile_photo_url }}" alt="">
                @else
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}
                @endif
            </div>
            <div class="rc-profile__identity-text">
                <p class="rc-profile__eyebrow">Regulierungs-CHECK · {{ $roleLabel }}</p>
                <h1>{{ $user->name }}</h1>
                <p class="rc-profile__email">{{ $user->email }}</p>
                <div class="rc-profile__meta">
                    <x-admin.profile.badge :tone="$user->isActive() ? 'success' : 'danger'">{{ $user->isActive() ? 'Konto aktiv' : 'Konto deaktiviert' }}</x-admin.profile.badge>
                    <span>Registriert am {{ $user->created_at?->format('d.m.Y') ?? '—' }}</span>
                    @if($user->currentTeam)<span>Team {{ $user->currentTeam->name }}</span>@endif
                </div>
            </div>
        </div>
        <div class="rc-profile__actions">
            @if($canSendUserMail)
                <button type="button" wire:click="openMailModal" wire:loading.attr="disabled" wire:target="openMailModal" class="rc-profile__button rc-profile__button--primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="m3 7 9 6 9-6"/></svg>
                    E-Mail schreiben
                </button>
            @endif
            @if($canChangeStatus)
                <button type="button" wire:click="{{ $user->status ? 'deactivateUser' : 'activateUser' }}" wire:confirm="{{ $user->status ? 'Dieses Konto wirklich deaktivieren?' : 'Dieses Konto wieder aktivieren?' }}" wire:loading.attr="disabled" class="rc-profile__button">
                    {{ $user->status ? 'Konto deaktivieren' : 'Konto aktivieren' }}
                </button>
            @endif
        </div>
    </header>

    <div class="rc-profile__tabs" role="tablist" aria-label="Profilbereiche"
         x-data="{ tabs: @js(array_keys($profileTabs)) }"
         @keydown.arrow-right.prevent="selectTab(tabs[(tabs.indexOf(selectedTab)+1)%tabs.length])"
         @keydown.arrow-left.prevent="selectTab(tabs[(tabs.indexOf(selectedTab)+tabs.length-1)%tabs.length])"
         @keydown.home.prevent="selectTab(tabs[0])" @keydown.end.prevent="selectTab(tabs[tabs.length-1])">
        @foreach($profileTabs as $tab => $label)
            <button type="button" id="profile-tab-{{ $tab }}" x-ref="{{ $tab }}Tab" role="tab" aria-controls="profile-panel-{{ $tab }}"
                    :aria-selected="selectedTab === '{{ $tab }}'" :tabindex="selectedTab === '{{ $tab }}' ? 0 : -1"
                    @click="selectedTab = '{{ $tab }}'">{{ $label }}</button>
        @endforeach
    </div>

    <section id="profile-panel-details" role="tabpanel" aria-labelledby="profile-tab-details" tabindex="0" x-show="selectedTab === 'details'" class="rc-profile__panel">
        <div class="rc-profile__details-grid">
            <section class="rc-profile__card">
                <div class="rc-profile__section-heading"><p class="rc-profile__eyebrow">Kontakt &amp; Anschrift</p><h2>Personendaten</h2><p>Die zum Konto hinterlegten Angaben.</p></div>
                <dl class="rc-profile__fields">
                    <x-admin.profile.field label="Anzeigename" :value="$user->name" />
                    <x-admin.profile.field label="E-Mail-Adresse" :value="$user->email" />
                    <x-admin.profile.field label="Vorname" :value="$customer?->first_name" />
                    <x-admin.profile.field label="Nachname" :value="$customer?->last_name" />
                    <x-admin.profile.field label="Telefon" :value="$customer?->phone_number" />
                    <x-admin.profile.field label="Straße und Hausnummer" :value="$customer?->street" />
                    <x-admin.profile.field label="Postleitzahl und Ort" :value="trim(($customer?->postal_code ?? '').' '.($customer?->city ?? ''))" />
                    <x-admin.profile.field label="Bundesland" :value="$customer?->state" />
                    <x-admin.profile.field label="Land" :value="$customer?->country" />
                </dl>
                @if(! $customer)<p class="rc-profile__note">Zu diesem Konto sind noch keine weiteren Personendaten hinterlegt.</p>@endif
            </section>

            <aside class="rc-profile__card rc-profile__card--muted">
                <div class="rc-profile__section-heading"><p class="rc-profile__eyebrow">Kontoinformationen</p><h2>Konto &amp; Anmeldung</h2></div>
                <dl class="rc-profile__fields rc-profile__fields--single">
                    <x-admin.profile.field label="Kontotyp" :value="$roleLabel" />
                    <x-admin.profile.field label="E-Mail-Bestätigung" :value="$user->email_verified_at ? 'Bestätigt am '.$user->email_verified_at->format('d.m.Y H:i') : 'Noch nicht bestätigt'" />
                    <x-admin.profile.field label="Registriert" :value="$user->created_at?->format('d.m.Y H:i')" />
                    @if(in_array($user->role, ['admin', 'staff'], true))
                        <x-admin.profile.field label="Aktuelles Team" :value="$user->currentTeam?->name ?? 'Kein Team zugeordnet'" />
                    @endif
                </dl>
                @if($socialAccounts->isNotEmpty())
                    <div class="rc-profile__connections">
                        <h3>Verknüpfte Anmeldungen</h3>
                        @foreach($socialAccounts as $identity)
                            <x-admin.profile.badge>{{ ucfirst($identity->provider) }} verknüpft</x-admin.profile.badge>
                        @endforeach
                    </div>
                @endif
            </aside>
        </div>
    </section>

    <section id="profile-panel-ratings" role="tabpanel" aria-labelledby="profile-tab-ratings" tabindex="0" x-show="selectedTab === 'ratings'" x-cloak class="rc-profile__panel">
        <div class="rc-profile__section-heading"><p class="rc-profile__eyebrow">Erfahrungen mit der Schadenregulierung</p><h2>Bewertungen des Benutzers</h2><p>Versicherung, Bearbeitungsstand und Ergebnis der eingereichten Bewertungen.</p></div>
        @if(! $canViewRatings)
            <div class="rc-profile__empty"><h3>Bewertungen nicht freigegeben</h3><p>Zum Anzeigen ist die Berechtigung „Bewertungen verwalten“ erforderlich.</p></div>
        @elseif(! $ratingsReady)
            <div class="rc-profile__empty"><h3>Bewertungen derzeit nicht verfügbar</h3><p>Die Bewertungsdaten konnten nicht bereitgestellt werden.</p></div>
        @elseif($ratings->isEmpty())
            <div class="rc-profile__empty"><h3>Noch keine Bewertungen</h3><p>Dieses Konto hat bisher keine Bewertung eingereicht.</p></div>
        @else
            <div class="rc-profile__review-list" wire:loading.class="opacity-50" wire:target="gotoPage,nextPage,previousPage">
                @foreach($ratings as $rating)
                    @php
                        $ratingStatus = match($rating->status) {
                            'pending' => 'Ausstehend', 'pending_validation' => 'In Prüfung', 'rating' => 'Wird ausgewertet',
                            'rated' => 'Ausgewertet', 'approved' => 'Freigegeben', 'published' => 'Veröffentlicht',
                            'rejected' => 'Abgelehnt', default => 'Status unbekannt'
                        };
                        $ratingTone = match($rating->status) { 'rated', 'approved', 'published' => 'success', 'rejected' => 'danger', default => 'neutral' };
                        $isVisible = $rating->is_public && in_array($rating->status, \App\Models\ClaimRating::publicVisibleStatuses(), true);
                    @endphp
                    <article class="rc-profile__review" wire:key="profile-rating-{{ $rating->id }}">
                        <div class="rc-profile__review-main">
                            <p class="rc-profile__small">Bewertung #{{ $rating->id }} · {{ $rating->created_at?->format('d.m.Y') ?? 'Datum unbekannt' }}</p>
                            <h3>{{ $rating->insurance?->name ?? 'Versicherung nicht mehr verfügbar' }}</h3>
                            <p>{{ collect([$rating->insuranceType?->name, $rating->insuranceSubtype?->name])->filter()->join(' · ') ?: 'Keine Versicherungsart hinterlegt' }}</p>
                            <div class="rc-profile__meta"><x-admin.profile.badge :tone="$ratingTone">{{ $ratingStatus }}</x-admin.profile.badge><span>{{ $isVisible ? 'Öffentlich sichtbar' : 'Nicht öffentlich sichtbar' }}</span></div>
                        </div>
                        <div class="rc-profile__review-result">
                            <span class="rc-profile__small">Bewertungsergebnis</span>
                            <strong>{{ $rating->rating_score !== null ? number_format((float) $rating->rating_score * 5, 1, ',', '.').' / 5' : 'Noch offen' }}</strong>
                            <a href="{{ route('admin.reviews.show', ['ratingId' => $rating->id]) }}" wire:navigate class="rc-profile__text-link">Bewertung öffnen <span aria-hidden="true">↗</span></a>
                        </div>
                    </article>
                @endforeach
            </div>
            @if($ratings->hasPages())
                <nav class="rc-profile__pagination" aria-label="Bewertungsseiten">
                    <span class="rc-profile__small">{{ $ratings->firstItem() }}–{{ $ratings->lastItem() }} von {{ $ratings->total() }} Bewertungen</span>
                    <div class="rc-profile__actions">
                        <button type="button" wire:click="previousPage('ratingsPage')" wire:loading.attr="disabled" @disabled($ratings->onFirstPage()) class="rc-profile__button">Zurück</button>
                        <span class="rc-profile__small" aria-live="polite">Seite {{ $ratings->currentPage() }} von {{ $ratings->lastPage() }}</span>
                        <button type="button" wire:click="nextPage('ratingsPage')" wire:loading.attr="disabled" @disabled(! $ratings->hasMorePages()) class="rc-profile__button">Weiter</button>
                    </div>
                </nav>
            @endif
        @endif
    </section>

    <section id="profile-panel-promotion" role="tabpanel" aria-labelledby="profile-tab-promotion" tabindex="0" x-show="selectedTab === 'promotion'" x-cloak class="rc-profile__panel">
        @include('livewire.admin.user-profile.promotion-history')
    </section>

    @if($canSeeNewsletter)
    <section id="profile-panel-newsletter" role="tabpanel" aria-labelledby="profile-tab-newsletter" x-show="selectedTab === 'newsletter'" x-cloak class="rc-profile__panel">
        <livewire:admin.newsletter-preference type="user" :record-id="$user->id" :key="'newsletter-user-'.$user->id" />
    </section>
    @endif
    @if($canSendUserMail)
        @include('livewire.admin.customer-mail-form')
    @endif
</div>
