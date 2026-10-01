{{-- Filtres communs Présences / Rapports. $filters, $participants, $periods --}}
<form method="GET" class="card p-3 sm:p-4 mb-4" id="filters">
    <div class="flex flex-wrap gap-1.5 mb-3" role="radiogroup" aria-label="Période">
        @foreach ($periods as $key => $label)
            <label class="cursor-pointer">
                <input type="radio" name="period" value="{{ $key }}" class="peer sr-only" @checked($filters['period'] === $key) onchange="this.form.requestSubmit()">
                <span class="inline-block rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 peer-checked:bg-navy peer-checked:text-white peer-checked:border-navy peer-focus-visible:ring-2 peer-focus-visible:ring-odc">{{ $label }}</span>
            </label>
        @endforeach
    </div>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[auto_auto_1fr_auto_1fr_auto]">
        <input type="date" name="from" value="{{ $filters['from']->toDateString() }}" class="input" aria-label="Date de début" data-custom>
        <input type="date" name="to" value="{{ $filters['to']->toDateString() }}" class="input" aria-label="Date de fin" data-custom>
        <select name="participant_id" class="input" aria-label="Participant">
            <option value="">Tous les participants</option>
            @foreach ($participants as $p)
                <option value="{{ $p->id }}" @selected($filters['participant_id'] === $p->id)>{{ $p->first_name }} {{ $p->last_name }} ({{ $p->code }})</option>
            @endforeach
        </select>
        <select name="status" class="input" aria-label="Statut">
            <option value="">Tous les statuts</option>
            <option value="present" @selected($filters['status'] === 'present')>Présent</option>
            <option value="late" @selected($filters['status'] === 'late')>Retard</option>
            <option value="absent" @selected($filters['status'] === 'absent')>Absent</option>
        </select>
        <label class="relative">
            <span class="sr-only">Rechercher</span>
            <x-icon name="search" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Rechercher un participant…" class="input pl-10">
        </label>
        <button class="btn btn-dark">{{ $submitLabel ?? 'Filtrer' }}</button>
    </div>
</form>
<script>
    // Modifier une date bascule automatiquement en « période personnalisée »
    document.querySelectorAll('#filters [data-custom]').forEach(i => i.addEventListener('change', () => {
        const custom = document.querySelector('#filters input[name=period][value=custom]');
        if (custom) custom.checked = true;
    }));
</script>
