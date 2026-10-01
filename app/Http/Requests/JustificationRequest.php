<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JustificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'participant_id'  => ['required', 'integer', 'exists:participants,id'],
            'date'            => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'reason'          => ['required', 'string', 'max:1000'],
            'document'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'remove_document' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'reason'   => 'motif',
            'document' => 'justificatif',
        ];
    }
}
