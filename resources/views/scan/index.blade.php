@extends('layouts.app')
@section('title', 'Scanner')

@section('content')
<div class="grid lg:grid-cols-[minmax(0,1fr)_340px] gap-5 max-w-6xl">
    <div>
        <div class="mb-4">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Scanner un badge</h1>
            <p class="text-sm sm:text-base text-slate-500 mt-1">Présentez le QR Code du participant devant la caméra.</p>
        </div>

        @if ($departureEnabled)
            <div class="inline-grid grid-cols-2 p-1 rounded-xl bg-white border border-slate-200 mb-4" role="radiogroup" aria-label="Type de scan">
                <button type="button" data-mode="arrival" class="mode-btn px-5 py-2.5 rounded-lg text-sm font-semibold bg-navy text-white" aria-checked="true" role="radio">Arrivée</button>
                <button type="button" data-mode="departure" class="mode-btn px-5 py-2.5 rounded-lg text-sm font-semibold text-slate-600" aria-checked="false" role="radio">Départ</button>
            </div>
        @endif

        {{-- Zone caméra --}}
        <div class="relative aspect-square sm:aspect-[4/3] w-full rounded-3xl overflow-hidden bg-navy">
            <div id="reader" class="absolute inset-0"></div>
            <div class="absolute inset-0 pointer-events-none grid place-items-center" aria-hidden="true">
                <div class="relative w-[68%] aspect-square max-w-[22rem]">
                    <span class="absolute top-0 left-0 w-12 h-12 border-t-[6px] border-l-[6px] border-odc rounded-tl-2xl"></span>
                    <span class="absolute top-0 right-0 w-12 h-12 border-t-[6px] border-r-[6px] border-odc rounded-tr-2xl"></span>
                    <span class="absolute bottom-0 left-0 w-12 h-12 border-b-[6px] border-l-[6px] border-odc rounded-bl-2xl"></span>
                    <span class="absolute bottom-0 right-0 w-12 h-12 border-b-[6px] border-r-[6px] border-odc rounded-br-2xl"></span>
                </div>
            </div>
            <div id="camera-overlay" class="absolute inset-0 grid place-items-center text-center text-white p-6">
                <div>
                    <x-icon name="camera" class="w-12 h-12 mx-auto text-white/70"/>
                    <p class="mt-3 font-semibold" data-cam-text>Démarrage de la caméra…</p>
                    <button type="button" id="start-camera" class="btn btn-primary mt-4 hidden">Activer la caméra</button>
                </div>
            </div>
        </div>

        <div class="mt-3 flex items-center justify-between gap-3 text-sm">
            <span id="cam-status" class="inline-flex items-center gap-2 text-slate-500">
                <span class="w-2.5 h-2.5 rounded-full bg-slate-300" data-dot></span><span data-label>Caméra inactive</span>
            </span>
            <button type="button" id="switch-camera" class="btn btn-ghost btn-sm hidden"><x-icon name="refresh" class="w-4 h-4"/> Changer de caméra</button>
        </div>
    </div>

    <aside class="flex flex-col gap-4">
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-bold">Aujourd'hui</h2>
                <span class="text-xs text-slate-500">Limite {{ $cutoff }}@if ($absenceTime) · pointage jusqu'à {{ $absenceTime }}@endif</span>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-xl bg-ok-50 py-3"><p class="text-2xl font-extrabold text-ok tabular-nums" data-stat="present">{{ $stats['present'] }}</p><p class="text-xs text-slate-600">Présents</p></div>
                <div class="rounded-xl bg-odc-50 py-3"><p class="text-2xl font-extrabold text-odc-600 tabular-nums" data-stat="late">{{ $stats['late'] }}</p><p class="text-xs text-slate-600">Retards</p></div>
                <div class="rounded-xl bg-slate-100 py-3"><p class="text-2xl font-extrabold tabular-nums" data-stat="remaining">{{ $stats['absent'] }}</p><p class="text-xs text-slate-600">Restants</p></div>
            </div>
        </div>

        <form id="manual-form" class="card p-5" autocomplete="off">
            <h2 class="font-bold">Saisie manuelle</h2>
            <p class="text-sm text-slate-500 mt-1">Si le QR Code est illisible, saisissez le numéro du badge.</p>
            <div class="mt-3 flex gap-2">
                <input id="manual-code" class="input uppercase" placeholder="ODC-00452" maxlength="100" aria-label="Numéro du badge">
                <button class="btn btn-dark">Valider</button>
            </div>
        </form>
    </aside>
