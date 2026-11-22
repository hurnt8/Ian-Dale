@extends('layouts.app')
@section('title', 'Mes listes')
@section('subtitle', 'Les listes que vous avez soumises et leur avancement.')
@section('actions')
    <a href="{{ route('propet.campaigns.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Nouvelle liste</a>
@endsection

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Liste</th><th>Message</th><th>Destinataires</th><th>Statut</th><th>Soumise le</th><th></th></tr></thead>
            <tbody>
            @forelse($campaigns as $c)
                <tr>
                    <td class="fw-semibold">{{ $c->title }}</td>
                    <td class="text-secondary">{{ $c->template?->name }}</td>
                    <td>{{ $c->total }}</td>
                    <td>@include('partials.status-badge', ['status' => $c->status])</td>
                    <td class="text-secondary">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                    <td class="text-end"><a href="{{ route('propet.campaigns.show', $c) }}" class="btn btn-sm btn-light">Voir <i class="bi bi-chevron-right"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">
                    <i class="bi bi-collection"></i>Vous n'avez encore soumis aucune liste.
                    <div class="mt-3"><a href="{{ route('propet.campaigns.create') }}" class="btn btn-primary btn-sm">Soumettre ma première liste</a></div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($campaigns->hasPages())<div class="mt-3">{{ $campaigns->links() }}</div>@endif
@endsection
