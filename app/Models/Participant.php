<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Participant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'first_name', 'last_name', 'phone', 'email',
        'organization', 'function', 'photo_path', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function badges(): HasMany
    {
        return $this->hasMany(Badge::class);
    }

    /** Badge actif courant. */
    public function activeBadge(): HasOne
    {
        return $this->hasOne(Badge::class)->ofMany(['id' => 'max'], fn ($q) => $q->where('status', Badge::ACTIVE));
    }

    /** Dernier badge émis, quel que soit son statut. */
    public function currentBadge(): HasOne
    {
        return $this->hasOne(Badge::class)->latestOfMany();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function justifications(): HasMany
    {
        return $this->hasMany(Justification::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getInitialsAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Recherche multi-mots : chaque mot doit apparaître dans au moins une colonne. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $words = preg_split('/\s+/', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $word) {
            $like = '%'.$word.'%';
            $query->where(fn ($w) => $w
                ->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('organization', 'like', $like));
        }

        return $query;
    }
}
