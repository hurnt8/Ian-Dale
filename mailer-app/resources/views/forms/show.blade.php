@php
    /** @var array $form  @var array $answers */
    $old = fn (string $key) => old("answers.$key", $answers[$key] ?? null);
    $thanks = session('thanks');
@endphp
@extends('layouts.public')
@section('title', $form['title'])

@section('card')
    @if($thanks)
        <div class="pub-done">
            <div class="ic"><i class="bi bi-check-lg"></i></div>
            <h1 class="h4 fw-bold">{{ $form['thanks'] }}</h1>
            <p class="text-secondary mb-4">Vous pouvez modifier votre réponse à tout moment avec le même lien.</p>
            <a href="{{ url()->current() }}" class="btn btn-light">Modifier ma réponse</a>
        </div>
    @elseif($closed)
        <div class="pub-done closed">
            <div class="ic"><i class="bi bi-lock"></i></div>
            <h1 class="h5 fw-bold">Ce formulaire est fermé.</h1>
        </div>
    @else
        <div class="pub-body">
            <h1 class="pub-title">{{ $form['title'] }}</h1>
            @if($form['intro'])<p class="pub-intro">{{ $form['intro'] }}</p>@endif

            <div class="text-center mb-4">
                <span class="pub-who" title="Votre adresse est reconnue automatiquement grâce au lien reçu par email.">
                    <i class="bi bi-person-check"></i> Répondre en tant que {{ $maskedEmail }}
                </span>
            </div>

            @if($response)
                <div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-1"></i>Vous avez déjà répondu le {{ $response->updated_at->format('d/m/Y à H:i') }}. Vous pouvez modifier votre réponse.</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ $action ?? '#' }}" @if(! $action) onsubmit="event.preventDefault(); alert('Aperçu : la réponse n\'est pas enregistrée.')" @endif novalidate>
                @csrf
                @include('partials.public-fields', ['form' => $form, 'old' => $old])
                <button class="pub-submit">{{ $form['submit_label'] }}</button>
            </form>
        </div>
    @endif
@endsection
