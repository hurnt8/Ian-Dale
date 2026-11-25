@php
    /** @var array $form */
    $old = fn (string $key) => old("answers.$key");
    $thanks = session('thanks');
@endphp
@extends('layouts.public')
@section('title', $form['title'])

@section('card')
    @if($thanks)
        <div class="pub-done">
            <div class="ic"><i class="bi bi-check-lg"></i></div>
            <h1 class="h4 fw-bold">{{ $form['thanks'] }}</h1>
        </div>
    @else
        <div class="pub-body">
            <h1 class="pub-title">{{ $form['title'] }}</h1>
            @if($form['intro'])<p class="pub-intro">{{ $form['intro'] }}</p>@endif

            @if($errors->any())
                <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ $action ?? '#' }}" @if(! $action) onsubmit="event.preventDefault(); alert('Aperçu : rien n\'est enregistré.')" @endif novalidate>
                @csrf
                @include('partials.public-fields', ['form' => $form, 'old' => $old])
                <button class="pub-submit">{{ $form['submit_label'] }}</button>
            </form>
        </div>
    @endif
@endsection
