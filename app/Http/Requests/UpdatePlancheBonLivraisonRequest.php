<?php

namespace App\Http\Requests;

use App\Models\PlancheBonLivraison;
use App\Models\PlancheDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class UpdatePlancheBonLivraisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'date_livraison' => ['required', 'date'],
            'statut' => ['required', 'in:brouillon,valide'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.planche_detail_id' => ['required', 'integer', 'distinct', 'exists:planche_details,id'],
            'lignes.*.quantite_livree' => ['required', 'integer', 'min:1'],
            'lignes.*.prix_unitaire' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $lignes = collect($this->input('lignes', []));

            if ($lignes->isEmpty()) {
                return;
            }

            $bonLivraison = $this->route('plancheBonLivraison');
            $bonLivraisonId = $bonLivraison instanceof PlancheBonLivraison
                ? $bonLivraison->id
                : (int) $bonLivraison;

            $details = PlancheDetail::query()
                ->whereIn('id', $lignes->pluck('planche_detail_id')->filter())
                ->get(['id', 'quantite_prevue'])
                ->keyBy('id');

            $livrees = DB::table('planche_bon_livraison_lignes')
                ->select('planche_detail_id', DB::raw('SUM(quantite_livree) as total_livree'))
                ->whereIn('planche_detail_id', $details->keys())
                ->when($bonLivraisonId > 0, function ($query) use ($bonLivraisonId) {
                    $query->where('planche_bon_livraison_id', '!=', $bonLivraisonId);
                })
                ->groupBy('planche_detail_id')
                ->pluck('total_livree', 'planche_detail_id');

            foreach ($lignes as $index => $ligne) {
                $detailId = (int) ($ligne['planche_detail_id'] ?? 0);
                $quantiteLivree = (int) ($ligne['quantite_livree'] ?? 0);
                $detail = $details->get($detailId);

                if (!$detail) {
                    continue;
                }

                $quantiteDisponible = max(
                    (int) $detail->quantite_prevue - (int) ($livrees[$detailId] ?? 0),
                    0
                );

                if ($quantiteLivree > $quantiteDisponible) {
                    $validator->errors()->add(
                        "lignes.$index.quantite_livree",
                        'La quantite livree depasse le disponible pour cette ligne.'
                    );
                }
            }
        });
    }
}
