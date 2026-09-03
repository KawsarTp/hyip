<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Language;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class LocalizationService
{
    public function enabledLanguages(): Collection
    {
        try {
            return Language::query()
                ->where('is_enabled', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        } catch (Throwable) {
            return new Collection;
        }
    }

    public function enabledCurrencies(): Collection
    {
        try {
            return Currency::query()
                ->where('is_enabled', true)
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get();
        } catch (Throwable) {
            return new Collection;
        }
    }

    public function defaultLanguageCode(): string
    {
        try {
            return (string) (Language::query()->where('is_enabled', true)->where('is_default', true)->value('code')
                ?? config('app.fallback_locale', 'en'));
        } catch (Throwable) {
            return (string) config('app.fallback_locale', 'en');
        }
    }

    public function defaultCurrencyCode(): string
    {
        try {
            $currency = Currency::query()->where('is_enabled', true)->where('is_default', true)->value('code');

            if ($currency !== null) {
                return (string) $currency;
            }

            $setting = SiteSetting::query()->where('key', 'default_currency')->value('value');

            return (string) ($setting ?: 'USD');
        } catch (Throwable) {
            return 'USD';
        }
    }

    public function language(string $code): ?Language
    {
        try {
            return Language::query()->where('code', $code)->where('is_enabled', true)->first();
        } catch (Throwable) {
            return null;
        }
    }

    public function currency(?string $code = null): ?Currency
    {
        try {
            return Currency::query()
                ->where('code', strtoupper($code ?: $this->defaultCurrencyCode()))
                ->where('is_enabled', true)
                ->first();
        } catch (Throwable) {
            return null;
        }
    }

    public function currentCurrencyCode(): string
    {
        $requested = session('currency_code');
        $userCurrency = auth()->user()?->currency_code;

        return strtoupper((string) ($requested ?: $userCurrency ?: $this->defaultCurrencyCode()));
    }

    /**
     * Resolve a database translation while retaining a readable fallback.
     *
     * @param  array<string, scalar|null>  $replace
     */
    public function text(string $key, string $fallback, array $replace = []): string
    {
        $translated = __($key, $replace);

        return is_string($translated) && $translated !== $key ? $translated : $fallback;
    }

    public function formatMinor(int $amountMinor, ?string $currencyCode = null): string
    {
        $currency = $this->currency($currencyCode);

        if ($currency === null) {
            return strtoupper($currencyCode ?: $this->defaultCurrencyCode()).' '.number_format($amountMinor / 100, 2, '.', '');
        }

        $divisor = 10 ** $currency->decimal_places;

        return $currency->format($amountMinor / $divisor);
    }

    public function setDefaultLanguage(Language $language): void
    {
        DB::transaction(function () use ($language): void {
            Language::query()->update(['is_default' => false]);
            $language->update(['is_enabled' => true, 'is_default' => true]);
        });
    }

    public function setDefaultCurrency(Currency $currency): void
    {
        DB::transaction(function () use ($currency): void {
            Currency::query()->update(['is_default' => false]);
            $currency->update(['is_enabled' => true, 'is_default' => true]);
            SiteSetting::query()->updateOrCreate(
                ['key' => 'default_currency'],
                ['value' => $currency->code, 'type' => 'string', 'group_name' => 'general'],
            );
        });
    }
}
