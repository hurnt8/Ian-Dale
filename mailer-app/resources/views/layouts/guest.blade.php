<!doctype html>
<html lang="fr">
<head>@include('layouts.head')</head>
<body class="pub">
    <div class="pub-wrap" style="max-width: 440px">
        <div class="pub-card">
            <div class="pub-logo">
                <a href="{{ url('/') }}" class="brand p-0 d-inline-flex" style="color:var(--pub-ink)">
                    <span class="brand-logo"><i class="bi bi-envelope-paper-fill"></i></span>{{ config('app.name') }}
                </a>
            </div>
            <div class="pub-body pt-2">@yield('content')</div>
        </div>
        <p class="pub-foot">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</body>
</html>
