@extends('layouts.app')
@section('title', 'Tableau de bord')
@section('subtitle', "Vue d'ensemble de tous les envois.")
@section('actions')
    <a href="{{ route('admin.emails.index') }}" class="btn btn-light"><i class="bi bi-envelope-at me-1"></i> Tous les emails</a>
@endsection

@section('content')
<div class="row g-3 mb-4">
    @foreach($cards as $card)
        <div class="col-6 col-lg">@include('partials.stat-card', $card)</div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="bi bi-hourglass-split me-2"></i>Listes à valider</span>
                <a href="{{ route('admin.campaigns.index', ['status' => 'pending']) }}" class="small text-decoration-none fw-normal">Tout voir</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Liste</th><th>Propet</th><th>Adresses</th><th></th></tr></thead>
                    <tbody>
                    @forelse($pending as $c)
                        <tr>
                            <td><div class="fw-semibold">{{ $c->title }}</div><div class="small text-secondary">{{ $c->created_at->diffForHumans() }}</div></td>
                            <td>{{ $c->user->name }}</td>
                            <td>{{ $c->total }}</td>
                            <td class="text-end"><a href="{{ route('admin.campaigns.show', $c) }}" class="btn btn-sm btn-primary">Examiner</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty"><i class="bi bi-check2-all"></i>Rien à valider pour le moment.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header"><span><i class="bi bi-people me-2"></i>Emails par propet</span></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Propet</th><th class="text-end">Listes</th><th class="text-end">Emails</th><th class="text-end">Envoyés</th></tr></thead>
                    <tbody>
                    @forelse($perPropet as $p)
                        <tr>
                            <td>
                                <a href="{{ route('admin.emails.index', ['propet' => $p->id]) }}" class="text-decoration-none text-reset d-flex align-items-center gap-2">
                                    <span class="avatar" style="width:28px;height:28px;font-size:.72rem">{{ mb_strtoupper(mb_substr($p->name, 0, 1)) }}</span>
                                    <span class="fw-semibold">{{ $p->name }}</span>
                                </a>
                            </td>
                            <td class="text-end">{{ $p->lists }}</td>
                            <td class="text-end">{{ $p->total }}</td>
                            <td class="text-end">
                                {{ (int) $p->sent }}
                                @if($p->failed > 0)<span class="text-danger small ms-1" title="Échecs">({{ (int) $p->failed }} ✕)</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty"><i class="bi bi-person"></i>Aucun propet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
