<?php

namespace App\Http\Controllers;

use App\Http\Requests\CohortRequest;
use App\Models\Cohort;
use App\Models\Participant;
use App\Services\ActivityLogger;
use App\Services\CohortContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Cohortes de formation (P11 Après-midi, P12 Matin…) : création, horaires, archivage. */
class CohortController extends Controller
{
    public function index(CohortContext $context): View
    {
        return view('cohorts.index', [
            'cohorts' => Cohort::withCount([
                'participants' => fn ($q) => $q->where('is_active', true),
                'agents',
            ])->orderByDesc('is_active')->orderByDesc('id')->get(),
            'current' => $context->current(),
        ]);
    }

    public function create(): View
    {
        return view('cohorts.form', ['cohort' => Cohort::defaults()]);
    }

    public function store(CohortRequest $request, CohortContext $context): RedirectResponse
    {
        $cohort = Cohort::create($request->cohortData() + ['is_active' => true]);
        $context->switchTo($cohort);

        ActivityLogger::log('cohort.create', "Création de la cohorte {$cohort->label}");

        return redirect()->route('cohorts.index')
            ->with('success', "Cohorte « {$cohort->label} » créée et affichée. Ajoutez maintenant ses participants et ses agents.");
    }

    public function edit(Cohort $cohort): View
    {
        return view('cohorts.form', ['cohort' => $cohort]);
    }

    public function update(CohortRequest $request, Cohort $cohort): RedirectResponse
    {
        $cohort->update($request->cohortData());
        ActivityLogger::log('cohort.update', "Modification de la cohorte {$cohort->label} (heure limite {$cohort->cutoff_time})");

        return redirect()->route('cohorts.index')->with('success', 'Cohorte mise à jour.');
    }

    /** Archiver une cohorte terminée (ou la réactiver). Ses données restent consultables. */
    public function toggle(Cohort $cohort): RedirectResponse
    {
        $cohort->update(['is_active' => ! $cohort->is_active]);
        $label = $cohort->is_active ? 'réactivée' : 'archivée';
        ActivityLogger::log('cohort.toggle', "Cohorte {$cohort->label} {$label}");

        return back()->with('success', "Cohorte {$label}.");
    }

    /** Suppression possible uniquement pour une cohorte vide (aucun participant, même supprimé). */
    public function destroy(Cohort $cohort): RedirectResponse
    {
        if (Participant::withTrashed()->where('cohort_id', $cohort->id)->exists()) {
            return back()->with('error', 'Cette cohorte contient des participants : archivez-la plutôt que de la supprimer.');
        }
        if (Cohort::count() === 1) {
            return back()->with('error', 'Impossible de supprimer la dernière cohorte.');
        }

        ActivityLogger::log('cohort.delete', "Suppression de la cohorte {$cohort->label}");
        $cohort->delete();

        return redirect()->route('cohorts.index')->with('success', 'Cohorte supprimée.');
    }

    /** Sélecteur de l'en-tête : cohorte affichée par l'administrateur. */
    public function select(Request $request, Cohort $cohort, CohortContext $context): RedirectResponse
    {
        $context->switchTo($cohort);

        $back = (string) $request->input('back');

        return redirect(str_starts_with($back, url('/')) ? $back : route('dashboard'))
            ->with('info', "Cohorte affichée : {$cohort->label}.");
    }
}
