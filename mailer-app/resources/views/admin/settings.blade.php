@extends('layouts.app')
@section('title', 'Paramètres')
@section('subtitle', "Comportement de l'envoi automatique.")
@section('actions')
    <a href="{{ route('admin.presentation.edit') }}" class="btn btn-light"><i class="bi bi-palette me-1"></i> Présentation des emails</a>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-xl-7">
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf @method('PUT')

            <div class="card mb-4">
                <div class="card-header"><span><i class="bi bi-diagram-3 me-2"></i>Déclenchement</span></div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="require_approval" value="1" class="form-check-input" id="ra" @checked($requireApproval)>
                        <label class="form-check-label" for="ra">
                            <span class="fw-semibold">Validation admin obligatoire</span>
                            <div class="form-text mt-0">Désactivé : l'envoi part automatiquement dès qu'un propet soumet une liste.</div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><span><i class="bi bi-patch-check me-2"></i>Vérification des adresses</span></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="skip_invalid" value="1" class="form-check-input" id="si" @checked($skipInvalid)>
                        <label class="form-check-label" for="si">
                            <span class="fw-semibold">Écarter les adresses invalides</span>
                            <div class="form-text mt-0">Syntaxe incorrecte, domaine inexistant, adresse jetable, boîte déjà rejetée.</div>
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="skip_typo" value="1" class="form-check-input" id="st" @checked($skipTypo)>
                        <label class="form-check-label" for="st">
                            <span class="fw-semibold">Écarter aussi les fautes de frappe probables</span>
                            <div class="form-text mt-0">Ex. : <code>gmial.com</code>, <code>orange.fe</code>.</div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><span><i class="bi bi-speedometer2 me-2"></i>Débit et limites</span></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Emails par minute</label>
                            <input type="number" name="per_minute" value="{{ $perMinute }}" min="1" max="600" class="form-control">
                            <div class="form-text">Un débit faible évite d'être classé en spam.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Adresses max par liste</label>
                            <input type="number" name="max_recipients" value="{{ $maxRecipients }}" min="1" max="10000" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><span><i class="bi bi-person-badge me-2"></i>Expéditeur</span></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom affiché</label>
                            <input name="mail_from_name" value="{{ old('mail_from_name', $fromName) }}" maxlength="100" class="form-control" placeholder="{{ config('mail.from.name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Adresse de réponse</label>
                            <input type="email" name="mail_reply_to" value="{{ old('mail_reply_to', $replyTo) }}" maxlength="190" class="form-control" placeholder="reponse@votre-domaine.fr">
                        </div>
                        <div class="col-12 small text-secondary">
                            Adresse d'envoi (fichier .env) : <code>{{ $from }}</code> · mailer <code>{{ $mailer }}</code>
                            @if($mailer === 'log')
                                <div class="alert alert-warning mt-2 mb-0 small"><i class="bi bi-exclamation-triangle me-1"></i>Mode <code>log</code> : les emails sont écrits dans <code>storage/logs/laravel.log</code>, pas réellement envoyés.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Enregistrer</button>
        </form>
    </div>

    <div class="col-xl-5">
        <div class="card">
            <div class="card-header"><span><i class="bi bi-person-dash me-2"></i>Liste de suppression</span><span class="text-secondary fw-normal small">{{ $unsubscribes->total() }}</span></div>
            <div class="table-responsive">
                <table class="table">
                    <tbody>
                    @forelse($unsubscribes as $u)
                        <tr>
                            <td class="email-cell">{{ $u->email }}<div class="small text-secondary">{{ $u->created_at->format('d/m/Y') }}</div></td>
                            <td><span class="pill {{ $u->reason === 'hard_bounce' ? 'tone-red' : 'tone-gray' }}">{{ $u->reason === 'hard_bounce' ? 'Boîte inexistante' : 'Désinscrit' }}</span></td>
                            <td class="text-end">
                                @if($u->reason === 'hard_bounce')
                                    <form method="POST" action="{{ route('admin.suppressions.destroy', $u) }}" data-confirm="{{ $u->email }} pourra de nouveau recevoir des emails." data-confirm-title="Réactiver cette adresse ?" data-confirm-button="Réactiver" data-confirm-variant="warning">
                                        @csrf @method('DELETE')<button class="btn btn-sm btn-light btn-icon" title="Réactiver"><i class="bi bi-arrow-counterclockwise"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty"><i class="bi bi-emoji-smile"></i>Aucune adresse.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($unsubscribes->hasPages())<div class="mt-3">{{ $unsubscribes->links() }}</div>@endif
    </div>
</div>
@endsection
