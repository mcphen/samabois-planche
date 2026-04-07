<?php

namespace App\Http\Requests;

use App\Models\Epaisseur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class UpdateEpaisseurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $intitule = trim((string) $this->input('intitule', ''));
        $slug = trim((string) $this->input('slug', ''));

        $this->merge([
            'intitule' => $intitule,
            'slug' => Str::slug($slug !== '' ? $slug : $intitule),
        ]);
    }

    public function rules(): array
    {
        return [
            'intitule' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $epaisseur = $this->route('epaisseur');

            if (!$epaisseur instanceof Epaisseur) {
                return;
            }

            $slug = trim((string) $this->input('slug', ''));
            $intitule = trim((string) $this->input('intitule', ''));

            if ($slug === '') {
                $validator->errors()->add('slug', 'Le slug genere est vide. Merci de saisir un intitule plus explicite.');
            }

            $intituleExists = Epaisseur::query()
                ->where('intitule', $intitule)
                ->whereKeyNot($epaisseur->id)
                ->exists();

            if ($intituleExists) {
                $validator->errors()->add('intitule', 'Cet intitule existe deja.');
            }

            $slugExists = Epaisseur::query()
                ->where('slug', $slug)
                ->whereKeyNot($epaisseur->id)
                ->exists();

            if ($slugExists) {
                $validator->errors()->add('slug', 'Ce slug existe deja.');
            }
        });
    }
}
