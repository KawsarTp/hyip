<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CurrencyRequest;
use App\Http\Requests\Admin\LanguageRequest;
use App\Http\Requests\Admin\TranslationLinesRequest;
use App\Models\Currency;
use App\Models\Language;
use App\Models\TranslationLine;
use App\Models\User;
use App\Services\LocalizationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LocalizationController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.languages.index');
    }

    public function languages(): View
    {
        $languages = Language::query()->withCount('translations')->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.languages.index', compact('languages'));
    }

    public function currencies(): View
    {
        $currencies = Currency::query()->orderBy('sort_order')->orderBy('code')->get();

        return view('admin.currencies.index', compact('currencies'));
    }

    public function storeLanguage(LanguageRequest $request): RedirectResponse
    {
        $attributes = $this->languageAttributes($request);
        $makeDefault = $request->boolean('is_default') || ! Language::query()->where('is_default', true)->exists();
        $attributes['is_enabled'] = $attributes['is_enabled'] || $makeDefault;

        DB::transaction(function () use ($attributes, $makeDefault): void {
            if ($makeDefault) {
                Language::query()->update(['is_default' => false]);
            }

            Language::query()->create($attributes + ['is_default' => $makeDefault]);
        });

        return back()->with('status', 'Language added successfully.');
    }

    public function updateLanguage(LanguageRequest $request, Language $language): RedirectResponse
    {
        $attributes = $this->languageAttributes($request);

        if (! $request->boolean('is_enabled') && $language->is_default) {
            throw ValidationException::withMessages(['is_enabled' => 'The default language must remain enabled.']);
        }

        DB::transaction(function () use ($attributes, $language, $request): void {
            if ($request->boolean('is_default')) {
                Language::query()->update(['is_default' => false]);
            }

            $language->update($attributes + ['is_default' => $request->boolean('is_default') || $language->is_default]);
        });

        return back()->with('status', 'Language settings updated.');
    }

    public function setDefaultLanguage(Language $language, LocalizationService $localization): RedirectResponse
    {
        $localization->setDefaultLanguage($language);

        return back()->with('status', $language->name.' is now the default language.');
    }

    public function translations(Language $language): View
    {
        $translations = $language->translations()->orderBy('group_name')->orderBy('key')->get();

        return view('admin.languages.translations', compact('language', 'translations'));
    }

    public function updateTranslations(TranslationLinesRequest $request, Language $language): RedirectResponse
    {
        DB::transaction(function () use ($request, $language): void {
            foreach ($request->validated('translations', []) as $translation) {
                $value = trim((string) ($translation['value'] ?? ''));

                if ($value === '') {
                    TranslationLine::query()
                        ->where('language_id', $language->getKey())
                        ->where('group_name', $translation['group_name'])
                        ->where('key', $translation['key'])
                        ->delete();

                    continue;
                }

                TranslationLine::query()->updateOrCreate(
                    [
                        'language_id' => $language->getKey(),
                        'group_name' => $translation['group_name'],
                        'key' => $translation['key'],
                    ],
                    ['value' => $value],
                );
            }
        });

        return back()->with('status', 'Translations saved successfully.');
    }

    public function storeCurrency(CurrencyRequest $request): RedirectResponse
    {
        $attributes = $this->currencyAttributes($request);
        $makeDefault = $request->boolean('is_default') || ! Currency::query()->where('is_default', true)->exists();
        $attributes['is_enabled'] = $attributes['is_enabled'] || $makeDefault;

        DB::transaction(function () use ($attributes, $makeDefault): void {
            if ($makeDefault) {
                Currency::query()->update(['is_default' => false]);
            }

            Currency::query()->create($attributes + ['is_default' => $makeDefault]);
        });

        return back()->with('status', 'Currency added successfully.');
    }

    public function updateCurrency(CurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $attributes = $this->currencyAttributes($request);

        if (! $request->boolean('is_enabled') && $currency->is_default) {
            throw ValidationException::withMessages(['is_enabled' => 'The default currency must remain enabled.']);
        }

        $oldCode = $currency->code;

        DB::transaction(function () use ($attributes, $currency, $request, $oldCode): void {
            if ($request->boolean('is_default')) {
                Currency::query()->update(['is_default' => false]);
            }

            $currency->update($attributes + ['is_default' => $request->boolean('is_default') || $currency->is_default]);

            if ($oldCode !== $currency->code) {
                User::query()->where('currency_code', $oldCode)->update(['currency_code' => $currency->code]);
            }
        });

        return back()->with('status', 'Currency settings updated.');
    }

    public function setDefaultCurrency(Currency $currency, LocalizationService $localization): RedirectResponse
    {
        $localization->setDefaultCurrency($currency);

        return back()->with('status', $currency->code.' is now the default currency.');
    }

    /**
     * @return array<string, mixed>
     */
    private function languageAttributes(LanguageRequest $request): array
    {
        $validated = $request->validated();

        return [
            'code' => strtolower(str_replace('_', '-', $validated['code'])),
            'name' => $validated['name'],
            'native_name' => $validated['native_name'],
            'direction' => $validated['direction'],
            'is_enabled' => $request->boolean('is_enabled'),
            'sort_order' => $validated['sort_order'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function currencyAttributes(CurrencyRequest $request): array
    {
        $validated = $request->validated();

        return [
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'symbol' => $validated['symbol'],
            'decimal_places' => $validated['decimal_places'],
            'rate_to_default' => $validated['rate_to_default'],
            'rate_source' => $validated['rate_source'] ?? null,
            'rate_updated_at' => now(),
            'symbol_position' => $validated['symbol_position'],
            'thousands_separator' => $validated['thousands_separator'],
            'decimal_separator' => $validated['decimal_separator'],
            'is_enabled' => $request->boolean('is_enabled'),
            'sort_order' => $validated['sort_order'],
        ];
    }
}
