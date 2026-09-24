<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('partner'));
    }

    public function rules(): array
    {
        $uid = $this->route('partner')->user_id;

        return ['parent_id' => ['nullable', 'integer', 'exists:partners,id'], 'name' => ['required', 'string', 'max:120'], 'username' => ['required', 'alpha_dash', 'max:80', Rule::unique('users')->ignore($uid)], 'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($uid)], 'password' => ['nullable', 'string', 'min:1', 'max:255', 'confirmed'], 'status' => ['required', Rule::in(['active', 'blocked'])]];
    }
}
