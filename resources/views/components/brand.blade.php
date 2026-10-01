@props(['dark' => true, 'size' => 'md'])
@php $box = $size === 'lg' ? 'w-14 h-14 text-[11px]' : 'w-10 h-10 text-[9px]'; @endphp
<div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    @if (file_exists(public_path('images/odc-logo.png')))
        <img src="{{ asset('images/odc-logo.png') }}" alt="Orange Digital Center" class="{{ $box }} object-contain">
    @else
        <span class="{{ $box }} bg-odc text-white font-bold flex items-end p-1.5 leading-none shrink-0">ODC</span>
    @endif
    <span class="leading-tight {{ $dark ? 'text-white' : 'text-navy' }}">
        <span class="block font-bold {{ $size === 'lg' ? 'text-xl' : 'text-[15px]' }}">Orange</span>
        <span class="block font-bold {{ $size === 'lg' ? 'text-xl' : 'text-[15px]' }}">Digital Center</span>
    </span>
</div>
