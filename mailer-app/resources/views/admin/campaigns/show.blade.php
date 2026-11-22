@extends('layouts.app')
@section('title', $campaign->title)
@section('subtitle')
    <a href="{{ route('admin.campaigns.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Listes</a>
    <span class="mx-1">·</span> Soumise par <strong>{{ $campaign->user->name }}</strong> ({{ $campaign->user->email }})
    @if($campaign->reviewer)<span class="mx-1">·</span> traitée par {{ $campaign->reviewer->name }} le {{ $campaign->reviewed_at->format('d/m/Y H:i') }}@endif
@endsection

@section('actions')
    @if($campaign->status === \App\Enums\CampaignStatus::Pending)
        <form method="POST" action="{{ route('admin.campaigns.approve', $campaign) }}" data-confirm="Le message sera envoyé automatiquement à {{ $stats['pending'] }} destinataire(s) de « {{ $campaign->title }} »." data-confirm-title="Valider et lancer l'envoi ?" data-confirm-button="Valider et envoyer" data-confirm-variant="success">
            @csrf<button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i> Valider et envoyer</button>
        </form>
        <form method="POST" action="{{ route('admin.campaigns.reject', $campaign) }}" data-confirm="La liste ne sera pas envoyée. Le propet verra qu'elle a été refusée." data-confirm-title="Refuser cette liste ?" data-confirm-button="Refuser" data-confirm-variant="danger">
            @csrf<button class="btn btn-light text-danger"><i class="bi bi-x-circle me-1"></i> Refuser</button>
        </form>
    @endif
    @if($stats['failed'] > 0)
        <form method="POST" action="{{ route('admin.campaigns.retry', $campaign) }}" data-confirm="{{ $stats['failed'] }} envoi(s) en échec seront remis en file d'attente." data-confirm-title="Relancer les échecs ?" data-confirm-button="Relancer" data-confirm-variant="warning">
            @csrf<button class="btn btn-warning"><i class="bi bi-arrow-repeat me-1"></i> Relancer {{ $stats['failed'] }} échec(s)</button>
        </form>
    @endif
    <a href="{{ route('admin.emails.export', ['campaign' => $campaign->id]) }}" class="btn btn-light"><i class="bi bi-download"></i></a>
    <form method="POST" action="{{ route('admin.campaigns.destroy', $campaign) }}" data-confirm="La liste, ses adresses et ses réponses seront supprimées définitivement." data-confirm-title="Supprimer cette liste ?" data-confirm-button="Supprimer" data-confirm-variant="danger">
        @csrf @method('DELETE')<button class="btn btn-light" title="Supprimer"><i class="bi bi-trash"></i></button>
    </form>
@endsection

@section('content')
@include('partials.campaign-detail')
@endsection
