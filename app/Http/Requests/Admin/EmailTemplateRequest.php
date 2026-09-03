<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailTemplateRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string', 'max:100000'],
            'body_text' => ['nullable', 'string', 'max:100000'],
            'variables' => ['nullable', 'string', 'max:2000'],
            'locale' => ['required', 'string', 'max:10', Rule::exists('languages', 'code')->where('is_enabled', true)],
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }
}
