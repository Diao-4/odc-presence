@extends('layouts.app')
@section('title', 'Journal des opérations')

@section('content')
<x-page-header title="Journal des opérations" subtitle="Connexions, créations, exports, saisies manuelles et modifications de paramètres" :back="route('settings.index')"/>

<form method="GET" class="card p-3 sm:p-4 mb-4 grid gap-3 grid-cols-2 lg:grid-cols-[10rem_10rem_1fr_auto] lg:items-end">
    <div>
        <label for="log-from" class="label">Du</label>
        <input id="log-from" type="date" name="from" value="{{ $from?->toDateString() }}" class="input">
    </div>
    <div>
        <label for="log-to" class="label">Au</label>
        <input id="log-to" type="date" name="to" value="{{ $to?->toDateString() }}" class="input">
    </div>
    <div class="col-span-2 lg:col-span-1">
        <label for="log-q" class="label">Recherche</label>
        <input id="log-q" type="search" name="q" value="{{ request('q') }}" placeholder="Action, nom, badge…" class="input">
    </div>
    <div class="col-span-2 lg:col-span-1 flex gap-2">
        <button class="btn btn-dark flex-1">Filtrer</button>
        <a href="{{ route('logs.index') }}" class="btn btn-secondary">Effacer</a>
    </div>
</form>

@if ($logs->isEmpty())
    <div class="card px-6 py-12 text-center text-sm text-slate-500">Aucune opération pour ces critères.</div>
@else
    {{-- Tableau (écrans larges) --}}
    <div class="card overflow-hidden hidden md:block">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Détail</th><th>Adresse IP</th></tr></thead>
                <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td class="tabular-nums whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $log->user?->name ?? '—' }}</td>
                        <td><code class="text-xs bg-slate-100 rounded px-1.5 py-0.5">{{ $log->action }}</code></td>
                        <td class="text-slate-700">{{ $log->description }}</td>
                        <td class="tabular-nums text-slate-500 text-xs">{{ $log->ip_address }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Cartes (mobile) --}}
    <div class="md:hidden space-y-2">
        @foreach ($logs as $log)
            <div class="card p-4">
                <div class="flex items-center justify-between gap-2 text-xs text-slate-500">
                    <span class="tabular-nums">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                    <code class="bg-slate-100 rounded px-1.5 py-0.5">{{ $log->action }}</code>
                </div>
                <p class="mt-1.5 text-sm text-navy break-words">{{ $log->description }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $log->user?->name ?? '—' }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
@endif
@endsection
