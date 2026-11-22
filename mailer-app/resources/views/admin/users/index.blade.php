@extends('layouts.app')
@section('title', 'Utilisateurs')
@section('subtitle', 'Administrateurs et propets ayant accès à la plateforme.')
@section('actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> Nouvel utilisateur</a>
@endsection

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Utilisateur</th><th>Rôle</th><th>Listes</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $u)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                            <div><div class="fw-semibold">{{ $u->name }}</div><div class="small text-secondary">{{ $u->email }}</div></div>
                        </div>
                    </td>
                    <td><span class="pill {{ $u->isAdmin() ? 'tone-indigo' : 'tone-blue' }}">{{ $u->role->label() }}</span></td>
                    <td>
                        @if($u->isPropet())
                            <a href="{{ route('admin.emails.index', ['propet' => $u->id]) }}" class="text-decoration-none">{{ $u->campaigns_count }}</a>
                        @else — @endif
                    </td>
                    <td><span class="pill {{ $u->is_active ? 'tone-green' : 'tone-red' }}">{{ $u->is_active ? 'Actif' : 'Désactivé' }}</span></td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-light btn-icon" title="Modifier"><i class="bi bi-pencil"></i></a>
                            @unless($u->is(auth()->user()))
                                <form method="POST" action="{{ route('admin.users.destroy', $u) }}" data-confirm="{{ $u->name }} et toutes ses listes, adresses et réponses seront supprimés définitivement." data-confirm-title="Supprimer cet utilisateur ?" data-confirm-button="Supprimer" data-confirm-variant="danger">
                                    @csrf @method('DELETE')<button class="btn btn-sm btn-light btn-icon text-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>
                            @endunless
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@if($users->hasPages())<div class="mt-3">{{ $users->links() }}</div>@endif
@endsection
