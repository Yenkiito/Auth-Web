<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('project'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('key_prefix')) {
            $this->merge(['key_prefix' => strtoupper((string) $this->input('key_prefix'))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('projects')->ignore($this->route('project'))],
            'key_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/', Rule::unique('projects', 'key_prefix')->ignore($this->route('project'))],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