</div>

{{-- Résultat du scan (plein écran sur mobile) --}}
<div id="result" class="fixed inset-0 z-50 hidden items-center justify-center bg-navy/60 backdrop-blur-sm p-0 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="result-title">
    <div class="bg-white w-full h-full sm:h-auto sm:max-w-lg sm:rounded-3xl overflow-hidden flex flex-col shadow-2xl">
        <div id="result-head" class="px-6 pt-[calc(1.5rem+env(safe-area-inset-top))] sm:pt-6 pb-6 text-white">
            <div class="flex items-center gap-3">
                <span id="result-icon" class="grid place-items-center w-12 h-12 rounded-full bg-white/20 text-2xl font-bold" aria-hidden="true"></span>
                <h2 id="result-title" class="text-2xl sm:text-[1.7rem] font-extrabold tracking-tight uppercase"></h2>
            </div>
            <p id="result-message" class="mt-3 text-white/90 hidden"></p>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-6">
            <div id="result-person" class="flex items-center gap-4">
                <div id="result-avatar" class="w-20 h-20 rounded-full bg-navy text-white text-2xl font-bold grid place-items-center overflow-hidden shrink-0"></div>
                <div class="min-w-0">
                    <p id="result-name" class="text-2xl font-extrabold leading-tight"></p>
                    <dl class="mt-1 text-sm text-slate-600 space-y-0.5">
                        <div><dt class="inline">Badge :</dt> <dd class="inline font-semibold text-navy" data-f="badge"></dd></div>
                        <div><dt class="inline">Organisation :</dt> <dd class="inline" data-f="organization"></dd></div>
                        <div><dt class="inline">Fonction :</dt> <dd class="inline" data-f="function"></dd></div>
                    </dl>
                </div>
            </div>

            <div id="result-grid" class="mt-6 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-sm text-slate-500" data-time-label>Arrivée</p>
                    <p class="text-3xl font-extrabold tabular-nums" data-f="time"></p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-sm text-slate-500">Statut</p>
                    <p class="mt-1"><span id="result-status" class="pill text-sm px-3 py-1"></span></p>
                    <p class="text-sm font-semibold text-odc-600 mt-1 hidden" data-f="late"></p>
                </div>
            </div>
        </div>

        <div class="px-6 pb-[calc(1.5rem+env(safe-area-inset-bottom))] sm:pb-6">
            <button type="button" id="next-scan" class="btn btn-dark w-full py-4 text-base">
                <x-icon name="qr" class="w-5 h-5"/> Scanner un autre badge
            </button>
            <div class="mt-3 h-1.5 rounded-full bg-slate-100 overflow-hidden" aria-hidden="true">
                <div id="countdown" class="h-full bg-odc w-full origin-left"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(() => {
    const ENDPOINT = @json(route('scan.store'));
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const RESET_MS = {{ max(1, $resetSeconds) }} * 1000;

    const $ = (s, r = document) => r.querySelector(s);
    const result = $('#result');
    let scanner = null, cameras = [], camIndex = 0;
    let busy = false, mode = 'arrival', lastCode = null, lastAt = 0, timer = null;

    // ---------- Caméra ----------
    function setCam(state, text) {
        const dot = $('#cam-status [data-dot]'), label = $('#cam-status [data-label]');
        dot.className = 'w-2.5 h-2.5 rounded-full ' + ({ ok: 'bg-ok', error: 'bg-ko', idle: 'bg-slate-300' }[state]);
        label.textContent = text;
        $('#camera-overlay').classList.toggle('hidden', state === 'ok');
        $('[data-cam-text]').textContent = text;
        $('#start-camera').classList.toggle('hidden', state !== 'error');
    }

    async function startCamera() {
        if (!window.Html5Qrcode) { setCam('error', 'Lecteur QR indisponible (connexion internet requise).'); return; }
        if (!window.isSecureContext) { setCam('error', 'La caméra nécessite une connexion HTTPS.'); return; }
        try {
            scanner = scanner || new Html5Qrcode('reader', { formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE], verbose: false });
            const config = { fps: 12, qrbox: (w, h) => { const s = Math.floor(Math.min(w, h) * 0.68); return { width: s, height: s }; } };
            const source = cameras.length ? { deviceId: { exact: cameras[camIndex].id } } : { facingMode: 'environment' };
            await scanner.start(source, config, onDecode, () => {});
            setCam('ok', 'Caméra activée');
            if (!cameras.length) {
                cameras = await Html5Qrcode.getCameras().catch(() => []);
                $('#switch-camera').classList.toggle('hidden', cameras.length < 2);
            }
        } catch (e) {
            setCam('error', "Impossible d'accéder à la caméra. Autorisez son utilisation puis réessayez.");
        }
    }

    $('#start-camera').addEventListener('click', startCamera);
    $('#switch-camera').addEventListener('click', async () => {
        if (!scanner || cameras.length < 2) return;
        await scanner.stop().catch(() => {});
        camIndex = (camIndex + 1) % cameras.length;
        startCamera();
    });

    // ---------- Mode arrivée / départ ----------
    document.querySelectorAll('.mode-btn').forEach(btn => btn.addEventListener('click', () => {
        mode = btn.dataset.mode;
        document.querySelectorAll('.mode-btn').forEach(b => {
            const on = b === btn;
            b.classList.toggle('bg-navy', on); b.classList.toggle('text-white', on); b.classList.toggle('text-slate-600', !on);
            b.setAttribute('aria-checked', on);
        });
    }));

    // ---------- Scan ----------
    function onDecode(text) {
        const now = Date.now();
        if (busy || (text === lastCode && now - lastAt < 4000)) return; // anti-rafale
        lastCode = text; lastAt = now;
        submit(text, false);
    }

    $('#manual-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const code = $('#manual-code').value.trim();
        if (code && !busy) submit(code, true);
    });

    async function submit(code, manual) {
        busy = true;
        try { scanner?.getState() === 2 && scanner.pause(true); } catch (_) {}
        try {
            const res = await fetch(ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ code, mode, manual }),
            });
            if (res.status === 401 || res.status === 419) { location.reload(); return; }
            if (!res.ok) throw new Error('HTTP ' + res.status);
            render(await res.json());
        } catch (e) {
            render({ result: 'error', title: 'Erreur réseau', message: "Le serveur n'a pas répondu. Vérifiez la connexion puis réessayez." });
        }
    }

    // ---------- Résultat ----------
    const THEMES = {
        present:   { head: 'bg-ok',     icon: '✓', pill: 'bg-ok-50 text-ok' },
        late:      { head: 'bg-odc',    icon: '!', pill: 'bg-odc-50 text-odc-600' },
        duplicate: { head: 'bg-sky-700', icon: 'i', pill: 'bg-slate-100 text-slate-700' },
        departure: { head: 'bg-navy',   icon: '✓', pill: 'bg-slate-100 text-slate-700' },
        invalid:   { head: 'bg-ko',     icon: '✕', pill: 'bg-ko-50 text-ko' },
        error:     { head: 'bg-ko',     icon: '✕', pill: 'bg-ko-50 text-ko' },
    };

    function render(d) {
        const t = THEMES[d.result] || THEMES.error;
        $('#result-head').className = 'px-6 pt-[calc(1.5rem+env(safe-area-inset-top))] sm:pt-6 pb-6 text-white ' + t.head;
        $('#result-icon').textContent = t.icon;
        $('#result-title').textContent = d.title || '';
        const msg = $('#result-message');
        msg.textContent = d.message || '';
        msg.classList.toggle('hidden', !d.message);

        const p = d.participant;
        $('#result-person').classList.toggle('hidden', !p);
        if (p) {
            $('#result-name').textContent = p.name;
            result.querySelector('[data-f="badge"]').textContent = p.badge || '—';
            result.querySelector('[data-f="organization"]').textContent = p.organization || '—';
            result.querySelector('[data-f="function"]').textContent = p.function || '—';
            const av = $('#result-avatar');
            av.textContent = '';
            if (p.photo) { const img = new Image(); img.src = p.photo; img.alt = ''; img.className = 'w-full h-full object-cover'; av.appendChild(img); }
            else av.textContent = p.initials;
        }

        const showGrid = !!(d.arrived_at || d.departed_at);
        $('#result-grid').classList.toggle('hidden', !showGrid);
        if (showGrid) {
            const isDeparture = d.result === 'departure' || (d.result === 'duplicate' && d.departed_at && mode === 'departure');
            result.querySelector('[data-time-label]').textContent = isDeparture ? 'Départ' : (d.result === 'duplicate' ? 'Première arrivée' : 'Arrivée');
            result.querySelector('[data-f="time"]').textContent = isDeparture ? d.departed_at : d.arrived_at;
            const pill = $('#result-status');
            pill.className = 'pill text-sm px-3 py-1 ' + (d.status === 'late' ? THEMES.late.pill : THEMES.present.pill);
            pill.textContent = (d.status_label || '').toUpperCase();
            const late = result.querySelector('[data-f="late"]');
            late.textContent = d.late_minutes > 0 ? 'Retard : ' + d.late_minutes + ' minute' + (d.late_minutes > 1 ? 's' : '') : '';
            late.classList.toggle('hidden', !(d.late_minutes > 0));
        }

        if (d.stats) {
            document.querySelector('[data-stat="present"]').textContent = d.stats.present;
            document.querySelector('[data-stat="late"]').textContent = d.stats.late;
            document.querySelector('[data-stat="remaining"]').textContent = d.stats.absent;
        }

        feedback(d.result);
        result.classList.remove('hidden'); result.classList.add('flex');
        $('#next-scan').focus();

        // Retour automatique au scanner (plus long en cas d'erreur pour laisser lire)
        const delay = d.ok || d.result === 'duplicate' ? RESET_MS : Math.max(RESET_MS, 5000);
        const bar = $('#countdown');
        bar.style.transition = 'none'; bar.style.transform = 'scaleX(1)';
        requestAnimationFrame(() => requestAnimationFrame(() => {
            bar.style.transition = `transform ${delay}ms linear`; bar.style.transform = 'scaleX(0)';
        }));
        clearTimeout(timer);
        timer = setTimeout(reset, delay);
    }

    function reset() {
        clearTimeout(timer);
        result.classList.add('hidden'); result.classList.remove('flex');
        $('#manual-code').value = '';
        busy = false;
        try { scanner?.getState() === 3 && scanner.resume(); } catch (_) {}
    }

    $('#next-scan').addEventListener('click', reset);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !result.classList.contains('hidden')) reset(); });

    // Son + vibration
    let ctx;
    function feedback(kind) {
        try {
            ctx = ctx || new (window.AudioContext || window.webkitAudioContext)();
            const tones = { present: [880], late: [660, 520], departure: [880], duplicate: [520], invalid: [220, 180], error: [220, 180] }[kind] || [440];
            tones.forEach((f, i) => {
                const o = ctx.createOscillator(), g = ctx.createGain();
                o.frequency.value = f; o.type = 'sine'; o.connect(g); g.connect(ctx.destination);
                const t0 = ctx.currentTime + i * 0.16;
                g.gain.setValueAtTime(0.18, t0); g.gain.exponentialRampToValueAtTime(0.001, t0 + 0.14);
                o.start(t0); o.stop(t0 + 0.15);
            });
        } catch (_) {}
        if (navigator.vibrate) navigator.vibrate(kind === 'invalid' || kind === 'error' ? [80, 60, 80] : 60);
    }

    startCamera();
    document.addEventListener('visibilitychange', () => { if (document.hidden) { try { scanner?.getState() === 2 && scanner.pause(true); } catch (_) {} } else if (!busy) { try { scanner?.getState() === 3 && scanner.resume(); } catch (_) {} } });
})();
</script>
@endpush
