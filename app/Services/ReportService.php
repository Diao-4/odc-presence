<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Cohort;
use App\Models\Justification;
use App\Models\Participant;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReportService
{
    public function __construct(private SettingService $settings, private CohortContext $cohorts) {}

    /** Statistiques d'une journée (tableau de bord, scanner), pour une cohorte ou pour toutes. */
    public function dayStats(Carbon $date, ?Cohort $cohort = null): array
    {
        $day = $date->toDateString();

        $total = Participant::active()->inCohort($cohort)->whereDate('created_at', '<=', $day)->count();

        $counts = Attendance::query()
            ->where('date', $day)
            ->whereIn('participant_id', Participant::active()->inCohort($cohort)->select('id'))
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $present = (int) ($counts[Attendance::PRESENT] ?? 0);
        $late    = (int) ($counts[Attendance::LATE] ?? 0);

        return [
            'total'   => $total,
            'present' => $present,
            'late'    => $late,
            'absent'  => max(0, $total - $present - $late),
            'scanned' => $present + $late,
            'rate'    => $total ? (int) round(($present + $late) * 100 / $total) : 0,
        ];
    }

    /** Lecture des filtres communs aux pages Présences / Rapports / Exports. */
    public function filters(Request $request, string $defaultPeriod = 'today'): array
    {
        $period = (string) $request->input('period', $request->filled('from') ? 'custom' : $defaultPeriod);
        $today  = today();

        [$from, $to] = match ($period) {
            'today'     => [$today->copy(), $today->copy()],
            'yesterday' => [$today->copy()->subDay(), $today->copy()->subDay()],
            'week'      => [$today->copy()->startOfWeek(), $today->copy()],
            'month'     => [$today->copy()->startOfMonth(), $today->copy()],
            default     => [
                $this->parseDate($request->input('from'), $today->copy()->startOfWeek()),
                $this->parseDate($request->input('to'), $today->copy()),
            ],
        };

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        if ($from->diffInDays($to) > 366) {
            $from = $to->copy()->subDays(366);
        }

        $status = in_array($request->input('status'), [Attendance::PRESENT, Attendance::LATE, Attendance::ABSENT], true)
            ? $request->input('status') : null;

        return [
            'period'         => $period,
            'from'           => $from->startOfDay(),
            'to'             => $to->startOfDay(),
            'participant_id' => $request->integer('participant_id') ?: null,
            'status'         => $status,
            'q'              => trim((string) $request->input('q')) ?: null,
            'cohort'         => $this->cohorts->current(),
        ];
    }

    /**
     * Lignes de présence pour une période : présences enregistrées + absences calculées.
     * Les absences ne sont jamais stockées en base.
     */
    public function rows(array $f): Collection
    {
        $cohort = $f['cohort'] ?? null; // null : toutes les cohortes
        $inCohort = fn ($q) => $q->whereIn('participant_id', Participant::withTrashed()->inCohort($cohort)->select('id'));

        $attendances = Attendance::with(['participant', 'badge'])
            ->whereBetween('date', [$f['from']->toDateString(), $f['to']->toDateString()])
            ->when($f['participant_id'], fn ($q, $id) => $q->where('participant_id', $id))
            ->when($cohort, $inCohort)
            ->get();

        $justifications = Justification::query()
            ->whereBetween('date', [$f['from']->toDateString(), $f['to']->toDateString()])
            ->when($f['participant_id'], fn ($q, $id) => $q->where('participant_id', $id))
            ->when($cohort, $inCohort)
            ->get()
            ->keyBy(fn ($j) => $j->participant_id.'|'.$j->date->toDateString());

        $rows = collect();

        if ($f['status'] !== Attendance::ABSENT) {
            foreach ($attendances as $a) {
                if (! $a->participant || ($f['status'] && $a->status !== $f['status'])) {
                    continue;
                }
                $rows->push($this->row($a->date, $a->participant, $a->badge?->number ?? $a->participant->code, $a,
                    $justifications[$a->participant_id.'|'.$a->date->toDateString()] ?? null));
            }
        }

        if (! $f['status'] || $f['status'] === Attendance::ABSENT) {
            $seen = $attendances->mapWithKeys(fn ($a) => [$a->participant_id.'|'.$a->date->toDateString() => true]);

            $participants = Participant::active()
                ->with('cohort')
                ->inCohort($cohort)
                ->when($f['participant_id'], fn ($q, $id) => $q->whereKey($id))
                ->get();

            $defaults = Cohort::defaults();
            $applies  = []; // cache : [cohorte|jour] => bool

            foreach (CarbonPeriod::create($f['from'], $f['to']) as $day) {
                $d = $day->toDateString();

                foreach ($participants as $p) {
                    $schedule = $p->cohort ?? $defaults;
                    $key = ($schedule->id ?? 0).'|'.$d;
                    if (! ($applies[$key] ??= $this->absenceApplies($day, $schedule))) {
                        continue; // pas un jour de formation de sa cohorte, ou heure limite pas encore passée
                    }
                    if ($p->created_at && $p->created_at->toDateString() > $d) {
                        continue; // pas encore inscrit ce jour-là
                    }
                    if (! isset($seen[$p->id.'|'.$d])) {
                        $rows->push($this->row(Carbon::parse($d), $p, $p->code, null, $justifications[$p->id.'|'.$d] ?? null));
                    }
                }
            }
        }

        if ($f['q']) {
            $needle = mb_strtolower(Str::ascii($f['q']));
            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower(Str::ascii($r['name'].' '.$r['badge'])), $needle));
        }

        return $rows->sortBy([['date_key', 'desc'], ['name', 'asc']])->values();
    }

    /**
     * Un participant non scanné est absent : jour de formation passé, ou aujourd'hui après
     * l'heure limite de pointage (à défaut, après l'heure limite d'arrivée).
     */
    public function absenceApplies(Carbon $day, ?Cohort $schedule = null): bool
    {
        $schedule ??= Cohort::defaults();

        if (! $schedule->isTrainingDay($day)) {
            return false;
        }

        $today = today();

        if ($day->lt($today)) {
            return true;
        }

        return $day->isSameDay($today) && now()->gt($schedule->absentFromAt($today));
    }

    public function summary(Collection $rows, array $f): array
    {
        $present = $rows->where('status', Attendance::PRESENT)->count();
        $late    = $rows->where('status', Attendance::LATE)->count();
        $absent  = $rows->where('status', Attendance::ABSENT)->count();

        $attended = $present + $late;
        $expected = $attended + $absent;

        $seconds = $rows->pluck('arrived_seconds')->filter(fn ($s) => $s !== null);

        return [
            'participants' => $f['participant_id'] ? 1 : Participant::active()->inCohort($f['cohort'] ?? null)->count(),
            'present'      => $present,
            'late'         => $late,
            'absent'       => $absent,
            'late_justified'   => $rows->where('status', Attendance::LATE)->where('justified', true)->count(),
            'absent_justified' => $rows->where('status', Attendance::ABSENT)->where('justified', true)->count(),
            'attended'     => $attended,
            'rate'         => $expected ? round($attended * 100 / $expected, 1) : 0,
            'late_total'   => (int) $rows->sum('late_minutes'),
            'avg_arrival'  => $seconds->isNotEmpty() ? gmdate('H:i', (int) round($seconds->avg())) : '—',
            'records'      => $rows->count(),
        ];
    }

    /** Synthèse par participant (page Rapports). */
    public function byParticipant(Collection $rows): Collection
    {
        return $rows->groupBy('participant_id')->map(function (Collection $items) {
            $first   = $items->first();
            $present = $items->where('status', Attendance::PRESENT)->count();
            $late    = $items->where('status', Attendance::LATE)->count();
            $absent  = $items->where('status', Attendance::ABSENT)->count();
            $total   = $present + $late + $absent;

            return [
                'participant_id' => $first['participant_id'],
                'name'           => $first['name'],
                'badge'          => $first['badge'],
                'present'        => $present,
                'late'           => $late,
                'absent'         => $absent,
                'late_total'     => (int) $items->sum('late_minutes'),
                'rate'           => $total ? round(($present + $late) * 100 / $total) : 0,
            ];
        })->sortBy('name')->values();
    }

    public function paginate(Collection $items, Request $request, int $perPage = 25): LengthAwarePaginator
    {
        $page = max(1, (int) $request->input('page', 1));

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    private function row(Carbon $date, Participant $p, ?string $badge, ?Attendance $a, ?Justification $j = null): array
    {
        $status = $a?->status ?? Attendance::ABSENT;

        // Un justificatif ne s'applique qu'à un retard ou une absence.
        $justified = $j && $status !== Attendance::PRESENT;

        return [
            'date'            => $date->copy(),
            'date_key'        => $date->toDateString(),
            'participant_id'  => $p->id,
            'name'            => $p->full_name,
            'badge'           => $badge ?: '—',
            'arrived'         => $a?->arrived_at?->format('H:i'),
            'departed'        => $a?->departed_at?->format('H:i'),
            'arrived_seconds' => $a?->arrived_at
                ? $a->arrived_at->hour * 3600 + $a->arrived_at->minute * 60 + $a->arrived_at->second
                : null,
            'status'          => $status,
            'status_label'    => Attendance::statusLabel($status, $justified),
            'late_minutes'    => (int) ($a?->late_minutes ?? 0),
            'justified'       => $justified,
            'justification'   => $justified ? $j : null,
        ];
    }

    private function parseDate(?string $value, Carbon $fallback): Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('Y-m-d', $value)->startOfDay() : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
