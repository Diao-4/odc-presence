@extends('layouts.app')
@section('title', 'Gestion des badges')

@section('content')
<x-page-header title="Gestion des badges" :subtitle="$activeCount.' badge(s) actif(s) prêt(s) à imprimer'"/>

<form method="GET" class="card p-3 sm:p-4 mb-3 flex flex-wrap items-center gap-3">
    <input type="hidden" name="q" value="{{ request('q') }}"><input type="hidden" name="status" value="{{ $status }}">
    <label for="size" class="text-sm font-semibold">Taille des badges téléchargés</label>
    <select id="size" name="size" class="input sm:w-64" onchange="this.form.submit()">
        @foreach ($sizes as $k => [$l])
            <option value="{{ $k }}" @selected($size === $k)>{{ $l }}</option>
        @endforeach
    </select>
    <noscript><button class="btn btn-secondary btn-sm">Appliquer</button></noscript>
</form>

<div class="grid sm:grid-cols-3 gap-3 mb-6">
    <a href="{{ route('badges.zip', ['size' => $size]) }}" class="rounded-2xl bg-odc hover:bg-odc-600 text-white p-5 flex items-start gap-4 transition-colors" data-loading="Préparation de l'archive…">
        <span class="w-11 h-11 rounded-xl bg-white/20 grid place-items-center shrink-0"><x-icon name="archive" class="w-6 h-6"/></span>
        <span><span class="block font-bold text-lg leading-tight">Télécharger tous les badges</span><span class="block text-sm text-white/85 mt-1">Archive ZIP, un PDF par participant · {{ $sizes[app(\App\Services\BadgeService::class)->singleSize($size)][0] }}</span></span>
    </a>
    <a href="{{ route('badges.pdf', ['size' => $size]) }}" class="card hover:border-odc p-5 flex items-start gap-4 transition-colors" data-loading="Génération du PDF…">
        <span class="w-11 h-11 rounded-xl bg-ko-50 text-ko grid place-items-center shrink-0"><x-icon name="doc" class="w-6 h-6"/></span>
        <span><span class="block font-bold text-lg leading-tight">Télécharger tous les badges en PDF</span><span class="block text-sm text-slate-500 mt-1">Un seul fichier · {{ isset(\App\Services\BadgeService::SHEETS[$size]) ? array_product(\App\Services\BadgeService::SHEETS[$size]).' badges par feuille A4, prêt à imprimer' : 'un badge par page, '.$sizes[$size][0] }}</span></span>
    </a>
    <a href="{{ route('badges.print') }}" target="_blank" class="card hover:border-odc p-5 flex items-start gap-4 transition-colors">
        <span class="w-11 h-11 rounded-xl bg-sky-50 text-sky-700 grid place-items-center shrink-0"><x-icon name="printer" class="w-6 h-6"/></span>
        <span><span class="block font-bold text-lg leading-tight">Imprimer tous les badges</span><span class="block text-sm text-slate-500 mt-1">9 badges par feuille A4</span></span>
    </a>
</div>

<form method="GET" class="card p-3 sm:p-4 mb-4 grid gap-3 sm:grid-cols-[1fr_auto_auto]">
    <label class="relative">
        <span class="sr-only">Rechercher</span>
        <x-icon name="search" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Participant ou numéro de badge…" class="input pl-10">
    </label>
    <input type="hidden" name="size" value="{{ $size }}">
    <select name="status" class="input sm:w-44" aria-label="Statut">
        @foreach (['active' => 'Actifs', 'disabled' => 'Désactivés', 'replaced' => 'Remplacés', 'all' => 'Tous'] as $k => $l)
            <option value="{{ $k }}" @selected($status === $k)>{{ $l }}</option>
        @endforeach
    </select>
    <button class="btn btn-dark">Filtrer</button>
</form>

<div class="card overflow-hidden">
    @if ($badges->isEmpty())
        <p class="px-6 py-12 text-center text-sm text-slate-500">Aucun badge pour ces critères.</p>
    @else
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Participant</th><th>Numéro du badge</th><th>QR Code</th><th>Statut</th><th>Date de création</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                @foreach ($badges as $b)
                    <tr>
                        <td>
                            <a href="{{ route('participants.show', $b->participant_id) }}" class="flex items-center gap-3 font-semibold hover:text-odc">
                                @include('partials.avatar', ['participant' => $b->participant, 'size' => 'w-9 h-9 text-xs'])
                                {{ $b->participant->full_name }}
                            </a>
                        </td>
                        <td class="tabular-nums font-medium">{{ $b->number }} <span class="text-xs text-slate-400">v{{ $b->version }}</span></td>
                        <td><img src="{{ $qr($b) }}" alt="QR Code {{ $b->number }}" class="w-12 h-12 {{ $b->isActive() ? '' : 'opacity-30' }}"></td>
                        <td>@include('partials.status', ['status' => $b->status])</td>
                        <td class="tabular-nums text-slate-600">{{ $b->issued_at?->format('d/m/Y') }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-0.5">
                                <a href="{{ route('badges.show', $b) }}" class="btn-icon" title="Voir"><x-icon name="eye"/></a>
                                <a href="{{ route('badges.download', ['badge' => $b, 'size' => $size]) }}" class="btn-icon" title="Télécharger ({{ $sizes[$size][0] }})"><x-icon name="download"/></a>
                                <a href="{{ route('badges.print-one', $b) }}" target="_blank" class="btn-icon" title="Imprimer"><x-icon name="printer"/></a>
                                @if ($b->status !== 'replaced')
                                    <form method="POST" action="{{ route('badges.replace', $b) }}" onsubmit="return confirm('Émettre un nouveau badge ? L\'ancien QR Code sera refusé.')">@csrf
                                        <button class="btn-icon" title="Remplacer"><x-icon name="refresh"/></button>
                                    </form>
                                    <form method="POST" action="{{ route('badges.toggle', $b) }}" onsubmit="return confirm('{{ $b->isActive() ? 'Désactiver' : 'Réactiver' }} ce badge ?')">@csrf @method('PATCH')
                                        <button class="btn-icon {{ $b->isActive() ? 'hover:text-ko' : 'hover:text-ok' }}" title="{{ $b->isActive() ? 'Désactiver' : 'Réactiver' }}">
                                            <x-icon :name="$b->isActive() ? 'ban' : 'check'"/>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $badges->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Indique à l'administrateur que la génération est en cours (ZIP / PDF de tous les badges)
    document.querySelectorAll('[data-loading]').forEach(a => a.addEventListener('click', () => {
        const span = a.querySelector('span span:last-child');
        if (span) span.textContent = a.dataset.loading;
        a.style.opacity = '.75';
        setTimeout(() => { a.style.opacity = ''; }, 8000);
    }));
</script>
@endpush
