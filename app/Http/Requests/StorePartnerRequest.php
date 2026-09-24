<?php

namespace App\Http\Requests;

use App\Models\Partner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Partner::class);
    }

    public function rules(): array
    {
        return ['project_id' => ['nullable', 'integer', 'exists:projects,id'], 'parent_id' => ['nullable', 'integer', 'exists:partners,id'], 'name' => ['required', 'string', 'max:120'], 'username' => ['required', 'alpha_dash', 'max:80', 'unique:users,username'], 'email' => ['nullable', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'string', 'min:1', 'max:255', 'confirmed'], 'status' => ['required', Rule::in(['active', 'blocked'])]];
    }
}
