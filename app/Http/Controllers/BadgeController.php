<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\Participant;
use App\Services\ActivityLogger;
use App\Services\BadgeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BadgeController extends Controller
{
    public function __construct(private BadgeService $badges) {}

    public function index(Request $request): View
    {
        $status = $request->input('status', 'active');

        $list = Badge::query()
            ->with('participant')
            ->whereHas('participant', fn ($q) => $q->whereNull('participants.deleted_at')->search($request->input('q')))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('badges.index', [
            'badges'      => $list,
            'status'      => $status,
            'activeCount' => $this->badges->activeBadges()->count(),
            'qr'          => fn (Badge $b) => $this->badges->qrDataUri($b->token, 120),
            'sizes'       => BadgeService::SIZES,
            'size'        => $this->badges->size($request->input('size')),
        ]);
    }

    public function show(Badge $badge): View
    {
        $badge->load('participant');

        return view('badges.show', [
            'badge' => $badge,
            'card'  => $this->badges->cardData($badge, 420),
            'sizes' => BadgeService::SIZES,
            'size'  => $this->badges->size(null),
        ]);
    }

    public function download(Request $request, Badge $badge): Response
    {
        $badge->load('participant');
        $size = $this->badges->size($request->input('size'));
        ActivityLogger::log('badge.download', "Téléchargement du badge {$badge->number} ({$size})");

        return $this->badges->pdf(collect([$badge]), $size)->download($this->badges->fileName($badge->participant));
    }

    /** Depuis la liste des participants : télécharge le badge actif (le crée s'il n'existe pas). */
    public function participantDownload(Request $request, Participant $participant): Response
    {
        $badge = $participant->activeBadge ?? $this->badges->issue($participant);

        return $this->download($request, $badge);
    }

    public function generate(Participant $participant): RedirectResponse
    {
        if ($participant->activeBadge) {
            return redirect()->route('badges.show', $participant->activeBadge)
                ->with('info', 'Ce participant possède déjà un badge actif. Utilisez « Remplacer » pour en émettre un nouveau.');
        }

        $badge = $this->badges->issue($participant);
        ActivityLogger::log('badge.create', "Génération du badge {$badge->number}");

        return redirect()->route('badges.show', $badge)->with('success', 'Badge généré.');
    }

    public function replace(Badge $badge): RedirectResponse
    {
        $new = $this->badges->issue($badge->participant);
        ActivityLogger::log('badge.replace', "Remplacement du badge {$badge->number} (v{$badge->version} → v{$new->version})");

        return redirect()->route('badges.show', $new)
            ->with('success', "Nouveau badge émis. L'ancien QR Code est désormais refusé.");
    }

    public function toggle(Badge $badge): RedirectResponse
    {
        if ($badge->isActive()) {
            $this->badges->disable($badge);
            ActivityLogger::log('badge.disable', "Désactivation du badge {$badge->number}");

            return back()->with('success', 'Badge désactivé : il sera refusé au scan.');
        }

        if (! $this->badges->enable($badge)) {
            return back()->with('error', 'Impossible de réactiver ce badge : il a été remplacé ou un autre badge est actif.');
        }

        ActivityLogger::log('badge.enable', "Réactivation du badge {$badge->number}");

        return back()->with('success', 'Badge réactivé.');
    }

    public function zip(Request $request): Response|RedirectResponse
    {
        $size = $this->badges->size($request->input('size'));
        $list = $this->badges->activeBadges();
        if ($list->isEmpty()) {
            return back()->with('error', 'Aucun badge actif à télécharger.');
        }

        $path = $this->badges->zip($list, $size);
        ActivityLogger::log('badge.zip', "Téléchargement ZIP de {$list->count()} badges ({$size})");

        return response()->download($path, config('odc.files.badges').'.zip')->deleteFileAfterSend();
    }

    public function pdfAll(Request $request): Response|RedirectResponse
    {
        $size = $this->badges->size($request->input('size'));
        $list = $this->badges->activeBadges();
        if ($list->isEmpty()) {
            return back()->with('error', 'Aucun badge actif à télécharger.');
        }

        ActivityLogger::log('badge.pdf', "Téléchargement PDF multipage de {$list->count()} badges ({$size})");

        return $this->badges->pdf($list, $size)->download(config('odc.files.badges').'.pdf');
    }

    public function printAll(): View
    {
        @set_time_limit(300);

        return view('badges.print', [
            'cards' => $this->badges->activeBadges()->map(fn ($b) => $this->badges->cardData($b, 300))->all(),
        ]);
    }

    public function printOne(Badge $badge): View
    {
        $badge->load('participant');

        return view('badges.print', ['cards' => [$this->badges->cardData($badge, 300)]]);
    }
}
