@extends('layouts.app')
@section('title', 'Présences')

@section('content')
<x-page-header title="Présences"
    :subtitle="$filters['from']->isSameDay($filters['to']) ? $filters['from']->translatedFormat('l j F Y') : 'Du '.$filters['from']->format('d/m/Y').' au '.$filters['to']->format('d/m/Y')">
    @include('partials.export-buttons')
</x-page-header>

@include('partials.period-filters', [
    'periods' => ['today' => "Aujourd'hui", 'yesterday' => 'Hier', 'week' => 'Cette semaine', 'month' => 'Ce mois', 'custom' => 'Date personnalisée'],
])

<div class="grid grid-cols-3 gap-3 mb-4">
    <div class="card px-4 py-3"><p class="text-xs text-slate-500">Présents</p><p class="text-2xl font-extrabold text-ok tabular-nums">{{ $summary['present'] }}</p></div>
    <div class="card px-4 py-3"><p class="text-xs text-slate-500">Retards</p><p class="text-2xl font-extrabold text-odc-600 tabular-nums">{{ $summary['late'] }}</p>@if ($summary['late_justified'])<p class="text-xs text-sky-800">dont {{ $summary['late_justified'] }} justifié(s)</p>@endif</div>
    <div class="card px-4 py-3"><p class="text-xs text-slate-500">Absents</p><p class="text-2xl font-extrabold text-ko tabular-nums">{{ $summary['absent'] }}</p>@if ($summary['absent_justified'])<p class="text-xs text-sky-800">dont {{ $summary['absent_justified'] }} justifiée(s)</p>@endif</div>
</div>

<div class="card overflow-hidden">
    @if ($rows->isEmpty())
        <div class="px-6 py-12 text-center">
            <p class="font-semibold">Aucune présence pour ces critères</p>
            <p class="text-sm text-slate-500 mt-1">Les absences d'aujourd'hui apparaissent après l'heure limite.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Date</th><th>Participant</th><th>Badge</th><th>Heure</th><th>Statut</th><th>Retard</th><th>Justification</th></tr></thead>
                <tbody>
                @foreach ($rows as $r)
                    <tr>
                        <td class="tabular-nums whitespace-nowrap">{{ $r['date']->format('d/m/Y') }}</td>
                        <td class="font-medium"><a href="{{ route('participants.show', $r['participant_id']) }}" class="hover:text-odc">{{ $r['name'] }}</a></td>
                        <td class="tabular-nums text-slate-600">{{ $r['badge'] }}</td>
                        <td class="tabular-nums font-semibold whitespace-nowrap">{{ $r['arrived'] ?? '—' }}@if ($r['departed'])<span class="font-normal text-slate-500"> → {{ $r['departed'] }}</span>@endif</td>
                        <td>@include('partials.status', ['status' => $r['status'], 'justified' => $r['justified']])</td>
                        <td class="tabular-nums {{ $r['late_minutes'] ? 'text-odc-600 font-semibold' : 'text-slate-500' }}">{{ $r['status'] === 'absent' ? '—' : $r['late_minutes'].' min' }}</td>
                        <td>@include('partials.justification-cell')</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $rows->links() }}</div>
    @endif
</div>
@endsection
