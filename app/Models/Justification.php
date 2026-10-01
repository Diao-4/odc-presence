<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Justification extends Model
{
    public const LATE   = 'late';
    public const ABSENT = 'absent';

    protected $fillable = [
        'participant_id', 'date', 'type', 'reason',
        'document_path', 'document_name', 'justified_by',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
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

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'justified_by');
    }

    public static function typeLabel(string $type): string
    {
        return $type === self::LATE ? 'Retard' : 'Absence';
    }
}
