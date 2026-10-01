@extends('layouts.app')
@section('title', $type === 'late' ? 'Justifier un retard' : 'Justifier une absence')

@section('content')
<div class="max-w-2xl">
<x-page-header :title="$type === 'late' ? 'Justifier un retard' : 'Justifier une absence'" :back="$back"/>

<section class="card p-5 sm:p-6 mb-4 flex items-center gap-4">
    @include('partials.avatar', ['participant' => $participant, 'size' => 'w-14 h-14 text-lg'])
    <div class="flex-1 min-w-0">
        <p class="font-bold text-lg">{{ $participant->full_name }}</p>
        <p class="text-sm text-slate-500 first-letter:uppercase">{{ $date->translatedFormat('l j F Y') }}</p>
    </div>
    <div class="text-right">
        @include('partials.status', ['status' => $type, 'justified' => (bool) $justification])
        @if ($attendance)
            <p class="text-sm text-slate-500 mt-1 tabular-nums">Arrivée {{ $attendance->arrived_at->format('H:i') }} · {{ $attendance->late_minutes }} min</p>
        @endif
    </div>
</section>

<form method="POST" action="{{ route('justifications.store') }}" enctype="multipart/form-data" class="card p-5 sm:p-6 space-y-5">
    @csrf
    <input type="hidden" name="participant_id" value="{{ $participant->id }}">
    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
    <input type="hidden" name="back" value="{{ $back }}">

    <div>
        <label for="reason" class="label">Motif</label>
        <textarea id="reason" name="reason" rows="4" maxlength="1000" required placeholder="Ex. : rendez-vous médical, problème de transport…"
                  class="input @error('reason') input-error @enderror">{{ old('reason', $justification?->reason) }}</textarea>
        @error('reason')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="document" class="label">Justificatif <span class="font-normal text-slate-500">(facultatif — PDF ou image, 5 Mo max.)</span></label>
        @if ($justification?->document_path)
            <div class="flex flex-wrap items-center gap-3 mb-2 text-sm">
                <a href="{{ route('justifications.document', $justification) }}" class="inline-flex items-center gap-1.5 font-semibold text-odc hover:underline"><x-icon name="doc" class="w-4 h-4"/> {{ $justification->document_name }}</a>
                <label class="flex items-center gap-2 text-slate-600"><input type="checkbox" name="remove_document" value="1" class="accent-[#FF7900]"> Supprimer</label>
            </div>
        @endif
        <input id="document" name="document" type="file" accept="application/pdf,image/jpeg,image/png,image/webp" class="block text-sm file:mr-3 file:rounded-lg file:border file:border-slate-300 file:bg-white file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-navy">
        @error('document')<p class="error">{{ $message }}</p>@enderror
    </div>

    @if ($justification)
        <p class="text-xs text-slate-500">Justifié le {{ $justification->updated_at->format('d/m/Y à H:i') }}@if ($justification->author) par {{ $justification->author->name }}@endif.</p>
    @endif

    <div class="flex flex-wrap justify-end gap-2">
        @if ($justification)
            <button type="submit" form="delete-justification" class="btn btn-ghost text-ko hover:bg-ko-50"><x-icon name="trash" class="w-4 h-4"/> Retirer la justification</button>
        @endif
        <button class="btn btn-primary"><x-icon name="check" class="w-4 h-4"/> Enregistrer</button>
    </div>
</form>

@if ($justification)
    <form id="delete-justification" method="POST" action="{{ route('justifications.destroy', $justification) }}" onsubmit="return confirm('Retirer cette justification ?')">@csrf @method('DELETE')<input type="hidden" name="back" value="{{ $back }}"></form>
@endif
</div>
@endsection
