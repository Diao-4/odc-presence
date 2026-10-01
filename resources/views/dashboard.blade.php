@extends('layouts.app')
@section('title', 'Tableau de bord')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Bienvenue, {{ auth()->user()->name }}</h1>
            <p class="text-sm text-slate-500 mt-1">{{ config('odc.organization') }} · {{ config('odc.program') }} {{ config('odc.cohort') }}</p>
        </div>
        <div class="sm:text-right">
            <p class="text-sm text-slate-500 first-letter:uppercase" data-live-date>{{ now()->translatedFormat('l j F Y') }}</p>
            <p class="text-3xl font-extrabold tabular-nums" data-live-clock>{{ now()->format('H:i') }}</p>
        </div>
    </div>

    {{-- Action principale --}}
    <a href="{{ route('scan') }}"
       class="rounded-3xl bg-odc hover:bg-odc-600 text-white p-6 sm:p-8 flex items-center gap-5 shadow-lg shadow-orange-500/25 transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-odc/40">
        <span class="grid place-items-center w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-white/15 shrink-0">
            <x-icon name="qr" class="w-10 h-10 sm:w-12 sm:h-12"/>
        </span>
        <span class="flex-1 min-w-0">
            <span class="block text-2xl sm:text-3xl font-extrabold tracking-tight">SCANNER UN BADGE</span>
            <span class="block text-white/85 text-sm sm:text-base mt-0.5">Ouvre la caméra et enregistre l'arrivée du participant</span>
        </span>
        <x-icon name="arrow-right" class="hidden sm:block w-8 h-8 shrink-0"/>
    </a>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4">
        @include('partials.stat', ['label' => 'Total participants', 'value' => $stats['total'], 'tone' => 'navy', 'icon' => 'users'])
        @include('partials.stat', ['label' => 'Présents', 'value' => $stats['present'], 'tone' => 'green', 'icon' => 'check'])
        @include('partials.stat', ['label' => 'Retards', 'value' => $stats['late'], 'tone' => 'orange', 'icon' => 'clock'])
        @include('partials.stat', ['label' => 'Absents', 'value' => $stats['absent'], 'tone' => 'red', 'icon' => 'x-circle',
            'hint' => now()->format('H:i') <= $absentFrom ? 'Non encore scannés' : null])
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
        <div class="card lg:col-span-2 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h2 class="font-bold">Dernières présences</h2>
                <a href="{{ route('attendances.index') }}" class="text-sm font-semibold text-odc hover:underline">Voir tout</a>
            </div>
            @if ($latest->isEmpty())
                <div class="px-5 py-12 text-center">
                    <p class="font-semibold">Aucune présence enregistrée aujourd'hui</p>
                    <p class="text-sm text-slate-500 mt-1">Les arrivées apparaîtront ici dès le premier scan.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Participant</th><th>Badge</th><th>Heure</th><th>Statut</th></tr></thead>
                        <tbody>
                        @foreach ($latest as $a)
                            <tr>
                                <td>
                                    <a href="{{ route('participants.show', $a->participant_id) }}" class="flex items-center gap-3 font-medium hover:text-odc">
                                        @include('partials.avatar', ['participant' => $a->participant, 'size' => 'w-8 h-8 text-xs'])
                                        {{ $a->participant->full_name }}
                                    </a>
                                </td>
                                <td class="text-slate-600 tabular-nums">{{ $a->badge?->number ?? $a->participant->code }}</td>
                                <td class="font-semibold tabular-nums">{{ $a->arrived_at->format('H:i') }}</td>
                                <td>@include('partials.status', ['status' => $a->status])</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card p-5 flex flex-col gap-5">
            <div>
                <p class="text-sm text-slate-500">Heure limite d'arrivée</p>
                <p class="text-3xl font-extrabold tabular-nums mt-1">{{ $cutoff }}</p>
                @can('admin')
                    <a href="{{ route('settings.index') }}" class="text-sm font-semibold text-odc hover:underline">Modifier</a>
                @endcan
            </div>
            <div>
                <div class="flex items-baseline justify-between">
                    <p class="text-sm text-slate-500">Taux de présence du jour</p>
                    <p class="font-bold tabular-nums">{{ $stats['rate'] }} %</p>
                </div>
                <div class="mt-2 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-ok rounded-full" style="width: {{ $stats['rate'] }}%"></div>
                </div>
                <p class="text-xs text-slate-500 mt-2">{{ $stats['scanned'] }} scannés sur {{ $stats['total'] }} participants</p>
            </div>
            @unless ($isTrainingDay)
                <p class="rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-600">Aujourd'hui n'est pas un jour de formation : aucune absence ne sera comptée.</p>
            @endunless
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        // Horloge alignée sur l'heure du serveur (affichage uniquement)
        const offset = {{ now()->getTimestampMs() }} - Date.now();
        const tz = @json(config('app.timezone'));
        const clock = document.querySelector('[data-live-clock]');
        const tick = () => {
            clock.textContent = new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit', timeZone: tz }).format(new Date(Date.now() + offset));
        };
        tick(); setInterval(tick, 10000);
    })();
</script>
@endpush
