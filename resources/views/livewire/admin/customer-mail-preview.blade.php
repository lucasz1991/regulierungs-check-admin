<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
  <meta name="color-scheme" content="light">
  <meta name="supported-color-schemes" content="light">
  <title>{{ $subject ?: config('app.name') }}</title>

  <style>
    table, td { mso-table-lspace:0pt; mso-table-rspace:0pt; }
    a[x-apple-data-detectors] { color:inherit !important; text-decoration:none !important; }
    @media only screen {
      .mail-frame { background-color:transparent !important; }
    }
    @media only screen and (max-width:1199px) {
      .mail-arc-left, .mail-arc-right { background-size:180px auto !important; }
      .mail-arc-left { background-position:left -90px top !important; }
      .mail-arc-right { background-position:right -90px bottom !important; }
    }
    @media only screen and (max-width:600px) {
      .mail-arc-left, .mail-arc-right { background-size:128px auto !important; }
      .mail-arc-left { background-position:left -75px top !important; }
      .mail-arc-right { background-position:right -75px bottom !important; }
      .mail-space { padding:0 8px !important; }
      .mail-signature { padding-bottom:28px !important; }
      .mail-header { padding-top:24px !important; padding-bottom:22px !important; }
      .mail-header img { width:166px !important; }
      .mail-inner { padding-left:20px !important; padding-right:20px !important; }
    }
    @media only screen and (max-width:540px) {
      .signature-brand, .signature-contacts, .signature-address { display:block !important; width:auto !important; border:0 !important; }
      .signature-brand { padding:0 0 12px !important; }
      .signature-contacts { padding:0 0 8px !important; }
      .signature-address { padding:0 !important; }
    }
    @media only screen and (max-width:360px) {
      .mail-inner { padding-left:20px !important; padding-right:20px !important; }
    }
  </style>
