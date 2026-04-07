<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavePlancheLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code_couleur'    => ['required', 'string', 'max:255'],
            'categorie'       => ['required', 'in:mate,semi_brillant,brillant'],
            'epaisseur'       => ['required', 'numeric', 'min:0.01'],
            'quantite_prevue' => ['required', 'integer', 'min:1'],
        ];
    }
}
