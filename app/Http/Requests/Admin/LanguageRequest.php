<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LanguageRequest extends FormRequest
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
        $language = $this->route('language');

        return [
            'code' => ['required', 'string', 'max:10', 'regex:/^[a-zA-Z]{2,3}(?:[-_][a-zA-Z]{2,4})?$/', Rule::unique('languages', 'code')->ignore($language)],
            'name' => ['required', 'string', 'max:80'],
            'native_name' => ['required', 'string', 'max:80'],
            'direction' => ['required', Rule::in(['ltr', 'rtl'])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_enabled' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
