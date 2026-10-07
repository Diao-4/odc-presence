@php
    $nav = [
        ['route' => 'dashboard',          'match' => 'dashboard',      'label' => 'Tableau de bord', 'icon' => 'home',      'admin' => false],
        ['route' => 'scan',               'match' => 'scan*',          'label' => 'Scanner',         'icon' => 'qr',        'admin' => false],
        ['route' => 'participants.index', 'match' => 'participants.*', 'label' => 'Participants',    'icon' => 'users',     'admin' => false],
        ['route' => 'attendances.index',  'match' => 'attendances.*',  'label' => 'Présences',       'icon' => 'clipboard', 'admin' => false],
        ['route' => 'badges.index',       'match' => 'badges.*',       'label' => 'Badges',          'icon' => 'badge',     'admin' => true],
        ['route' => 'reports.index',      'match' => 'reports.*',      'label' => 'Rapports',        'icon' => 'chart',     'admin' => true],
        ['route' => 'cohorts.index',      'match' => 'cohorts.*',      'label' => 'Cohortes',        'icon' => 'calendar',  'admin' => true],
        ['route' => 'users.index',       'match' => 'users.*',        'label' => 'Agents',          'icon' => 'user',      'admin' => true],
        ['route' => 'settings.index',     'match' => ['settings.*', 'logs.*'], 'label' => 'Paramètres', 'icon' => 'cog', 'admin' => true],
    ];
    $isAdmin = auth()->user()->isAdmin();
    $nav = array_values(array_filter($nav, fn ($i) => ! $i['admin'] || $isAdmin));
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0B1426">
    @include('partials.pwa-head')
    <title>@yield('title', 'Tableau de bord') · {{ config('odc.app_name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-slate-100 text-navy antialiased">
<div class="min-h-screen">
    {{-- Barre latérale (ordinateur / tablette paysage) --}}
    <aside class="hidden lg:flex fixed inset-y-0 left-0 w-64 flex-col bg-navy text-white z-40">
        <div class="px-5 pt-6 pb-5 border-b border-white/10">
            <x-brand/>
            <p class="mt-4 text-xs text-white/60">{{ $currentCohort?->label ?? config('odc.program') }}</p>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            @foreach ($nav as $item)
                @php $active = request()->routeIs(...(array) $item['match']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors {{ $active ? 'bg-odc text-white' : 'text-white/75 hover:bg-white/10 hover:text-white' }}"
                   @if($active) aria-current="page" @endif>
                    <x-icon :name="$item['icon']"/> {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        <div class="px-3 py-4 border-t border-white/10">
            <a href="{{ route('password.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-white/10">
                <span class="w-9 h-9 rounded-full bg-white/10 grid place-items-center text-sm font-semibold">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="min-w-0 leading-tight">
                    <span class="block text-sm font-semibold truncate">{{ auth()->user()->name }}</span>
                    <span class="block text-xs text-white/60">{{ auth()->user()->role_label }}</span>
                </span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf
                <button class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-white/75 hover:bg-white/10 hover:text-white">
                    <x-icon name="logout"/> Déconnexion
                </button>
            </form>
        </div>
    </aside>

    <div class="lg:pl-64 flex flex-col min-h-screen">
        {{-- En-tête --}}
        <header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-slate-200 pt-[env(safe-area-inset-top)]">
            <div class="h-14 sm:h-16 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="lg:hidden w-8 h-8 bg-odc text-white text-[8px] font-bold flex items-end p-1 leading-none shrink-0">ODC</span>
                    <div class="min-w-0 leading-tight">
                        @if ($isAdmin && $cohortChoices->count() > 1)
                            {{-- Sélecteur de cohorte (administrateur) --}}
                            <form method="POST" action="{{ route('cohorts.select', $currentCohort?->id ?? 0) }}" data-cohort-switch>
                                @csrf
                                <input type="hidden" name="back" value="{{ url()->full() }}">
                                <label for="cohort-switch" class="sr-only">Cohorte affichée</label>
                                <select id="cohort-switch" class="max-w-[58vw] sm:max-w-md truncate rounded-lg border border-slate-200 bg-slate-50 py-1 pl-2 pr-7 text-base sm:text-[15px] font-bold text-navy focus:border-odc focus:outline-none focus:ring-2 focus:ring-odc/25">
                                    @foreach ($cohortChoices as $c)
                                        <option value="{{ $c->id }}" @selected($currentCohort?->is($c))>{{ $c->label }}{{ $c->is_active ? '' : ' (archivée)' }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <p class="font-bold text-sm sm:text-base truncate">{{ $currentCohort?->label ?? config('odc.program') }}</p>
                        @endif
                        <p class="text-xs text-slate-500 truncate hidden sm:block">{{ config('odc.app_name') }} · {{ config('odc.organization') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 text-right">
                    <span class="hidden sm:block leading-tight">
                        <span class="block text-sm font-semibold">{{ auth()->user()->name }}</span>
                        <span class="block text-xs text-slate-500">{{ auth()->user()->role_label }}</span>
                    </span>
                    <span class="w-9 h-9 rounded-full bg-navy text-white grid place-items-center text-sm font-semibold">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 sm:px-6 lg:px-8 py-5 sm:py-8 pb-28 lg:pb-10">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>

{{-- Navigation mobile : gros bouton Scanner au centre --}}
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-slate-200 pb-[env(safe-area-inset-bottom)]" aria-label="Navigation principale">
    <div class="grid grid-cols-5 items-end h-16">
        @php $mobile = [['dashboard', 'dashboard', 'Accueil', 'home'], ['participants.index', 'participants.*', 'Participants', 'users']]; @endphp
        @foreach ($mobile as [$r, $m, $l, $i])
            <a href="{{ route($r) }}" class="flex flex-col items-center justify-center gap-0.5 h-16 text-[11px] font-medium {{ request()->routeIs($m) ? 'text-odc' : 'text-slate-500' }}">
                <x-icon :name="$i" class="w-6 h-6"/>{{ $l }}
            </a>
        @endforeach
        <a href="{{ route('scan') }}" class="flex flex-col items-center -mt-6" aria-label="Scanner un badge">
            <span class="w-16 h-16 rounded-2xl bg-odc text-white grid place-items-center shadow-lg shadow-orange-500/40 ring-4 ring-white">
                <x-icon name="qr" class="w-8 h-8"/>
            </span>
            <span class="text-[11px] font-semibold mt-1 {{ request()->routeIs('scan*') ? 'text-odc' : 'text-navy' }}">Scanner</span>
        </a>
        <a href="{{ route('attendances.index') }}" class="flex flex-col items-center justify-center gap-0.5 h-16 text-[11px] font-medium {{ request()->routeIs('attendances.*') ? 'text-odc' : 'text-slate-500' }}">
            <x-icon name="clipboard" class="w-6 h-6"/>Présences
        </a>
        <button type="button" data-open-menu class="flex flex-col items-center justify-center gap-0.5 h-16 text-[11px] font-medium text-slate-500">
            <x-icon name="menu" class="w-6 h-6"/>Menu
        </button>
    </div>
</nav>

<dialog id="mobile-menu" class="lg:hidden m-0 mt-auto w-full max-w-none rounded-t-3xl p-0 backdrop:bg-navy/50">
    <div class="p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))]">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="font-bold">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-500">{{ auth()->user()->role_label }}</p>
            </div>
            <button type="button" data-close-menu class="btn-icon" aria-label="Fermer"><x-icon name="x"/></button>
        </div>
        <div class="grid grid-cols-1 gap-1">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[15px] font-medium hover:bg-slate-100">
                    <x-icon :name="$item['icon']" class="w-5 h-5 text-slate-500"/> {{ $item['label'] }}
                </a>
            @endforeach
            <a href="{{ route('password.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[15px] font-medium hover:bg-slate-100">
                <x-icon name="key" class="w-5 h-5 text-slate-500"/> Mot de passe
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full flex items-center gap-3 rounded-xl px-3 py-3 text-[15px] font-medium text-ko hover:bg-ko-50">
                    <x-icon name="logout" class="w-5 h-5"/> Déconnexion
                </button>
            </form>
        </div>
    </div>
</dialog>

<script>
    (() => {
        const menu = document.getElementById('mobile-menu');
        document.querySelector('[data-open-menu]')?.addEventListener('click', () => menu.showModal());
        document.querySelector('[data-close-menu]')?.addEventListener('click', () => menu.close());
        menu?.addEventListener('click', (e) => { if (e.target === menu) menu.close(); });

        // Changement de cohorte : la cohorte choisie est placée dans l'adresse du formulaire.
        const sw = document.querySelector('[data-cohort-switch]');
        sw?.querySelector('select').addEventListener('change', (e) => {
            sw.action = sw.action.replace(/\/\d+\/afficher$/, '/' + e.target.value + '/afficher');
            sw.submit();
        });
    })();
</script>
@stack('scripts')
</body>
</html>
