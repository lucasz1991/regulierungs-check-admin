<!doctype html>
<html lang="de">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Neuigkeiten zu deinem Glücksrad-Gewinn</title></head>
<body style="margin:0;background:#eaf4f2;color:#082f35;font-family:'Segoe UI','Avenir Next',Tahoma,sans-serif;-webkit-text-size-adjust:100%">
@php
    $isCode = $type === 'code';
    $isProfileRequired = $type === 'profile_required';
    $eyebrow = $isCode ? 'Sicher zugestellt' : ($isProfileRequired ? 'Deine Mithilfe ist gefragt' : 'Gewinn freigegeben');
    $headline = $isCode ? 'Dein Gutschein ist da.' : ($isProfileRequired ? 'Nur noch ein kurzer Schritt.' : 'Dein Gewinn ist bestätigt.');
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#eaf4f2"><tr><td align="center" style="padding:30px 12px">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;max-width:620px">
        <tr><td style="padding:0 8px 10px"><table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td style="width:12px;background:#f4c95d;border-radius:99px"></td><td style="padding-left:12px;font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#527078">Regulierungs-CHECK Promotion</td><td align="right" style="font-size:11px;font-weight:700;color:#789094">Digitale Gewinnausgabe</td></tr></table></td></tr>
        <tr><td style="border:1px solid #d8e7e4;border-radius:28px;background:#ffffff;overflow:hidden;box-shadow:0 18px 50px rgba(8,47,53,.10)">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr><td style="padding:28px 32px;background:#082f35;color:#ffffff"><table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td><strong style="font-size:20px">Regulierungs-CHECK</strong><div style="margin-top:5px;color:#8ad7d0;font-size:11px;font-weight:800;letter-spacing:1.8px;text-transform:uppercase">Promotion-Glücksrad</div></td><td align="right"><span style="display:inline-block;width:44px;height:44px;line-height:44px;text-align:center;border-radius:50%;background:#f4c95d;color:#082f35;font-size:20px;font-weight:900">{{ $isCode ? '€' : '✓' }}</span></td></tr></table></td></tr>
                <tr><td style="padding:38px 32px 34px">
                    <div style="font-size:12px;font-weight:800;letter-spacing:1.7px;text-transform:uppercase;color:#0d9187">{{ $eyebrow }}</div>
                    <h1 style="margin:10px 0 0;font-size:33px;line-height:1.12;letter-spacing:-1px;color:#082f35">{{ $headline }}</h1>
                    @if($type === 'approved')
                        <p style="margin:18px 0 0;font-size:17px;line-height:1.65;color:#48666c">Dein Gewinn <strong style="color:#082f35">{{ $result->label_snapshot }}</strong> wurde bestätigt. Wir prüfen jetzt die sichere digitale Auslieferung.</p>
                    @elseif($isProfileRequired)
                        <p style="margin:18px 0 0;font-size:17px;line-height:1.65;color:#48666c">Für die sichere Auslieferung von <strong style="color:#082f35">{{ $result->label_snapshot }}</strong> benötigen wir noch deinen vollständigen Namen und deine Anschrift.</p>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:24px;border-radius:18px;background:#fff7df"><tr><td style="padding:20px"><div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:1.3px;color:#845d09">Noch erforderlich</div><div style="margin-top:8px;font-size:14px;line-height:1.7;color:#6f5318">Vorname, Nachname, Straße, PLZ, Ort und Land</div></td></tr></table>
                    @else
                        <p style="margin:18px 0 0;font-size:17px;line-height:1.65;color:#48666c">Dein Gewinn <strong style="color:#082f35">{{ $result->label_snapshot }}</strong> wurde versendet. Behandle den folgenden Code wie Bargeld und gib ihn nicht weiter.</p>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:24px;border:1px solid #d9c167;border-radius:18px;background:#fff8da"><tr><td align="center" style="padding:24px 18px"><div style="font-size:10px;font-weight:800;letter-spacing:1.6px;text-transform:uppercase;color:#83680c">Amazon-Gutscheincode</div><div style="margin-top:10px;font-family:Consolas,'Courier New',monospace;font-size:23px;font-weight:800;line-height:1.35;letter-spacing:1.5px;word-break:break-all;color:#082f35">{{ $code }}</div></td></tr></table>
                    @endif
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:26px"><tr><td align="center" style="border-radius:14px;background:#0d9187"><a href="{{ $participantUrl }}" style="display:block;padding:15px 22px;color:#ffffff;text-align:center;text-decoration:none;font-size:15px;font-weight:800">{{ $isProfileRequired ? 'Profil jetzt vervollständigen' : 'Gewinnstatus ansehen' }}&nbsp;&nbsp;›</a></td></tr></table>
                    @if($isCode)<p style="margin:22px 0 0;font-size:12px;line-height:1.6;color:#789094">Der Gutscheincode wird aus Sicherheitsgründen ausschließlich in dieser E-Mail angezeigt. Regulierungs-CHECK fragt ihn niemals telefonisch oder per Chat ab.</p>@endif
                </td></tr>
            </table>
        </td></tr>
        <tr><td align="center" style="padding:20px 20px 0;font-size:11px;line-height:1.6;color:#789094">Bei Fragen zu deiner Teilnahme wende dich bitte an das Promotion-Team.</td></tr>
    </table>
</td></tr></table>
</body>
</html>
