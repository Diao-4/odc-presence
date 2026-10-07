<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Cohort;
use App\Models\Participant;
use App\Services\CohortContext;
use App\Services\ReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ReportService $reports, CohortContext $cohorts): View
    {
        $today    = today();
        $cohort   = $cohorts->current();
        $schedule = $cohort ?? Cohort::defaults();

        return view('dashboard', [
            'cohort'        => $cohort,
            'stats'         => $reports->dayStats($today, $cohort),
            'latest'        => Attendance::with(['participant', 'badge'])
                ->where('date', $today->toDateString())
                ->whereIn('participant_id', Participant::withTrashed()->inCohort($cohort)->select('id'))
                ->orderByDesc('arrived_at')
                ->limit(8)
                ->get(),
            'cutoff'        => $schedule->cutoffAt($today)->format('H:i'),
            'absentFrom'    => $schedule->absentFromAt($today)->format('H:i'),
            'isTrainingDay' => $schedule->isTrainingDay($today),
        ]);
    }
}
