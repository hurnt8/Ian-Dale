@extends('layouts.app')
@section('title', $isAdmin ? 'Tous les emails' : 'Mes emails')
@section('subtitle', $isAdmin ? "L'ensemble des adresses soumises par tous les propets." : 'Uniquement les adresses de vos listes.')

@if($isAdmin)
    @section('actions')
        <a href="{{ route('admin.emails.export', request()->query()) }}" class="btn btn-light"><i class="bi bi-download me-1"></i> Exporter CSV</a>
    @endsection
@endif

@section('content')
@php
    $base = $isAdmin ? 'admin.emails.index' : 'propet.emails.index';
    $q = request()->except('status', 'page');
@endphp

<div class="card">
    <div class="card-header flex-wrap">
        <div class="tabs">
            <a href="{{ route($base, $q) }}" class="tab {{ request('status') ? '' : 'active' }}">Tous<span class="n">{{ $totals['all'] }}</span></a>
            @foreach($statuses as $s)
                <a href="{{ route($base, [...$q, 'status' => $s->value]) }}" class="tab {{ request('status') === $s->value ? 'active' : '' }}">{{ $s->label() }}<span class="n">{{ $totals[$s->value] }}</span></a>
            @endforeach
        </div>
    </div>

    <div class="card-body border-bottom py-3">
        <form method="GET" class="filters">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <div class="search">
                <i class="bi bi-search"></i>
                <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher une adresse…">
            </div>
            @if($isAdmin)
                <select name="propet" class="form-select" onchange="this.form.submit()">
                    <option value="">Tous les propets</option>
                    @foreach($propets as $p)
                        <option value="{{ $p->id }}" @selected((string) request('propet') === (string) $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            @endif
            <select name="campaign" class="form-select" onchange="this.form.submit()">
                <option value="">Toutes les listes</option>
                @foreach($campaigns as $c)
                    <option value="{{ $c->id }}" @selected((string) request('campaign') === (string) $c->id)>{{ $c->title }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrer</button>
            @if(request()->hasAny(['q', 'propet', 'campaign']))
                <a href="{{ route($base) }}" class="btn btn-link text-secondary">Réinitialiser</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Vérification</th>
                    <th>Liste</th>
                    @if($isAdmin)<th>Propet</th>@endif
                    <th>Statut</th>
                    <th>Envoyé le</th>
                </tr>
            </thead>
            <tbody>
            @forelse($recipients as $r)
                <tr>
                    <td class="email-cell">{{ $r->email }}</td>
                    <td>@if($r->verification)<span title="{{ $r->verification_detail }}">@include('partials.status-badge', ['status' => $r->verification])</span>@else<span class="text-secondary">—</span>@endif</td>
                    <td>
                        <a href="{{ $isAdmin ? route('admin.campaigns.show', $r->campaign_id) : route('propet.campaigns.show', $r->campaign_id) }}" class="text-decoration-none">{{ $r->campaign->title }}</a>
                    </td>
                    @if($isAdmin)
                        <td><span class="d-inline-flex align-items-center gap-2"><span class="avatar" style="width:26px;height:26px;font-size:.7rem">{{ mb_strtoupper(mb_substr($r->campaign->user->name, 0, 1)) }}</span>{{ $r->campaign->user->name }}</span></td>
                    @endif
                    <td>@include('partials.status-badge', ['status' => $r->status])</td>
                    <td class="text-secondary small">{{ $r->sent_at?->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $isAdmin ? 6 : 5 }}" class="empty"><i class="bi bi-inbox"></i>Aucune adresse trouvée.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($recipients->hasPages())<div class="mt-3">{{ $recipients->links() }}</div>@endif
@endsection
