<!doctype html>
<html lang="de">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dein Glücksrad-Ergebnis</title></head>
<body style="margin:0;background:#eaf4f2;color:#082f35;font-family:'Segoe UI','Avenir Next',Tahoma,sans-serif;-webkit-text-size-adjust:100%">
@php
    $outcome = $result->outcome_type_snapshot instanceof \BackedEnum ? $result->outcome_type_snapshot->value : (string) $result->outcome_type_snapshot;
    $participationId = $result->ticket?->participation?->public_id;
    $won = $outcome !== 'no_win';
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#eaf4f2"><tr><td align="center" style="padding:30px 12px">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;max-width:620px">
        <tr><td style="padding:0 8px 10px"><table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr>
            <td style="width:12px;background:#0d9187;border-radius:99px"></td>
            <td style="padding:3px 0 3px 12px;font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#527078">Regulierungs-CHECK Promotion</td>
            <td align="right" style="font-size:11px;font-weight:700;color:#789094">Sicher deinem Konto zugeordnet</td>
        </tr></table></td></tr>
        <tr><td style="border:1px solid #d8e7e4;border-radius:28px;background:#ffffff;overflow:hidden;box-shadow:0 18px 50px rgba(8,47,53,.10)">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr><td style="padding:28px 32px;background:#082f35;color:#ffffff">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr>
                        <td><strong style="font-size:20px;letter-spacing:-.3px">Regulierungs-CHECK</strong><div style="margin-top:5px;color:#8ad7d0;font-size:11px;font-weight:800;letter-spacing:1.8px;text-transform:uppercase">Promotion-Glücksrad</div></td>
                        <td align="right"><span style="display:inline-block;width:44px;height:44px;line-height:44px;text-align:center;border-radius:50%;background:{{ $won ? '#f4c95d' : '#dbe7e5' }};color:#082f35;font-size:22px;font-weight:900">{{ $won ? '✓' : '•' }}</span></td>
                    </tr></table>
                </td></tr>
                <tr><td style="padding:38px 32px 34px">
                    @if($correction)<div style="display:inline-block;margin-bottom:18px;padding:8px 12px;border-radius:99px;background:#fff3d5;color:#805300;font-size:12px;font-weight:800">Aktualisiertes Ergebnis</div>@endif
                    <div style="font-size:12px;font-weight:800;letter-spacing:1.7px;text-transform:uppercase;color:#0d9187">Dein Moment am Glücksrad</div>
                    <h1 style="margin:10px 0 0;font-size:34px;line-height:1.12;letter-spacing:-1px;color:#082f35">{{ $won ? 'Herzlichen Glückwunsch!' : 'Danke fürs Mitmachen!' }}</h1>
                    <p style="margin:18px 0 0;font-size:17px;line-height:1.65;color:#48666c">{{ $won ? 'Dein beobachtetes Ergebnis wurde sicher gespeichert.' : 'Diesmal war es leider eine Niete. Dein Dreh wurde trotzdem sicher gespeichert.' }}</p>
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:26px;border:1px solid #dceae7;border-radius:18px;background:#f4f9f8">
                        <tr><td style="padding:22px 22px 18px"><div style="font-size:11px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;color:#698187">Ergebnis</div><div style="margin-top:7px;font-size:22px;font-weight:800;line-height:1.25;color:#082f35">{{ $won ? ($result->label_snapshot ?: 'Gewinn') : 'Niete' }}</div></td></tr>
                        <tr><td style="padding:0 22px 22px"><table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr>
                            <td style="padding-top:16px;border-top:1px solid #dceae7"><div style="font-size:10px;font-weight:800;letter-spacing:1.3px;text-transform:uppercase;color:#789094">Kampagne</div><div style="margin-top:5px;font-size:14px;font-weight:700;color:#193f46">{{ $result->campaign?->name }}</div></td>
                            @if($participationId)<td align="right" style="padding-top:16px;border-top:1px solid #dceae7"><div style="font-size:10px;font-weight:800;letter-spacing:1.3px;text-transform:uppercase;color:#789094">Teilnahme-ID</div><div style="margin-top:5px;font-family:Consolas,monospace;font-size:13px;font-weight:800;color:#193f46">{{ $participationId }}</div></td>@endif
                        </tr></table></td></tr>
                    </table>
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:26px"><tr><td align="center" style="border-radius:14px;background:#0d9187"><a href="{{ $participantUrl }}" style="display:block;padding:15px 22px;color:#ffffff;text-align:center;text-decoration:none;font-size:15px;font-weight:800">Ergebnis sicher ansehen&nbsp;&nbsp;›</a></td></tr></table>
                    <p style="margin:22px 0 0;font-size:12px;line-height:1.6;color:#789094">Diese Nachricht enthält keinen Gutscheincode. Ein digitaler Gewinn wird nach der Freigabe in einer eigenen, sicheren E-Mail versendet.</p>
                </td></tr>
            </table>
        </td></tr>
        <tr><td align="center" style="padding:20px 20px 0;font-size:11px;line-height:1.6;color:#789094">Diese Nachricht gehört zu deiner Teilnahme am Regulierungs-CHECK Glücksrad.</td></tr>
    </table>
</td></tr></table>
</body>
</html>
