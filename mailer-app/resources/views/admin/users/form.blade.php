@extends('layouts.app')
@section('title', $user->exists ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur')
@section('subtitle')
    <a href="{{ route('admin.users.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Utilisateurs</a>
@endsection

@section('content')
<div class="row"><div class="col-xl-8">
<div class="card"><div class="card-body">
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
        @csrf
        @if($user->exists) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nom</label>
                <input name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email de connexion</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Rôle</label>
                <select name="role" class="form-select">
                    @foreach(\App\Enums\Role::cases() as $r)
                        <option value="{{ $r->value }}" @selected(old('role', $user->role?->value) === $r->value)>{{ $r->label() }}</option>
                    @endforeach
                </select>
                <div class="form-text">Un propet ne voit que ses propres listes. Un admin voit tout.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-control" @required(! $user->exists) autocomplete="new-password" placeholder="{{ $user->exists ? 'Laisser vide pour ne pas changer' : '8 caractères minimum' }}">
            </div>
            @if($user->exists)
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" @checked(old('is_active', $user->is_active))>
                        <label class="form-check-label" for="is_active">Compte actif</label>
                    </div>
                </div>
            @endif

            <div class="col-12">
                <label class="form-label">Messages autorisés pour cet agent</label>
                <div class="form-text mb-2">Cochez les messages que ce propet peut utiliser lors d'un envoi. <strong>Si rien n'est coché, il peut utiliser tous les messages disponibles.</strong> (Sans effet pour un admin, qui voit tout.)</div>
                @php $assignedIds = old('templates', $assigned ?? []); @endphp
                @forelse($templates as $t)
                    <div class="form-check">
                        <input type="checkbox" name="templates[]" value="{{ $t->id }}" class="form-check-input" id="tpl{{ $t->id }}"
                               @checked(in_array((string) $t->id, array_map('strval', $assignedIds), true))>
                        <label class="form-check-label" for="tpl{{ $t->id }}">{{ $t->name }}@if($t->hasForm())<span class="badge bg-light text-secondary ms-1">formulaire</span>@endif</label>
                    </div>
                @empty
                    <div class="text-secondary small">Aucun message disponible. Créez-en dans Admin &gt; Messages.</div>
                @endforelse
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Enregistrer</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-light">Annuler</a>
        </div>
    </form>
</div></div>
</div></div>
@endsection
