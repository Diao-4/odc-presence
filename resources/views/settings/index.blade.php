@extends('layouts.app')
@section('title', 'Paramètres')

@section('content')
<div class="max-w-4xl">
<x-page-header title="Paramètres"/>

<form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
    @csrf @method('PUT')

    <section class="card p-5 sm:p-6">
        <h2 class="font-bold text-lg">Présence</h2>
        <div class="mt-5 grid sm:grid-cols-2 gap-5">
            <div>
                <label for="cutoff_time" class="label">Heure limite d'arrivée</label>
                <input id="cutoff_time" type="time" name="cutoff_time" value="{{ old('cutoff_time', $s['cutoff_time']) }}" required class="input text-lg font-semibold tabular-nums @error('cutoff_time') input-error @enderror">
                <p class="text-xs text-slate-500 mt-1.5">Arrivée à {{ $s['cutoff_time'] }} = Présent. Une minute plus tard = Retard.</p>
                @error('cutoff_time')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="absence_time" class="label">Heure limite de pointage <span class="font-normal text-slate-500">(facultatif)</span></label>
                <input id="absence_time" type="time" name="absence_time" value="{{ old('absence_time', $s['absence_time']) }}" class="input text-lg font-semibold tabular-nums @error('absence_time') input-error @enderror">
                <p class="text-xs text-slate-500 mt-1.5">Après cette heure, le scan d'arrivée est refusé et le participant non pointé est déclaré absent. Laisser vide pour accepter les retards toute la journée.</p>
                @error('absence_time')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="scan_reset_seconds" class="label">Retour automatique au scanner</label>
                <div class="flex items-center gap-2">
                    <input id="scan_reset_seconds" type="number" min="1" max="30" name="scan_reset_seconds" value="{{ old('scan_reset_seconds', $s['scan_reset_seconds']) }}" class="input w-24">
                    <span class="text-sm text-slate-500">secondes après l'affichage du résultat</span>
                </div>
                @error('scan_reset_seconds')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>

        <fieldset class="mt-5">
            <legend class="label">Jours de formation <span class="font-normal text-slate-500">(les absences ne sont comptées que ces jours-là)</span></legend>
            <div class="flex flex-wrap gap-2">
                @foreach ([1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 7 => 'Dim'] as $n => $d)
                    <label class="cursor-pointer">
                        <input type="checkbox" name="training_days[]" value="{{ $n }}" class="peer sr-only" @checked(in_array($n, old('training_days', $days)))>
                        <span class="inline-block rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-600 peer-checked:bg-navy peer-checked:text-white peer-checked:border-navy peer-focus-visible:ring-2 peer-focus-visible:ring-odc">{{ $d }}</span>
                    </label>
                @endforeach
            </div>
            @error('training_days')<p class="error">{{ $message }}</p>@enderror
        </fieldset>

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
        <a href="{{ route('users.index') }}" class="rounded-xl border border-slate-200 p-4 hover:border-odc flex items-center gap-3"><x-icon name="users" class="text-odc"/><span class="font-medium">Gestion des utilisateurs</span></a>
        <a href="{{ route('logs.index') }}" class="rounded-xl border border-slate-200 p-4 hover:border-odc flex items-center gap-3"><x-icon name="list" class="text-odc"/><span class="font-medium">Journal des opérations</span></a>
    </div>
</section>
</div>
@endsection
