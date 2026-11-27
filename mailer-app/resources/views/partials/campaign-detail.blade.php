{{-- Variables : $campaign, $stats, $recipients --}}
@php
    $done = array_sum($stats) - $stats['pending'];
    $pct = $campaign->total > 0 ? (int) round($done * 100 / $campaign->total) : 0;
    $hasForm = $campaign->hasForm();
    $responsesCount = $hasForm ? $campaign->responses()->count() : 0;
    $responsesRoute = auth()->user()->isAdmin() ? 'admin.responses.index' : 'propet.responses.index';
@endphp

@if($hasForm)
    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center gap-3 py-3">
            <div class="stat-icon tone-indigo"><i class="bi bi-chat-left-quote"></i></div>
            <div class="flex-grow-1">
                <div class="fw-semibold">Formulaire « {{ $campaign->form['title'] }} »</div>
                <div class="small text-secondary">{{ $responsesCount }} réponse(s) sur {{ $stats['sent'] }} email(s) envoyé(s)</div>
            </div>
            <a href="#reponses" class="btn btn-primary"><i class="bi bi-eye me-1"></i> Voir les réponses</a>
        </div>
    </div>
@endif

<div class="row g-3 mb-4">
    @foreach(\App\Enums\RecipientStatus::cases() as $s)
        <div class="col-6 col-lg">
            @include('partials.stat-card', ['label' => $s->label(), 'value' => $stats[$s->value], 'icon' => $s->icon(), 'tone' => $s->tone()])
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-envelope-at me-2"></i>Destinataires <span class="text-secondary fw-normal">({{ $campaign->total }})</span></span>
                <span class="d-flex align-items-center gap-2 small text-secondary fw-normal" style="min-width: 180px">
                    <div class="progress flex-grow-1"><div class="progress-bar" style="width: {{ $pct }}%"></div></div>{{ $pct }}%
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Email</th><th>Vérification</th><th>Envoi</th>@if($hasForm)<th>Réponse</th>@endif<th>Envoyé le</th></tr></thead>
                    <tbody>
                    @forelse($recipients as $r)
                        <tr>
                            <td class="email-cell">{{ $r->email }}
                                @if($r->error)<div class="small text-danger text-truncate" style="max-width: 320px" title="{{ $r->error }}">{{ $r->error }}</div>@endif
                            </td>
                            <td>
                                @if($r->verification)
                                    <span title="{{ $r->verification_detail }}">@include('partials.status-badge', ['status' => $r->verification])</span>
                                @else <span class="text-secondary">—</span> @endif
                            </td>
                            <td>@include('partials.status-badge', ['status' => $r->status])</td>
                            @if($hasForm)
                                <td>
                                    @if($r->response)
                                        <a href="{{ route($responsesRoute, ['campaign' => $campaign->id, 'q' => $r->email]) }}" class="pill tone-green text-decoration-none">Répondu</a>
                                    @elseif($r->form_opened_at)
                                        <span class="pill tone-amber">Lien ouvert</span>
                                    @else
                                        <span class="text-secondary small">—</span>
                                    @endif
                                </td>
                            @endif
                            <td class="text-secondary small">{{ $r->sent_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $hasForm ? 5 : 4 }}" class="empty"><i class="bi bi-inbox"></i>Aucun destinataire.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($recipients->hasPages())<div class="mt-3">{{ $recipients->links() }}</div>@endif
    </div>

    <div class="col-xl-5">
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-chat-square-text me-2"></i>Message envoyé</span>
                <span class="small text-secondary fw-normal">{{ $campaign->template?->name }}</span>
            </div>
            <div class="card-body p-2">
                <div class="px-2 pt-1 pb-2 small"><span class="text-secondary">Sujet :</span> <strong>{{ $campaign->subject }}</strong></div>
                <iframe src="{{ route('preview.campaign', $campaign) }}" class="mail-frame sm" title="Aperçu"></iframe>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-body small">
                <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Soumise le</span><span>{{ $campaign->created_at->format('d/m/Y H:i') }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Envoi lancé</span><span>{{ $campaign->dispatched_at?->format('d/m/Y H:i') ?? '—' }}</span></div>
                <div class="d-flex justify-content-between"><span class="text-secondary">Statut</span>@include('partials.status-badge', ['status' => $campaign->status])</div>
            </div>
        </div>
    </div>
</div>

@if($hasForm && isset($responses))
    <div class="card mt-4" id="reponses">
        <div class="card-header">
            <span><i class="bi bi-chat-left-quote me-2"></i>Réponses des clients <span class="text-secondary fw-normal">({{ $responses->total() }})</span></span>
            @if($responses->total())
                <a href="{{ route($responsesRoute, ['campaign' => $campaign->id]) }}" class="small fw-normal text-decoration-none">Synthèse et export CSV <i class="bi bi-arrow-right"></i></a>
            @endif
        </div>
        @forelse($responses as $resp)
            <div class="card-body border-bottom py-3">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <span class="d-flex align-items-center gap-2 overflow-hidden">
                        <span class="avatar" style="width:28px;height:28px;font-size:.72rem">{{ mb_strtoupper(mb_substr($resp->email, 0, 1)) }}</span>
                        <span class="email-cell text-truncate fw-semibold">{{ $resp->email }}</span>
                    </span>
                    <span class="small text-secondary text-nowrap">
                        {{ $resp->updated_at->format('d/m/Y H:i') }}
                        @if($resp->edits > 0)<span class="pill tone-gray ms-1">modifiée</span>@endif
                    </span>
                </div>
                @include('partials.response-answers', ['answers' => $resp->answers])
            </div>
        @empty
            <div class="empty"><i class="bi bi-inbox"></i>Aucune réponse pour le moment.
                <div class="small mt-2">Les réponses des clients s'afficheront ici dès qu'ils rempliront le formulaire depuis l'email.</div>
            </div>
        @endforelse
    </div>
    @if($responses->hasPages())<div class="mt-3">{{ $responses->fragment('reponses')->links() }}</div>@endif
@endif
