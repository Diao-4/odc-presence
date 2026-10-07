{{-- Actions sur un compte ($u) --}}
<div class="flex justify-end gap-0.5 shrink-0">
    <a href="{{ route('users.edit', $u) }}" class="btn-icon" title="Modifier" aria-label="Modifier {{ $u->name }}"><x-icon name="pencil"/></a>
    @unless ($u->is(auth()->user()))
        <form method="POST" action="{{ route('users.toggle', $u) }}">@csrf @method('PATCH')
            <button class="btn-icon" title="{{ $u->is_active ? 'Désactiver' : 'Réactiver' }}" aria-label="{{ $u->is_active ? 'Désactiver' : 'Réactiver' }} {{ $u->name }}"><x-icon :name="$u->is_active ? 'ban' : 'check'"/></button>
        </form>
        <form method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return confirm('Supprimer le compte {{ $u->username }} ?')">@csrf @method('DELETE')
            <button class="btn-icon hover:text-ko" title="Supprimer" aria-label="Supprimer {{ $u->name }}"><x-icon name="trash"/></button>
        </form>
    @endunless
</div>
