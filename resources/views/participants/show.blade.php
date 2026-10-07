@extends('layouts.app')
@section('title', $participant->full_name)

@section('content')
<x-page-header title="Détail du participant" :back="route('participants.index')">
    @can('admin')
        <a href="{{ route('participants.edit', $participant) }}" class="btn btn-secondary"><x-icon name="pencil" class="w-4 h-4"/> Modifier</a>
        <a href="{{ route('participants.badge.download', $participant) }}" class="btn btn-primary"><x-icon name="download" class="w-4 h-4"/> Télécharger le badge</a>
    @endcan
</x-page-header>

<div class="grid lg:grid-cols-[minmax(0,1fr)_300px] gap-4 mb-4">
    <div class="card p-5 sm:p-6 flex flex-col sm:flex-row gap-5">
        @include('partials.avatar', ['participant' => $participant, 'size' => 'w-28 h-28 text-3xl'])
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-2xl font-extrabold tracking-tight">{{ $participant->full_name }}</h2>
                @include('partials.status', ['status' => $participant->is_active ? 'active' : 'inactive'])
            </div>
            <dl class="mt-4 grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div><dt class="text-slate-500">Nom</dt><dd class="font-medium">{{ $participant->last_name }}</dd></div>
                <div><dt class="text-slate-500">Prénom</dt><dd class="font-medium">{{ $participant->first_name }}</dd></div>
                <div><dt class="text-slate-500">Téléphone</dt><dd class="font-medium tabular-nums">{{ $participant->phone ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Email</dt><dd class="font-medium break-all">{{ $participant->email ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Cohorte</dt><dd class="font-medium">{{ $participant->cohort?->label ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Organisation</dt><dd class="font-medium">{{ $participant->organization ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Fonction</dt><dd class="font-medium">{{ $participant->function ?: '—' }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="card p-5">
        <p class="text-sm text-slate-500">Badge</p>
        <p class="text-2xl font-extrabold tabular-nums">{{ $participant->code ?? '—' }}</p>
        <div class="mt-2">
            @if ($badge)
                @include('partials.status', ['status' => $badge->status])
                <p class="text-xs text-slate-500 mt-2">Émis le {{ $badge->issued_at?->format('d/m/Y') }} · version {{ $badge->version }}</p>
                @can('admin')
                    <a href="{{ route('badges.show', $badge) }}" class="btn btn-secondary btn-sm mt-3">Gérer le badge</a>
                @endcan
            @else
                <p class="text-sm text-slate-500">Aucun badge émis.</p>
                @can('admin')
                    <form method="POST" action="{{ route('participants.badge.generate', $participant) }}" class="mt-3">@csrf
                        <button class="btn btn-primary btn-sm">Générer le badge</button>
                    </form>
                @endcan
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
    @include('partials.stat', ['label' => 'Présences', 'value' => $summary['present'], 'tone' => 'green', 'icon' => 'check'])
    @include('partials.stat', ['label' => 'Retards', 'value' => $summary['late'], 'tone' => 'orange', 'icon' => 'clock', 'hint' => $summary['late_total'].' min cumulées'])
    @include('partials.stat', ['label' => 'Absences', 'value' => $summary['absent'], 'tone' => 'red', 'icon' => 'x-circle'])
    @include('partials.stat', ['label' => 'Taux de présence', 'value' => $summary['rate'].' %', 'tone' => 'navy', 'icon' => 'chart'])
</div>

<div class="card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-bold">Historique des présences</h2>
        <span class="text-sm text-slate-500">Arrivée moyenne : <strong class="text-navy tabular-nums">{{ $summary['avg_arrival'] }}</strong></span>
    </div>
    @if ($history->isEmpty())
        <p class="px-5 py-10 text-center text-sm text-slate-500">Aucun jour de formation depuis l'inscription.</p>
    @else
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Date</th><th>Arrivée</th><th>Départ</th><th>Statut</th><th>Retard</th><th>Justification</th></tr></thead>
                <tbody>
                @foreach ($history as $r)
                    <tr>
                        <td class="tabular-nums">{{ $r['date']->format('d/m/Y') }} <span class="text-slate-400 text-xs">{{ $r['date']->translatedFormat('D') }}</span></td>
                        <td class="font-semibold tabular-nums">{{ $r['arrived'] ?? '—' }}</td>
                        <td class="tabular-nums">{{ $r['departed'] ?? '—' }}</td>
                        <td>@include('partials.status', ['status' => $r['status'], 'justified' => $r['justified']])</td>
                        <td class="tabular-nums {{ $r['late_minutes'] ? 'text-odc-600 font-semibold' : 'text-slate-500' }}">{{ $r['status'] === 'absent' ? '—' : $r['late_minutes'].' min' }}</td>
                        <td>@include('partials.justification-cell')</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $history->links() }}</div>
    @endif
</div>
@endsection
