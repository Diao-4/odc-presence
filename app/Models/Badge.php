<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Badge extends Model
{
    public const ACTIVE   = 'active';
    public const DISABLED = 'disabled';
    public const REPLACED = 'replaced';

    protected $fillable = ['participant_id', 'number', 'token', 'version', 'status', 'issued_at', 'disabled_at'];

    protected function casts(): array
    {
        return [
            'issued_at'   => 'datetime',
            'disabled_at' => 'datetime',
            'version'     => 'integer',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class)->withTrashed();
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::ACTIVE   => 'Actif',
            self::REPLACED => 'Remplacé',
            default        => 'Désactivé',
        };
    }
}
