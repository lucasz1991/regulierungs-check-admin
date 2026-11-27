@extends('layouts.master')
@section('title', 'Newsletter')
@section('css')
<style>
.nl-workspace{--nl-ink:#163b45;--nl-teal:#176d60;--nl-line:#d8e2de;color:var(--nl-ink);max-width:1100px;margin:auto;padding:clamp(18px,3vw,42px);background:#fff;border:1px solid var(--nl-line);border-radius:12px}
.nl-workspace *{box-sizing:border-box}.nl-workspace h1{font-size:clamp(1.65rem,3vw,2.4rem);font-weight:700;line-height:1.15;letter-spacing:-.035em;margin:0}.nl-workspace h2,.nl-workspace legend{font-size:1.12rem;font-weight:650;margin:0 0 12px}
.nl-workspace header,.nl-row,.nl-actions{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}.nl-workspace header{margin-bottom:24px}.nl-workspace p{margin:5px 0}.nl-muted,.nl-workspace small{color:#536c71}.nl-workspace small{font-size:.85rem}
.nl-actions{justify-content:flex-start}.nl-workspace a.nl-link{color:var(--nl-teal);font-weight:600;text-decoration:underline;text-underline-offset:.22em}
.nl-workspace section,.nl-workspace fieldset,.nl-rule{border:0;border-top:1px solid var(--nl-line);margin-top:22px;padding-top:18px}.nl-grid{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1fr);gap:30px}.nl-workspace .nl-grid section:first-child{margin-top:0}
.nl-field{display:grid;gap:5px;margin-bottom:15px;font-size:.9rem;font-weight:600}.nl-workspace :is(input:not([type=checkbox],[type=range],[type=hidden]),textarea,select){width:100%;min-height:42px;border:1px solid #b7c9c2;border-radius:6px;background:#fff;color:var(--nl-ink);padding:8px 10px;font:inherit;font-weight:400}.nl-workspace textarea{resize:vertical}
.nl-workspace :is(input,textarea,button,a,select,summary):focus-visible{outline:3px solid #429e89;outline-offset:2px}.nl-workspace input[type=range]{width:100%;accent-color:var(--nl-teal)}
.nl-btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:8px 15px;color:var(--nl-teal);background:#fff;border:1px solid #b8cdc4;border-radius:6px;font-weight:600;cursor:pointer}.nl-btn.primary{color:white;background:var(--nl-teal);border-color:var(--nl-teal)}.nl-btn:hover{filter:brightness(.95)}
.nl-table-wrap{overflow-x:auto}.nl-table{border-collapse:collapse;width:100%;text-align:left}.nl-table :is(th,td){border-bottom:1px solid var(--nl-line);padding:12px 8px}.nl-table th{font-size:.85rem;color:#536c71}.nl-empty{padding:22px 0}
.nl-status{font-size:.82rem;color:#275b50;background:#eaf3ee;border-radius:20px;padding:4px 10px}.nl-notice,.nl-errors{padding:10px 14px;border-radius:6px;margin:12px 0 20px;background:#e7f4ec}.nl-errors{background:#fff0e8}.nl-crop{background:#e8efeb;width:100%;aspect-ratio:2/1;object-fit:cover;border-radius:6px}.nl-workspace iframe{width:100%;border:1px solid var(--nl-line);height:65vh;min-height:430px;background:#fff}.nl-workspace [x-cloak]{display:none!important}
@media(max-width:740px){.nl-grid{grid-template-columns:1fr;gap:10px}.nl-workspace{border-radius:6px}.nl-actions>.nl-btn{flex:1 1 auto}}
</style>
@endsection
@section('content')
<main class="bg-gray-100"><div class="main-content group-data-[sidebar-size=sm]:ml-[70px]"><div class="page-content min-h-screen">
<div class="nl-workspace">
<nav aria-label="Newsletter" class="nl-actions" style="margin-bottom:20px">
    <a href="{{ route('admin.newsletter.index') }}" class="nl-link">Kampagnen</a>
    <a href="{{ route('admin.newsletter.subscriptions') }}" class="nl-link">Abonnements</a>
</nav>
@if(session('notice')) <div class="nl-notice" role="status">{{ session('notice') }}</div> @endif
@if($errors->any()) <div class="nl-errors" role="alert">@foreach($errors->unique() as $message)<p>{{ $message }}</p>@endforeach</div> @endif
@yield('newsletter')
</div></div></div></main>
@endsection
