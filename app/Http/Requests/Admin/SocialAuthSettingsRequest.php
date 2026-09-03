<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialAuthSettingsRequest extends FormRequest
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
            'providers' => ['required', 'array'],
            'providers.google' => ['required', 'array'],
            'providers.facebook' => ['required', 'array'],
            'providers.apple' => ['required', 'array'],
            'providers.*.is_enabled' => ['nullable', 'boolean'],
            'providers.*.client_id' => ['nullable', 'string', 'max:255'],
            'providers.*.client_secret' => ['nullable', 'string', 'max:2000'],
            'providers.*.redirect_url' => ['nullable', 'url', 'max:2048'],
            'tab' => ['nullable', Rule::in(['social'])],
        ];
    }
}
