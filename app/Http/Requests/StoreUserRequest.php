<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'partner_id' => ['nullable', 'integer', 'exists:partners,id'],
            'username' => ['required', 'alpha_dash', 'max:80', 'unique:users,username'],
            'password' => ['required', Password::defaults()],
            'expiration' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hwid_affected' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'expiration.date_format' => 'Selecciona una fecha válida.',
            'expiration.after_or_equal' => 'La fecha de expiración debe ser hoy o posterior.',
        ];
    }
}
