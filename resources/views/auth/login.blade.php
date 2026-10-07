<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0B1426">
    @include('partials.pwa-head')
    <title>Connexion · {{ config('odc.app_name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-navy text-navy antialiased">
<div class="min-h-screen grid lg:grid-cols-[1.1fr_1fr]">
    {{-- Panneau identité --}}
    <section class="relative overflow-hidden text-white px-6 sm:px-10 pt-10 pb-8 lg:p-14 flex flex-col justify-between gap-10">
        <div class="absolute -right-24 -bottom-24 w-[28rem] h-[28rem] rounded-full border-[56px] border-odc/15 pointer-events-none" aria-hidden="true"></div>
        <div class="relative flex items-center justify-between gap-6">
            <x-brand size="lg"/>
            <span class="text-right leading-tight">
                <span class="block text-2xl font-extrabold tracking-tight">aws</span>
                <span class="block text-sm text-white/80">re/Start</span>
            </span>
        </div>

        <div class="relative">
            <p class="text-odc font-semibold">{{ config('odc.program') }}</p>
            <h1 class="mt-3 text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight">ODC Présence</h1>
            <p class="mt-4 max-w-md text-white/75 text-base sm:text-lg">{{ config('odc.tagline') }}</p>
        </div>

        <p class="relative hidden lg:block text-sm text-white/50">© {{ date('Y') }} {{ config('odc.organization') }}</p>
    </section>

    {{-- Formulaire --}}
    <section class="bg-slate-100 lg:bg-white rounded-t-3xl lg:rounded-none px-6 sm:px-10 py-10 flex items-center justify-center">
        <div class="w-full max-w-sm">
            <h2 class="text-2xl font-extrabold tracking-tight">Se connecter</h2>
            <p class="mt-1 text-sm text-slate-500">Accédez à votre espace de contrôle des présences.</p>

            @if (str_contains((string) session('url.intended'), '/b/'))
                <div class="mt-6 flex items-start gap-3 rounded-xl border border-orange-200 bg-odc-50 px-4 py-3 text-sm text-orange-900" role="status">
                    <x-icon name="qr" class="w-5 h-5 shrink-0 text-odc"/>
                    <p><strong>Badge détecté.</strong> Connectez-vous pour continuer : la présence sera enregistrée dans le scanner de l'application.</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-6 flex items-start gap-3 rounded-xl border border-red-200 bg-ko-50 px-4 py-3 text-sm text-red-800" role="alert">
                    <x-icon name="x-circle" class="w-5 h-5 shrink-0"/>
                    <p>{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5" novalidate>
                @csrf
                <div>
                    <label for="username" class="label">Identifiant</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                           autocomplete="username" autocapitalize="none" spellcheck="false"
                           placeholder="Entrez votre identifiant" class="input py-3 @error('username') input-error @enderror">
                </div>
                <div>
                    <label for="password" class="label">Mot de passe</label>
                    <div class="relative">
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                               placeholder="Entrez votre mot de passe" class="input py-3 pr-12">
                        <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 px-3.5 text-slate-500 hover:text-navy" aria-label="Afficher le mot de passe">
                            <span data-show><x-icon name="eye"/></span>
                            <span data-hide class="hidden"><x-icon name="eye-slash"/></span>
                        </button>
                    </div>
                </div>
                <label class="flex items-center gap-2.5 text-sm text-slate-700 select-none">
                    <input type="checkbox" name="remember" value="1" class="w-4 h-4 rounded border-slate-300 accent-[#FF7900]" @checked(old('remember'))>
                    Se souvenir de moi
                </label>
                <button class="btn btn-primary w-full py-3.5 text-base">Se connecter</button>
                <p class="text-center text-xs text-slate-500">Mot de passe oublié ? Contactez l'administrateur de la formation.</p>
            </form>
        </div>
    </section>
</div>
<script>
    document.getElementById('toggle-password').addEventListener('click', function () {
        const input = document.getElementById('password');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.querySelector('[data-show]').classList.toggle('hidden', show);
        this.querySelector('[data-hide]').classList.toggle('hidden', !show);
        this.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    });
</script>
</body>
</html>
