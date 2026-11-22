@php
    /** @var array $branding */
    $b = $branding;
    // Logo intégré au message (pièce jointe inline / CID) ; en aperçu : data-URI.
    $logoSrc = null;
    if ($logoFile) {
        $logoSrc = isset($message) && $message instanceof \Illuminate\Mail\Message
            ? $message->embed($logoFile)
            : 'data:'.mime_content_type($logoFile).';base64,'.base64_encode(file_get_contents($logoFile));
    }
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
</head>
<body style="margin:0;padding:0;background:{{ $b['bg'] }};">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $b['bg'] }};">
    <tr><td align="center" style="padding:28px 12px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
               style="max-width:600px;background:{{ $b['card'] }};border-radius:10px;overflow:hidden;font-family:{{ $b['font'] }};color:{{ $b['text'] }};">
            <tr><td style="height:5px;background:{{ $b['primary'] }};font-size:0;line-height:0;">&nbsp;</td></tr>

            @if($logoSrc)
                <tr><td align="{{ $b['logo_align'] }}" style="padding:26px 32px 4px;">
                    <img src="{{ $logoSrc }}" alt="{{ config('app.name') }}" width="{{ $b['logo_width'] }}" style="display:block;max-width:100%;height:auto;border:0;">
                </td></tr>
            @endif

            @if($b['header'])
                <tr><td style="padding:18px 32px 0;font-size:20px;font-weight:bold;color:{{ $b['primary'] }};">{{ $b['header'] }}</td></tr>
            @endif

            <tr><td style="padding:20px 32px 8px;font-size:15px;line-height:1.65;">{!! nl2br(e($body)) !!}</td></tr>

            @if($formUrl)
                <tr><td align="center" style="padding:16px 32px 8px;">
                    <a href="{{ $formUrl }}" style="display:inline-block;background:{{ $b['primary'] }};color:#ffffff;text-decoration:none;font-weight:bold;font-size:16px;padding:14px 28px;border-radius:8px;">{{ $formButton }}</a>
                </td></tr>
            @endif

            @if($buttonText && $buttonUrl)
                <tr><td style="padding:14px 32px 8px;">
                    <a href="{{ $buttonUrl }}" style="display:inline-block;background:{{ $b['primary'] }};color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:8px;">{{ $buttonText }}</a>
                </td></tr>
            @endif

            <tr><td style="padding:24px 32px;font-size:12px;line-height:1.5;color:#8a8fa3;border-top:1px solid #eceef3;">
                @if($b['footer']){!! nl2br(e($b['footer'])) !!}<br>@endif
                <a href="{{ $unsubscribeUrl }}" style="color:#8a8fa3;">Se désinscrire</a>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
