<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiteSettingsRequest extends FormRequest
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
            'site_name' => ['sometimes', 'required', 'string', 'max:120'],
            'support_email' => ['sometimes', 'required', 'email', 'max:255'],
            'registration_number' => ['sometimes', 'nullable', 'string', 'max:120'],
            'certificate_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'default_currency' => ['sometimes', 'required', 'string', 'size:3', 'uppercase', Rule::exists('currencies', 'code')->where('is_enabled', true)],
            'pagination' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'timezone' => ['sometimes', 'required', 'timezone'],
            'risk_notice' => ['sometimes', 'required', 'string', 'max:2000'],
            'maintenance_mode' => ['sometimes', 'nullable', 'boolean'],
            'logo' => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            'favicon' => ['sometimes', 'nullable', 'file', 'mimes:ico,jpg,jpeg,png,webp,svg', 'max:2048'],
            'remove_logo' => ['sometimes', 'nullable', 'boolean'],
            'remove_favicon' => ['sometimes', 'nullable', 'boolean'],
            'facebook_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'twitter_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'instagram_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'linkedin_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'youtube_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'telegram_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'whatsapp_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'cookie_consent_enabled' => ['sometimes', 'nullable', 'boolean'],
            'cookie_consent_title' => ['sometimes', 'nullable', 'string', 'max:120'],
            'cookie_consent_message' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'cookie_policy_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'cookie_accept_label' => ['sometimes', 'nullable', 'string', 'max:40'],
            'captcha_enabled' => ['sometimes', 'nullable', 'boolean'],
            'captcha_provider' => ['sometimes', 'nullable', Rule::in(['recaptcha', 'hcaptcha', 'turnstile'])],
            'captcha_site_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'captcha_secret_key' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'live_chat_enabled' => ['sometimes', 'nullable', 'boolean'],
            'live_chat_provider' => ['sometimes', 'nullable', Rule::in(['tawk', 'crisp', 'intercom', 'custom'])],
            'live_chat_code' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ];
    }
}
