@extends('layouts.app')
@section('title', 'Mes messages')
@section('subtitle', 'Créez vos propres modèles de message avec leur formulaire, puis choisissez-les lors de l\'envoi d\'une liste.')
@section('actions')
    <a href="{{ route('propet.templates.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Nouveau message</a>
@endsection

@section('content')
<div class="row g-4">
    @forelse($templates as $t)
        @php($logo = \App\Support\MailBranding::dataUri($t->logo_path))
        <div class="col-lg-6 col-xxl-4">
            <div class="card h-100 {{ $t->is_active ? '' : 'opacity-75' }}">
                <div class="card-header">
                    <span class="d-flex align-items-center gap-2 overflow-hidden">
                        @if($logo)<img src="{{ $logo }}" class="choice-logo" style="width:28px;height:28px" alt="">@endif
                        <span class="text-truncate">{{ $t->name }}</span>
                    </span>
                    <span class="d-flex gap-1">
                        @if($t->hasForm())<span class="pill tone-indigo"><i class="bi bi-ui-checks"></i> Formulaire</span>@endif
                        <span class="pill {{ $t->is_active ? 'tone-green' : 'tone-gray' }}">{{ $t->is_active ? 'Actif' : 'Inactif' }}</span>
                    </span>
                </div>
                <div class="card-body d-flex flex-column">
                    <div class="stat-label">Sujet</div>
                    <div class="fw-semibold mb-2">{{ $t->subject }}</div>
                    <div class="message-preview small mb-3" style="max-height: 150px; overflow: hidden">{{ \Illuminate\Support\Str::limit($t->body, 280) }}</div>
                    <div class="mt-auto d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('propet.templates.edit', $t) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil me-1"></i> Modifier</a>
                        <a href="{{ route('preview.template', $t) }}" target="_blank" class="btn btn-sm btn-light" title="Aperçu de l'email"><i class="bi bi-eye"></i></a>
                        @if($t->hasForm())
                            <a href="{{ route('preview.form', $t) }}" target="_blank" class="btn btn-sm btn-light" title="Voir le formulaire"><i class="bi bi-ui-checks"></i></a>
                        @endif
                        <a href="{{ route('propet.responses.index', ['template' => $t->id]) }}" class="btn btn-sm btn-light"><i class="bi bi-chat-left-quote me-1"></i> Réponses <span class="fw-semibold">{{ $t->responses_count }}</span></a>
                        @if($t->campaigns_count === 0)
                            <form method="POST" action="{{ route('propet.templates.destroy', $t) }}" data-confirm="Le message « {{ $t->name }} » sera supprimé définitivement." data-confirm-title="Supprimer ce message ?" data-confirm-button="Supprimer" data-confirm-variant="danger">
                                @csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                            </form>
                        @endif
                        <span class="ms-auto small text-secondary">{{ $t->campaigns_count }} liste(s)</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card"><div class="empty">
            <i class="bi bi-chat-square-text"></i>Vous n'avez pas encore créé de message.
            <div class="mt-3"><a href="{{ route('propet.templates.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Créer mon premier message</a></div>
        </div></div></div>
    @endforelse
</div>
@endsection
