<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('license'));
    }

    public function rules(): array
    {
        return ['user_id' => ['nullable', 'integer', 'exists:users,id'], 'status' => ['required', Rule::in(['available', 'active', 'suspended', 'expired', 'revoked'])], 'expires_at' => ['nullable', 'date'], 'max_devices' => ['required', 'integer', 'min:1', 'max:10000']];
    }
}
