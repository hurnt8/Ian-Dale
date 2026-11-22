@extends('layouts.app')
@section('title', 'Nouvelle liste')
@section('subtitle', 'Choisissez le message, collez vos adresses, vérifiez-les puis soumettez.')

@section('content')
@if($templates->isEmpty())
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Aucun message n'est disponible pour le moment. Contactez l'administrateur.</div>
@else
<form method="POST" action="{{ route('propet.campaigns.store') }}" data-confirm data-confirm-message-fn="submitMessage"
      data-confirm-title="Soumettre cette liste ?" data-confirm-button="Soumettre" data-confirm-variant="success">
@csrf
<div class="row g-4">
    <div class="col-xl-7">
        {{-- 1. Choix du message --}}
        <div class="card mb-4">
            <div class="card-header"><span><span class="badge rounded-pill text-bg-dark me-2">1</span>Message à envoyer</span></div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($templates as $t)
                        @php($logo = \App\Support\MailBranding::dataUri($t->logo_path))
                        <div class="col-md-6">
                            <label class="choice h-100">
                                <input type="radio" name="message_template_id" value="{{ $t->id }}" data-preview="{{ route('preview.template', $t) }}" @checked($selected === $t->id)>
                                <div class="d-flex gap-2 align-items-center">
                                    @if($logo)<img src="{{ $logo }}" class="choice-logo" alt="">@else<span class="choice-logo d-grid place-items-center text-secondary" style="place-items:center;display:grid"><i class="bi bi-envelope"></i></span>@endif
                                    <div class="overflow-hidden">
                                        <div class="title text-truncate">{{ $t->name }} @if($t->is_default)<span class="pill tone-indigo ms-1">Recommandé</span>@endif</div>
                                        <div class="sub text-truncate">{{ $t->subject }}</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 2. Liste --}}
        <div class="card">
            <div class="card-header"><span><span class="badge rounded-pill text-bg-dark me-2">2</span>Adresses</span></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Nom de la liste</label>
                    <input name="title" value="{{ old('title') }}" class="form-control" maxlength="150" required placeholder="Ex. : Mairies Pas-de-Calais">
                </div>
                <label class="form-label" for="emails">Adresses email</label>
                <textarea id="emails" name="emails" rows="11" class="form-control emails" required
                    placeholder="contact@exemple.fr, autre@exemple.fr&#10;troisieme@exemple.fr">{{ old('emails') }}</textarea>
                <div class="form-text">Virgules, points-virgules, espaces ou retours à la ligne. Maximum {{ $maxRecipients }} adresses.</div>
                <div class="counter-box my-3" id="counter"><i class="bi bi-envelope-check me-1"></i> 0 adresse détectée</div>

                <div class="d-flex align-items-center flex-wrap gap-2">
                    <button type="button" id="verify-btn" class="btn btn-light"><i class="bi bi-patch-check me-1"></i> Vérifier les adresses</button>
                    <button class="btn btn-primary"><i class="bi bi-send me-1"></i> Soumettre la liste</button>
                </div>
                <p class="text-secondary small mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    {{ $requireApproval ? "L'envoi démarre après validation par un administrateur." : "L'envoi démarre dès la soumission." }}
                    @if($skipInvalid) Les adresses invalides sont écartées automatiquement. @endif
                </p>
            </div>
        </div>
        @include('partials.verify-widget', ['textarea' => 'emails', 'button' => 'verify-btn'])
    </div>

    {{-- Aperçu exact de l'email --}}
    <div class="col-xl-5">
        <div class="card position-sticky" style="top: 16px">
            <div class="card-header"><span><i class="bi bi-eye me-2"></i>Aperçu de l'email</span></div>
            <div class="card-body p-2">
                <iframe id="preview" class="mail-frame" title="Aperçu"></iframe>
            </div>
        </div>
    </div>
</div>
</form>

<script>
    (() => {
        // Aperçu du message sélectionné
        const frame = document.getElementById('preview');
        const radios = document.querySelectorAll('input[name="message_template_id"]');
        const show = () => { const r = document.querySelector('input[name="message_template_id"]:checked') || radios[0]; r.checked = true; frame.src = r.dataset.preview; };
        radios.forEach(r => r.addEventListener('change', show));
        show();

        // Compteur d'adresses
        const area = document.getElementById('emails'), out = document.getElementById('counter');
        const max = {{ $maxRecipients }}, re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const update = () => {
            const n = new Set(area.value.split(/[\s,;]+/)
                .map(t => t.replace(/^["'<(\[]+|[."'>)\]]+$/g, '').toLowerCase())
                .filter(t => re.test(t))).size;
            out.innerHTML = `<i class="bi bi-envelope-check me-1"></i> ${n} adresse${n > 1 ? 's' : ''} unique${n > 1 ? 's' : ''} détectée${n > 1 ? 's' : ''}`
                + (n > max ? ` — <span class="text-danger">limite de ${max} dépassée</span>` : '');
        };
        area.addEventListener('input', update);
        update();

        // Message du modal de confirmation
        window.submitMessage = () => {
            const n = new Set(area.value.split(/[\s,;]+/).map(t => t.replace(/^["'<(\[]+|[."'>)\]]+$/g, '').toLowerCase()).filter(t => re.test(t))).size;
            const tpl = document.querySelector('input[name="message_template_id"]:checked')?.closest('.choice')?.querySelector('.title')?.childNodes[0]?.textContent.trim();
            return `Le message « ${tpl} » sera envoyé à ${n} adresse(s). `
                + (@json($requireApproval) ? "L'envoi démarrera après validation par l'administrateur." : "L'envoi démarre immédiatement.");
        };
    })();
</script>
@endif
@endsection
