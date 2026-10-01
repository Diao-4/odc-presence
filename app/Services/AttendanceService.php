<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Badge;
use App\Models\Participant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Cœur métier : traitement d'un scan de badge.
 * QR Code → badge → participant → date/heure SERVEUR → comparaison heure limite → Présent / Retard.
 */
class AttendanceService
{
    public function __construct(
        private SettingService $settings,
        private ReportService $reports,
    ) {}

    public function scan(string $code, User $agent, string $mode = 'arrival', bool $manual = false): array
    {
        $code  = strtoupper(trim($code));
        $badge = $this->findBadge($code, $manual);

        if (! $badge) {
            return $this->payload('invalid', 'Badge invalide ou désactivé', 'Ce code ne correspond à aucun badge enregistré.');
        }

        $participant = $badge->participant;

        if (! $badge->isActive()) {
            $reason = $badge->status === Badge::REPLACED
                ? 'Ce badge a été remplacé par un nouveau badge.'
                : 'Ce badge a été désactivé.';

            return $this->payload('invalid', 'Badge invalide ou désactivé', $reason, $participant, $badge);
        }

        if (! $participant || $participant->trashed() || ! $participant->is_active) {
            return $this->payload('invalid', 'Badge invalide ou désactivé', 'Le participant associé est désactivé.', $participant, $badge);
        }

        if ($manual) {
            ActivityLogger::log('scan.manual', "Saisie manuelle du badge {$badge->number} ({$participant->full_name})");
        }

        // L'heure vient TOUJOURS du serveur, jamais du navigateur.
        $now = now();

        return $mode === 'departure'
            ? $this->departure($participant, $badge, $agent, $now)
            : $this->arrival($participant, $badge, $agent, $now);
    }

    /**
     * Règle du retard : comparaison à la minute.
     * 08:00 → Présent ; 08:01 → Retard 1 min ; 08:35 → Retard 35 min.
     */
    public function evaluate(Carbon $at): array
    {
        $arrival  = $at->copy()->startOfMinute();
        $deadline = $this->settings->cutoffAt($at);

        if (! $this->settings->bool('late_enabled') || $arrival->lte($deadline)) {
            return [Attendance::PRESENT, 0];
        }

        return [Attendance::LATE, (int) abs($deadline->diffInMinutes($arrival))];
    }

    private function arrival(Participant $p, Badge $b, User $agent, Carbon $now): array
    {
        $today = $now->toDateString();

        try {
            return DB::transaction(function () use ($p, $b, $agent, $now, $today) {
                $existing = Attendance::where('participant_id', $p->id)->where('date', $today)->lockForUpdate()->first();

                if ($existing) {
                    return $this->payload('duplicate', 'Présence déjà enregistrée', null, $p, $b, $existing);
                }

                // Heure limite de pointage dépassée : plus d'arrivée possible, le participant reste absent.
                $deadline = $this->settings->absenceDeadlineAt($now);
                if ($deadline && $now->copy()->startOfMinute()->gt($deadline)) {
                    return $this->payload('invalid', 'Pointage clos', "L'heure limite de pointage ({$deadline->format('H:i')}) est dépassée : le participant est déclaré absent. L'absence peut être justifiée par un administrateur.", $p, $b);
                }

                [$status, $late] = $this->evaluate($now);

                $attendance = Attendance::create([
                    'participant_id' => $p->id,
                    'badge_id'       => $b->id,
                    'date'           => $today,
                    'arrived_at'     => $now,
                    'status'         => $status,
                    'late_minutes'   => $late,
                    'scanned_by'     => $agent->id,
                ]);

                return $status === Attendance::LATE
                    ? $this->payload('late', 'Retard enregistré', null, $p, $b, $attendance)
                    : $this->payload('present', 'Présence enregistrée', null, $p, $b, $attendance);
            });
        } catch (UniqueConstraintViolationException) {
            // Deux scans simultanés du même badge : le second devient un « déjà enregistré ».
            $existing = Attendance::where('participant_id', $p->id)->where('date', $today)->first();

            return $this->payload('duplicate', 'Présence déjà enregistrée', null, $p, $b, $existing);
        }
    }

    private function departure(Participant $p, Badge $b, User $agent, Carbon $now): array
    {
        if (! $this->settings->bool('departure_enabled')) {
            return $this->payload('error', 'Départs désactivés', "L'enregistrement des départs est désactivé dans les paramètres.", $p, $b);
        }

        return DB::transaction(function () use ($p, $b, $agent, $now) {
            $attendance = Attendance::where('participant_id', $p->id)
                ->where('date', $now->toDateString())
                ->lockForUpdate()
                ->first();

            if (! $attendance) {
                return $this->payload('error', 'Aucune arrivée enregistrée', "Ce participant n'a pas été scanné à l'arrivée aujourd'hui.", $p, $b);
            }

            if ($attendance->departed_at) {
                return $this->payload('duplicate', 'Départ déjà enregistré', null, $p, $b, $attendance);
            }

            $attendance->update(['departed_at' => $now, 'departure_scanned_by' => $agent->id]);

            return $this->payload('departure', 'Départ enregistré', null, $p, $b, $attendance);
        });
    }

    private function findBadge(string $code, bool $manual): ?Badge
    {
        $badge = Badge::with('participant')->where('token', $code)->first();

        // En saisie manuelle uniquement, on accepte aussi le numéro imprimé (ODC-00452).
        if (! $badge && $manual && $code !== '') {
            $badge = Badge::with('participant')->where('number', $code)->where('status', Badge::ACTIVE)->latest('id')->first()
                ?? Badge::with('participant')->where('number', $code)->latest('id')->first();
        }

        return $badge;
    }

    private function payload(string $result, string $title, ?string $message, ?Participant $p = null, ?Badge $b = null, ?Attendance $a = null): array
    {
        return [
            'result'       => $result,
            'ok'           => in_array($result, ['present', 'late', 'departure'], true),
            'title'        => $title,
            'message'      => $message,
            'participant'  => $p ? [
                'name'         => $p->full_name,
                'badge'        => $b?->number ?? $p->code,
                'organization' => $p->organization ?: config('odc.organization'),
                'function'     => $p->function ?: '—',
                'photo'        => $p->photo_url,
                'initials'     => $p->initials,
            ] : null,
            'arrived_at'   => $a?->arrived_at?->format('H:i'),
            'departed_at'  => $a?->departed_at?->format('H:i'),
            'status'       => $a?->status,
            'status_label' => $a ? Attendance::statusLabel($a->status) : null,
            'late_minutes' => (int) ($a?->late_minutes ?? 0),
            'server_time'  => now()->format('H:i:s'),
            'stats'        => $this->reports->dayStats(today()),
        ];
    }
}
