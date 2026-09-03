<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmailSettingsRequest;
use App\Http\Requests\Admin\SiteSettingsRequest;
use App\Http\Requests\Admin\SocialAuthSettingsRequest;
use App\Models\Currency;
use App\Models\EmailSetting;
use App\Models\SiteSetting;
use App\Models\SocialAuthSetting;
use App\Services\LocalizationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index(LocalizationService $localization): View
    {
        $site = SiteSetting::query()->pluck('value', 'key');
        $email = EmailSetting::query()->first() ?? new EmailSetting(['mailer' => 'smtp', 'port' => 587, 'from_address' => 'no-reply@example.com', 'from_name' => 'Arcadia']);
        $socialAuth = SocialAuthSetting::query()->get()->keyBy('provider');

        $currencies = $localization->enabledCurrencies();

        return view('admin.settings.index', compact('site', 'email', 'currencies', 'socialAuth'));
    }

    public function updateSite(SiteSettingsRequest $request, LocalizationService $localization): RedirectResponse
    {
        $validated = $request->validated();
        $this->saveSiteSettings($validated, ['site_name', 'support_email', 'registration_number', 'certificate_url', 'default_currency', 'pagination', 'timezone', 'risk_notice', 'maintenance_mode'], 'general');

        if (isset($validated['default_currency'])) {
            $localization->setDefaultCurrency(Currency::query()->where('code', $validated['default_currency'])->firstOrFail());
        }

        return back()->with('status', 'General settings updated.')->with('settings_tab', 'general');
    }

    public function updateBranding(SiteSettingsRequest $request): RedirectResponse
    {
        $this->saveBrandingFile($request, 'logo', 'logo_path', 'remove_logo');
        $this->saveBrandingFile($request, 'favicon', 'favicon_path', 'remove_favicon');

        return back()->with('status', 'Branding settings updated.')->with('settings_tab', 'branding');
    }

    public function updateSocial(SiteSettingsRequest $request): RedirectResponse
    {
        $this->saveSiteSettings($request->validated(), ['facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url', 'youtube_url', 'telegram_url', 'whatsapp_url'], 'social');

        return back()->with('status', 'Social media settings updated.')->with('settings_tab', 'social');
    }

    public function updateSocialAuth(SocialAuthSettingsRequest $request): RedirectResponse
    {
        foreach ($request->validated('providers') as $provider => $settings) {
            $socialAuth = SocialAuthSetting::query()->firstOrNew(['provider' => $provider]);
            $socialAuth->fill([
                'client_id' => $settings['client_id'] ?? null,
                'redirect_url' => $settings['redirect_url'] ?? null,
                'is_enabled' => (bool) ($settings['is_enabled'] ?? false),
            ]);

            if (filled($settings['client_secret'] ?? null)) {
                $socialAuth->client_secret = $settings['client_secret'];
            }

            $socialAuth->save();
        }

        return back()->with('status', 'Social authentication settings updated.')->with('settings_tab', 'social');
    }

    public function updateCookie(SiteSettingsRequest $request): RedirectResponse
    {
        $this->saveSiteSettings($request->validated(), ['cookie_consent_enabled', 'cookie_consent_title', 'cookie_consent_message', 'cookie_policy_url', 'cookie_accept_label'], 'cookie');

        return back()->with('status', 'Cookie consent settings updated.')->with('settings_tab', 'cookie');
    }

    public function updateExtensions(SiteSettingsRequest $request): RedirectResponse
    {
        $this->saveSiteSettings($request->validated(), ['captcha_enabled', 'captcha_provider', 'captcha_site_key', 'captcha_secret_key', 'live_chat_enabled', 'live_chat_provider', 'live_chat_code'], 'extensions');

        return back()->with('status', 'Extension settings updated.')->with('settings_tab', 'extensions');
    }

    public function updateEmail(EmailSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $email = EmailSetting::query()->firstOrNew(['id' => 1]);
        $attributes = collect($validated)->except('password')->all();
        if (filled($validated['password'] ?? null)) {
            $attributes['password'] = $validated['password'];
        }
        $email->fill($attributes);
        $email->save();

        return back()->with('status', 'Email transport settings updated.')->with('settings_tab', 'email');
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $keys
     */
    private function saveSiteSettings(array $values, array $keys, string $group): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            $isBoolean = str_ends_with($key, '_enabled') || $key === 'maintenance_mode';
            SiteSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $isBoolean ? ($values[$key] ? '1' : '0') : (string) ($values[$key] ?? ''),
                    'type' => $isBoolean ? 'boolean' : 'string',
                    'group_name' => $group,
                ],
            );
        }
    }

    private function saveBrandingFile(SiteSettingsRequest $request, string $input, string $settingKey, string $removeInput): void
    {
        $oldPath = SiteSetting::query()->where('key', $settingKey)->value('value');

        if ($request->boolean($removeInput) || $request->hasFile($input)) {
            if (is_string($oldPath) && str_starts_with($oldPath, 'site-branding/')) {
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->hasFile($input)
                ? $request->file($input)->store('site-branding', 'public')
                : null;

            SiteSetting::query()->updateOrCreate(
                ['key' => $settingKey],
                ['value' => $path, 'type' => 'string', 'group_name' => 'branding'],
            );
        }
    }
}
