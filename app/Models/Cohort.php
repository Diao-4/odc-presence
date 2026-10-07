<?php

namespace App\Models;

use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cohorte de formation (ex. AWS re/Start P12 — Matin) : ses participants, ses agents
 * et ses horaires (heure limite d'arrivée, heure limite de pointage, jours de formation).
 */
class Cohort extends Model
{
    public const DAYS = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 7 => 'Dim'];

    protected $fillable = [
        'name', 'program', 'starts_on', 'ends_on',
        'cutoff_time', 'absence_time', 'training_days', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on'   => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** Horaires par défaut (paramètres) : utilisés pour pré-remplir une nouvelle cohorte. */
    public static function defaults(): self
    {
        $s = app(SettingService::class);

        return new self([
            'program'       => config('odc.program'),
            'cutoff_time'   => $s->cutoffTime(),
            'absence_time'  => $s->absenceTime(),
            'training_days' => (string) $s->get('training_days', '1,2,3,4,5'),
            'is_active'     => true,
        ]);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_AGENT);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** « AWS re/Start P12 — Matin » */
    public function getLabelAttribute(): string
    {
        return trim($this->program.' '.$this->name);
    }

    /** Fichiers exportés : ODC_AWS_ReStart_P12_Matin */
    public function getFileSlugAttribute(): string
    {
        return 'ODC_'.trim(preg_replace('/[^A-Za-z0-9]+/', '_', \Illuminate\Support\Str::ascii($this->label)), '_');
    }

    // ----- Horaires -----

    public function cutoffAt(Carbon $day): Carbon
    {
        return Carbon::parse($day->toDateString().' '.($this->cutoff_time ?: '08:00').':00');
    }

    public function absenceDeadlineAt(Carbon $day): ?Carbon
    {
        return $this->absence_time ? Carbon::parse($day->toDateString().' '.$this->absence_time.':00') : null;
    }

    /** Moment à partir duquel les non-scannés du jour sont comptés absents. */
    public function absentFromAt(Carbon $day): Carbon
    {
        return $this->absenceDeadlineAt($day) ?? $this->cutoffAt($day);
    }

    public function trainingDays(): array
    {
        return array_map('intval', array_filter(explode(',', (string) $this->training_days)));
    }

    /** Jour de formation : bon jour de la semaine et compris entre les dates de début et de fin. */
    public function isTrainingDay(Carbon $day): bool
    {
        if ($this->starts_on && $day->lt($this->starts_on->copy()->startOfDay())) {
            return false;
        }
        if ($this->ends_on && $day->gt($this->ends_on->copy()->endOfDay())) {
            return false;
        }

        return in_array($day->dayOfWeekIso, $this->trainingDays(), true);
    }

    public function trainingDaysLabel(): string
    {
        return collect($this->trainingDays())->map(fn ($d) => self::DAYS[$d] ?? '')->implode(', ');
    }
}
