@php
    $field = fn ($name) => 'input'.($errors->has($name) ? ' input-error' : '');
@endphp
<div class="card p-5 sm:p-6">
    <div class="grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="cohort_id" class="label">Cohorte <span class="text-ko">*</span></label>
            <select id="cohort_id" name="cohort_id" required class="{{ $field('cohort_id') }}">
                @foreach ($cohorts as $c)
                    <option value="{{ $c->id }}" @selected((int) old('cohort_id', $participant->cohort_id) === $c->id)>{{ $c->label }}{{ $c->is_active ? '' : ' (archivée)' }}</option>
                @endforeach
            </select>
            @error('cohort_id')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="last_name" class="label">Nom <span class="text-ko">*</span></label>
            <input id="last_name" name="last_name" value="{{ old('last_name', $participant->last_name) }}" required maxlength="100" class="{{ $field('last_name') }}">
            @error('last_name')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="first_name" class="label">Prénom <span class="text-ko">*</span></label>
            <input id="first_name" name="first_name" value="{{ old('first_name', $participant->first_name) }}" required maxlength="100" class="{{ $field('first_name') }}">
            @error('first_name')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="phone" class="label">Téléphone</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone', $participant->phone) }}" maxlength="30" class="{{ $field('phone') }}">
            @error('phone')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $participant->email) }}" maxlength="150" class="{{ $field('email') }}">
            @error('email')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="organization" class="label">Organisation</label>
            <input id="organization" name="organization" value="{{ old('organization', $participant->organization) }}" maxlength="150" class="{{ $field('organization') }}">
            @error('organization')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="function" class="label">Fonction</label>
            <input id="function" name="function" value="{{ old('function', $participant->function) }}" maxlength="100" placeholder="Stagiaire" class="{{ $field('function') }}">
            @error('function')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <span class="label">Photo <span class="text-slate-400 font-normal">(facultative)</span></span>
            <div class="flex items-center gap-4">
                <div id="photo-preview" class="w-20 h-20 rounded-full bg-slate-100 overflow-hidden grid place-items-center text-slate-400 shrink-0">
                    @if ($participant->photo_url)
                        <img src="{{ $participant->photo_url }}" alt="" class="w-full h-full object-cover">
                    @else
                        <x-icon name="user" class="w-9 h-9"/>
                    @endif
                </div>
                <div class="space-y-2">
                    <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="block text-sm file:mr-3 file:rounded-lg file:border file:border-slate-300 file:bg-white file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-navy">
                    <p class="text-xs text-slate-500">JPG, PNG ou WEBP, 3 Mo maximum. Cadrage carré conseillé.</p>
                    @if ($participant->photo_path)
                        <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remove_photo" value="1" class="accent-[#FF7900]"> Supprimer la photo actuelle</label>
                    @endif
                </div>
            </div>
            @error('photo')<p class="error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('photo').addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const box = document.getElementById('photo-preview');
        const img = new Image();
        img.className = 'w-full h-full object-cover';
        img.src = URL.createObjectURL(file);
        box.replaceChildren(img);
    });
</script>
@endpush
