<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SupportReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('support.manage') === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:20000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,txt,doc,docx'],
            'is_internal' => ['nullable', 'boolean'],
        ];
    }
}
