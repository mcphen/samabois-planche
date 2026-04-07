<?php

namespace App\Http\Requests;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateConfigurationSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name', ''));

        $this->merge([
            'name' => $name,
            'address' => trim((string) $this->input('address', '')) ?: null,
            'phone' => trim((string) $this->input('phone', '')) ?: null,
            'email' => trim((string) $this->input('email', '')) ?: null,
            'slug_name' => Str::slug($name),
        ]);
    }

    public function rules(): array
    {
        /** @var Supplier|null $supplier */
        $supplier = $this->route('supplier');

        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'slug_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'slug_name')->ignore($supplier?->id),
            ],
        ];
    }
}
