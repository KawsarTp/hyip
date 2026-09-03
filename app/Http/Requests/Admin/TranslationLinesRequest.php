<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TranslationLinesRequest extends FormRequest
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
        return [
            'translations' => ['nullable', 'array', 'max:1000'],
            'translations.*.group_name' => ['required', 'string', 'max:80'],
            'translations.*.key' => ['required', 'string', 'max:191'],
            'translations.*.value' => ['nullable', 'string', 'max:100000'],
        ];
    }
}
