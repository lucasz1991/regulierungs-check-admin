<!doctype html>
<html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Glücksrad</title></head>
<body style="margin:0;background:#eef7f5;font-family:Arial,sans-serif;color:#0b3038"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:28px 12px"><tr><td align="center"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fff;border:1px solid #dcecea;border-radius:24px;overflow:hidden"><tr><td style="background:#0b3038;padding:26px 30px;color:#fff"><strong style="font-size:20px">Regulierungs-CHECK</strong><div style="margin-top:5px;color:#8dd7d1;font-size:13px;letter-spacing:.08em;text-transform:uppercase">Promotion-Glücksrad</div></td></tr><tr><td style="padding:32px 30px">
@if($type === 'approved')
<h1 style="margin:0;font-size:28px">Dein Gewinn wurde bestätigt</h1><p style="line-height:1.6">Dein Gewinn „{{ $result->label_snapshot }}“ ist bestätigt. Wir bereiten die digitale Auslieferung vor.</p>
@elseif($type === 'profile_required')
<h1 style="margin:0;font-size:28px">Fast geschafft</h1><p style="line-height:1.6">Für die sichere Auslieferung deines Gewinns benötigen wir noch deinen vollständigen Namen und deine Anschrift.</p>
@else
<h1 style="margin:0;font-size:28px">Dein Amazon-Gutscheincode</h1><p style="line-height:1.6">Dein Gewinn „{{ $result->label_snapshot }}“ wurde versendet. Bewahre diesen Code vertraulich auf.</p><div style="margin:22px 0;padding:18px;border-radius:12px;background:#f3f8f7;font-family:monospace;font-size:20px;font-weight:700;word-break:break-all">{{ $code }}</div>
@endif
<a href="{{ $participantUrl }}" style="display:inline-block;margin-top:18px;padding:14px 20px;border-radius:12px;background:#0d9187;color:#fff;text-decoration:none;font-weight:700">Zum Glücksrad-Profil</a>
</td></tr></table></td></tr></table></body></html>
