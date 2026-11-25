@extends('layouts.app')
@section('title', $isAdmin ? 'Réponses' : 'Réponses reçues')
@section('subtitle', $isAdmin ? 'Toutes les réponses des destinataires, toutes listes confondues.' : 'Les réponses des personnes à qui vous avez envoyé vos listes.')

@php($prefix = $isAdmin ? 'admin' : 'propet')

@section('actions')
    <a href="{{ route($prefix.'.responses.export', request()->query()) }}" class="btn btn-light"><i class="bi bi-download me-1"></i> Exporter CSV</a>
@endsection

@section('content')
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="filters">
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
                    <option value="{{ $c->id }}" @selected((string) request('campaign') === (string) $c->id)>{{ $c->title }} ({{ $c->responses_count }})</option>
                @endforeach
            </select>
            <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrer</button>
            @if(request()->hasAny(['q', 'propet', 'campaign']))
                <a href="{{ route($prefix.'.responses.index') }}" class="btn btn-link text-secondary">Réinitialiser</a>
            @endif
        </form>
    </div>
</div>

@if($summary)
    @php($current = $campaigns->firstWhere('id', (int) request('campaign')))
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-xl-3">
            @include('partials.stat-card', ['label' => 'Taux de réponse', 'value' => $current && $current->sent_count ? round($current->responses_count * 100 / $current->sent_count) : 0, 'icon' => 'bi-percent', 'tone' => 'indigo'])
        </div>
        @foreach($summary as $s)
            @if(isset($s['average']) || ! empty($s['distribution']))
                <div class="col-md-4 col-xl-3">
                    <div class="card h-100"><div class="card-body">
                        <div class="stat-label text-truncate mb-1" title="{{ $s['label'] }}">{{ $s['label'] }}</div>
                        @if(isset($s['average']) && $s['average'] !== null)
                            <div class="stat-value">{{ $s['average'] }}@if($s['type'] === 'rating')<span class="fs-6 text-secondary"> / 5</span>@endif</div>
                        @endif
                        @if(! empty($s['distribution']) && $s['type'] !== 'rating')
                            @foreach(array_slice($s['distribution'], 0, 4, true) as $opt => $n)
                                <div class="small d-flex justify-content-between gap-2 mt-1">
                                    <span class="text-truncate">{{ ucfirst($opt) }}</span>
                                    <span class="fw-semibold">{{ $n }}</span>
                                </div>
                                <div class="progress" style="height: 5px"><div class="progress-bar" style="width: {{ $s['count'] ? round($n * 100 / $s['count']) : 0 }}%"></div></div>
                            @endforeach
                        @endif
                        <div class="small text-secondary mt-2">{{ $s['count'] }} réponse(s)</div>
                    </div></div>
                </div>
            @endif
        @endforeach
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-2">
    <span class="text-secondary small">{{ $total }} réponse(s)</span>
</div>

@forelse($responses as $r)
    <div class="card mb-3">
        <div class="card-header">
            <span class="d-flex align-items-center gap-2 overflow-hidden">
                <span class="avatar" style="width:30px;height:30px;font-size:.75rem">{{ mb_strtoupper(mb_substr($r->email, 0, 1)) }}</span>
                <span class="email-cell text-truncate">{{ $r->email }}</span>
            </span>
            <span class="small text-secondary fw-normal text-end">
                <a href="{{ route($prefix.'.campaigns.show', $r->campaign_id) }}" class="text-decoration-none">{{ $r->campaign->title }}</a>
                @if($isAdmin)· {{ $r->campaign->user->name }}@endif
                · {{ $r->updated_at->format('d/m/Y H:i') }}
                @if($r->edits > 0)<span class="pill tone-gray ms-1">modifiée</span>@endif
            </span>
        </div>
        <div class="card-body py-2">
            <dl class="row mb-0">
                @foreach($r->answers as $a)
                    <dt class="col-sm-4 text-secondary fw-semibold small py-1">{{ $a['label'] }}</dt>
                    <dd class="col-sm-8 py-1 mb-0" style="white-space: pre-line">@if($a['type'] === 'rating' && $a['value'])<span style="color:#f5a524">{{ str_repeat('★', (int) $a['value']) }}</span><span style="color:#d4d7e1">{{ str_repeat('★', 5 - (int) $a['value']) }}</span> <span class="small text-secondary">{{ $a['value'] }}/5</span>@else{{ \App\Support\ResponseForm::display($a) }}@endif</dd>
                @endforeach
            </dl>
        </div>
    </div>
@empty
    <div class="card"><div class="empty">
        <i class="bi bi-chat-left-quote"></i>Aucune réponse pour le moment.
        <div class="small mt-2">Les réponses apparaissent ici dès qu'un destinataire remplit le formulaire depuis son email.</div>
    </div></div>
@endforelse

@if($responses->hasPages())<div class="mt-3">{{ $responses->links() }}</div>@endif

@if($pageResponses->isNotEmpty())
    <h2 class="h6 fw-bold mt-5 mb-1"><i class="bi bi-window me-1"></i> Réponses des vues</h2>
    <div class="text-secondary small mb-3">{{ $isAdmin ? 'Formulaires remplis sur les pages publiques /vue/…' : 'Formulaires remplis sur les pages publiques /vue/… qui vous sont attribuées' }} (30 dernières). Pour tout voir ou exporter, ouvrez la vue concernée.</div>
    @foreach($pageResponses as $pr)
        <div class="card mb-3">
            <div class="card-header">
                <span class="fw-semibold text-truncate">{{ $pr->page->title() }}</span>
                <span class="small text-secondary fw-normal text-end">
                    <a href="{{ route($prefix.'.pages.responses', $pr->page) }}" class="text-decoration-none">Toutes les réponses de cette vue</a>
                    · {{ $pr->created_at->format('d/m/Y H:i') }}
                </span>
            </div>
            <div class="card-body py-2">
                <dl class="row mb-0">
                    @foreach($pr->answers as $a)
                        <dt class="col-sm-4 text-secondary fw-semibold small py-1">{{ $a['label'] }}</dt>
                        <dd class="col-sm-8 py-1 mb-0" style="white-space: pre-line">{{ \App\Support\ResponseForm::display($a) }}</dd>
                    @endforeach
                </dl>
            </div>
        </div>
    @endforeach
@endif
@endsection
