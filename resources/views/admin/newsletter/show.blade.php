@extends('admin.newsletter.layout')
@section('newsletter')
<header><div><p class="nl-muted">{{ $campaign->post_id ? 'Aus Newsbeitrag' : 'Freie Ausgabe' }}</p><h1>{{ $campaign->name }}</h1></div><span class="nl-status">{{ $campaign->statusLabel() }}</span></header>
@if($campaign->status === 'draft')
<form method="post" action="{{ route('admin.newsletter.save',$campaign) }}">
@csrf<input type="hidden" name="revision" value="{{ $campaign->revision }}">
<div class="nl-grid">
<div>
<section><h2>Nachricht</h2>
<label class="nl-field">Interner Name<input type="text" name="name" value="{{ old('name',$campaign->name) }}" required maxlength="255"></label>
<label class="nl-field">Betreff<input type="text" name="subject" value="{{ old('subject',$campaign->subject) }}" maxlength="255"></label>
<label class="nl-field">Vorschautext im Posteingang<input name="preheader" maxlength="255" value="{{ old('preheader',$campaign->preheader) }}"><small>Optional · empfohlen sind bis zu 160 Zeichen.</small></label>
<label class="nl-field">{{ $campaign->post_id ? 'Teasertext' : 'Nachricht' }}<textarea name="body" rows="7" maxlength="50000">{{ old('body',$campaign->body) }}</textarea></label>
@if(! $campaign->post_id)
<label class="nl-field">Link für den Button <small>Optional</small><input name="link" type="url" value="{{ old('link',$campaign->link) }}"></label>
@else
<p><strong>Artikellink</strong><br><small>{{ $campaign->link }}</small></p>
@endif
<label class="nl-field">Buttontext<input name="button_label" placeholder="Weiter" maxlength="80" value="{{ old('button_label',$campaign->button_label) }}"></label>
</section>
</div>
<div>
@if($campaign->post_id)
<section><h2>Beitragsbild</h2>
@if($campaign->image_source)
<div x-data="{ hasImage: @js((bool) old('include_image', $campaign->include_image)), x: @js((int) old('focus_x',$campaign->focus_x)), y: @js((int) old('focus_y',$campaign->focus_y)), w:0, h:0, position(f,size,crop){ return size > crop ? Math.max(0,Math.min(100,(size*f-50*crop)/(size-crop))) : 50 } }">
<input type="hidden" name="include_image" value="0"><label><input type="checkbox" name="include_image" value="1" x-model="hasImage" @checked(old('include_image',$campaign->include_image))> Beitragsbild verwenden</label>
<div x-show="hasImage" class="nl-rule">
<img class="nl-crop" @load="w=$el.naturalWidth;h=$el.naturalHeight" x-init="if($el.complete){w=$el.naturalWidth;h=$el.naturalHeight}" :style="{objectPosition: position(x,w,Math.min(w,h*2))+'% '+position(y,h,Math.min(h,w/2))+'%'}" src="{{ \App\Models\NewsletterCampaign::publicUrl('/storage/'.$campaign->image_source) }}" alt="Vorschau des Bildausschnitts">
<small>Der 2:1-Ausschnitt gilt für Desktop und Handy.</small>
<label class="nl-field">Fokus horizontal<input type="range" min="0" max="100" name="focus_x" x-model="x" value="{{ old('focus_x',$campaign->focus_x) }}"></label>
<label class="nl-field">Fokus vertikal<input type="range" min="0" max="100" name="focus_y" x-model="y" value="{{ old('focus_y',$campaign->focus_y) }}"></label>
<label class="nl-field">Alternativtext<input name="image_alt" maxlength="500" value="{{ old('image_alt',$campaign->image_alt) }}"></label>
</div></div>
@else <p class="nl-muted">Die News hat kein Hauptbild. Der Teaser erscheint als Text.</p> @endif
</section>
@endif
<section><h2>Empfänger</h2>
<label class="nl-field">Zielgruppe<select name="audience">@foreach(['all'=>'Alle Abonnements','users'=>'Benutzer','contacts'=>'Kontakte'] as $key=>$value)<option value="{{ $key }}" @selected(old('audience',$campaign->audience)===$key)>{{ $value }}</option>@endforeach</select></label>
<label class="nl-field">Begrüßung<input name="greeting" maxlength="255" value="{{ old('greeting',$campaign->greeting) }}" placeholder="Persönlich mit Namen"><small>Leer lassen für die persönliche Anrede. Ohne Namen erscheint „Hallo,“.</small></label>
</section></div></div>
<div class="nl-actions nl-rule"><button class="nl-btn" name="after" value="edit">Entwurf speichern</button><button class="nl-btn primary" name="after" value="preview">Speichern und Vorschau öffnen</button></div>
</form>
@if($campaign->post_id) @can('content.news.manage')
<details class="nl-rule"><summary>Verknüpften Beitrag verwalten</summary><p class="nl-muted">{{ $campaign->article_title }}</p>
<form class="nl-actions" action="{{ route('admin.newsletter.source',$campaign) }}" method="post">@csrf<input type="hidden" name="revision" value="{{ $campaign->revision }}">
<button class="nl-btn" name="action" value="refresh" onclick="return confirm('Aktuellen Titel, Teaser und Bild aus dem Beitrag übernehmen und vorhandene Entwurfsänderungen verwerfen?')">Inhalte neu übernehmen</button>
<button class="nl-btn" name="action" value="detach">Verknüpfung lösen</button></form></details>
@endcan @endif
@else
<section><div class="nl-row"><h2>Versandbericht</h2><a href="{{ route('admin.newsletter.preview',$campaign) }}" class="nl-link">Inhalt ansehen</a></div>
<p>Geplant für {{ $campaign->scheduledLocal() }} Uhr (Berlin)</p>
<div class="nl-table-wrap"><table class="nl-table"><thead><tr><th>Übergabestatus</th><th>Adressen</th></tr></thead><tbody>
@foreach(['pending'=>'Ausstehend','processing'=>'In Bearbeitung','sent'=>'Übergeben','skipped'=>'Übersprungen','failed'=>'Fehlgeschlagen','uncertain'=>'Übergabe unklar'] as $key=>$value)
<tr><td>{{ $value }}</td><td>{{ $report->get($key,0) }}</td></tr>@endforeach
</tbody></table></div></section>
@if($exceptions->total())
<section><h2>Einzelne Übergaben prüfen</h2>
<div class="nl-table-wrap"><table class="nl-table"><thead><tr><th>Adresse</th><th>Übergabe</th></tr></thead><tbody>
@foreach($exceptions as $row)<tr><td>{{ $row->email }}</td><td>{{ ['failed'=>'Fehlgeschlagen','uncertain'=>'Unklar – einzeln prüfen','skipped'=>'Übersprungen'][$row->status] }}</td></tr>@endforeach
</tbody></table></div>{{ $exceptions->links() }}</section>
@endif
@can('newsletter.send')
<form class="nl-actions nl-rule" action="{{ route('admin.newsletter.planning',$campaign) }}" method="post">@csrf<input type="hidden" name="revision" value="{{ $campaign->revision }}">
@if($campaign->status==='scheduled') <button class="nl-btn" name="action" value="withdraw">Freigabe zurücknehmen</button>
@elseif($campaign->status==='blocked') <button class="nl-btn" name="action" value="resume">Öffentlichkeit prüfen und fortsetzen</button>
@elseif($campaign->status==='sent' && $report->get('failed',0)) <button class="nl-btn" name="action" value="retry">Fehlgeschlagene Übergaben wiederholen</button> @endif
</form>@endcan
@endif
@endsection
