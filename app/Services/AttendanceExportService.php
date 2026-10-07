<?php

namespace App\Services;

use App\Exports\AttendanceExport;
use App\Models\Cohort;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class AttendanceExportService
{
    public const HEADINGS = ['N°', 'Nom et prénom', 'Badge', 'Date', 'Arrivée', 'Départ', 'Statut', 'Retard (min)', 'Justification'];

    public function fileName(Carbon $from, Carbon $to, string $ext, ?Cohort $cohort = null): string
    {
        $period = $from->isSameDay($to)
            ? $from->format('d-m-Y')
            : $from->format('d-m-Y').'_'.$to->format('d-m-Y');

        $base = $cohort ? $cohort->file_slug.'_Presences' : config('odc.files.attendances');

        return $base.'_'.$period.'.'.$ext;
    }

    public function tableRows(Collection $rows): array
    {
        return $rows->values()->map(fn ($r, $i) => [
            $i + 1,
            $r['name'],
            $r['badge'],
            $r['date']->format('d/m/Y'),
            $r['arrived'] ?? '—',
            $r['departed'] ?? '—',
            $r['status_label'],
            $r['late_minutes'],
            $r['justification']?->reason ?? '',
        ])->all();
    }

    public function download(string $format, Collection $rows, array $summary, array $filters): Response
    {
        $name = $this->fileName($filters['from'], $filters['to'], $format, $filters['cohort'] ?? null);

        ActivityLogger::log('export.attendances', "Export {$format} des présences ({$name})");

        return match ($format) {
            'csv'   => $this->csv($rows, $name),
            'xlsx'  => Excel::download(new AttendanceExport($this->tableRows($rows)), $name),
            default => Pdf::loadView('pdf.attendances', [
                'rows'    => $rows,
                'summary' => $summary,
                'from'    => $filters['from'],
                'to'      => $filters['to'],
                'cohort'  => $filters['cohort'] ?? null,
            ])->setPaper('a4', 'portrait')->download($name),
        };
    }

    private function csv(Collection $rows, string $name): Response
    {
        $data = $this->tableRows($rows);

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 : accents corrects dans Excel
            fputcsv($out, self::HEADINGS, ';');
            foreach ($data as $line) {
                fputcsv($out, $line, ';');
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
