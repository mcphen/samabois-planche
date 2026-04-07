<?php

namespace App\Http\Requests;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class StoreSupplierBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rows = collect($this->input('rows', []))
            ->map(function ($row) {
                $name = trim((string) data_get($row, 'name', ''));

                return [
                    'name' => $name,
                    'slug_name' => Str::slug($name),
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
            'rows.*.name' => ['required', 'string', 'max:255'],
            'rows.*.slug_name' => ['required', 'string', 'max:255'],
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
        $names = [];
        $slugs = [];

        foreach ($rows as $index => $row) {
            $normalizedName = mb_strtolower(trim((string) data_get($row, 'name', '')));
            $normalizedSlug = mb_strtolower(trim((string) data_get($row, 'slug_name', '')));

            if (isset($names[$normalizedName])) {
                $validator->errors()->add("rows.$index.name", 'Ce fournisseur est en doublon dans la liste.');
            }

            if (isset($slugs[$normalizedSlug])) {
                $validator->errors()->add("rows.$index.name", 'Le slug genere est en doublon dans la liste.');
            }

            $names[$normalizedName] = true;
            $slugs[$normalizedSlug] = true;
        }

        $existingSlugs = Supplier::query()
            ->whereIn('slug_name', $rows->pluck('slug_name'))
            ->pluck('slug_name')
            ->map(fn (string $slug) => mb_strtolower(trim($slug)))
            ->flip();

        foreach ($rows as $index => $row) {
            if ($existingSlugs->has(mb_strtolower(trim((string) data_get($row, 'slug_name', ''))))) {
                $validator->errors()->add("rows.$index.name", 'Ce fournisseur existe deja.');
            }
        }
    }
}
