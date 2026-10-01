@php
    $map = [
        'present'  => ['Présent', 'bg-ok-50 text-ok ring-1 ring-inset ring-green-200'],
        'late'     => ['Retard', 'bg-odc-50 text-odc-600 ring-1 ring-inset ring-orange-200'],
        'absent'   => ['Absent', 'bg-ko-50 text-ko ring-1 ring-inset ring-red-200'],
        'late_justified'   => ['Retard justifié', 'bg-sky-50 text-sky-800 ring-1 ring-inset ring-sky-200'],
        'absent_justified' => ['Absence justifiée', 'bg-sky-50 text-sky-800 ring-1 ring-inset ring-sky-200'],
        'active'   => ['Actif', 'bg-ok-50 text-ok ring-1 ring-inset ring-green-200'],
        'disabled' => ['Désactivé', 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200'],
        'replaced' => ['Remplacé', 'bg-slate-100 text-slate-500 ring-1 ring-inset ring-slate-200'],
        'inactive' => ['Inactif', 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200'],
    ];
    $key = ($justified ?? false) && isset($map[$status.'_justified']) ? $status.'_justified' : $status;
    [$label, $classes] = $map[$key] ?? [$status, 'bg-slate-100 text-slate-600'];
@endphp
<span class="pill {{ $classes }}">{{ $label }}</span>
