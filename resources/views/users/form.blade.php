@extends('layouts.app')
@section('title', $user->exists ? 'Modifier un utilisateur' : 'Nouvel utilisateur')

@section('content')
<div class="max-w-2xl">
<x-page-header :title="$user->exists ? 'Modifier '.$user->name : 'Nouvel utilisateur'" :back="route('users.index')"/>

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
        <button class="btn btn-primary">{{ $user->exists ? 'Enregistrer' : "Créer l'utilisateur" }}</button>
    </div>
</form>
</div>
@endsection
