@extends('layouts.app')
@section('title', 'Présentation des emails')
@section('subtitle', "Logo, couleurs et textes communs à tous les messages. L'aperçu se met à jour en direct.")

@section('content')
<div class="row g-4">
    <div class="col-xl-5">
        <form method="POST" action="{{ route('admin.presentation.update') }}" enctype="multipart/form-data" id="pres-form">
            @csrf @method('PUT')

            <div class="card mb-4">
                <div class="card-header"><span><i class="bi bi-image me-2"></i>Logo</span></div>
                <div class="card-body">
                    <div class="logo-box mb-3">
                        @if($logo)<img src="{{ $logo }}" alt="Logo actuel">@else<span class="text-secondary small"><i class="bi bi-image me-1"></i>Aucun logo</span>@endif
                    </div>
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/gif,image/webp" class="form-control mb-1">
                    <div class="form-text mb-3">PNG, JPG, GIF ou WebP, 1 Mo max. Le logo est intégré dans l'email (pas de lien externe).</div>
                    @if($logo)
                        <div class="form-check mb-3">
                            <input type="checkbox" name="remove_logo" value="1" class="form-check-input" id="remove_logo">
                            <label class="form-check-label" for="remove_logo">Supprimer le logo</label>
                        </div>
                    @endif
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Largeur (px)</label>
                            <input type="number" name="mail_logo_width" value="{{ old('mail_logo_width', $b['logo_width']) }}" min="40" max="600" class="form-control live">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Alignement</label>
                            <select name="mail_logo_align" class="form-select live">
                                @foreach(['left' => 'Gauche', 'center' => 'Centré', 'right' => 'Droite'] as $v => $l)
                                    <option value="{{ $v }}" @selected($b['logo_align'] === $v)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><span><i class="bi bi-palette me-2"></i>Couleurs et police</span></div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach(['mail_primary_color' => ['Couleur principale', 'primary'], 'mail_text_color' => ['Texte', 'text'], 'mail_bg_color' => ['Fond', 'bg'], 'mail_card_color' => ['Carte du message', 'card']] as $key => [$label, $k])
                            <div class="col-6">
                                <label class="form-label">{{ $label }}</label>
                                <div class="d-flex gap-2">
                                    <input type="color" value="{{ old($key, $b[$k]) }}" class="form-control form-control-color" data-sync="{{ $key }}">
                                    <input name="{{ $key }}" value="{{ old($key, $b[$k]) }}" class="form-control live" maxlength="7" pattern="#[0-9a-fA-F]{6}">
                                </div>
                            </div>
                        @endforeach
                        <div class="col-12">
                            <label class="form-label">Police</label>
                            <select name="mail_font" class="form-select live">
                                @foreach($fonts as $v => $l)
                                    <option value="{{ $v }}" @selected($b['font'] === $v) style="font-family: {{ $v }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><span><i class="bi bi-type me-2"></i>Textes</span></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Titre d'en-tête <span class="text-secondary fw-normal">(facultatif)</span></label>
                        <input name="mail_header_text" value="{{ old('mail_header_text', $b['header']) }}" maxlength="150" class="form-control live" placeholder="Ex. : Lettre d'information">
                    </div>
                    <div>
                        <label class="form-label">Pied de page</label>
                        <textarea name="mail_footer_text" rows="3" maxlength="1000" class="form-control live">{{ old('mail_footer_text', $b['footer']) }}</textarea>
                        <div class="form-text">Le lien de désinscription est toujours ajouté sous ce texte.</div>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Enregistrer la présentation</button>
        </form>
    </div>

    <div class="col-xl-7">
        <div class="card position-sticky" style="top: 16px">
            <div class="card-header">
                <span><i class="bi bi-eye me-2"></i>Aperçu</span>
                @if($templates->isNotEmpty())
                    <select id="pv-template" class="form-select form-select-sm" style="width: auto">
                        @foreach($templates as $t)<option value="{{ route('preview.template', $t) }}">{{ $t->name }}</option>@endforeach
                    </select>
                @endif
            </div>
            <div class="card-body p-2">
                @if($templates->isNotEmpty())
                    <iframe id="pv" class="mail-frame" style="height: 680px" title="Aperçu"></iframe>
                @else
                    <div class="empty">Créez un message pour voir l'aperçu.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('pres-form');
    const frame = document.getElementById('pv');
    const sel = document.getElementById('pv-template');
    if (!frame) return;

    // Synchronise les sélecteurs de couleur et les champs texte
    form.querySelectorAll('[data-sync]').forEach(picker => {
        const input = form.querySelector(`[name="${picker.dataset.sync}"]`);
        picker.addEventListener('input', () => { input.value = picker.value; refresh(); });
        input.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(input.value)) picker.value = input.value; });
    });

    let t;
    function refresh() {
        clearTimeout(t);
        t = setTimeout(() => {
            const params = new URLSearchParams();
            form.querySelectorAll('.live').forEach(el => params.set(el.name, el.value));
            frame.src = sel.value + '?' + params.toString();
        }, 250);
    }
    form.querySelectorAll('.live').forEach(el => el.addEventListener('input', refresh));
    sel.addEventListener('change', refresh);
    refresh();
})();
</script>
@endsection
