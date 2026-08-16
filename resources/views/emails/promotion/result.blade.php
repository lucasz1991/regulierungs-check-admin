<!doctype html>
<html lang="de">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Glücksrad-Ergebnis</title></head>
<body style="margin:0;background:#eef7f5;font-family:Arial,sans-serif;color:#0b3038">
@php
    $outcome = $result->outcome_type_snapshot instanceof \BackedEnum ? $result->outcome_type_snapshot->value : (string) $result->outcome_type_snapshot;
    $participationId = $result->ticket?->participation?->public_id;
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef7f5;padding:28px 12px"><tr><td align="center">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fff;border-radius:24px;overflow:hidden;border:1px solid #dcecea">
        <tr><td style="background:#0b3038;padding:26px 30px;color:#fff"><strong style="font-size:20px">Regulierungs-CHECK</strong><div style="margin-top:5px;color:#8dd7d1;font-size:13px;letter-spacing:.08em;text-transform:uppercase">Promotion-Glücksrad</div></td></tr>
        <tr><td style="padding:34px 30px">
            @if($correction)<p style="margin:0 0 14px;color:#a86700;font-weight:700">Dein Ergebnis wurde korrigiert.</p>@endif
            <h1 style="margin:0;font-size:30px;line-height:1.15;color:#0b3038">{{ $outcome === 'no_win' ? 'Diesmal leider kein Gewinn' : 'Glückwunsch!' }}</h1>
            <p style="margin:18px 0 0;font-size:17px;line-height:1.6;color:#48636a">{{ $outcome === 'no_win' ? 'Danke, dass du beim Glücksrad mitgemacht hast.' : 'Dein Ergebnis: '.($result->label_snapshot ?: 'Gewinn') }}</p>
            <div style="margin-top:24px;padding:18px;border-radius:14px;background:#f3f8f7">
                <div style="font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#6a8085">Kampagne</div><strong>{{ $result->campaign?->name }}</strong>
                @if($participationId)<div style="margin-top:14px;font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#6a8085">Teilnahme-ID</div><strong style="font-family:monospace">{{ $participationId }}</strong>@endif
            </div>
            <a href="{{ $participantUrl }}" style="display:block;margin-top:26px;padding:14px 20px;border-radius:12px;background:#0d9187;color:#fff;text-align:center;text-decoration:none;font-weight:700">Ergebnis im Profil ansehen</a>
            <p style="margin:22px 0 0;font-size:12px;line-height:1.5;color:#71858a">Diese E-Mail enthält keinen Gutscheincode. Externe digitale Gewinne werden separat durch einen Administrator ausgegeben.</p>
        </td></tr>
    </table>
</td></tr></table>
</body>
</html>
