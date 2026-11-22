@extends('layouts.app')
@section('title', $campaign->title)
@section('subtitle')
    <a href="{{ route('propet.campaigns.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Mes listes</a>
@endsection

@section('content')
@if(session('invalid'))
    <div class="alert alert-warning">
        <strong><i class="bi bi-exclamation-triangle me-1"></i>{{ count(session('invalid')) }} adresse(s) invalide(s) ignorée(s) :</strong>
        <div class="email-cell mt-1">{{ implode(', ', session('invalid')) }}</div>
    </div>
@endif

@include('partials.campaign-detail')
@endsection
