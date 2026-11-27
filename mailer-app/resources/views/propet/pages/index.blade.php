@extends('layouts.app')
@section('title', 'Vues')
@section('subtitle')<span class="subtitle">Partagez votre lien personnel : les réponses reçues par ce lien vous reviennent.</span>@endsection

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Vue</th><th style="min-width: 340px">Mon lien personnel</th><th>Mes réponses</th><th></th></tr></thead>
            <tbody>
            @forelse($pages as $page)
                <tr>
                    <td class="fw-semibold">{{ $page->title() }}</td>
                    <td>
                        @if($page->is_published)
                            @include('propet.pages._share', ['url' => $page->linkFor(auth()->user())])
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
                <tr><td colspan="4" class="empty"><i class="bi bi-window"></i>Aucune vue publiée pour l'instant.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
