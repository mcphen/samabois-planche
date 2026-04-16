<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePlancheRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'                              => ['required', 'exists:suppliers,id'],
            'numero_contrat'                           => ['required', 'string', 'max:255'],
            'groupes'                                  => ['required', 'array', 'min:1'],
            'groupes.*.code_couleur'                   => ['required', 'string', 'max:255'],
            'groupes.*.categorie'                      => ['required', 'in:mate,semi_brillant,brillant'],
            'groupes.*.epaisseurs'                     => ['required', 'array', 'min:1'],
            'groupes.*.epaisseurs.*.epaisseur'          => ['required', 'numeric', 'min:0.01'],
            'groupes.*.epaisseurs.*.quantite_prevue'   => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'groupes.required' => 'Ajoutez au moins un groupe.',
            'groupes.min'      => 'Ajoutez au moins un groupe.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $groupes = $this->input('groupes', []);
            $groupesCles = [];
            $epaisseursCles = [];

            foreach ($groupes as $gi => $groupe) {
                $codeCouleur = mb_strtolower(trim((string) ($groupe['code_couleur'] ?? '')));
                $categorie   = (string) ($groupe['categorie'] ?? '');

                if ($codeCouleur === '') {
                    continue;
                }

                // Duplicate (color + category) group in same submission
                $cleGroupe = $codeCouleur . '|' . $categorie;
                if (isset($groupesCles[$cleGroupe])) {
                    $validator->errors()->add(
                        "groupes.$gi.categorie",
                        'Cette combinaison couleur/catégorie est déjà présente dans le formulaire.'
                    );
                }
                $groupesCles[$cleGroupe] = true;

                // Duplicate epaisseur within same group
                foreach ($groupe['epaisseurs'] ?? [] as $ei => $ep) {
                    $epaisseur = number_format((float) ($ep['epaisseur'] ?? 0), 2, '.', '');
                    $cleEp     = $cleGroupe . '|' . $epaisseur;

                    if (isset($epaisseursCles[$cleEp])) {
                        $validator->errors()->add(
                            "groupes.$gi.epaisseurs.$ei.epaisseur",
                            'Une même épaisseur ne peut apparaître qu\'une seule fois pour un groupe couleur/catégorie.'
                        );
                    }

                    $epaisseursCles[$cleEp] = true;
                }
            }
        });
    }
}
