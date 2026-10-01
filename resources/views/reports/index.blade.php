@extends('layouts.app')
@section('title', 'Rapports')

@section('content')
<x-page-header title="Rapports" :subtitle="'Du '.$filters['from']->format('d/m/Y').' au '.$filters['to']->format('d/m/Y')">
    @include('partials.export-buttons')
</x-page-header>

@include('partials.period-filters', [
    'periods' => ['week' => 'Cette semaine', 'month' => 'Ce mois', 'custom' => 'Période personnalisée'],
    'submitLabel' => 'Générer',
])

<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-4">
    @include('partials.stat', ['label' => 'Taux de présence', 'value' => $summary['rate'].' %', 'tone' => 'navy', 'icon' => 'chart'])
    @include('partials.stat', ['label' => 'Présents', 'value' => $summary['present'], 'tone' => 'green', 'icon' => 'check'])
    @include('partials.stat', ['label' => 'Retards', 'value' => $summary['late'], 'tone' => 'orange', 'icon' => 'clock'])
    @include('partials.stat', ['label' => 'Absents', 'value' => $summary['absent'], 'tone' => 'red', 'icon' => 'x-circle'])
    @include('partials.stat', ['label' => 'Arrivée moyenne', 'value' => $summary['avg_arrival'], 'tone' => 'navy', 'icon' => 'clock'])
    @include('partials.stat', ['label' => 'Retard cumulé', 'value' => $summary['late_total'].' min', 'tone' => 'orange', 'icon' => 'warning'])
</div>

<div class="card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
        <h2 class="font-bold">Synthèse par participant</h2>
        <span class="text-sm text-slate-500">{{ $perParticipant->count() }} participant(s)</span>
    </div>
    @if ($perParticipant->isEmpty())
        <p class="px-6 py-12 text-center text-sm text-slate-500">Aucune donnée pour cette période.</p>
    @else
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Participant</th><th>Badge</th><th class="text-right">Présences</th><th class="text-right">Retards</th><th class="text-right">Absences</th><th class="text-right">Retard cumulé</th><th class="w-48">Taux</th></tr></thead>
                <tbody>
                @foreach ($perParticipant as $p)
                    <tr>
                        <td class="font-medium"><a href="{{ route('participants.show', $p['participant_id']) }}" class="hover:text-odc">{{ $p['name'] }}</a></td>
                        <td class="tabular-nums text-slate-600">{{ $p['badge'] }}</td>
                        <td class="text-right tabular-nums">{{ $p['present'] }}</td>
                        <td class="text-right tabular-nums {{ $p['late'] ? 'text-odc-600 font-semibold' : '' }}">{{ $p['late'] }}</td>
                        <td class="text-right tabular-nums {{ $p['absent'] ? 'text-ko font-semibold' : '' }}">{{ $p['absent'] }}</td>
                        <td class="text-right tabular-nums">{{ $p['late_total'] }} min</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden"><div class="h-full rounded-full {{ $p['rate'] >= 80 ? 'bg-ok' : ($p['rate'] >= 60 ? 'bg-odc' : 'bg-ko') }}" style="width: {{ $p['rate'] }}%"></div></div>
                                <span class="text-sm font-semibold tabular-nums w-10 text-right">{{ $p['rate'] }}%</span>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
