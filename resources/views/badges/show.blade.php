@extends('layouts.app')
@section('title', 'Badge '.$badge->number)

@push('head')
    @include('badges._card-styles')
@endpush

@section('content')
<x-page-header :title="'Badge '.$badge->number" :subtitle="$badge->participant->full_name.' · version '.$badge->version" :back="route('badges.index')"/>

<div class="grid md:grid-cols-[auto_1fr] gap-6 items-start">
    <div class="mx-auto">@include('badges._card', ['card' => $card])</div>

    <div class="card p-5 sm:p-6 space-y-5">
        <div class="flex items-center gap-2">@include('partials.status', ['status' => $badge->status])
            <span class="text-sm text-slate-500">Émis le {{ $badge->issued_at?->format('d/m/Y à H:i') }}</span>
        </div>
        @unless ($badge->isActive())
            <p class="rounded-xl bg-ko-50 text-red-800 text-sm px-4 py-3">Ce badge est refusé au scan (« Badge invalide ou désactivé »).</p>
        @endunless
        <div>
            <p class="text-sm text-slate-500">Identifiant contenu dans le QR Code</p>
            <p class="font-mono text-sm mt-0.5 break-all">{{ $badge->token }}</p>
            <p class="text-xs text-slate-500 mt-1">Aucune donnée personnelle n'est stockée dans le QR Code.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="GET" action="{{ route('badges.download', $badge) }}" class="flex gap-2">
                <select name="size" class="input w-auto" aria-label="Taille du badge">
                    @foreach ($sizes as $k => [$l])
                        <option value="{{ $k }}" @selected($size === $k)>{{ $l }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary whitespace-nowrap"><x-icon name="download" class="w-4 h-4"/> Télécharger (PDF)</button>
            </form>
            <a href="{{ route('badges.print-one', $badge) }}" target="_blank" class="btn btn-secondary"><x-icon name="printer" class="w-4 h-4"/> Imprimer</a>
            @if ($badge->status !== 'replaced')
                <form method="POST" action="{{ route('badges.replace', $badge) }}" onsubmit="return confirm('Émettre un nouveau badge ? L\'ancien QR Code sera immédiatement refusé.')">@csrf
                    <button class="btn btn-secondary"><x-icon name="refresh" class="w-4 h-4"/> Remplacer</button>
                </form>
                <form method="POST" action="{{ route('badges.toggle', $badge) }}">@csrf @method('PATCH')
                    <button class="btn {{ $badge->isActive() ? 'btn-ghost text-ko hover:bg-ko-50' : 'btn-secondary' }}">
                        <x-icon :name="$badge->isActive() ? 'ban' : 'check'" class="w-4 h-4"/> {{ $badge->isActive() ? 'Désactiver' : 'Réactiver' }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
