<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Project::class);
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
            'key_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/', 'unique:projects,key_prefix'],
            'description' => ['nullable', 'string', 'max:2000'],
            'version' => ['nullable', 'regex:/^\d+\.\d+(?:\.\d+)?$/', 'max:20'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
