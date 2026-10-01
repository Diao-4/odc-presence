@props(['title', 'subtitle' => null, 'back' => null])
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-6">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-navy mb-2">
                <x-icon name="arrow-left" class="w-4 h-4"/> Retour
            </a>
        @endif
        <h1 class="text-2xl sm:text-[1.75rem] font-extrabold tracking-tight text-navy">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>@endif
    </div>
    @if (trim($slot))
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
