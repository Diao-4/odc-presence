<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    public const DEFAULTS = [
        'cutoff_time'        => '08:00',
        'absence_time'       => '', // heure limite de pointage ; vide = pas de limite
        'late_enabled'       => '1',
        'departure_enabled'  => '0',
        'default_export'     => 'pdf',
        'training_days'      => '1,2,3,4,5', // ISO : 1 = lundi … 7 = dimanche
        'scan_reset_seconds' => '3',
        'badge_validity'     => '',
        'badge_size'         => 'a4x9', // taille par défaut des badges téléchargés (voir BadgeService::SIZES)
    ];

    private const CACHE_KEY = 'odc.settings';

    public function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->pluck('value', 'key')->all());

        return array_merge(self::DEFAULTS, $stored);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function bool(string $key): bool
    {
        return (bool) (int) $this->get($key, '0');
    }

    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => is_bool($value) ? (int) $value : $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    public function cutoffTime(): string
    {
        return (string) $this->get('cutoff_time', '08:00');
    }

    /** Heure limite d'arrivée pour un jour donné (heure serveur). */
    public function cutoffAt(Carbon $day): Carbon
    {
        return Carbon::parse($day->toDateString().' '.$this->cutoffTime().':00');
    }

    public function absenceTime(): ?string
    {
        return ((string) $this->get('absence_time', '')) ?: null;
    }

    /** Heure limite de pointage pour un jour donné : au-delà, un participant non scanné est déclaré absent. */
    public function absenceDeadlineAt(Carbon $day): ?Carbon
    {
        $time = $this->absenceTime();

        return $time ? Carbon::parse($day->toDateString().' '.$time.':00') : null;
    }

    /** Moment à partir duquel les non-scannés du jour sont comptés absents. */
    public function absentFromAt(Carbon $day): Carbon
    {
        return $this->absenceDeadlineAt($day) ?? $this->cutoffAt($day);
    }

    public function trainingDays(): array
    {
        return array_map('intval', array_filter(explode(',', (string) $this->get('training_days', ''))));
    }

    public function isTrainingDay(Carbon $day): bool
    {
        return in_array($day->dayOfWeekIso, $this->trainingDays(), true);
    }
}
