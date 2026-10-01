<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public const PRESENT = 'present';
    public const LATE    = 'late';
    public const ABSENT  = 'absent'; // statut calculé, jamais enregistré en base

    protected $fillable = [
        'participant_id', 'badge_id', 'date', 'arrived_at', 'departed_at',
        'status', 'late_minutes', 'scanned_by', 'departure_scanned_by',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'arrived_at'   => 'datetime',
            'departed_at'  => 'datetime',
            'late_minutes' => 'integer',
        ];
    }

    /** Jour stocké en Y-m-d quel que soit le SGBD (SQLite n'a pas de vrai type DATE). */
    public function setDateAttribute(mixed $value): void
    {
        $this->attributes['date'] = Carbon::parse($value)->toDateString();
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class)->withTrashed();
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public static function statusLabel(?string $status, bool $justified = false): string
    {
        return match ($status) {
            self::PRESENT => 'Présent',
            self::LATE    => $justified ? 'Retard justifié' : 'Retard',
            self::ABSENT  => $justified ? 'Absence justifiée' : 'Absent',
            default       => '—',
        };
    }
}
