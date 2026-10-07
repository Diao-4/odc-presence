@extends('layouts.app')
@section('title', $user->exists ? 'Modifier un compte' : ($user->isAdmin() ? 'Nouvel administrateur' : 'Nouvel agent'))

@section('content')
<div class="max-w-2xl">
<x-page-header :title="$user->exists ? 'Modifier '.$user->name : ($user->isAdmin() ? 'Nouvel administrateur' : 'Nouvel agent')" subtitle="Communiquez l'identifiant et le mot de passe à la personne : elle pourra le changer depuis « Mot de passe »." :back="route('users.index')"/>

<form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="card p-5 sm:p-6 space-y-5">
    @csrf
    @if ($user->exists) @method('PUT') @endif
    <div class="grid sm:grid-cols-2 gap-5">
        <div>
            <label for="name" class="label">Nom complet</label>
            <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="input @error('name') input-error @enderror">
            @error('name')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="username" class="label">Identifiant de connexion</label>
            <input id="username" name="username" value="{{ old('username', $user->username) }}" required autocapitalize="none" class="input @error('username') input-error @enderror">
            @error('username')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="label">Email <span class="font-normal text-slate-400">(facultatif)</span></label>
            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="input @error('email') input-error @enderror">
            @error('email')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="role" class="label">Rôle</label>
            <select id="role" name="role" class="input">
                <option value="agent" @selected(old('role', $user->role) === 'agent')>Agent : scan et consultation</option>
                <option value="admin" @selected(old('role', $user->role) === 'admin')>Administrateur : accès complet</option>
            </select>
        </div>
        <div class="sm:col-span-2" id="cohort-field">
            <label for="cohort_id" class="label">Cohorte de l'agent <span class="text-ko">*</span></label>
            <select id="cohort_id" name="cohort_id" class="input @error('cohort_id') input-error @enderror">
                <option value="">Choisir une cohorte…</option>
                @foreach ($cohorts as $c)
                    <option value="{{ $c->id }}" @selected((int) old('cohort_id', $user->cohort_id) === $c->id)>{{ $c->label }}{{ $c->is_active ? '' : ' (archivée)' }}</option>
                @endforeach
            </select>
            <p class="text-xs text-slate-500 mt-1.5">L'agent ne voit et ne scanne que les participants de cette cohorte. Un administrateur a accès à toutes les cohortes.</p>
            @error('cohort_id')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="label">{{ $user->exists ? 'Nouveau mot de passe' : 'Mot de passe' }}</label>
            <input id="password" type="password" name="password" autocomplete="new-password" @unless($user->exists) required @endunless class="input @error('password') input-error @enderror">
            @if ($user->exists)<p class="text-xs text-slate-500 mt-1.5">Laisser vide pour conserver le mot de passe actuel.</p>@endif
            @error('password')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="label">Confirmation</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="input">
        </div>
    </div>
    <div class="flex justify-end gap-2">
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Annuler</a>
        <button class="btn btn-primary">{{ $user->exists ? 'Enregistrer' : 'Créer le compte' }}</button>
    </div>
</form>
</div>
@endsection

@push('scripts')
<script>
    // Le choix de la cohorte ne concerne que les agents.
    (() => {
        const role = document.getElementById('role'), field = document.getElementById('cohort-field');
        const sync = () => field.classList.toggle('hidden', role.value !== 'agent');
        role.addEventListener('change', sync);
        sync();
    })();
</script>
@endpush
