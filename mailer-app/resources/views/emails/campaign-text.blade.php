@if($branding['logo_label']){!! $branding['logo_label'] !!}

@endif
@if($branding['header']){!! $branding['header'] !!}

@endif
@if($branding['info_lines'])@foreach($branding['info_lines'] as $line){{ $line['label'] ? $line['label'].($line['value'] ? ' : ' : '') : '' }}{!! $line['value'] !!}
@endforeach
@endif
{!! $body !!}
@if($formUrl)

{!! $formButton !!} : {{ $formUrl }}
@endif
@if($buttonText && $buttonUrl)

{!! $buttonText !!} : {{ $buttonUrl }}
@endif

--
@if($branding['footer']){!! $branding['footer'] !!}
@endif
Se désinscrire : {{ $unsubscribeUrl }}
