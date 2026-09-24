<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $u = $this->route('user');

        return ['partner_id' => ['nullable', 'integer', 'exists:partners,id'], 'name' => ['required', 'string', 'max:120'], 'username' => ['required', 'alpha_dash', 'max:80', Rule::unique('users')->ignore($u)], 'email' => ['nullable', 'email', Rule::unique('users')->ignore($u)], 'password' => ['nullable', 'confirmed', Password::defaults()], 'status' => ['required', Rule::in(['active', 'blocked'])]];
    }
}
