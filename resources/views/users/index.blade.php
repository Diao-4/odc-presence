@extends('layouts.app')
@section('title', 'Agents et utilisateurs')

@section('content')
<x-page-header title="Agents et utilisateurs" subtitle="Créez les comptes des agents de contrôle et des administrateurs">
    <a href="{{ route('users.create', ['role' => 'admin']) }}" class="btn btn-secondary"><x-icon name="plus"/> Administrateur</a>
    <a href="{{ route('users.create') }}" class="btn btn-primary"><x-icon name="plus"/> Nouvel agent</a>
</x-page-header>

<div class="mb-4 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 flex items-start gap-3">
    <x-icon name="info" class="w-5 h-5 shrink-0 text-odc"/>
    <p><strong class="text-navy">Agent</strong> : scanne les badges et consulte les participants et les présences.
       <strong class="text-navy">Administrateur</strong> : accès complet (badges, exports, rapports, paramètres, comptes).</p>
</div>

<form method="GET" class="card p-3 sm:p-4 mb-4 grid gap-3 lg:grid-cols-[auto_14rem_1fr_auto] lg:items-center">
    <div class="flex flex-wrap gap-1.5" role="radiogroup" aria-label="Rôle">
        @foreach (['' => ['Tous', $counts->sum()], 'agent' => ['Agents', $counts['agent'] ?? 0], 'admin' => ['Administrateurs', $counts['admin'] ?? 0]] as $key => [$label, $n])
            <label class="cursor-pointer">
                <input type="radio" name="role" value="{{ $key }}" class="peer sr-only" @checked(($role ?? '') === $key) onchange="this.form.requestSubmit()">
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 peer-checked:bg-navy peer-checked:text-white peer-checked:border-navy peer-focus-visible:ring-2 peer-focus-visible:ring-odc">
                    {{ $label }} <span class="text-xs opacity-70 tabular-nums">{{ $n }}</span>
                </span>
            </label>
        @endforeach
    </div>
    <label for="filter-cohort" class="sr-only">Cohorte</label>
    <select id="filter-cohort" name="cohort_id" class="input" onchange="this.form.requestSubmit()">
        <option value="">Toutes les cohortes</option>
        @foreach ($cohorts as $c)
            <option value="{{ $c->id }}" @selected($cohortId === $c->id)>{{ $c->name }}</option>
        @endforeach
    </select>
    <label class="relative">
        <span class="sr-only">Rechercher</span>
        <x-icon name="search" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom, identifiant ou email…" class="input pl-10">
    </label>
    <button class="btn btn-dark">Rechercher</button>
</form>

@if ($users->isEmpty())
    <div class="card px-6 py-14 text-center">
        <p class="font-semibold">Aucun compte trouvé</p>
        <p class="text-sm text-slate-500 mt-1">Modifiez la recherche ou créez un nouvel agent.</p>
    </div>
@else
    {{-- Tableau (écrans larges) --}}
    <div class="card overflow-hidden hidden md:block">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Nom</th><th>Identifiant</th><th>Rôle</th><th>Cohorte</th><th>Statut</th><th>Dernière connexion</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                @foreach ($users as $u)
                    <tr class="{{ $u->is_active ? '' : 'opacity-60' }}">
                        <td class="font-semibold">{{ $u->name }} @if ($u->is(auth()->user()))<span class="text-xs text-slate-400 font-normal">(vous)</span>@endif</td>
                        <td class="font-mono text-sm">{{ $u->username }}</td>
                        <td><span class="pill {{ $u->isAdmin() ? 'bg-navy text-white' : 'bg-slate-100 text-slate-700' }}">{{ $u->role_label }}</span></td>
                        <td class="text-slate-600">{{ $u->isAdmin() ? 'Toutes' : ($u->cohort?->name ?? '—') }}</td>
                        <td>@include('partials.status', ['status' => $u->is_active ? 'active' : 'inactive'])</td>
                        <td class="tabular-nums text-slate-600">{{ $u->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</td>
                        <td>@include('users._actions')</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Cartes (mobile) --}}
    <div class="md:hidden space-y-2">
        @foreach ($users as $u)
            <div class="card p-4 {{ $u->is_active ? '' : 'opacity-60' }}">
                <div class="flex items-start gap-3">
                    <span class="w-11 h-11 rounded-full {{ $u->isAdmin() ? 'bg-navy text-white' : 'bg-odc-50 text-odc-600' }} grid place-items-center font-bold shrink-0">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold truncate">{{ $u->name }} @if ($u->is(auth()->user()))<span class="text-xs text-slate-400 font-normal">(vous)</span>@endif</p>
                        <p class="text-sm text-slate-500 truncate"><span class="font-mono">{{ $u->username }}</span> · {{ $u->isAdmin() ? 'Toutes les cohortes' : ($u->cohort?->name ?? 'Sans cohorte') }}</p>
                    </div>
                    <span class="pill {{ $u->isAdmin() ? 'bg-navy text-white' : 'bg-slate-100 text-slate-700' }}">{{ $u->role_label }}</span>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between gap-3">
                    <div class="text-xs text-slate-500 leading-snug">
                        @include('partials.status', ['status' => $u->is_active ? 'active' : 'inactive'])
                        <span class="block mt-1">Dernière connexion : {{ $u->last_login_at?->format('d/m/Y H:i') ?? 'jamais' }}</span>
                    </div>
                    @include('users._actions')
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endif
@endsection
