<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['username' => mb_strtolower(trim((string) $this->input('username')))]);
    }

    public function rules(): array
    {
        $id = $this->route('user')?->id;

        return [
            'name'     => ['required', 'string', 'max:100'],
            'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($id)],
            'email'    => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            'role'      => ['required', 'in:admin,agent'],
            'cohort_id' => ['nullable', 'required_if:role,agent', 'integer', 'exists:cohorts,id'],
            'password'  => [$id ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return ['cohort_id.required_if' => "Choisissez la cohorte de l'agent."];
    }

    public function attributes(): array
    {
        return ['name' => 'nom', 'username' => 'identifiant', 'role' => 'rôle', 'cohort_id' => 'cohorte', 'password' => 'mot de passe'];
    }

    /** Un administrateur n'est rattaché à aucune cohorte : il les voit toutes. */
    public function userData(): array
    {
        $data = $this->validated();
        if ($data['role'] === 'admin') {
            $data['cohort_id'] = null;
        }

        return $data;
    }
}
