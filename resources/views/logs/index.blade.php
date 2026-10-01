@extends('layouts.app')
@section('title', 'Journal des opérations')

@section('content')
<x-page-header title="Journal des opérations" subtitle="Connexions, créations, exports, saisies manuelles et modifications de paramètres" :back="route('settings.index')"/>

<form method="GET" class="card p-3 mb-4 flex gap-2">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher dans le journal…" class="input">
    <button class="btn btn-dark">Rechercher</button>
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Détail</th><th>Adresse IP</th></tr></thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td class="tabular-nums whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->user?->name ?? '—' }}</td>
                    <td><code class="text-xs bg-slate-100 rounded px-1.5 py-0.5">{{ $log->action }}</code></td>
                    <td class="text-slate-700">{{ $log->description }}</td>
                    <td class="tabular-nums text-slate-500 text-xs">{{ $log->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-slate-500 py-10">Aucune opération enregistrée.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3">{{ $logs->links() }}</div>
</div>
@endsection
