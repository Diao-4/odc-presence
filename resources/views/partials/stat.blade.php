@php
    $tones = [
        'navy'  => ['bg-sky-50 text-sky-700', 'text-navy'],
        'green' => ['bg-ok-50 text-ok', 'text-ok'],
        'orange'=> ['bg-odc-50 text-odc-600', 'text-odc-600'],
        'red'   => ['bg-ko-50 text-ko', 'text-ko'],
    ];
    [$iconCls, $valueCls] = $tones[$tone ?? 'navy'];
@endphp
<div class="card p-4 sm:p-5">
    <div class="flex items-center gap-2.5">
        <span class="grid place-items-center w-9 h-9 rounded-xl {{ $iconCls }}"><x-icon :name="$icon" class="w-5 h-5"/></span>
        <span class="text-sm font-medium text-slate-600">{{ $label }}</span>
    </div>
    <p class="mt-3 text-3xl sm:text-4xl font-extrabold tabular-nums {{ $valueCls }}">{{ $value }}</p>
    @isset($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endisset
</div>
