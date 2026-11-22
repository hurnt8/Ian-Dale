@extends('layouts.app')
@section('title', 'Listes')
@section('subtitle', 'Toutes les listes soumises par les propets.')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="tabs">
            <a href="{{ route('admin.campaigns.index') }}" class="tab {{ request('status') ? '' : 'active' }}">Toutes</a>
            @foreach($statuses as $s)
                <a href="{{ route('admin.campaigns.index', ['status' => $s->value]) }}" class="tab {{ request('status') === $s->value ? 'active' : '' }}">{{ $s->label() }}</a>
            @endforeach
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Liste</th><th>Propet</th><th>Adresses</th><th>Statut</th><th>Soumise le</th><th></th></tr></thead>
            <tbody>
            @forelse($campaigns as $c)
                <tr>
                    <td class="fw-semibold">{{ $c->title }}</td>
                    <td>{{ $c->user->name }}</td>
                    <td>{{ $c->total }}</td>
                    <td>@include('partials.status-badge', ['status' => $c->status])</td>
                    <td class="text-secondary">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                    <td class="text-end"><a href="{{ route('admin.campaigns.show', $c) }}" class="btn btn-sm btn-light">Voir <i class="bi bi-chevron-right"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty"><i class="bi bi-collection"></i>Aucune liste.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($campaigns->hasPages())<div class="mt-3">{{ $campaigns->links() }}</div>@endif
@endsection
