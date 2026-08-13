<!DOCTYPE html>
<html lang="de">
<body style="font-family:Arial,sans-serif;color:#172033;line-height:1.5">
    <h1 style="font-size:22px">Einladung zum Promotion-Team</h1>
    <p>Sie wurden als Mitarbeiterin oder Mitarbeiter fuer die Promotion-Konsole eingeladen.</p>
    @if($invitation->position)
        <p>Funktion: <strong>{{ $invitation->position }}</strong></p>
    @endif
    <p>Die Einladung ist bis {{ $invitation->expires_at->timezone(config('app.timezone'))->format('d.m.Y, H:i') }} Uhr gueltig.</p>
    <p><a href="{{ $acceptUrl }}" style="display:inline-block;background:#0f766e;color:white;padding:12px 18px;border-radius:8px;text-decoration:none">Konto einrichten</a></p>
    <p style="font-size:12px;color:#64748b">Falls Sie diese Einladung nicht erwartet haben, ignorieren Sie diese Nachricht.</p>
</body>
</html>
