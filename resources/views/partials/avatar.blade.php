@php $size = $size ?? 'w-10 h-10 text-sm'; @endphp
@if ($participant->photo_url)
    <img src="{{ $participant->photo_url }}" alt="" class="{{ $size }} rounded-full object-cover bg-slate-100 shrink-0">
@else
    <span class="{{ $size }} rounded-full bg-navy text-white font-semibold grid place-items-center shrink-0">{{ $participant->initials }}</span>
@endif
