@extends('layouts.app')
@section('title', $cohort->exists ? 'Modifier une cohorte' : 'Nouvelle cohorte')

@section('content')
<div class="max-w-3xl">
<x-page-header :title="$cohort->exists ? 'Modifier '.$cohort->label : 'Nouvelle cohorte'"
    subtitle="Exemples : P12 — Matin, P12 — Après-midi. Les horaires s'appliquent aux participants de cette cohorte."
    :back="route('cohorts.index')"/>

@php $field = fn ($name) => 'input'.($errors->has($name) ? ' input-error' : ''); @endphp

<form method="POST" action="{{ $cohort->exists ? route('cohorts.update', $cohort) : route('cohorts.store') }}" class="space-y-4">
    @csrf
    @if ($cohort->exists) @method('PUT') @endif

    <section class="card p-5 sm:p-6">
        <h2 class="font-bold text-lg">Identification</h2>
        <div class="mt-5 grid sm:grid-cols-2 gap-5">
            <div>
                <label for="program" class="label">Programme</label>
                <input id="program" name="program" value="{{ old('program', $cohort->program) }}" required maxlength="100" class="{{ $field('program') }}">
                @error('program')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="name" class="label">Nom de la cohorte <span class="text-ko">*</span></label>
                <input id="name" name="name" value="{{ old('name', $cohort->name) }}" required maxlength="100" placeholder="P12 — Matin" class="{{ $field('name') }}">
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="starts_on" class="label">Date de début <span class="font-normal text-slate-500">(facultative)</span></label>
                <input id="starts_on" type="date" name="starts_on" value="{{ old('starts_on', $cohort->starts_on?->toDateString()) }}" class="{{ $field('starts_on') }}">
                @error('starts_on')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="ends_on" class="label">Date de fin <span class="font-normal text-slate-500">(facultative)</span></label>
                <input id="ends_on" type="date" name="ends_on" value="{{ old('ends_on', $cohort->ends_on?->toDateString()) }}" class="{{ $field('ends_on') }}">
                @error('ends_on')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-500">Avant la date de début et après la date de fin, aucune absence n'est comptée.</p>
    </section>

    <section class="card p-5 sm:p-6">
        <h2 class="font-bold text-lg">Horaires</h2>
        <div class="mt-5 grid sm:grid-cols-2 gap-5">
            <div>
                <label for="cutoff_time" class="label">Heure limite d'arrivée</label>
                <input id="cutoff_time" type="time" name="cutoff_time" value="{{ old('cutoff_time', $cohort->cutoff_time) }}" required class="{{ $field('cutoff_time') }} text-lg font-semibold tabular-nums">
                <p class="text-xs text-slate-500 mt-1.5">Arrivée à cette heure = Présent. Une minute plus tard = Retard.</p>
                @error('cutoff_time')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="absence_time" class="label">Heure limite de pointage <span class="font-normal text-slate-500">(facultative)</span></label>
                <input id="absence_time" type="time" name="absence_time" value="{{ old('absence_time', $cohort->absence_time) }}" class="{{ $field('absence_time') }} text-lg font-semibold tabular-nums">
                <p class="text-xs text-slate-500 mt-1.5">Après cette heure, le scan d'arrivée est refusé et le participant non pointé est absent. Vide = retards acceptés toute la journée.</p>
                @error('absence_time')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>

        <fieldset class="mt-5">
            <legend class="label">Jours de formation</legend>
            <div class="flex flex-wrap gap-2">
                @foreach (\App\Models\Cohort::DAYS as $n => $d)
                    <label class="cursor-pointer">
                        <input type="checkbox" name="training_days[]" value="{{ $n }}" class="peer sr-only" @checked(in_array($n, array_map('intval', (array) old('training_days', $cohort->trainingDays()))))>
                        <span class="inline-block rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-600 peer-checked:bg-navy peer-checked:text-white peer-checked:border-navy peer-focus-visible:ring-2 peer-focus-visible:ring-odc">{{ $d }}</span>
                    </label>
                @endforeach
            </div>
            @error('training_days')<p class="error">{{ $message }}</p>@enderror
        </fieldset>
    </section>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
        <a href="{{ route('cohorts.index') }}" class="btn btn-secondary">Annuler</a>
        <button class="btn btn-primary">{{ $cohort->exists ? 'Enregistrer' : 'Créer la cohorte' }}</button>
    </div>
</form>
</div>
@endsection
