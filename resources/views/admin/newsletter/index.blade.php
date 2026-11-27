@extends('admin.newsletter.layout')
@section('newsletter')
<header><h1>Newsletter</h1>
<form method="post" action="{{ route('admin.newsletter.create') }}">@csrf<button class="nl-btn primary">Freie Ausgabe erstellen</button></form>
</header>
@can('content.news.manage')
@if($articles->isNotEmpty())
<section><h2>Aus einer News erstellen</h2>
<form method="post" action="{{ route('admin.newsletter.create') }}" class="nl-actions">@csrf
<label class="nl-field" style="flex:1;min-width:180px;margin-bottom:0"><span>Newsbeitrag</span><select name="post_id" required><option value="">Beitrag wählen</option>@foreach($articles as $article)<option value="{{ $article->id }}">{{ $article->title }}</option>@endforeach</select></label>
<button class="nl-btn" style="align-self:flex-end">Als Newsletter verwenden</button>
</form></section>
@endif
@endcan
<section><div class="nl-row"><h2>Ausgaben</h2>
<form method="get" class="nl-actions"><label for="filter-status">Status</label><select id="filter-status" name="status" style="width:auto"><option value="">Alle</option>@foreach(\App\Models\NewsletterCampaign::STATUSES as $key=>$label)<option value="{{ $key }}" @selected($status===$key)>{{ $label }}</option>@endforeach</select><button class="nl-btn">Filtern</button></form>
</div>
<div class="nl-table-wrap"><table class="nl-table"><thead><tr><th>Ausgabe</th><th>Quelle</th><th>Status</th><th>Versand · Berlin</th></tr></thead><tbody>
@forelse($campaigns as $campaign)
<tr><td><a class="nl-link" href="{{ route('admin.newsletter.show', $campaign) }}">{{ $campaign->name }}</a></td><td>{{ $campaign->post_id ? 'News' : 'Freie Ausgabe' }}</td><td><span class="nl-status">{{ $campaign->statusLabel() }}</span></td><td>{{ $campaign->scheduledLocal() ?? '–' }}</td></tr>
@empty <tr><td colspan="4" class="nl-empty">Hier gibt es noch keine Ausgaben.</td></tr> @endforelse
</tbody></table></div>{{ $campaigns->links() }}</section>
@endsection
