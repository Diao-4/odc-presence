<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Services\ReportService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports, SettingService $settings): View
    {
        $filters = $reports->filters($request, 'week');
        $rows    = $reports->rows($filters);

        return view('reports.index', [
            'filters'       => $filters,
            'summary'       => $reports->summary($rows, $filters),
            'perParticipant'=> $reports->byParticipant($rows),
            'participants'  => Participant::orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'code']),
            'defaultExport' => $settings->get('default_export', 'pdf'),
        ]);
    }
}
