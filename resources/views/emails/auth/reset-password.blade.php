<!doctype html>
<html lang="de">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Passwort zurücksetzen | Regulierungs-CHECK</title></head>
<body style="margin:0;background:#f3f6f6;color:#243b40;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 16px;"><tr><td align="center">
<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="width:100%;max-width:600px;background:#ffffff;">
<tr><td style="padding:28px 32px;background:#082f35;color:#ffffff;font-size:24px;font-weight:bold;">Regulierungs-CHECK</td></tr>
<tr><td style="padding:32px;font-size:16px;line-height:1.6;">
<h1 style="margin:0 0 24px;font-size:25px;line-height:1.3;">Passwort zurücksetzen</h1>
<p>Hallo{{ $name ? ' '.$name : '' }},</p>
<p>für dein Konto bei Regulierungs-CHECK wurde ein neues Passwort angefordert: <strong>{{ $accountLabel }}</strong>.</p>
<p>Über den folgenden Link kannst du ein neues Passwort festlegen. Der Link ist <strong>{{ $expiresIn }} Minuten</strong> gültig und kann nur einmal verwendet werden.</p>
<p style="margin:28px 0;"><a href="{{ $resetUrl }}" style="display:inline-block;padding:14px 22px;background:#082f35;color:#ffffff;text-decoration:none;font-weight:bold;border-radius:6px;">Neues Passwort festlegen</a></p>
<p>Falls du diese Anfrage nicht gestellt hast, kannst du diese E-Mail ignorieren. Dein bisheriges Passwort bleibt unverändert.</p>
<p>Viele Grüße<br>dein Regulierungs-CHECK Team</p>
<hr style="border:0;border-top:1px solid #e2e8e8;margin:28px 0;">
<p style="font-size:13px;color:#52686d;">Falls der Button nicht funktioniert, kopiere diesen Link in deinen Browser:</p>
<p style="font-size:12px;word-break:break-all;"><a href="{{ $resetUrl }}" style="color:#126c70;">{{ $resetUrl }}</a></p>
</td></tr>
<tr><td style="padding:20px 32px;background:#eaf0f0;font-size:12px;color:#52686d;">© {{ date('Y') }} <a href="{{ $appUrl }}" style="color:#52686d;">Regulierungs-CHECK</a></td></tr>
</table></td></tr></table>
</body>
</html>
