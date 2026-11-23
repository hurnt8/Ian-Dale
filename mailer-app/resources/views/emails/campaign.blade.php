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

            {{-- En-tête : logo + libellé à côté (comme « [logo] Le service clients ») --}}
            @if($b['logo_label'])
                <tr><td align="{{ $b['logo_align'] }}" style="padding:24px 32px 6px;">
                    <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                        @if($logoSrc)
                            <td valign="middle" style="padding-right:12px;">
                                <img src="{{ $logoSrc }}" alt="{{ config('app.name') }}" width="{{ min($b['logo_width'], 120) }}" style="display:block;max-width:100%;height:auto;border:0;">
                            </td>
                        @endif
                        <td valign="middle" style="font-size:17px;font-weight:bold;color:{{ $b['text'] }};">{{ $b['logo_label'] }}</td>
                    </tr></table>
                </td></tr>
            @elseif($logoSrc)
                <tr><td align="{{ $b['logo_align'] }}" style="padding:26px 32px 4px;">
                    <img src="{{ $logoSrc }}" alt="{{ config('app.name') }}" width="{{ $b['logo_width'] }}" style="display:block;max-width:100%;height:auto;border:0;">
                </td></tr>
            @endif

            @if($b['header'])
                <tr><td style="padding:16px 32px 4px;font-size:25px;font-weight:bold;line-height:1.2;color:{{ $b['text'] }};">{{ $b['header'] }}</td></tr>
            @endif

            {{-- Bloc d'informations à libellés (paramétré par l'admin), encadré comme la maquette --}}
            @if($b['info_lines'])
                <tr><td style="padding:10px 32px 6px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e6e6e6;border-bottom:1px solid #e6e6e6;">
                        <tr>
                            <td valign="top" width="30" style="padding:12px 0;">
                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='%23999'%3E%3Ccircle cx='12' cy='7' r='4'/%3E%3Cpath d='M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8z'/%3E%3C/svg%3E" width="20" height="20" alt="" style="display:block;border:0;">
                            </td>
                            <td style="padding:12px 4px;font-size:13px;line-height:1.75;color:{{ $b['text'] }};">
                                @foreach($b['info_lines'] as $line)
                                    @php $sep = ($line['label'] !== '' && $line['value'] !== '') ? ' : ' : ''; @endphp
                                    @if($line['label'])<strong>{{ $line['label'] }}</strong>@endif{{ $sep }}{{ $line['value'] }}@if(! $loop->last)<br>@endif
                                @endforeach
                            </td>
                        </tr>
                    </table>
                </td></tr>
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
