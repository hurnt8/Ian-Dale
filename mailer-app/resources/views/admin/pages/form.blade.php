@php
    $f = array_replace(\App\Support\ResponseForm::DEFAULTS, old('form', $page->form ?? []));
    $f['fields'] = array_values($f['fields'] ?? []);
@endphp
@extends('layouts.app')
@section('title', $page->exists ? 'Modifier la vue' : 'Nouvelle vue')
@section('subtitle')
    <a href="{{ route('admin.pages.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Vues</a>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-xl-7">
        <form method="POST" enctype="multipart/form-data" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
            @csrf
            @if($page->exists) @method('PUT') @endif

            <div class="card mb-4" id="form-builder"><div class="card-body" id="form-config">
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label">Titre de la vue</label>
                        <input name="form[title]" value="{{ $f['title'] }}" maxlength="150" class="form-control" required>
                        <div class="form-text">L'adresse publique est dérivée du titre
                            @if($page->exists)(actuellement <code>/vue/{{ $page->slug }}</code>)@endif.
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Introduction <span class="text-secondary small">(facultatif)</span></label>
                        <textarea name="form[intro]" rows="2" maxlength="1000" class="form-control">{{ $f['intro'] }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Texte du bouton d'envoi</label>
                        <input name="form[submit_label]" value="{{ $f['submit_label'] }}" maxlength="60" class="form-control">
                    </div>
                </div>

                <div class="border rounded-3 p-3 mb-1 bg-white">
                    <div class="fw-semibold mb-2"><i class="bi bi-check2-circle me-1"></i>Après l'envoi</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Message affiché après l'envoi</label>
                            <input name="form[thanks]" value="{{ $f['thanks'] }}" maxlength="500" class="form-control" placeholder="Ex. : Merci, votre réponse a bien été enregistrée.">
                        </div>
                    </div>
                </div>

                <label class="form-label mb-2">Champs de la vue</label>
                <div id="fields" class="d-flex flex-column gap-2"></div>
                <button type="button" class="btn btn-light btn-sm mt-2" id="add-field"><i class="bi bi-plus-lg me-1"></i> Ajouter un champ</button>
            </div></div>

            <template id="field-tpl">
                <div class="field-row">
                    <div class="d-flex gap-2 align-items-start">
                        <span class="handle text-secondary pt-2"><i class="bi bi-grip-vertical"></i></span>
                        <div class="row g-2 flex-grow-1">
                            <div class="col-md-5"><input data-n="label" class="form-control form-control-sm" placeholder="Libellé du champ (ex. : Votre nom)" maxlength="150"></div>
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

            <div class="card mb-4">
                <div class="card-header"><span><i class="bi bi-image me-2"></i>Logo <span class="text-secondary fw-normal small">(facultatif, sinon le logo général)</span></span></div>
                <div class="card-body">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="logo-box" style="width: 140px; min-height: 80px">
                            @if($logo)<img src="{{ $logo }}" alt="Logo">@else<span class="text-secondary small text-center">Logo général</span>@endif
                        </div>
                        <div class="flex-grow-1">
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/gif,image/webp" class="form-control">
                            <div class="form-text">PNG, JPG, GIF ou WebP, 1 Mo max.</div>
                            @if($logo)
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="remove_logo" value="1" class="form-check-input" id="remove_logo">
                                    <label class="form-check-label" for="remove_logo">Retirer ce logo</label>
                                </div>
                            @endif
                        </div>
                    </div>
                    @php $logoAlign = old('logo_align', $page->form['logo_align'] ?? 'center'); @endphp
                    <div class="mt-3" style="max-width: 260px">
                        <label class="form-label">Position du logo</label>
                        <select name="logo_align" class="form-select">
                            @foreach(['left' => 'Gauche', 'center' => 'Centré', 'right' => 'Droite'] as $v => $l)
                                <option value="{{ $v }}" @selected($logoAlign === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="card mb-4"><div class="card-body">
                <div class="form-check form-switch mb-0">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" class="form-check-input" id="is_published" @checked(old('is_published', $page->is_published))>
                    <label class="form-check-label" for="is_published"><span class="fw-semibold">Publiée</span> <span class="text-secondary small">— accessible publiquement par son adresse. Sinon, c'est un brouillon.</span></label>
                </div>
            </div></div>

            <div class="card mb-4"><div class="card-body">
                <div class="fw-semibold mb-1"><i class="bi bi-people me-1"></i>Agents qui voient les réponses</div>
                <div class="form-text mb-2">Les propets cochés retrouvent cette vue et ses réponses dans leur espace (menu « Vues »). L'admin voit toujours tout.</div>
                @php $assignedIds = array_map('strval', old('agents', $assigned ?? [])); @endphp
                @forelse($propets as $p)
                    <div class="form-check">
                        <input type="checkbox" name="agents[]" value="{{ $p->id }}" class="form-check-input" id="agent{{ $p->id }}" @checked(in_array((string) $p->id, $assignedIds, true))>
                        <label class="form-check-label" for="agent{{ $p->id }}">{{ $p->name }} <span class="text-secondary small">{{ $p->email }}</span></label>
                    </div>
                @empty
                    <div class="text-secondary small">Aucun propet. Créez-en dans Admin &gt; Utilisateurs.</div>
                @endforelse
            </div></div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Enregistrer</button>
                <a href="{{ route('admin.pages.index') }}" class="btn btn-light">Annuler</a>
                @if($page->exists && $page->hasForm())
                    <a href="{{ route('page.show', $page->slug) }}" target="_blank" class="btn btn-light ms-auto"><i class="bi bi-box-arrow-up-right me-1"></i> Ouvrir la vue</a>
                @endif
            </div>
        </form>

        <script>
        (() => {
            const list = document.getElementById('fields');
            const tpl = document.getElementById('field-tpl');
            const withOptions = @json(\App\Support\ResponseForm::WITH_OPTIONS);
            const initial = @json($f['fields']);

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
                    if (await askConfirm({ title: 'Retirer ce champ ?', message: `« ${label} » sera retiré de la vue.`, button: 'Retirer', variant: 'danger' })) row.remove();
                }
                reindex();
            });

            document.getElementById('add-field').addEventListener('click', () => add().querySelector('input').focus());

            initial.forEach(add);
            if (!list.children.length) add({ label: 'Votre nom', type: 'text', required: true });
        })();
        </script>
    </div>

    <div class="col-xl-5">
        <div class="card position-sticky" style="top: 16px">
            <div class="card-header"><span><i class="bi bi-eye me-2"></i>Aperçu de la vue</span></div>
            <div class="card-body p-2">
                @if($page->exists && $page->hasForm())
                    <iframe src="{{ route('preview.page', $page) }}" class="mail-frame" title="Aperçu"></iframe>
                @else
                    <div class="empty"><i class="bi bi-eye-slash"></i>Enregistrez la vue pour voir l'aperçu.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
