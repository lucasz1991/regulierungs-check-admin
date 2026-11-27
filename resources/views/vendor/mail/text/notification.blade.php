@isset($newsletter)
Regulierungs-CHECK · {{ $newsletter['kind'] === 'news' ? 'News' : 'Newsletter' }}

@endisset
@if (filled($greeting))
{{ $greeting }}

@endif
@if (($newsletter['kind'] ?? null) === 'news' && filled($newsletter['title'] ?? null))
{{ $newsletter['title'] }}

@endif
@foreach ($introLines as $line)
{{ $line instanceof \Illuminate\Contracts\Support\Htmlable ? strip_tags($line->toHtml()) : $line }}

@endforeach
@if (filled($actionText) && filled($actionUrl))
{{ $actionText }}
{{ $actionUrl }}

@endif
@foreach ($outroLines as $line)
{{ $line instanceof \Illuminate\Contracts\Support\Htmlable ? strip_tags($line->toHtml()) : $line }}

@endforeach
@isset($newsletter)
Newsletter-Einstellungen ändern
{{ $newsletter['settings_url'] }}
@else
Du hast Fragen? Schreib uns einfach.
info@regulierungs-check.de
@endisset

@foreach ($signoffLines as $signoffLine)
{{ $signoffLine }}
@endforeach
Regulierungs-Check RCQ GmbH
Oldesloer Str. 63 · 22457 Hamburg
Geschäftsführer Birkan Özülkü
Amtsgericht Hamburg · HRB 193808 · USt-ID DE458707066
https://www.regulierungs-check.de/
Impressum: https://www.regulierungs-check.de/imprint
Datenschutz: https://www.regulierungs-check.de/privacypolicy
@isset($newsletter)
Newsletter abbestellen
{{ $newsletter['unsubscribe_url'] }}
@endisset
