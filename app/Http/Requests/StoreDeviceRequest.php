<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->value !== 'CLIENT';
    }

    public function rules(): array
    {
        return [
            'license_id' => ['required', 'integer', 'exists:licenses,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'hwid' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['active', 'blocked'])],
        ];
    }
}
