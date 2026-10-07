<?php

namespace App\Services;

use App\Models\Cohort;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Cohorte affichée dans l'application.
 * Agent : toujours sa propre cohorte. Administrateur : la cohorte choisie dans le sélecteur
 * (mémorisée en session), à défaut la dernière cohorte active.
 */
class CohortContext
{
    private const SESSION_KEY = 'cohort_id';

    private ?Cohort $current = null;

    public function current(): ?Cohort
    {
        if ($this->current) {
            return $this->current;
        }

        $user = auth()->user();

        if ($user && ! $user->isAdmin() && $user->cohort_id) {
            return $this->current = $user->cohort;
        }

        $id = session(self::SESSION_KEY);

        return $this->current = ($id ? Cohort::find($id) : null)
            ?? Cohort::active()->latest('id')->first()
            ?? Cohort::latest('id')->first();
    }

    public function switchTo(Cohort $cohort): void
    {
        session([self::SESSION_KEY => $cohort->id]);
        $this->current = $cohort;
    }

    /** Un agent rattaché à une cohorte n'accède qu'à celle-ci. */
    public function allows(User $user, ?int $cohortId): bool
    {
        return $user->isAdmin() || ! $user->cohort_id || $user->cohort_id === $cohortId;
    }

    /** Cohortes proposées dans le sélecteur (actives d'abord). */
    public function choices(): Collection
    {
        return Cohort::orderByDesc('is_active')->orderByDesc('id')->get();
    }
}
