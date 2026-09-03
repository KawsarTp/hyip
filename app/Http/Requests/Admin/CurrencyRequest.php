<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdminArea() === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $currency = $this->route('currency');

        return [
            'code' => ['required', 'string', 'size:3', 'alpha', 'uppercase', Rule::unique('currencies', 'code')->ignore($currency)],
            'name' => ['required', 'string', 'max:80'],
            'symbol' => ['required', 'string', 'max:10'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:8'],
            'rate_to_default' => ['required', 'numeric', 'gt:0', 'max:1000000000'],
            'rate_source' => ['nullable', 'string', 'max:80'],
            'symbol_position' => ['required', Rule::in(['prefix', 'suffix'])],
            'thousands_separator' => ['required', 'string', 'max:2'],
            'decimal_separator' => ['required', 'string', 'max:2'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_enabled' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
