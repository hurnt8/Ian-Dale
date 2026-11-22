@extends('layouts.guest')
@section('title', 'Connexion')
@section('content')
<h1 class="h3 fw-bold mb-1">Bon retour</h1>
<p class="text-secondary mb-4">Connectez-vous à votre espace.</p>

@if($errors->any())
    <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label">Adresse email</label>
        <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg" required autofocus placeholder="vous@exemple.fr">
    </div>
    <div class="mb-3">
        <label class="form-label">Mot de passe</label>
        <input type="password" name="password" class="form-control form-control-lg" required placeholder="••••••••">
    </div>
    <div class="form-check mb-4">
        <input type="checkbox" name="remember" class="form-check-input" id="remember">
        <label class="form-check-label" for="remember">Se souvenir de moi</label>
    </div>
    <button class="pub-submit">Se connecter</button>
</form>
@endsection
