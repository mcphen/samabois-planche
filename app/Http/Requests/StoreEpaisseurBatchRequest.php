<?php

namespace App\Http\Requests;

use App\Models\Epaisseur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class StoreEpaisseurBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rows = collect($this->input('rows', []))
            ->map(function ($row) {
                $intitule = trim((string) data_get($row, 'intitule', ''));
                $slug = trim((string) data_get($row, 'slug', ''));

                return [
                    'intitule' => $intitule,
                    'slug' => Str::slug($slug !== '' ? $slug : $intitule),
                ];
            })
            ->values()
            ->all();

        $this->merge(['rows' => $rows]);
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.intitule' => ['required', 'string', 'max:255'],
            'rows.*.slug' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $rows = collect($this->input('rows', []));

            if ($rows->isEmpty()) {
                return;
            }

            $this->validateRows($validator, $rows);
        });
    }

    private function validateRows(Validator $validator, Collection $rows): void
    {
        $intitules = [];
        $slugs = [];

        foreach ($rows as $index => $row) {
            $normalizedIntitule = mb_strtolower(trim((string) data_get($row, 'intitule', '')));
            $normalizedSlug = mb_strtolower(trim((string) data_get($row, 'slug', '')));

            if ($normalizedSlug === '') {
                $validator->errors()->add("rows.$index.slug", 'Le slug genere est vide. Merci de saisir un intitule plus explicite.');
            }

            if (isset($intitules[$normalizedIntitule])) {
                $validator->errors()->add("rows.$index.intitule", 'Cet intitule est en doublon dans la liste.');
            }

            if (isset($slugs[$normalizedSlug])) {
                $validator->errors()->add("rows.$index.slug", 'Ce slug est en doublon dans la liste.');
            }

            $intitules[$normalizedIntitule] = true;
            $slugs[$normalizedSlug] = true;
        }

        $existingIntitules = Epaisseur::query()
            ->whereIn('intitule', $rows->pluck('intitule'))
            ->pluck('intitule')
            ->map(fn (string $intitule) => mb_strtolower(trim($intitule)))
            ->flip();

        $existingSlugs = Epaisseur::query()
            ->whereIn('slug', $rows->pluck('slug'))
            ->pluck('slug')
            ->map(fn (string $slug) => mb_strtolower(trim($slug)))
            ->flip();

        foreach ($rows as $index => $row) {
            if ($existingIntitules->has(mb_strtolower(trim((string) data_get($row, 'intitule', ''))))) {
                $validator->errors()->add("rows.$index.intitule", 'Cet intitule existe deja.');
            }

            if ($existingSlugs->has(mb_strtolower(trim((string) data_get($row, 'slug', ''))))) {
                $validator->errors()->add("rows.$index.slug", 'Ce slug existe deja.');
            }
        }
    }
}
