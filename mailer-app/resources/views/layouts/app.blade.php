@php
    $me = auth()->user();
    $initials = collect(explode(' ', $me->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $pendingCount = $me->isAdmin()
        ? \App\Models\Campaign::where('status', \App\Enums\CampaignStatus::Pending->value)->count()
        : 0;
    $link = fn (string $route, string $pattern) => request()->routeIs($pattern) ? 'side-link active' : 'side-link';
@endphp
<!doctype html>
<html lang="fr">
<head>@include('layouts.head')</head>
<body>
<div class="mobile-bar">
    <button class="btn btn-sm text-white" onclick="document.querySelector('.sidebar').classList.toggle('open')"><i class="bi bi-list fs-4"></i></button>
    <strong>{{ config('app.name') }}</strong>
</div>

<div class="app">
    <aside class="sidebar" onclick="if(event.target===this)this.classList.remove('open')">
        <a href="{{ route('home') }}" class="brand"><span class="brand-logo"><i class="bi bi-envelope-paper-fill"></i></span>{{ config('app.name') }}</a>

        @if($me->isAdmin())
            <div class="nav-section">Pilotage</div>
            <a href="{{ route('admin.dashboard') }}" class="{{ $link('admin.dashboard', 'admin.dashboard') }}"><i class="bi bi-grid-1x2"></i> Tableau de bord</a>
            <a href="{{ route('admin.campaigns.index') }}" class="{{ $link('', 'admin.campaigns.*') }}"><i class="bi bi-collection"></i> Listes
                @if($pendingCount)<span class="count">{{ $pendingCount }}</span>@endif
            </a>
            <a href="{{ route('admin.emails.index') }}" class="{{ $link('', 'admin.emails.*') }}"><i class="bi bi-envelope-at"></i> Tous les emails</a>
            <a href="{{ route('admin.responses.index') }}" class="{{ $link('', 'admin.responses.*') }}"><i class="bi bi-chat-left-quote"></i> Réponses</a>
            <a href="{{ route('verification.show') }}" class="{{ $link('', 'verification.*') }}"><i class="bi bi-patch-check"></i> Vérifier des emails</a>
            <div class="nav-section">Configuration</div>
            <a href="{{ route('admin.templates.index') }}" class="{{ $link('', 'admin.templates.*') }}"><i class="bi bi-chat-square-text"></i> Messages</a>
            <a href="{{ route('admin.pages.index') }}" class="{{ $link('', 'admin.pages.*') }}"><i class="bi bi-window"></i> Vues</a>
            <a href="{{ route('admin.presentation.edit') }}" class="{{ $link('', 'admin.presentation.*') }}"><i class="bi bi-palette"></i> Présentation</a>
            <a href="{{ route('admin.users.index') }}" class="{{ $link('', 'admin.users.*') }}"><i class="bi bi-people"></i> Utilisateurs</a>
            <a href="{{ route('admin.settings.edit') }}" class="{{ $link('', 'admin.settings.*') }}"><i class="bi bi-sliders"></i> Paramètres</a>
        @else
            <div class="nav-section">Mon espace</div>
            <a href="{{ route('propet.campaigns.create') }}" class="{{ $link('', 'propet.campaigns.create') }}"><i class="bi bi-plus-circle"></i> Nouvelle liste</a>
            <a href="{{ route('propet.campaigns.index') }}" class="{{ request()->routeIs('propet.campaigns.index', 'propet.campaigns.show') ? 'side-link active' : 'side-link' }}"><i class="bi bi-collection"></i> Mes listes</a>
            <a href="{{ route('propet.templates.index') }}" class="{{ $link('', 'propet.templates.*') }}"><i class="bi bi-chat-square-text"></i> Mes messages</a>
            <a href="{{ route('propet.emails.index') }}" class="{{ $link('', 'propet.emails.*') }}"><i class="bi bi-envelope-at"></i> Mes emails</a>
            <a href="{{ route('propet.responses.index') }}" class="{{ $link('', 'propet.responses.*') }}"><i class="bi bi-chat-left-quote"></i> Réponses reçues</a>
            <a href="{{ route('propet.pages.index') }}" class="{{ $link('', 'propet.pages.*') }}"><i class="bi bi-window"></i> Vues</a>
            <a href="{{ route('verification.show') }}" class="{{ $link('', 'verification.*') }}"><i class="bi bi-patch-check"></i> Vérifier des emails</a>
        @endif

        <div class="sidebar-user">
            <span class="avatar">{{ mb_strtoupper($initials) }}</span>
            <div class="overflow-hidden">
                <div class="name text-truncate">{{ $me->name }}</div>
                <div class="role">{{ $me->role->label() }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="ms-auto">
                @csrf
                <button class="btn-logout" title="Déconnexion"><i class="bi bi-box-arrow-right fs-5"></i></button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div>
                <h1>@yield('title')</h1>
                @hasSection('subtitle')<p class="subtitle">@yield('subtitle')</p>@endif
            </div>
            <div class="d-flex gap-2 flex-wrap">@yield('actions')</div>
        </header>

        <div class="content">
            @if(session('success'))<div class="alert alert-success d-flex gap-2"><i class="bi bi-check-circle-fill"></i>{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger d-flex gap-2"><i class="bi bi-exclamation-octagon-fill"></i>{{ session('error') }}</div>@endif
            @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            @yield('content')
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@include('partials.confirm-modal')
</body>
</html>
