@if($branding['header']){!! $branding['header'] !!}

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
