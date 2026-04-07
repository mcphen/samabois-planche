<?php

namespace App\Http\Requests;

use App\Models\PlancheCouleur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlancheCouleurRequest extends FormRequest
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
        /** @var PlancheCouleur|null $plancheCouleur */
        $plancheCouleur = $this->route('plancheCouleur');

        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('planche_couleurs', 'code')->ignore($plancheCouleur?->id),
            ],
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