</head>
<body bgcolor="#ffffff" style="margin:0;padding:0;background-color:#ffffff;color:#143b47;font-family:'Trebuchet MS',Arial,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
  @if (filled($newsletter['preheader'] ?? null))
    <div aria-hidden="true" style="display:none!important;mso-hide:all;visibility:hidden;opacity:0;overflow:hidden;max-height:0;max-width:0;font-size:1px;line-height:1px;color:#fff;">{{ $newsletter['preheader'] }}</div>
  @endif
  <!--[if !mso]><!-->
  <table class="mail-background" role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;">
    <tr><td class="mail-arc-left" background="https://www.regulierungs-check.de/site-images/mail/background-circles-left.png" style="padding:0;background-image:url('https://www.regulierungs-check.de/site-images/mail/background-circles-left.png');background-position:left top;background-repeat:no-repeat;background-size:300px auto;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;">
        <tr><td class="mail-arc-right mail-space" align="center" valign="top" background="https://www.regulierungs-check.de/site-images/mail/background-circles-right.png" style="padding:24px 12px 0;background-image:url('https://www.regulierungs-check.de/site-images/mail/background-circles-right.png');background-position:right bottom;background-repeat:no-repeat;background-size:300px auto;">
  <!--<![endif]-->
  <!--[if mso]>
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;">
    <tr>
      <td align="left" valign="top" style="padding:0;font-size:0;line-height:0;"><img src="https://www.regulierungs-check.de/site-images/mail/background-circles-left.png" width="65" height="104" alt="" style="display:block;border:0;"></td>
      <td width="630" align="center" valign="top" style="width:472.5pt;padding:18pt 0 0;">
  <![endif]-->
      <table class="mail-frame" role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#ffffff" style="width:100%;max-width:760px;margin:0 auto;table-layout:fixed;border-collapse:collapse;background-color:#ffffff;color:#143b47;text-align:left;">
        <tr><td class="mail-inner mail-header" align="left" style="padding:29px 36px 29px;border-bottom:1px solid #dce5df;text-align:left;background-color:transparent;color:#143b47;font-size:0;line-height:0;">
          @isset($newsletter)
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="left" valign="middle">
          @endisset
          <a href="https://www.regulierungs-check.de/" style="color:#167668;text-decoration:none;"><img src="https://www.regulierungs-check.de/site-images/logo-xs.png" width="206" height="65" alt="Regulierungs-CHECK" style="display:block;width:206px;max-width:100%;height:auto;border:0;outline:none;text-decoration:none;"></a>
          @isset($newsletter)
          </td><td align="right" valign="middle" style="padding-left:12px;color:#24695d;font:600 13px/18px 'Trebuchet MS',Arial,sans-serif;white-space:nowrap;"><span aria-hidden="true" style="color:#77a795;">&bull;&nbsp;</span>{{ $newsletter['kind'] === 'news' ? 'News' : 'Newsletter' }}</td></tr></table>
          @endisset
        </td></tr>
        <tr><td class="mail-inner" align="left" style="padding:32px 36px 0;text-align:left;background-color:transparent;color:#143b47;font-family:'Trebuchet MS',Arial,sans-serif;font-size:16px;line-height:28px;">
          @if (filled($greeting))
            <p style="margin:0;padding:0 0 22px;font-family:'Trebuchet MS',Arial,sans-serif;font-size:16px;line-height:28px;font-weight:bold;color:#143b47;">{{ $greeting }}</p>
          @endif
          @if (($newsletter['kind'] ?? null) === 'news')
            @if (!empty($newsletter['image']['url']))
              <a href="{{ $actionUrl }}" style="display:block;margin:0 0 18px;color:#167668;text-decoration:none;">
                <img src="{{ $newsletter['image']['url'] }}" width="558" alt="{{ $newsletter['image']['alt'] ?? '' }}" style="display:block;width:100%;height:auto;border:0;">
              </a>
            @endif
            @if (filled($newsletter['title'] ?? null))
              <h1 style="margin:0;padding:0 0 12px;font-family:'Trebuchet MS',Arial,sans-serif;font-size:24px;line-height:30px;color:#143b47;">{{ $newsletter['title'] }}</h1>
            @endif
          @endif
          @foreach ($introLines as $line)
            <p style="margin:0;padding:0 0 {{ $loop->last ? 0 : 20 }}px;font-family:'Trebuchet MS',Arial,sans-serif;font-size:16px;line-height:28px;color:#344f58;">{{ $line }}</p>
          @endforeach
        </td></tr>
        @if (filled($actionText) && filled($actionUrl))
        <tr><td class="mail-inner" align="left" style="padding:32px 36px 34px;text-align:left;background-color:transparent;color:#143b47;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;border-spacing:0;margin:0;text-align:left;">
            <tr><td align="left" bgcolor="#f9b029" style="padding:0;text-align:left;background-color:#f9b029;color:#143b47;border:0;border-radius:8px;mso-padding-alt:14px 22px;">
              <a href="{{ $actionUrl }}" style="display:inline-block;word-wrap:break-word;overflow-wrap:anywhere;background-color:#f9b029;border-radius:8px;padding:14px 22px;font-family:'Trebuchet MS',Arial,sans-serif;font-size:14px;line-height:20px;font-weight:bold;color:#143b47;text-align:center;text-decoration:none;">{{ $actionText }}&nbsp;&nbsp;&#8594;</a>
            </td></tr>
          </table>
        </td></tr>
        @endif
        @if ($outroLines)
        <tr><td class="mail-inner" align="left" style="padding:0 36px 24px;text-align:left;font-family:'Trebuchet MS',Arial,sans-serif;font-size:16px;line-height:28px;color:#344f58;">
          @foreach ($outroLines as $line)
            <p style="margin:0;padding:0 0 {{ $loop->last ? 0 : 20 }}px;font-family:'Trebuchet MS',Arial,sans-serif;font-size:16px;line-height:28px;color:#344f58;">{{ $line }}</p>
          @endforeach
        </td></tr>
        @endif
        @if (filled($actionText) && filled($actionUrl))
        <tr><td class="mail-inner" align="left" style="padding:0 36px 24px;text-align:left;color:#526c75;font-family:'Trebuchet MS',Arial,sans-serif;font-size:12px;line-height:18px;">
          Falls der Button nicht funktioniert, öffne diesen Link im Browser:<br>
          <a href="{{ $actionUrl }}" style="color:#167668;text-decoration:underline;overflow-wrap:anywhere;word-break:break-all;">{{ $displayableActionUrl }}</a>
        </td></tr>
        @endif
        @isset($newsletter)
        <tr><td class="mail-inner" style="padding:{{ filled($actionUrl) ? '0' : '24px' }} 36px 20px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#edf5f1" style="background-color:#edf5f1;border-radius:5px;">
            <tr><td style="padding:0 0 0 14px;">
              <a href="{{ $newsletter['settings_url'] }}" style="display:block;padding:10px 0;color:#24695d;font:600 13px/20px 'Trebuchet MS',Arial,sans-serif;text-decoration:underline;">Newsletter-Einstellungen ändern</a>
            </td><td width="24" align="right" valign="middle" aria-hidden="true" style="padding-right:14px;color:#24695d;font:16px/20px Arial,sans-serif;">&#8250;</td></tr>
          </table>
        </td></tr>
        @else
        <tr><td class="mail-inner" align="left" style="padding:{{ filled($actionText) && filled($actionUrl) ? '0' : '30px' }} 36px 32px;text-align:left;background-color:transparent;color:#526c75;font-family:'Trebuchet MS',Arial,sans-serif;font-size:14px;line-height:23px;">Du hast Fragen? Schreib uns einfach.<br><a href="mailto:info@regulierungs-check.de" style="color:#167668;text-decoration:underline;">info@regulierungs-check.de</a></td></tr>
        @endisset
        <tr><td class="mail-inner" style="padding:0 36px;background-color:transparent;color:#ffffff;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;border-top:1px solid #dce5df;"><tr><td style="padding:0;font-size:0;line-height:0;">&nbsp;</td></tr></table>
        </td></tr>
        <tr><td class="mail-inner mail-signature" align="left" style="padding:20px 36px 38px;text-align:left;background-color:transparent;color:#143b47;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;margin:0 auto;text-align:left;font-family:'Trebuchet MS',Arial,sans-serif;background-color:transparent;color:#143b47;">
            <tr><td align="left" style="padding:0 0 12px;text-align:left;background-color:transparent;color:#526c75;font-family:'Trebuchet MS',Arial,sans-serif;font-size:13px;line-height:20px;">
                @foreach ($signoffLines as $signoffLine)
                  {{ $signoffLine }}@unless ($loop->last)<br>@endunless
                @endforeach
              </td></tr>
            <tr><td align="left">
              <!--[if mso]><table role="presentation" cellpadding="0" cellspacing="0" border="0" width="536"><tr><td><![endif]-->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;max-width:536px;border-collapse:collapse;text-align:left;">
                <tr>
                  <td class="signature-brand" width="26%" valign="top" align="left" style="width:26%;padding:2px 16px 0 0;text-align:left;vertical-align:top;background-color:transparent;color:#143b47;font-size:0;line-height:0;">
                    <a href="https://www.regulierungs-check.de/" style="color:#167668;text-decoration:none;"><img src="https://www.regulierungs-check.de/site-images/logo-xs.png" width="124" height="39" alt="Regulierungs-CHECK" style="display:block;width:124px;max-width:100%;height:auto;border:0;outline:none;text-decoration:none;"></a>
                  </td>
                  <td class="signature-contacts" width="43%" valign="top" align="left" style="width:43%;padding:0 12px 0 14px;border-left:1px solid #cadbd2;text-align:left;vertical-align:top;background-color:transparent;color:#143b47;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;text-align:left;">
                      <tr>
                        <td width="21" valign="top" style="width:21px;padding:2px 0;line-height:0;"><img src="https://www.regulierungs-check.de/site-images/mail/envelope.png" width="14" height="14" alt="" style="display:block;width:14px;height:14px;border:0;"></td>
                        <td valign="top" style="padding:0 0 2px;color:#344f58;font-family:'Trebuchet MS',Arial,sans-serif;font-size:12px;line-height:20px;mso-line-height-rule:exactly;overflow-wrap:break-word;"><a href="mailto:info@regulierungs-check.de" style="color:#143b47;text-decoration:none;">info@regulierungs-check.de</a></td>
                      </tr>
                      <tr>
                        <td width="21" valign="top" style="width:21px;padding:2px 0;line-height:0;"><img src="https://www.regulierungs-check.de/site-images/mail/globe.png" width="14" height="14" alt="" style="display:block;width:14px;height:14px;border:0;"></td>
                        <td valign="top" style="padding:0 0 2px;color:#344f58;font-family:'Trebuchet MS',Arial,sans-serif;font-size:12px;line-height:20px;mso-line-height-rule:exactly;overflow-wrap:break-word;"><a href="https://www.regulierungs-check.de/" style="color:#167668;font-weight:bold;text-decoration:none;">regulierungs-check.de</a></td>
                      </tr>
                    </table>
                  </td>
                  <td class="signature-address" width="31%" valign="top" align="left" style="width:31%;padding:0;text-align:left;vertical-align:top;color:#344f58;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;text-align:left;">
                      <tr>
                        <td width="21" valign="top" style="width:21px;padding:2px 0 0;line-height:0;"><img src="https://www.regulierungs-check.de/site-images/mail/location.png" width="14" height="14" alt="" style="display:block;width:14px;height:14px;border:0;"></td>
                        <td valign="top" style="padding:0;color:#344f58;font-family:'Trebuchet MS',Arial,sans-serif;font-size:12px;line-height:20px;mso-line-height-rule:exactly;">Oldesloer Str. 63<br>22457 Hamburg</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              <!--[if mso]></td></tr></table><![endif]-->
            </td></tr>
            <tr><td style="padding:11px 0 7px;background-color:transparent;color:#ffffff;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;border-top:1px solid #e1e9e4;"><tr><td style="padding:0;font-size:0;line-height:0;">&nbsp;</td></tr></table>
            </td></tr>
            <tr><td align="left" style="padding:0;text-align:left;background-color:transparent;color:#526c75;font-family:'Trebuchet MS',Arial,sans-serif;font-size:11px;line-height:16px;">
              <strong style="font-weight:normal;color:#526c75;">Regulierungs-Check RCQ GmbH</strong> · Geschäftsführer Birkan Özülkü<br>
              Amtsgericht Hamburg · HRB 193808 · USt-ID DE458707066
            </td></tr>
            <tr><td align="left" style="padding:3px 0 0;text-align:left;background-color:transparent;color:#526c75;font-family:'Trebuchet MS',Arial,sans-serif;font-size:11px;line-height:16px;">
              <a href="https://www.regulierungs-check.de/imprint" style="color:#526c75;text-decoration:underline;">Impressum</a>&nbsp;&nbsp;·&nbsp;&nbsp;<a href="https://www.regulierungs-check.de/privacypolicy" style="color:#526c75;text-decoration:underline;">Datenschutz</a>
              @isset($newsletter)
                &nbsp;&nbsp;·&nbsp;&nbsp;<a href="{{ $newsletter['unsubscribe_url'] }}" style="color:#526c75;text-decoration:underline;">Newsletter abbestellen</a>
              @endisset
            </td></tr>
          </table>
        </td></tr>
      </table>
  <!--[if mso]>
      </td>
      <td align="right" valign="bottom" style="padding:0;font-size:0;line-height:0;"><img src="https://www.regulierungs-check.de/site-images/mail/background-circles-right.png" width="65" height="104" alt="" style="display:block;border:0;"></td>
    </tr>
  </table>
  <![endif]-->
  <!--[if !mso]><!-->
        </td></tr>
      </table>
    </td></tr>
  </table>
  <!--<![endif]-->
</body>
</html>
