@extends('layouts.app')
@section('title', 'Vues')
@section('subtitle')<span class="subtitle">Pages publiques qui vous sont attribuées et leurs réponses.</span>@endsection

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Vue</th><th>Adresse publique</th><th>Réponses</th><th></th></tr></thead>
            <tbody>
            @forelse($pages as $page)
                <tr>
                    <td class="fw-semibold">{{ $page->title() }}</td>
                    <td class="email-cell">
                        @if($page->is_published)
                            <a href="{{ route('page.show', $page->slug) }}" target="_blank" class="text-decoration-none">/vue/{{ $page->slug }} <i class="bi bi-box-arrow-up-right small"></i></a>
                        @else
                            <span class="pill tone-gray">Brouillon</span>
                        @endif
                    </td>
                    <td class="fw-semibold">{{ $page->responses_count }}</td>
                    <td class="text-end">
                        <a href="{{ route('propet.pages.responses', $page) }}" class="btn btn-light btn-sm"><i class="bi bi-chat-left-quote me-1"></i> Voir les réponses</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty"><i class="bi bi-window"></i>Aucune vue ne vous est attribuée pour l'instant.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
