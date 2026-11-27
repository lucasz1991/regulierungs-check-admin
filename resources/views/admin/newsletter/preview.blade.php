@extends('admin.newsletter.layout')
@section('newsletter')
<header><div><p class="nl-muted">{{ $campaign->name }}</p><h1>Versand prüfen</h1></div><a class="nl-link" href="{{ route('admin.newsletter.show',$campaign) }}">Zur Ausgabe</a></header>
<form class="nl-actions" method="get"><label for="profile">Musteransprache</label><select style="width:auto" name="profile" id="profile">@foreach(['user'=>'Benutzer','contact'=>'Kontakt','neutral'=>'Ohne Namen'] as $key=>$value)<option value="{{ $key }}" @selected($profile===$key)>{{ $value }}</option>@endforeach</select><button class="nl-btn">Anzeigen</button></form>
<section x-data="{ mode:'desktop' }">
<div class="nl-row"><h2>{{ $campaign->subject }}</h2><div class="nl-actions" aria-label="Vorschauformat">
@foreach(['desktop'=>'Desktop','mobile'=>'Handy','text'=>'Text'] as $key=>$label)<button type="button" class="nl-btn" @click="mode='{{ $key }}'" :aria-pressed="mode==='{{ $key }}'">{{ $label }}</button>@endforeach
</div></div>
<div style="margin:14px auto" x-show="mode!=='text'" :style="{maxWidth:mode==='mobile'?'390px':'100%'}"><iframe title="Newsletter-Vorschau" sandbox srcdoc="{{ $html }}"></iframe></div>
<pre x-cloak x-show="mode==='text'" class="nl-rule" style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $text }}</pre>
</section>
@if($campaign->status==='draft')
@can('newsletter.send')
<div class="nl-grid">
<section><h2>Test an eine Adresse</h2>
<form action="{{ route('admin.newsletter.test',$campaign) }}" method="post">@csrf<input type="hidden" name="revision" value="{{ $campaign->revision }}"><input type="hidden" name="test_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="profile" value="{{ $profile }}">
<label class="nl-field">Testadresse<input type="email" name="test_email" required value="{{ old('test_email') }}"></label><button class="nl-btn">Testmail senden</button></form></section>
<section><h2>Versand freigeben</h2>
<p><strong>{{ number_format($counts->get('eligible', 0), 0, ',', '.') }} {{ $counts->get('eligible') == 1 ? 'versandberechtigte Adresse' : 'versandberechtigte Adressen' }}</strong><br><small>Bei Freigabe wird die Empfängerliste noch einmal abgeglichen.</small></p>
@if($excluded = $counts->except('eligible')->sum())
<details><summary>{{ number_format($excluded, 0, ',', '.') }} {{ $excluded == 1 ? 'ausgeschlossenes Profil' : 'ausgeschlossene Profile' }}</summary>
<ul>@foreach(['disabled'=>'Abgemeldet oder gesperrt','address'=>'Ohne gültige E-Mail-Adresse','proof'=>'Ohne Versandnachweis','identity'=>'Adresszuordnung oder Kontostatus ungeklärt'] as $reason=>$label)
@if($counts->get($reason))<li>{{ $label }} · {{ number_format($counts[$reason], 0, ',', '.') }}</li>@endif
@endforeach</ul></details>
@endif
<form action="{{ route('admin.newsletter.schedule',$campaign) }}" method="post">@csrf<input type="hidden" name="revision" value="{{ $campaign->revision }}"><label class="nl-field">Termin · Europe/Berlin<input type="datetime-local" name="scheduled" value="{{ old('scheduled') }}"><small>Ohne Termin wird die Ausgabe beim nächsten Versandlauf fällig.</small></label>
<button class="nl-btn primary">Versand verbindlich freigeben</button></form>
</section></div>
@endcan @endif
@endsection
