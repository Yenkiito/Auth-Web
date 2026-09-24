<?php

namespace App\Http\Requests;

class BulkGenerateLicenseRequest extends GenerateLicenseRequest
{
    public function rules(): array
    {
        return ['quantity' => ['required', 'integer', 'min:1'], ...parent::rules()];
    }
}
