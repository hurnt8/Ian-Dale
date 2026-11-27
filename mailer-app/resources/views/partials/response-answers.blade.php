{{-- Réponses d'un destinataire (champ → valeur). Variable : $answers (liste [{label, type, value}]) --}}
<dl class="row mb-0">
    @foreach($answers as $a)
        <dt class="col-sm-4 text-secondary fw-semibold small py-1">{{ $a['label'] }}</dt>
        <dd class="col-sm-8 py-1 mb-0" style="white-space: pre-line">@if($a['type'] === 'rating' && $a['value'])<span style="color:#f5a524">{{ str_repeat('★', (int) $a['value']) }}</span><span style="color:#d4d7e1">{{ str_repeat('★', 5 - (int) $a['value']) }}</span> <span class="small text-secondary">{{ $a['value'] }}/5</span>@else{{ \App\Support\ResponseForm::display($a) }}@endif</dd>
    @endforeach
</dl>
