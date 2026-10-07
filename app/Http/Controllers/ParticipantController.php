<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParticipantRequest;
use App\Models\Badge;
use App\Models\Participant;
use App\Services\ActivityLogger;
use App\Services\BadgeService;
use App\Services\CohortContext;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ParticipantController extends Controller
{
    public function index(Request $request, CohortContext $cohorts): View
    {
        $status = $request->input('status', 'all');
        $cohort = $cohorts->current();

        $participants = Participant::query()
            ->with('activeBadge')
            ->inCohort($cohort)
            ->search($request->input('q'))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->filled('organization'), fn ($q) => $q->where('organization', $request->input('organization')))
            ->orderBy('last_name')->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('participants.index', [
            'participants'  => $participants,
            'organizations' => Participant::query()->inCohort($cohort)->whereNotNull('organization')->distinct()->orderBy('organization')->pluck('organization'),
            'status'        => $status,
            'cohort'        => $cohort,
        ]);
    }

    public function create(CohortContext $cohorts): View
    {
        Gate::authorize('create', Participant::class);

        return view('participants.create', [
            'participant' => new Participant(['organization' => config('odc.organization'), 'cohort_id' => $cohorts->current()?->id]),
            'cohorts'     => $cohorts->choices(),
        ]);
    }

    public function store(ParticipantRequest $request, BadgeService $badges): RedirectResponse
    {
        $data = $request->safe()->except(['photo', 'remove_photo']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('participants', 'public');
        }

        // Création + identifiant unique + badge + QR Code, en une seule transaction.
        $participant = DB::transaction(function () use ($data, $badges) {
            $participant = Participant::create($data + ['is_active' => true]);
            $badges->issue($participant);

            return $participant;
        });

        ActivityLogger::log('participant.create', "Création de {$participant->full_name} ({$participant->code})");

        return redirect()->route('participants.show', $participant)
            ->with('success', "Participant créé. Badge {$participant->code} généré.");
    }

    public function show(Request $request, Participant $participant, ReportService $reports, CohortContext $cohorts): View
    {
        abort_unless($cohorts->allows($request->user(), $participant->cohort_id), 404); // agent : sa cohorte uniquement

        $participant->load('activeBadge', 'currentBadge', 'cohort');

        $filters = [
            'from'           => $participant->created_at->copy()->startOfDay(),
            'to'             => today(),
            'participant_id' => $participant->id,
            'status'         => null,
            'q'              => null,
        ];
        $rows = $reports->rows($filters);

        return view('participants.show', [
            'participant' => $participant,
            'history'     => $reports->paginate($rows, $request, 15),
            'summary'     => $reports->summary($rows, $filters),
            'badge'       => $participant->activeBadge ?? $participant->currentBadge,
        ]);
    }

    public function edit(Participant $participant, CohortContext $cohorts): View
    {
        Gate::authorize('update', $participant);

        return view('participants.edit', ['participant' => $participant, 'cohorts' => $cohorts->choices()]);
    }

    public function update(ParticipantRequest $request, Participant $participant): RedirectResponse
    {
        $data = $request->safe()->except(['photo', 'remove_photo']);

        if ($request->boolean('remove_photo') || $request->hasFile('photo')) {
            if ($participant->photo_path) {
                Storage::disk('public')->delete($participant->photo_path);
            }
            $data['photo_path'] = $request->hasFile('photo')
                ? $request->file('photo')->store('participants', 'public')
                : null;
        }

        $participant->update($data);
        ActivityLogger::log('participant.update', "Modification de {$participant->full_name} ({$participant->code})");

        return redirect()->route('participants.show', $participant)->with('success', 'Participant mis à jour.');
    }

    public function toggle(Participant $participant): RedirectResponse
    {
        Gate::authorize('update', $participant);

        $participant->update(['is_active' => ! $participant->is_active]);
        $label = $participant->is_active ? 'réactivé' : 'désactivé';
        ActivityLogger::log('participant.toggle', "Participant {$participant->code} {$label}");

        return back()->with('success', "Participant {$label}.");
    }

    public function destroy(Participant $participant): RedirectResponse
    {
        Gate::authorize('delete', $participant);

        DB::transaction(function () use ($participant) {
            $participant->badges()->where('status', Badge::ACTIVE)->update(['status' => Badge::DISABLED, 'disabled_at' => now()]);
            $participant->update(['is_active' => false]);
            $participant->delete(); // suppression logique : l'historique des présences est conservé
        });

        ActivityLogger::log('participant.delete', "Suppression de {$participant->full_name} ({$participant->code})");

        return redirect()->route('participants.index')->with('success', 'Participant supprimé.');
    }
}
