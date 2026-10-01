@foreach (['success' => ['bg-ok-50 border-green-200 text-green-800', 'check'], 'error' => ['bg-ko-50 border-red-200 text-red-800', 'x-circle'], 'info' => ['bg-sky-50 border-sky-200 text-sky-800', 'info']] as $type => [$classes, $icon])
    @if (session($type))
        <div class="mb-5 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm {{ $classes }}" role="status">
            <x-icon :name="$icon" class="w-5 h-5 shrink-0"/>
            <p>{{ session($type) }}</p>
        </div>
    @endif
@endforeach
@if ($errors->any() && ! request()->routeIs('login'))
    <div class="mb-5 rounded-xl border border-red-200 bg-ko-50 px-4 py-3 text-sm text-red-800">
        Vérifiez les champs signalés ci-dessous.
    </div>
@endif
