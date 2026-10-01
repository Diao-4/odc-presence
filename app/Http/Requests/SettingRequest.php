<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'cutoff_time'        => ['required', 'date_format:H:i'],
            'absence_time'       => ['nullable', 'date_format:H:i', 'after:cutoff_time'],
            'default_export'     => ['required', 'in:pdf,xlsx,csv'],
            'training_days'      => ['required', 'array', 'min:1'],
            'training_days.*'    => ['integer', 'between:1,7'],
            'scan_reset_seconds' => ['required', 'integer', 'between:1,30'],
            'badge_validity'     => ['nullable', 'string', 'max:100'],
            'badge_size'         => ['required', 'in:'.implode(',', array_keys(\App\Services\BadgeService::SIZES))],
        ];
    }

    public function attributes(): array
    {
        return [
            'cutoff_time' => "heure limite d'arrivée",
            'absence_time' => 'heure limite de pointage',
            'training_days' => 'jours de formation',
            'scan_reset_seconds' => 'délai de retour au scanner',
        ];
    }
}
