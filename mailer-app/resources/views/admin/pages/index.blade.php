@extends('layouts.app')
@section('title', 'Vues')
@section('subtitle')<span class="subtitle">Pages publiques autonomes (inscription, avis, contact…) accessibles par un lien.</span>@endsection
@section('actions')
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Nouvelle vue</a>
@endsection

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Vue</th><th>Adresse publique</th><th>Statut</th><th>Réponses</th><th></th></tr></thead>
            <tbody>
            @forelse($pages as $page)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $page->title() }}</div>
                        <div class="small text-secondary">{{ count($page->form['fields'] ?? []) }} champ(s)</div>
                    </td>
                    <td class="email-cell">
                        <a href="{{ route('page.show', $page->slug) }}" target="_blank" class="text-decoration-none">/vue/{{ $page->slug }} <i class="bi bi-box-arrow-up-right small"></i></a>
                    </td>
                    <td>
                        @if($page->is_published)
                            <span class="pill tone-green">Publiée</span>
                        @else
                            <span class="pill tone-gray">Brouillon</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.pages.responses', $page) }}" class="text-decoration-none fw-semibold">{{ $page->responses_count }}</a>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="d-inline"
                              data-confirm="« {{ $page->title() }} » et ses {{ $page->responses_count }} réponse(s) seront définitivement supprimées."
                              data-confirm-title="Supprimer cette vue ?"
                              data-confirm-button="Supprimer" data-confirm-variant="danger">
                            @csrf @method('DELETE')
                            <button class="btn btn-light btn-sm text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty"><i class="bi bi-window"></i>Aucune vue. Créez-en une pour recueillir des réponses via un simple lien.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
