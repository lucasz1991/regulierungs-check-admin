<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ihr Mitarbeiterzugang für Regulierungs-CHECK</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f4;color:#17343a;font-family:Arial,Helvetica,sans-serif;line-height:1.55">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f1f5f4">
    <tr>
        <td align="center" style="padding:28px 14px">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;background:#ffffff;border:1px solid #dce8e6;border-radius:18px;overflow:hidden">
                <tr>
                    <td style="background:#082f35;padding:28px 34px">
                        <img src="{{ asset('site-images/logo/logo-white.png') }}" width="190" alt="Regulierungs-CHECK" style="display:block;max-width:190px;height:auto;border:0">
                        <div style="margin-top:20px;color:#8dd7d1;font-size:12px;font-weight:bold;letter-spacing:.14em;text-transform:uppercase">Mitarbeiterzugang</div>
                        <h1 style="margin:7px 0 0;color:#ffffff;font-size:26px;line-height:1.25">Willkommen im Team {{ $invitation->team->name }}</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:30px 34px">
                        <p style="margin:0 0 16px">Hallo,</p>
                        <p style="margin:0 0 18px">ein Administrator hat für Sie einen Mitarbeiterzugang bei Regulierungs-CHECK vorbereitet.</p>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 22px;background:#edf8f6;border-left:4px solid #0d9187;border-radius:10px">
                            <tr>
                                <td style="padding:16px 18px">
                                    <div style="font-size:12px;color:#647d82;text-transform:uppercase;letter-spacing:.08em">Zugewiesenes Team</div>
                                    <div style="margin-top:3px;font-size:18px;font-weight:bold;color:#082f35">{{ $invitation->team->name }}</div>
                                    @if($invitation->position)
                                        <div style="margin-top:8px;color:#38545a">Funktion: <strong>{{ $invitation->position }}</strong></div>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 22px">Öffnen Sie den folgenden Link und legen Sie Ihren Namen sowie ein persönliches Passwort fest. Danach sind Sie direkt angemeldet. Eine zusätzliche E-Mail-Verifizierung ist nicht notwendig.</p>

                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 22px">
                            <tr>
                                <td style="border-radius:10px;background:#0d766e">
                                    <a href="{{ $acceptUrl }}" style="display:inline-block;padding:13px 22px;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none">Passwort festlegen &amp; Zugang aktivieren</a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0;color:#5e7479;font-size:13px">Der Einrichtungslink ist bis <strong>{{ $invitation->expires_at->timezone(config('app.timezone'))->format('d.m.Y, H:i') }} Uhr</strong> gültig und kann nur einmal verwendet werden.</p>
                    </td>
                </tr>
                <tr>
                    <td style="border-top:1px solid #e5eeec;background:#f8fbfa;padding:20px 34px;color:#708287;font-size:12px">
                        Falls Sie diesen Zugang nicht erwartet haben, verwenden Sie den Link nicht und informieren Sie den Administrator. Antworten Sie nicht mit einem Passwort auf diese E-Mail.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
