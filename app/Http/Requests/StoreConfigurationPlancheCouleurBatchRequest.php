<?php

namespace App\Http\Requests;

use App\Models\PlancheCouleur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

class StoreConfigurationPlancheCouleurBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rows = collect($this->input('rows', []))
            ->map(function ($row) {
                return [
                    'code' => trim((string) data_get($row, 'code', '')),
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
            'rows.*.code' => ['required', 'string', 'max:255'],
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
        $codes = [];

        foreach ($rows as $index => $row) {
            $normalizedCode = mb_strtolower(trim((string) data_get($row, 'code', '')));

            if (isset($codes[$normalizedCode])) {
                $validator->errors()->add("rows.$index.code", 'Ce code couleur est en doublon dans la liste.');
            }

            $codes[$normalizedCode] = true;
        }

        $existingCodes = PlancheCouleur::query()
            ->whereIn('code', $rows->pluck('code'))
            ->pluck('code')
            ->map(fn (string $code) => mb_strtolower(trim($code)))
            ->flip();

        foreach ($rows as $index => $row) {
            if ($existingCodes->has(mb_strtolower(trim((string) data_get($row, 'code', ''))))) {
                $validator->errors()->add("rows.$index.code", 'Ce code couleur existe deja.');
            }
        }
    }
}
