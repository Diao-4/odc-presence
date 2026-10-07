@extends('layouts.app')
@section('title', 'Cohortes')

@section('content')
<x-page-header title="Cohortes" subtitle="Chaque cohorte a ses participants, ses agents et ses horaires">
    <a href="{{ route('cohorts.create') }}" class="btn btn-primary"><x-icon name="plus"/> Nouvelle cohorte</a>
</x-page-header>

<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
    @foreach ($cohorts as $c)
        @php $isCurrent = $current?->is($c); @endphp
        <article class="card p-5 flex flex-col {{ $c->is_active ? '' : 'opacity-70' }} {{ $isCurrent ? 'ring-2 ring-odc border-odc' : '' }}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-odc-600">{{ $c->program }}</p>
                    <h2 class="text-lg font-extrabold leading-tight break-words">{{ $c->name }}</h2>
                </div>
                @if ($isCurrent)
                    <span class="pill bg-odc text-white shrink-0">Affichée</span>
                @elseif (! $c->is_active)
                    <span class="pill bg-slate-100 text-slate-600 shrink-0">Archivée</span>
                @endif
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <div><dt class="text-slate-500 text-xs">Heure limite</dt><dd class="font-semibold tabular-nums">{{ $c->cutoff_time }}</dd></div>
                <div><dt class="text-slate-500 text-xs">Pointage jusqu'à</dt><dd class="font-semibold tabular-nums">{{ $c->absence_time ?: '—' }}</dd></div>
                <div class="col-span-2"><dt class="text-slate-500 text-xs">Jours de formation</dt><dd>{{ $c->trainingDaysLabel() }}</dd></div>
                <div class="col-span-2"><dt class="text-slate-500 text-xs">Période</dt>
                    <dd>
                        @if ($c->starts_on || $c->ends_on)
                            Du {{ $c->starts_on?->format('d/m/Y') ?? '…' }} au {{ $c->ends_on?->format('d/m/Y') ?? '…' }}
                        @else
                            <span class="text-slate-400">Non renseignée</span>
                        @endif
                    </dd>
                </div>
                <div><dt class="text-slate-500 text-xs">Participants actifs</dt><dd class="font-semibold tabular-nums">{{ $c->participants_count }}</dd></div>
                <div><dt class="text-slate-500 text-xs">Agents</dt><dd class="font-semibold tabular-nums">{{ $c->agents_count }}</dd></div>
            </dl>

            <div class="mt-5 pt-4 border-t border-slate-100 flex flex-wrap items-center gap-2">
                @unless ($isCurrent)
                    <form method="POST" action="{{ route('cohorts.select', $c) }}">@csrf
                        <input type="hidden" name="back" value="{{ route('dashboard') }}">
                        <button class="btn btn-dark btn-sm">Afficher</button>
                    </form>
                @endunless
                <a href="{{ route('users.create', ['cohort_id' => $c->id]) }}" class="btn btn-secondary btn-sm"><x-icon name="plus" class="w-4 h-4"/> Agent</a>
                <div class="ml-auto flex gap-0.5">
                    <a href="{{ route('cohorts.edit', $c) }}" class="btn-icon" title="Modifier" aria-label="Modifier {{ $c->label }}"><x-icon name="pencil"/></a>
                    <form method="POST" action="{{ route('cohorts.toggle', $c) }}">@csrf @method('PATCH')
                        <button class="btn-icon" title="{{ $c->is_active ? 'Archiver' : 'Réactiver' }}" aria-label="{{ $c->is_active ? 'Archiver' : 'Réactiver' }} {{ $c->label }}"><x-icon :name="$c->is_active ? 'archive' : 'refresh'"/></button>
                    </form>
                    @if ($c->participants_count === 0)
                        <form method="POST" action="{{ route('cohorts.destroy', $c) }}" onsubmit="return confirm('Supprimer la cohorte {{ addslashes($c->name) }} ?')">@csrf @method('DELETE')
                            <button class="btn-icon hover:text-ko" title="Supprimer" aria-label="Supprimer {{ $c->label }}"><x-icon name="trash"/></button>
                        </form>
                    @endif
                </div>
            </div>
        </article>
    @endforeach
</div>
@endsection
