{{-- Carte publique centrée (bandeau orange) : formulaire, vues, connexion.
     Variables attendues : $logo (data URI|null), $logoAlign (left|center|right), $footer (string|null) --}}
<!doctype html>
<html lang="fr">
<head>
    @include('layouts.head')
    <meta name="robots" content="noindex, nofollow">
    @yield('head')
</head>
<body class="pub">
    <div class="pub-wrap">
        @php
            // Bandeau et couleur d'accent choisis dans le formulaire (plus aucun bandeau en haut)
            $pubForm = isset($form) && is_array($form) ? $form : [];
            $pubAccent = \App\Support\ResponseForm::color($pubForm['accent_color'] ?? null);
        @endphp
        <div class="pub-card" style="--pub-accent: {{ $pubAccent }}">
            @isset($logo)
                @if($logo)
                    <div class="pub-logo {{ $logoAlign ?? 'center' }}">
                        <img src="{{ $logo }}" alt="{{ config('app.name') }}">
                    </div>
                @endif
            @endisset

            @yield('card')
        </div>

        @if(! empty($footer))
            <p class="pub-foot">{{ $footer }}</p>
        @endif
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
