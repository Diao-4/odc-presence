@extends('layouts.app')
@section('title', 'Mot de passe')

@section('content')
<div class="max-w-lg">
<x-page-header title="Changer le mot de passe" subtitle="8 caractères minimum."/>
<form method="POST" action="{{ route('password.update') }}" class="card p-5 sm:p-6 space-y-5">
    @csrf @method('PUT')
    <div>
        <label for="current_password" class="label">Mot de passe actuel</label>
        <input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="input @error('current_password') input-error @enderror">
        @error('current_password')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="password" class="label">Nouveau mot de passe</label>
        <input id="password" type="password" name="password" required autocomplete="new-password" class="input @error('password') input-error @enderror">
        @error('password')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="password_confirmation" class="label">Confirmer le nouveau mot de passe</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="input">
    </div>
    <button class="btn btn-primary w-full">Modifier le mot de passe</button>
</form>
</div>
@endsection
