<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Services\ReportService;
use App\Services\SettingService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ReportService $reports, SettingService $settings): View
    {
        $today = today();

        return view('dashboard', [
            'stats'         => $reports->dayStats($today),
            'latest'        => Attendance::with(['participant', 'badge'])
                ->where('date', $today->toDateString())
                ->orderByDesc('arrived_at')
                ->limit(8)
                ->get(),
            'cutoff'        => $settings->cutoffTime(),
            'absentFrom'    => $settings->absentFromAt($today)->format('H:i'),
            'isTrainingDay' => $settings->isTrainingDay($today),
        ]);
    }
}
