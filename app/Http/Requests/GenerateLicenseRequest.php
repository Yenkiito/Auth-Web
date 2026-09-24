<?php

namespace App\Http\Requests;

use App\Models\License;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', License::class);
    }

    public function rules(): array
    {
        return [
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'partner_id' => ['nullable', 'integer', 'exists:partners,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'license_mask' => ['required', 'string', 'min:4', 'max:100', 'regex:/^[A-Za-z0-9*_-]+$/'],
            'subscription' => ['required', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:500'],
            'expiry_unit' => ['required', Rule::in(['seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years', 'lifetime'])],
            'expiry_duration' => ['nullable', 'required_unless:expiry_unit,lifetime', 'integer', 'min:1'],
            'lowercase' => ['sometimes', 'boolean'],
            'uppercase' => ['sometimes', 'boolean'],
            'max_devices' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
