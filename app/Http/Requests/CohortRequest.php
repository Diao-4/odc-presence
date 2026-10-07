<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CohortRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => preg_replace('/\s+/', ' ', trim((string) $this->input('name')))]);
    }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:100', Rule::unique('cohorts', 'name')->ignore($this->route('cohort')?->id)],
            'program'         => ['required', 'string', 'max:100'],
            'starts_on'       => ['nullable', 'date_format:Y-m-d'],
            'ends_on'         => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'cutoff_time'     => ['required', 'date_format:H:i'],
            'absence_time'    => ['nullable', 'date_format:H:i', 'after:cutoff_time'],
            'training_days'   => ['required', 'array', 'min:1'],
            'training_days.*' => ['integer', 'between:1,7'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'          => 'nom de la cohorte',
            'program'       => 'programme',
            'starts_on'     => 'date de début',
            'ends_on'       => 'date de fin',
            'cutoff_time'   => "heure limite d'arrivée",
            'absence_time'  => 'heure limite de pointage',
            'training_days' => 'jours de formation',
        ];
    }

    /** Données prêtes à enregistrer. */
    public function cohortData(): array
    {
        $data = $this->safe()->except('training_days');
        $data['training_days'] = collect($this->validated('training_days'))->map(fn ($d) => (int) $d)->unique()->sort()->implode(',');
        $data['absence_time'] = $data['absence_time'] ?? null;

        return $data;
    }
}
