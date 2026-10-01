@extends('layouts.app')
@section('title', 'Modifier '.$participant->full_name)

@section('content')
<div class="max-w-3xl">
    <x-page-header :title="'Modifier '.$participant->full_name" :subtitle="'Badge '.$participant->code" :back="route('participants.show', $participant)"/>
    <form method="POST" action="{{ route('participants.update', $participant) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf @method('PUT')
        @include('participants._form')
        <div class="flex flex-col-reverse sm:flex-row sm:justify-between gap-2">
            <button type="submit" form="delete-form" class="btn btn-ghost text-ko hover:bg-ko-50"><x-icon name="trash" class="w-4 h-4"/> Supprimer</button>
            <div class="flex flex-col-reverse sm:flex-row gap-2">
                <a href="{{ route('participants.show', $participant) }}" class="btn btn-secondary">Annuler</a>
                <button class="btn btn-primary">Enregistrer les modifications</button>
            </div>
        </div>
    </form>
    <form id="delete-form" method="POST" action="{{ route('participants.destroy', $participant) }}"
          onsubmit="return confirm('Supprimer {{ addslashes($participant->full_name) }} ? Son badge sera désactivé ; son historique de présence est conservé.')">
        @csrf @method('DELETE')
    </form>
</div>
@endsection
