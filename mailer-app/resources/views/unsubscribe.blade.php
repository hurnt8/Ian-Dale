@extends('layouts.guest')
@section('title', 'Désinscription')
@section('content')
<div class="text-center">
    @if($done)
        <div class="stat-icon tone-green mx-auto mb-3"><i class="bi bi-check2"></i></div>
        <h1 class="h4 fw-bold">Désinscription confirmée</h1>
        <p class="text-secondary"><strong>{{ $email }}</strong> ne recevra plus de messages.</p>
    @else
        <div class="stat-icon tone-red mx-auto mb-3"><i class="bi bi-envelope-slash"></i></div>
        <h1 class="h4 fw-bold">Se désinscrire</h1>
        <p class="text-secondary">Ne plus recevoir de messages à l'adresse <strong>{{ $email }}</strong> ?</p>
        <form method="POST" action="{{ request()->fullUrl() }}">
            @csrf
            <button class="btn btn-danger w-100">Confirmer la désinscription</button>
        </form>
    @endif
</div>
@endsection
