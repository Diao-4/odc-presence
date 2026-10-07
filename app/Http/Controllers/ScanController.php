<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScanRequest;
use App\Models\Cohort;
use App\Services\AttendanceService;
use App\Services\CohortContext;
use App\Services\ReportService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function index(Request $request, SettingService $settings, ReportService $reports, CohortContext $cohorts): View
    {
        // Code reçu d'un lien de badge (/b/{jeton}) : le scanner l'envoie automatiquement à l'ouverture.
        $code     = (string) $request->query('code');
        $cohort   = $cohorts->current();
        $schedule = $cohort ?? Cohort::defaults();

        return view('scan.index', [
            'pendingCode'      => preg_match('/^[A-Za-z0-9-]{1,100}$/', $code) ? $code : null,
            'departureEnabled' => $settings->bool('departure_enabled'),
            'resetSeconds'     => (int) $settings->get('scan_reset_seconds', 3),
            'cohort'           => $cohort,
            'cutoff'           => $schedule->cutoffAt(today())->format('H:i'),
            'absenceTime'      => $schedule->absenceDeadlineAt(today())?->format('H:i'),
            'stats'            => $reports->dayStats(today(), $cohort),
        ]);
    }

    /**
     * Lien contenu dans le QR Code, ouvert par un scanner quelconque. Utilisateur connecté :
     * redirection vers le scanner de l'application. Sinon, le middleware « auth » affiche
     * d'abord la connexion puis revient ici.
     */
    public function link(string $token): RedirectResponse
    {
        return redirect()->route('scan', ['code' => $token]);
    }

    public function store(ScanRequest $request, AttendanceService $service): JsonResponse
    {
        $result = $service->scan(
            (string) $request->validated('code'),
            $request->user(),
            (string) $request->input('mode', 'arrival'),
            $request->boolean('manual'),
        );

        return response()->json($result);
    }
}
