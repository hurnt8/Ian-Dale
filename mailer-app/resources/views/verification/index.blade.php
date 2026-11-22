@extends('layouts.app')
@section('title', 'Vérifier des emails')
@section('subtitle', "Contrôle de la syntaxe, de l'existence du domaine et de son serveur mail, et des fautes de frappe.")

@section('content')
<div class="row g-4">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <label class="form-label" for="v-emails">Adresses à vérifier</label>
                <textarea id="v-emails" rows="10" class="form-control emails" placeholder="contact@exemple.fr, autre@exemple.fr&#10;troisieme@exemple.fr"></textarea>
                <div class="form-text mb-3">Collez la liste telle quelle. Maximum {{ $max }} adresses par vérification.</div>
                <button type="button" id="v-btn" class="btn btn-primary"><i class="bi bi-patch-check me-1"></i> Vérifier</button>
                @if(auth()->user()->isPropet())
                    <a href="{{ route('propet.campaigns.create') }}" class="btn btn-light ms-1">Créer une liste <i class="bi bi-arrow-right"></i></a>
                @endif
            </div>
        </div>
        @include('partials.verify-widget', ['textarea' => 'v-emails', 'button' => 'v-btn'])
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><span><i class="bi bi-info-circle me-2"></i>Ce qui est vérifié</span></div>
            <div class="card-body small">
                @foreach(\App\Enums\VerificationStatus::cases() as $s)
                    <div class="d-flex gap-2 align-items-start mb-2">
                        @include('partials.status-badge', ['status' => $s])
                        <span class="text-secondary">{{ match($s->value) {
                            'valid' => 'Syntaxe correcte, le domaine possède un serveur mail.',
                            'risky' => "Le domaine existe mais n'annonce aucun serveur mail.",
                            'typo' => 'Domaine proche d’un fournisseur connu (gmial.com → gmail.com).',
                            'invalid_syntax' => "L'adresse est mal formée.",
                            'domain_not_found' => "Le domaine n'existe pas.",
                            'disposable' => 'Adresse jetable (yopmail, mailinator…).',
                            'mailbox_not_found' => 'Rejetée par le serveur lors d’un envoi précédent.',
                        } }}</span>
                    </div>
                @endforeach
                <hr>
                <p class="text-secondary mb-0">
                    <i class="bi bi-shield-exclamation me-1"></i>
                    Le système ne peut pas savoir à l'avance si une boîte précise existe : les grands fournisseurs (Orange, Gmail…) ne le révèlent pas.
                    Une adresse rejetée lors d'un envoi est ensuite écartée automatiquement.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
