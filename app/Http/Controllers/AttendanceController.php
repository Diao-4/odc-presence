<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Services\AttendanceExportService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        $filters = $reports->filters($request, 'today');
        $rows    = $reports->rows($filters);

        return view('attendances.index', [
            'filters'      => $filters,
            'rows'         => $reports->paginate($rows, $request, 25),
            'summary'      => $reports->summary($rows, $filters),
            'participants' => Participant::orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'code']),
        ]);
    }

    public function export(Request $request, string $format, ReportService $reports, AttendanceExportService $exporter): Response
    {
        $filters = $reports->filters($request, 'today');
        $rows    = $reports->rows($filters);

        return $exporter->download($format, $rows, $reports->summary($rows, $filters), $filters);
    }
}
