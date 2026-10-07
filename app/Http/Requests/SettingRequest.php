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
            'default_export'     => ['required', 'in:pdf,xlsx,csv'],
            'scan_reset_seconds' => ['required', 'integer', 'between:1,30'],
            'badge_validity'     => ['nullable', 'string', 'max:100'],
            'badge_size'         => ['required', 'in:'.implode(',', array_keys(\App\Services\BadgeService::SIZES))],
        ];
    }

    public function attributes(): array
    {
        return [
            'scan_reset_seconds' => 'délai de retour au scanner',
        ];
    }
}
