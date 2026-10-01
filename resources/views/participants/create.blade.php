@extends('layouts.app')
@section('title', 'Nouveau participant')

@section('content')
<div class="max-w-3xl">
    <x-page-header title="Nouveau participant" subtitle="L'identifiant unique, le badge et le QR Code sont générés automatiquement à l'enregistrement." :back="route('participants.index')"/>
    <form method="POST" action="{{ route('participants.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @include('participants._form')
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <a href="{{ route('participants.index') }}" class="btn btn-secondary">Annuler</a>
            <button class="btn btn-primary">Enregistrer et générer le badge</button>
        </div>
    </form>
</div>
@endsection
