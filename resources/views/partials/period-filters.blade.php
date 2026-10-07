{{-- Filtres communs Présences / Rapports. $filters, $participants, $periods --}}
<form method="GET" class="card p-3 sm:p-4 mb-4" id="filters">
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Période</p>
    <div class="flex flex-wrap gap-1.5 mb-4" role="radiogroup" aria-label="Période">
        @foreach ($periods as $key => $label)
            <label class="cursor-pointer">
                <input type="radio" name="period" value="{{ $key }}" class="peer sr-only" @checked($filters['period'] === $key) onchange="this.form.requestSubmit()">
                <span class="inline-block rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 peer-checked:bg-navy peer-checked:text-white peer-checked:border-navy peer-focus-visible:ring-2 peer-focus-visible:ring-odc">{{ $label }}</span>
            </label>
        @endforeach
    </div>

    {{-- Intervalle de dates : « du … au … » --}}
    <fieldset class="rounded-xl bg-slate-50 border border-slate-200 p-3 mb-3">
        <legend class="sr-only">Filtrer d'une date à une autre</legend>
        <div class="grid grid-cols-2 gap-3 sm:flex sm:items-end">
            <div class="sm:w-48">
                <label for="filter-from" class="label">Du</label>
                <input id="filter-from" type="date" name="from" value="{{ $filters['from']->toDateString() }}" max="{{ today()->toDateString() }}" class="input" data-custom>
            </div>
            <div class="sm:w-48">
                <label for="filter-to" class="label">Au</label>
                <input id="filter-to" type="date" name="to" value="{{ $filters['to']->toDateString() }}" min="{{ $filters['from']->toDateString() }}" class="input" data-custom>
            </div>
            <p class="col-span-2 sm:flex-1 text-sm text-slate-600 sm:pb-2.5" aria-live="polite">
                <x-icon name="calendar" class="w-4 h-4 inline -mt-0.5 text-odc"/>
                @php $days = $filters['from']->diffInDays($filters['to']) + 1; @endphp
                @if ($filters['from']->isSameDay($filters['to']))
                    Le <strong class="text-navy">{{ $filters['from']->translatedFormat('l j F Y') }}</strong>
                @else
                    Du <strong class="text-navy">{{ $filters['from']->translatedFormat('j F Y') }}</strong>
                    au <strong class="text-navy">{{ $filters['to']->translatedFormat('j F Y') }}</strong>
                    <span class="text-slate-500">({{ (int) $days }} jours)</span>
                @endif
            </p>
        </div>
    </fieldset>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_auto_1fr_auto]">
        <div>
            <label for="filter-participant" class="sr-only">Participant</label>
            <select id="filter-participant" name="participant_id" class="input">
                <option value="">Tous les participants</option>
                @foreach ($participants as $p)
                    <option value="{{ $p->id }}" @selected($filters['participant_id'] === $p->id)>{{ $p->first_name }} {{ $p->last_name }} ({{ $p->code }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-status" class="sr-only">Statut</label>
            <select id="filter-status" name="status" class="input">
                <option value="">Tous les statuts</option>
                <option value="present" @selected($filters['status'] === 'present')>Présent</option>
                <option value="late" @selected($filters['status'] === 'late')>Retard</option>
                <option value="absent" @selected($filters['status'] === 'absent')>Absent</option>
            </select>
        </div>
        <label class="relative">
            <span class="sr-only">Rechercher</span>
            <x-icon name="search" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Rechercher un participant…" class="input pl-10">
        </label>
        <div class="flex gap-2">
            <button class="btn btn-dark flex-1">{{ $submitLabel ?? 'Filtrer' }}</button>
            <a href="{{ url()->current() }}" class="btn btn-secondary" title="Réinitialiser les filtres">Effacer</a>
        </div>
    </div>
</form>
<script>
    (() => {
        const form = document.getElementById('filters');
        const from = form.querySelector('[name=from]'), to = form.querySelector('[name=to]');
        // Modifier une date bascule en « période personnalisée » ; la date de fin ne peut précéder la date de début.
        form.querySelectorAll('[data-custom]').forEach(i => i.addEventListener('change', () => {
            const custom = form.querySelector('input[name=period][value=custom]');
            if (custom) custom.checked = true;
            to.min = from.value;
            if (from.value && to.value && to.value < from.value) to.value = from.value;
        }));
    })();
</script>
