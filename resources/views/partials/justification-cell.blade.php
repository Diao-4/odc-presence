{{-- Colonne « Justification » d'une ligne de présence ($r) --}}
@if ($r['justified'])
    <div class="max-w-xs">
        <p class="text-sm text-slate-700 truncate" title="{{ $r['justification']->reason }}">{{ $r['justification']->reason }}</p>
        <div class="flex items-center gap-3 mt-0.5 text-xs">
            @can('admin')
                @if ($r['justification']->document_path)
                    <a href="{{ route('justifications.document', $r['justification']) }}" class="inline-flex items-center gap-1 font-semibold text-odc hover:underline"><x-icon name="doc" class="w-3.5 h-3.5"/> Pièce jointe</a>
                @endif
                <a href="{{ route('justifications.create', ['participant_id' => $r['participant_id'], 'date' => $r['date_key'], 'back' => request()->fullUrl()]) }}" class="text-slate-500 hover:text-navy">Modifier</a>
            @endcan
        </div>
    </div>
@elseif (in_array($r['status'], ['late', 'absent'], true))
    @can('admin')
        <a href="{{ route('justifications.create', ['participant_id' => $r['participant_id'], 'date' => $r['date_key'], 'back' => request()->fullUrl()]) }}" class="btn btn-secondary btn-sm whitespace-nowrap">Justifier</a>
    @else
        <span class="text-slate-400">—</span>
    @endcan
@else
    <span class="text-slate-400">—</span>
@endif
