<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code'   => ['required', 'string', 'max:100'],
            'mode'   => ['nullable', 'in:arrival,departure'],
            'manual' => ['nullable', 'boolean'],
        ];
    }
}
