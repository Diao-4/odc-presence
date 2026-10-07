@extends('layouts.app')
@section('title', 'Paramètres')

@section('content')
<div class="max-w-4xl">
<x-page-header title="Paramètres"/>

<form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
    @csrf @method('PUT')

    <section class="card p-5 sm:p-6">
        <h2 class="font-bold text-lg">Présence</h2>
        <a href="{{ route('cohorts.index') }}" class="mt-4 flex items-start gap-3 rounded-xl border border-orange-200 bg-odc-50 px-4 py-3 text-sm text-orange-900 hover:border-odc">
            <x-icon name="clock" class="w-5 h-5 shrink-0 text-odc"/>
            <span><strong>Les horaires se règlent par cohorte</strong> (heure limite d'arrivée, heure limite de pointage, jours de formation). Ouvrir les cohortes →</span>
        </a>
        <div class="mt-5">
            <label for="scan_reset_seconds" class="label">Retour automatique au scanner</label>
            <div class="flex items-center gap-2">
                <input id="scan_reset_seconds" type="number" min="1" max="30" name="scan_reset_seconds" value="{{ old('scan_reset_seconds', $s['scan_reset_seconds']) }}" class="input w-24">
                <span class="text-sm text-slate-500">secondes après l'affichage du résultat</span>
            </div>
            @error('scan_reset_seconds')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="mt-5 divide-y divide-slate-100 border-t border-slate-100">
            @foreach ([
                'late_enabled' => ['Activer la gestion des retards', 'Désactivé : toute arrivée est enregistrée comme « Présent ».'],
                'departure_enabled' => ["Activer l'enregistrement des départs", 'Ajoute un mode « Départ » au scanner : le badge est scanné une seconde fois en fin de journée.'],
            ] as $key => [$label, $help])
                <label class="flex items-center justify-between gap-4 py-4 cursor-pointer">
                    <span><span class="block font-medium">{{ $label }}</span><span class="block text-sm text-slate-500">{{ $help }}</span></span>
                    <input type="checkbox" name="{{ $key }}" value="1" class="peer sr-only" @checked(old($key, $s[$key]))>
                    <span class="relative w-11 h-6 shrink-0 rounded-full bg-slate-300 peer-checked:bg-odc transition-colors after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-5 peer-focus-visible:ring-2 peer-focus-visible:ring-odc peer-focus-visible:ring-offset-2"></span>
                </label>
            @endforeach
        </div>
    </section>

    <section class="card p-5 sm:p-6 grid sm:grid-cols-2 gap-5">
        <div>
            <h2 class="font-bold text-lg">Export</h2>
            <label for="default_export" class="label mt-4">Format par défaut</label>
            <select id="default_export" name="default_export" class="input">
                @foreach (['pdf' => 'PDF', 'xlsx' => 'Excel (XLSX)', 'csv' => 'CSV'] as $k => $l)
                    <option value="{{ $k }}" @selected(old('default_export', $s['default_export']) === $k)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <h2 class="font-bold text-lg">Badges</h2>
            <label for="badge_validity" class="label mt-4">Période de validité imprimée</label>
            <input id="badge_validity" name="badge_validity" value="{{ old('badge_validity', $s['badge_validity']) }}" maxlength="100" placeholder="Ex. : septembre 2026 – février 2027" class="input">
            <p class="text-xs text-slate-500 mt-1.5">Laisser vide pour ne rien afficher.</p>
            <label for="badge_size" class="label mt-4">Taille par défaut des badges téléchargés</label>
            <select id="badge_size" name="badge_size" class="input">
                @foreach (\App\Services\BadgeService::SIZES as $k => [$l])
                    <option value="{{ $k }}" @selected(old('badge_size', $s['badge_size']) === $k)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
    </section>

    <div class="flex justify-end"><button class="btn btn-primary">Enregistrer les paramètres</button></div>
</form>

<section class="card p-5 sm:p-6 mt-6">
    <h2 class="font-bold text-lg">Sécurité</h2>
    <div class="mt-4 grid sm:grid-cols-3 gap-3">
        <a href="{{ route('password.edit') }}" class="rounded-xl border border-slate-200 p-4 hover:border-odc flex items-center gap-3"><x-icon name="key" class="text-odc"/><span class="font-medium">Changer le mot de passe</span></a>
        <a href="{{ route('users.index') }}" class="rounded-xl border border-slate-200 p-4 hover:border-odc flex items-center gap-3"><x-icon name="users" class="text-odc"/><span class="font-medium">Agents et utilisateurs</span></a>
        <a href="{{ route('logs.index') }}" class="rounded-xl border border-slate-200 p-4 hover:border-odc flex items-center gap-3"><x-icon name="list" class="text-odc"/><span class="font-medium">Journal des opérations</span></a>
    </div>
</section>
</div>
@endsection
