<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'cohort_id'    => ['required', 'integer', 'exists:cohorts,id'],
            'last_name'    => ['required', 'string', 'max:100'],
            'first_name'   => ['required', 'string', 'max:100'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'email'        => ['nullable', 'email', 'max:150'],
            'organization' => ['nullable', 'string', 'max:150'],
            'function'     => ['nullable', 'string', 'max:100'],
            'photo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'last_name' => 'nom', 'first_name' => 'prénom', 'phone' => 'téléphone',
            'organization' => 'organisation', 'function' => 'fonction', 'photo' => 'photo',
        ];
    }
}
