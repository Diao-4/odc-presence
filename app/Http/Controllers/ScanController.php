<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScanRequest;
use App\Services\AttendanceService;
use App\Services\ReportService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function index(SettingService $settings, ReportService $reports): View
    {
        return view('scan.index', [
            'departureEnabled' => $settings->bool('departure_enabled'),
            'resetSeconds'     => (int) $settings->get('scan_reset_seconds', 3),
            'cutoff'           => $settings->cutoffTime(),
            'absenceTime'      => $settings->absenceTime(),
            'stats'            => $reports->dayStats(today()),
        ]);
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
