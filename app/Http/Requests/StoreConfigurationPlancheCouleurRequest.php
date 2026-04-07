<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConfigurationPlancheCouleurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => trim((string) $this->input('code', '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('planche_couleurs', 'code')],
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
