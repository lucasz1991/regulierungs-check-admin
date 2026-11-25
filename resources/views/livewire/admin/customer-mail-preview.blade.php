<!doctype html>
<html lang="de" style="height:100%;" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml">
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
    @media only screen and (max-width:600px) {
      .mail-space { padding:0 !important; }
      .mail-header { padding-top:20px !important; padding-bottom:16px !important; }
      .mail-header img { width:166px !important; }
      .mail-inner { padding-left:24px !important; padding-right:24px !important; }
    }
    @media only screen and (max-width:600px) {
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
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#ffffff" style="width:100%;border-collapse:collapse;background-color:transparent;color:#143b47;">
    <tr><td class="mail-space" align="center" style="padding:24px 12px 20px;text-align:center;background-color:transparent;color:#143b47;">
      <!--[if mso]><table role="presentation" cellpadding="0" cellspacing="0" border="0" width="760" style="width:570pt;border-collapse:collapse;"><tr><td><![endif]-->
      <table class="mail-frame" role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;max-width:760px;margin:0 auto;table-layout:fixed;border-collapse:collapse;color:#143b47;text-align:left;">
        <tr><td class="mail-background" background="https://www.regulierungs-check.de/site-images/mail/background-lines.png" bgcolor="#ffffff" valign="top" style="padding:0;background-color:#ffffff;background-image:url('https://www.regulierungs-check.de/site-images/mail/background-lines.png');background-position:center top;background-repeat:no-repeat;background-size:100% 100%;vertical-align:top;">
          <!-- Outlook Classic needs its own image fill; the shape grows with the message. -->
          <!--[if gte mso 9]>
          <v:rect fill="true" stroke="false" style="width:570pt;">
            <v:fill type="frame" src="https://www.regulierungs-check.de/site-images/mail/background-lines.png" color="#ffffff" />
            <v:textbox inset="0,0,0,0" style="mso-fit-shape-to-text:true;">
          <![endif]-->
          <div>
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;table-layout:fixed;border-collapse:collapse;color:#143b47;text-align:left;">
        <tr><td class="mail-inner mail-header" align="left" style="padding:29px 36px 22px;border-bottom:1px solid #dce5df;text-align:left;background-color:transparent;color:#143b47;font-size:0;line-height:0;">
          <a href="https://www.regulierungs-check.de/" style="color:#167668;text-decoration:none;"><img src="https://www.regulierungs-check.de/site-images/logo-xs.png" width="206" height="65" alt="Regulierungs-CHECK" style="display:block;width:206px;max-width:100%;height:auto;border:0;outline:none;text-decoration:none;"></a>
        </td></tr>
        <tr><td class="mail-inner" align="left" style="padding:25px 36px 0;text-align:left;background-color:transparent;color:#143b47;font-family:'Trebuchet MS',Arial,sans-serif;font-size:16px;line-height:26px;">
          @if (filled($greeting))
            <p style="margin:0;padding:0 0 17px;font-family:'Trebuchet MS',Arial,sans-serif;font-size:16px;line-height:26px;font-weight:bold;color:#143b47;">{{ $greeting }}</p>
          @endif
          @foreach ($introLines as $line)
            <p style="margin:0;padding:0 0 {{ $loop->last ? 0 : 16 }}px;font-family:'Trebuchet MS',Arial,sans-serif;font-size:16px;line-height:26px;color:#344f58;">{{ $line }}</p>
          @endforeach
        </td></tr>
        @if (filled($actionText) && filled($actionUrl))
        <tr><td class="mail-inner" align="left" style="padding:25px 36px 28px;text-align:left;background-color:transparent;color:#143b47;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;border-spacing:0;margin:0;text-align:left;">
            <tr><td align="left" bgcolor="#f9b029" style="padding:0;text-align:left;background-color:#f9b029;color:#143b47;border:0;border-radius:8px;mso-padding-alt:12px 20px;">
              <a href="{{ $actionUrl }}" style="display:inline-block;background-color:#f9b029;border-radius:8px;padding:12px 20px;font-family:'Trebuchet MS',Arial,sans-serif;font-size:14px;line-height:20px;font-weight:bold;color:#143b47;text-align:center;text-decoration:none;">{{ $actionText }}&nbsp;&nbsp;&#8594;</a>
            </td></tr>
          </table>
        </td></tr>
        @endif
        @if ($outroLines)
        <tr><td class="mail-inner" align="left" style="padding:0 36px 18px;text-align:left;font-family:'Trebuchet MS',Arial,sans-serif;font-size:16px;line-height:26px;color:#344f58;">
          @foreach ($outroLines as $line)
            <p style="margin:0;padding:0 0 {{ $loop->last ? 0 : 16 }}px;font-family:'Trebuchet MS',Arial,sans-serif;font-size:16px;line-height:26px;color:#344f58;">{{ $line }}</p>
          @endforeach
        </td></tr>
        @endif
        @if (filled($actionText) && filled($actionUrl))
        <tr><td class="mail-inner" align="left" style="padding:0 36px 18px;text-align:left;color:#526c75;font-family:'Trebuchet MS',Arial,sans-serif;font-size:12px;line-height:18px;">
          Falls der Button nicht funktioniert, öffne diesen Link im Browser:<br>
          <a href="{{ $actionUrl }}" style="color:#167668;text-decoration:underline;overflow-wrap:anywhere;word-break:break-all;">{{ $displayableActionUrl }}</a>
        </td></tr>
        @endif
        <tr><td class="mail-inner" align="left" style="padding:{{ filled($actionText) && filled($actionUrl) ? '0' : '24px' }} 36px 26px;text-align:left;background-color:transparent;color:#526c75;font-family:'Trebuchet MS',Arial,sans-serif;font-size:14px;line-height:23px;">Du hast Fragen? Schreib uns einfach.<br><a href="mailto:info@regulierungs-check.de" style="color:#167668;text-decoration:underline;">info@regulierungs-check.de</a></td></tr>
        <tr><td class="mail-inner" style="padding:0 36px;background-color:transparent;color:#ffffff;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;border-top:1px solid #dce5df;"><tr><td style="padding:0;font-size:0;line-height:0;">&nbsp;</td></tr></table>
        </td></tr>
        <tr><td class="mail-inner" align="left" style="padding:14px 36px 18px;text-align:left;background-color:transparent;color:#143b47;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;max-width:760px;border-collapse:collapse;margin:0 auto;text-align:left;font-family:'Trebuchet MS',Arial,sans-serif;background-color:transparent;color:#143b47;">
            <tr><td colspan="3" align="left" style="padding:0 0 8px;text-align:left;background-color:transparent;color:#526c75;font-family:'Trebuchet MS',Arial,sans-serif;font-size:13px;line-height:20px;">
                @foreach ($signoffLines as $signoffLine)
                  {{ $signoffLine }}@unless ($loop->last)<br>@endunless
                @endforeach
              </td></tr>
            <tr>
              <td class="signature-brand" width="24%" valign="top" align="left" style="width:24%;padding:2px 16px 0 0;text-align:left;vertical-align:top;background-color:transparent;color:#143b47;font-size:0;line-height:0;">
                <a href="https://www.regulierungs-check.de/" style="color:#167668;text-decoration:none;"><img src="https://www.regulierungs-check.de/site-images/logo-xs.png" width="124" height="39" alt="Regulierungs-CHECK" style="display:block;width:124px;max-width:100%;height:auto;border:0;outline:none;text-decoration:none;"></a>
              </td>
              <td class="signature-contacts" width="46%" valign="top" align="left" style="width:46%;padding:0 16px 0 17px;border-left:1px solid #cadbd2;text-align:left;vertical-align:top;background-color:transparent;color:#143b47;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;text-align:left;">
                  <tr>
                    <td width="24" valign="top" style="width:24px;padding:2px 0 4px;line-height:0;"><img src="https://www.regulierungs-check.de/site-images/mail/envelope.png" width="16" height="16" alt="" style="display:block;width:16px;height:16px;border:0;"></td>
                    <td valign="top" style="padding:0 0 4px;color:#344f58;font-family:'Trebuchet MS',Arial,sans-serif;font-size:13px;line-height:20px;overflow-wrap:break-word;"><a href="mailto:info@regulierungs-check.de" style="color:#143b47;text-decoration:none;">info@regulierungs-check.de</a></td>
                  </tr>
                  <tr>
                    <td width="24" valign="top" style="width:24px;padding:2px 0 4px;line-height:0;"><img src="https://www.regulierungs-check.de/site-images/mail/globe.png" width="16" height="16" alt="" style="display:block;width:16px;height:16px;border:0;"></td>
                    <td valign="top" style="padding:0 0 4px;color:#344f58;font-family:'Trebuchet MS',Arial,sans-serif;font-size:13px;line-height:20px;overflow-wrap:break-word;"><a href="https://www.regulierungs-check.de/" style="color:#167668;font-weight:bold;text-decoration:none;">regulierungs-check.de</a></td>
                  </tr>
                </table>
              </td>
              <td class="signature-address" width="30%" valign="top" align="left" style="width:30%;padding:0 0 0 17px;border-left:1px solid #cadbd2;text-align:left;vertical-align:top;color:#344f58;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;text-align:left;">
                  <tr>
                    <td width="24" valign="top" style="width:24px;padding:2px 0 0;line-height:0;"><img src="https://www.regulierungs-check.de/site-images/mail/location.png" width="16" height="16" alt="" style="display:block;width:16px;height:16px;border:0;"></td>
                    <td valign="top" style="padding:0;color:#344f58;font-family:'Trebuchet MS',Arial,sans-serif;font-size:13px;line-height:20px;">Oldesloer Str. 63<br>22457 Hamburg</td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr><td colspan="3" style="padding:8px 0 5px;background-color:transparent;color:#ffffff;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;border-top:1px solid #e1e9e4;"><tr><td style="padding:0;font-size:0;line-height:0;">&nbsp;</td></tr></table>
            </td></tr>
            <tr><td colspan="3" align="left" style="padding:0;text-align:left;background-color:transparent;color:#526c75;font-family:'Trebuchet MS',Arial,sans-serif;font-size:11px;line-height:16px;">
              <strong style="font-weight:normal;color:#526c75;">Regulierungs-Check RCQ GmbH</strong> · Geschäftsführer Birkan Özülkü<br>
              Amtsgericht Hamburg · HRB 193808 · USt-ID DE458707066
            </td></tr>
            <tr><td colspan="3" align="left" style="padding:3px 0 0;text-align:left;background-color:transparent;color:#526c75;font-family:'Trebuchet MS',Arial,sans-serif;font-size:11px;line-height:16px;">
              <a href="https://www.regulierungs-check.de/imprint" style="color:#526c75;text-decoration:underline;">Impressum</a>&nbsp;&nbsp;·&nbsp;&nbsp;<a href="https://www.regulierungs-check.de/privacypolicy" style="color:#526c75;text-decoration:underline;">Datenschutz</a>
            </td></tr>
          </table>
        </td></tr>
      </table>
          </div>
          <!--[if gte mso 9]></v:textbox></v:rect><![endif]-->
        </td></tr>
      </table>
      <!--[if mso]></td></tr></table><![endif]-->
    </td></tr>
  </table>
</body>
</html>