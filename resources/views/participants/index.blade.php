@extends('layouts.app')
@section('title', 'Participants')

@section('content')
<x-page-header title="Participants" :subtitle="$participants->total().' participant(s)'.($cohort ? ' · '.$cohort->label : '')">
    @can('admin')
        <a href="{{ route('participants.create') }}" class="btn btn-primary"><x-icon name="plus"/> Nouveau participant</a>
    @endcan
</x-page-header>

<form method="GET" class="card p-3 sm:p-4 mb-4 grid gap-3 sm:grid-cols-[1fr_auto_auto_auto]">
    <label class="relative">
        <span class="sr-only">Rechercher</span>
        <x-icon name="search" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom, badge, téléphone, email…" class="input pl-10">
    </label>
    <select name="status" class="input sm:w-40" aria-label="Statut">
        <option value="all" @selected($status === 'all')>Tous les statuts</option>
        <option value="active" @selected($status === 'active')>Actifs</option>
        <option value="inactive" @selected($status === 'inactive')>Désactivés</option>
    </select>
    <select name="organization" class="input sm:w-52" aria-label="Organisation">
        <option value="">Toutes les organisations</option>
        @foreach ($organizations as $org)
            <option value="{{ $org }}" @selected(request('organization') === $org)>{{ $org }}</option>
        @endforeach
    </select>
    <button class="btn btn-dark">Filtrer</button>
</form>

@if ($participants->isEmpty())
    <div class="card px-6 py-14 text-center">
        <p class="font-semibold">Aucun participant trouvé</p>
        <p class="text-sm text-slate-500 mt-1">Modifiez la recherche @can('admin') ou ajoutez un participant @endcan.</p>
    </div>
@else
    {{-- Tableau (écrans larges) --}}
    <div class="card overflow-hidden hidden md:block">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                <tr><th>N°</th><th>Photo</th><th>Nom et prénom</th><th>Téléphone</th><th>Email</th><th>Organisation</th><th>Fonction</th><th>Badge</th><th>Statut</th><th class="text-right">Actions</th></tr>
                </thead>
                <tbody>
                @foreach ($participants as $p)
                    <tr class="{{ $p->is_active ? '' : 'opacity-60' }}">
                        <td class="text-slate-500 tabular-nums">{{ $participants->firstItem() + $loop->index }}</td>
                        <td>@include('partials.avatar', ['participant' => $p, 'size' => 'w-9 h-9 text-xs'])</td>
                        <td class="font-semibold"><a href="{{ route('participants.show', $p) }}" class="hover:text-odc">{{ $p->full_name }}</a></td>
                        <td class="tabular-nums text-slate-600">{{ $p->phone ?: '—' }}</td>
                        <td class="text-slate-600 max-w-48 truncate">{{ $p->email ?: '—' }}</td>
                        <td class="text-slate-600">{{ $p->organization ?: '—' }}</td>
                        <td class="text-slate-600">{{ $p->function ?: '—' }}</td>
                        <td class="tabular-nums font-medium">{{ $p->code ?? '—' }}</td>
                        <td>@include('partials.status', ['status' => $p->is_active ? 'active' : 'inactive'])</td>
                        <td>
                            <div class="flex items-center justify-end gap-0.5">
                                <a href="{{ route('participants.show', $p) }}" class="btn-icon" title="Voir"><x-icon name="eye"/></a>
                                @can('admin')
                                    <a href="{{ route('participants.edit', $p) }}" class="btn-icon" title="Modifier"><x-icon name="pencil"/></a>
                                    @if ($p->activeBadge)
                                        <a href="{{ route('badges.show', $p->activeBadge) }}" class="btn-icon" title="Voir le badge"><x-icon name="badge"/></a>
                                    @else
                                        <form method="POST" action="{{ route('participants.badge.generate', $p) }}">@csrf
                                            <button class="btn-icon text-odc" title="Générer badge"><x-icon name="badge"/></button>
                                        </form>
                                    @endif
                                    <a href="{{ route('participants.badge.download', $p) }}" class="btn-icon" title="Télécharger badge"><x-icon name="download"/></a>
                                    <form method="POST" action="{{ route('participants.toggle', $p) }}"
                                          onsubmit="return confirm('{{ $p->is_active ? 'Désactiver' : 'Réactiver' }} {{ addslashes($p->full_name) }} ?')">
                                        @csrf @method('PATCH')
                                        <button class="btn-icon {{ $p->is_active ? 'hover:text-ko' : 'hover:text-ok' }}" title="{{ $p->is_active ? 'Désactiver' : 'Réactiver' }}">
                                            <x-icon :name="$p->is_active ? 'ban' : 'check'"/>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Cartes (mobile) --}}
    <div class="md:hidden space-y-2">
        @foreach ($participants as $p)
            <a href="{{ route('participants.show', $p) }}" class="card p-4 flex items-center gap-3 {{ $p->is_active ? '' : 'opacity-60' }}">
                @include('partials.avatar', ['participant' => $p, 'size' => 'w-12 h-12 text-sm'])
                <span class="flex-1 min-w-0">
                    <span class="block font-semibold truncate">{{ $p->full_name }}</span>
                    <span class="block text-sm text-slate-500 truncate">{{ $p->code }} · {{ $p->function ?: 'Participant' }}</span>
                </span>
                @include('partials.status', ['status' => $p->is_active ? 'active' : 'inactive'])
            </a>
        @endforeach
    </div>

    <div class="mt-4">{{ $participants->links() }}</div>
@endif
@endsection
