<?php

namespace App\Http\Controllers;

use App\Http\Requests\JustificationRequest;
use App\Models\Attendance;
use App\Models\Justification;
use App\Models\Participant;
use App\Services\ActivityLogger;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Justification d'un retard ou d'une absence (motif + pièce jointe facultative).
 * Le type est déduit de la situation réelle du jour : on ne justifie que ce qui existe.
 */
class JustificationController extends Controller
{
    private const NOTHING = 'Aucun retard ni absence à justifier pour ce participant à cette date.';

    public function create(Request $request, ReportService $reports): View|RedirectResponse
    {
        $participant = Participant::withTrashed()->findOrFail($request->integer('participant_id'));
        $date        = $this->parseDate($request->input('date'));
        $type        = $date ? $this->typeFor($participant, $date, $reports) : null;

        if (! $type) {
            return back()->with('error', self::NOTHING);
        }

        return view('justifications.form', [
            'participant'   => $participant,
            'date'          => $date,
            'type'          => $type,
            'attendance'    => Attendance::where('participant_id', $participant->id)->where('date', $date->toDateString())->first(),
            'justification' => Justification::where('participant_id', $participant->id)->where('date', $date->toDateString())->first(),
            'back'          => $this->safeBack($request->input('back', url()->previous())),
        ]);
    }

    public function store(JustificationRequest $request, ReportService $reports): RedirectResponse
    {
        $participant = Participant::withTrashed()->findOrFail($request->validated('participant_id'));
        $date        = Carbon::createFromFormat('Y-m-d', $request->validated('date'))->startOfDay();
        $back        = $this->safeBack($request->input('back'));
        $type        = $this->typeFor($participant, $date, $reports);

        if (! $type) {
            return redirect($back)->with('error', self::NOTHING);
        }

        $justification = Justification::firstOrNew(['participant_id' => $participant->id, 'date' => $date->toDateString()]);
        $justification->fill([
            'type'         => $type,
            'reason'       => $request->validated('reason'),
            'justified_by' => $request->user()->id,
        ]);

        if ($request->boolean('remove_document') || $request->hasFile('document')) {
            $this->deleteDocument($justification);
        }
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $justification->document_path = $file->store('justifications', 'local'); // disque privé
            $justification->document_name = mb_substr($file->getClientOriginalName(), 0, 255);
        }

        $justification->save();

        $label = $type === Justification::LATE ? 'Retard justifié' : 'Absence justifiée';
        ActivityLogger::log('justification.save', "{$label} : {$participant->full_name} le {$date->format('d/m/Y')}");

        return redirect($back)->with('success', "{$label} pour {$participant->full_name}.");
    }

    public function document(Justification $justification): StreamedResponse
    {
        abort_unless($justification->document_path && Storage::disk('local')->exists($justification->document_path), 404);

        return Storage::disk('local')->download($justification->document_path, $justification->document_name);
    }

    public function destroy(Request $request, Justification $justification): RedirectResponse
    {
        $this->deleteDocument($justification);
        $justification->delete();

        ActivityLogger::log('justification.delete', "Justification supprimée : {$justification->participant?->full_name} le {$justification->date->format('d/m/Y')}");

        return redirect($this->safeBack($request->input('back', url()->previous())))->with('success', 'Justification supprimée.');
    }

    /** Retard si une arrivée en retard existe, absence si le participant est compté absent ce jour-là. */
    private function typeFor(Participant $participant, Carbon $date, ReportService $reports): ?string
    {
        $attendance = Attendance::where('participant_id', $participant->id)->where('date', $date->toDateString())->first();

        if ($attendance) {
            return $attendance->status === Attendance::LATE ? Justification::LATE : null;
        }

        $enrolled = ! $participant->created_at || $participant->created_at->toDateString() <= $date->toDateString();

        return $enrolled && $reports->absenceApplies($date, $participant->schedule()) ? Justification::ABSENT : null;
    }

    private function deleteDocument(Justification $justification): void
    {
        if ($justification->document_path) {
            Storage::disk('local')->delete($justification->document_path);
        }
        $justification->document_path = null;
        $justification->document_name = null;
    }

    private function parseDate(?string $value): ?Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('Y-m-d', $value)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** N'autorise le retour que vers une page de l'application. */
    private function safeBack(?string $url): string
    {
        return $url && str_starts_with($url, url('/')) ? $url : route('attendances.index');
    }
}
