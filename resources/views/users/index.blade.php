@extends('layouts.app')
@section('title', 'Utilisateurs')

@section('content')
<x-page-header title="Gestion des utilisateurs" subtitle="Administrateurs et agents de contrôle" :back="route('settings.index')">
    <a href="{{ route('users.create') }}" class="btn btn-primary"><x-icon name="plus"/> Nouvel utilisateur</a>
</x-page-header>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Nom</th><th>Identifiant</th><th>Rôle</th><th>Statut</th><th>Dernière connexion</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr class="{{ $u->is_active ? '' : 'opacity-60' }}">
                    <td class="font-semibold">{{ $u->name }} @if ($u->is(auth()->user()))<span class="text-xs text-slate-400 font-normal">(vous)</span>@endif</td>
                    <td class="font-mono text-sm">{{ $u->username }}</td>
                    <td><span class="pill {{ $u->isAdmin() ? 'bg-navy text-white' : 'bg-slate-100 text-slate-700' }}">{{ $u->role_label }}</span></td>
                    <td>@include('partials.status', ['status' => $u->is_active ? 'active' : 'inactive'])</td>
                    <td class="tabular-nums text-slate-600">{{ $u->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</td>
                    <td>
                        <div class="flex justify-end gap-0.5">
                            <a href="{{ route('users.edit', $u) }}" class="btn-icon" title="Modifier"><x-icon name="pencil"/></a>
                            @unless ($u->is(auth()->user()))
                                <form method="POST" action="{{ route('users.toggle', $u) }}">@csrf @method('PATCH')
                                    <button class="btn-icon" title="{{ $u->is_active ? 'Désactiver' : 'Réactiver' }}"><x-icon :name="$u->is_active ? 'ban' : 'check'"/></button>
                                </form>
                                <form method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return confirm('Supprimer le compte {{ $u->username }} ?')">@csrf @method('DELETE')
                                    <button class="btn-icon hover:text-ko" title="Supprimer"><x-icon name="trash"/></button>
                                </form>
                            @endunless
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3">{{ $users->links() }}</div>
</div>
@endsection
