@extends('layouts.app')
@section('title', $template->exists ? 'Modifier le message' : 'Nouveau message')
@section('subtitle')
    <a href="{{ route('admin.templates.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Messages</a>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-xl-7">
        <form method="POST" enctype="multipart/form-data" action="{{ $template->exists ? route('admin.templates.update', $template) : route('admin.templates.store') }}">
            @csrf
            @if($template->exists) @method('PUT') @endif

            <div class="card mb-4"><div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Nom (visible par les propets)</label>
                        <input name="name" value="{{ old('name', $template->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Sujet de l'email</label>
                        <input name="subject" value="{{ old('subject', $template->subject) }}" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Corps du message</label>
                        <textarea name="body" rows="12" class="form-control" required>{{ old('body', $template->body) }}</textarea>
                        <div class="form-text">Variables : <code>{email}</code> <code>{date}</code> <code>{app}</code> <code>{lien}</code> (lien personnel vers le formulaire)</div>
                    </div>
                </div>
            </div></div>

            <div class="card mb-4">
                <div class="card-header"><span><i class="bi bi-image me-2"></i>Logo de ce message <span class="text-secondary fw-normal small">(remplace le logo général)</span></span></div>
                <div class="card-body">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="logo-box" style="width: 140px; min-height: 80px">
                            @if($logo)<img src="{{ $logo }}" alt="Logo">@else<span class="text-secondary small text-center">Logo général</span>@endif
                        </div>
                        <div class="flex-grow-1">
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/gif,image/webp" class="form-control">
                            <div class="form-text">PNG, JPG, GIF ou WebP, 1 Mo max. Intégré directement dans l'email.</div>
                            @if($logo)
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="remove_logo" value="1" class="form-check-input" id="remove_logo">
                                    <label class="form-check-label" for="remove_logo">Retirer ce logo (utiliser le logo général)</label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><span><i class="bi bi-hand-index me-2"></i>Bouton d'action <span class="text-secondary fw-normal small">(facultatif)</span></span></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Texte du bouton</label>
                            <input name="button_text" value="{{ old('button_text', $template->button_text) }}" maxlength="80" class="form-control" placeholder="Ex. : En savoir plus">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Lien</label>
                            <input name="button_url" type="url" value="{{ old('button_url', $template->button_url) }}" maxlength="500" class="form-control" placeholder="https://…">
                        </div>
                    </div>
                </div>
            </div>

            @php
                $f = array_replace(\App\Support\ResponseForm::DEFAULTS, old('form', $template->form ?? []));
                $f['fields'] = array_values($f['fields'] ?? []);
            @endphp
            <div class="card mb-4" id="form-builder">
                <div class="card-header">
                    <span><i class="bi bi-ui-checks me-2"></i>Formulaire de réponse <span class="text-secondary fw-normal small">(avis, inscription…)</span></span>
                    <div class="form-check form-switch mb-0">
                        <input type="hidden" name="form[enabled]" value="0">
                        <input type="checkbox" name="form[enabled]" value="1" class="form-check-input" id="form_enabled" @checked($f['enabled'])>
                        <label class="form-check-label small" for="form_enabled">Activer</label>
                    </div>
                </div>
                <div class="card-body" id="form-config">
                    <p class="small text-secondary">
                        <i class="bi bi-link-45deg me-1"></i>
                        Chaque destinataire reçoit un bouton avec un <strong>lien personnel</strong> : il est reconnu automatiquement et n'a pas à saisir son email.
                        Vous pouvez aussi placer le lien dans le texte avec <code>{lien}</code>.
                    </p>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Texte du bouton dans l'email</label>
                            <input name="form[button_text]" value="{{ $f['button_text'] }}" maxlength="60" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Titre de la page</label>
                            <input name="form[title]" value="{{ $f['title'] }}" maxlength="150" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Introduction</label>
                            <textarea name="form[intro]" rows="2" maxlength="1000" class="form-control">{{ $f['intro'] }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Texte du bouton d'envoi</label>
                            <input name="form[submit_label]" value="{{ $f['submit_label'] }}" maxlength="60" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Message affiché après l'envoi</label>
                            <input name="form[thanks]" value="{{ $f['thanks'] }}" maxlength="500" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">…ou rediriger vers une adresse après l'envoi <span class="text-secondary small">(facultatif)</span></label>
                            <input name="form[redirect_url]" type="url" value="{{ $f['redirect_url'] ?? '' }}" maxlength="500" class="form-control" placeholder="https://…">
                            <div class="form-text">Si renseignée, le destinataire y est redirigé après avoir répondu, <strong>au lieu</strong> de voir le message de remerciement.</div>
                        </div>
                    </div>

                    <label class="form-label mb-2">Champs du formulaire</label>
                    <div id="fields" class="d-flex flex-column gap-2"></div>
                    <button type="button" class="btn btn-light btn-sm mt-2" id="add-field"><i class="bi bi-plus-lg me-1"></i> Ajouter un champ</button>
                    @if($template->exists && $template->hasForm())
                        <a href="{{ route('preview.form', $template) }}" target="_blank" class="btn btn-link btn-sm mt-2"><i class="bi bi-box-arrow-up-right me-1"></i> Voir le formulaire</a>
                    @endif
                </div>
            </div>

            <template id="field-tpl">
                <div class="field-row">
                    <div class="d-flex gap-2 align-items-start">
                        <span class="handle text-secondary pt-2"><i class="bi bi-grip-vertical"></i></span>
                        <div class="row g-2 flex-grow-1">
                            <div class="col-md-5"><input data-n="label" class="form-control form-control-sm" placeholder="Libellé du champ (ex. : Votre note)" maxlength="150"></div>
                            <div class="col-md-4">
                                <select data-n="type" class="form-select form-select-sm">
                                    @foreach(\App\Support\ResponseForm::TYPES as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-center gap-2">
                                <div class="form-check mb-0"><input type="checkbox" data-n="required" value="1" class="form-check-input"><label class="form-check-label small">Obligatoire</label></div>
                            </div>
                            <div class="col-md-6 opt-placeholder"><input data-n="placeholder" class="form-control form-control-sm" placeholder="Texte d'aide (facultatif)" maxlength="150"></div>
                            <div class="col-12 opt-options d-none">
                                <textarea data-n="options" rows="2" class="form-control form-control-sm" placeholder="Une option par ligne"></textarea>
                            </div>
                            <input type="hidden" data-n="key">
                        </div>
                        <div class="d-flex flex-column gap-1">
                            <button type="button" class="btn btn-light btn-icon btn-sm" data-act="up" title="Monter"><i class="bi bi-arrow-up"></i></button>
                            <button type="button" class="btn btn-light btn-icon btn-sm" data-act="down" title="Descendre"><i class="bi bi-arrow-down"></i></button>
                            <button type="button" class="btn btn-light btn-icon btn-sm text-danger" data-act="del" title="Retirer"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                </div>
            </template>

            <div class="card mb-4"><div class="card-body">
                <div class="form-check form-switch mb-2">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" @checked(old('is_active', $template->is_active))>
                    <label class="form-check-label" for="is_active"><span class="fw-semibold">Disponible pour les propets</span> <span class="text-secondary small">— ils pourront le choisir lors de l'envoi d'une liste</span></label>
                </div>
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_default" value="1" class="form-check-input" id="is_default" @checked(old('is_default', $template->is_default))>
                    <label class="form-check-label" for="is_default"><span class="fw-semibold">Message recommandé</span> <span class="text-secondary small">— présélectionné pour les propets</span></label>
                </div>
            </div></div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Enregistrer</button>
                <a href="{{ route('admin.templates.index') }}" class="btn btn-light">Annuler</a>
            </div>
        </form>

        <script>
        (() => {
            const list = document.getElementById('fields');
            const tpl = document.getElementById('field-tpl');
            const toggle = document.getElementById('form_enabled');
            const config = document.getElementById('form-config');
            const withOptions = @json(\App\Support\ResponseForm::WITH_OPTIONS);
            const initial = @json($f['fields']);

            // Renomme les champs selon leur position : form[fields][i][...]
            const reindex = () => [...list.children].forEach((row, i) =>
                row.querySelectorAll('[data-n]').forEach(el => el.name = `form[fields][${i}][${el.dataset.n}]`));

            const syncType = row => {
                const t = row.querySelector('[data-n="type"]').value;
                row.querySelector('.opt-options').classList.toggle('d-none', !withOptions.includes(t));
                row.querySelector('.opt-placeholder').classList.toggle('d-none', !['text', 'textarea', 'number', 'tel', 'email'].includes(t));
            };

            const add = (f = {}) => {
                const row = tpl.content.firstElementChild.cloneNode(true);
                row.querySelector('[data-n="label"]').value = f.label ?? '';
                row.querySelector('[data-n="type"]').value = f.type ?? 'text';
                row.querySelector('[data-n="required"]').checked = !!f.required;
                row.querySelector('[data-n="placeholder"]').value = f.placeholder ?? '';
                row.querySelector('[data-n="options"]').value = Array.isArray(f.options) ? f.options.join('\n') : (f.options ?? '');
                row.querySelector('[data-n="key"]').value = f.key ?? '';
                row.querySelector('[data-n="type"]').addEventListener('change', () => syncType(row));
                list.appendChild(row);
                syncType(row);
                reindex();
                return row;
            };

            list.addEventListener('click', async e => {
                const b = e.target.closest('[data-act]');
                if (!b) return;
                const row = b.closest('.field-row');
                if (b.dataset.act === 'up' && row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
                if (b.dataset.act === 'down' && row.nextElementSibling) list.insertBefore(row.nextElementSibling, row);
                if (b.dataset.act === 'del') {
                    const label = row.querySelector('[data-n="label"]').value || 'ce champ';
                    if (await askConfirm({ title: 'Retirer ce champ ?', message: `« ${label} » sera retiré du formulaire (les réponses déjà reçues sont conservées).`, button: 'Retirer', variant: 'danger' })) row.remove();
                }
                reindex();
            });

            document.getElementById('add-field').addEventListener('click', () => add().querySelector('input').focus());
            const syncToggle = () => config.classList.toggle('opacity-50', !toggle.checked);
            toggle.addEventListener('change', () => { syncToggle(); if (toggle.checked && !list.children.length) add({ label: 'Votre note', type: 'rating', required: true }); });

            initial.forEach(add);
            syncToggle();
        })();
        </script>
    </div>

    <div class="col-xl-5">
        <div class="card position-sticky" style="top: 16px">
            <div class="card-header">
                <span><i class="bi bi-eye me-2"></i>Aperçu</span>
                <a href="{{ route('admin.presentation.edit') }}" class="small fw-normal text-decoration-none">Modifier la présentation</a>
            </div>
            <div class="card-body p-2">
                @if($template->exists)
                    <iframe src="{{ route('preview.template', $template) }}" class="mail-frame" title="Aperçu"></iframe>
                @else
                    <div class="empty"><i class="bi bi-eye-slash"></i>Enregistrez le message pour voir l'aperçu.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
