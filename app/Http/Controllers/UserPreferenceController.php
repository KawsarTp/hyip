<?php

namespace App\Http\Controllers;

use App\Services\LocalizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserPreferenceController extends Controller
{
    public function updateLocale(Request $request, LocalizationService $localization): RedirectResponse
    {
        $validated = $request->validate(['locale' => ['required', 'string', 'max:10']]);
        $language = $localization->language($validated['locale']);

        if ($language === null) {
            throw ValidationException::withMessages(['locale' => 'The selected language is not available.']);
        }

        $request->user()->update(['locale' => $language->code]);
        $request->session()->put('locale', $language->code);

        return back()->with('status', 'Language preference updated.');
    }

    public function updateCurrency(Request $request, LocalizationService $localization): RedirectResponse
    {
        $validated = $request->validate(['currency_code' => ['required', 'string', 'size:3', 'uppercase']]);
        $currency = $localization->currency($validated['currency_code']);

        if ($currency === null) {
            throw ValidationException::withMessages(['currency_code' => 'The selected currency is not available.']);
        }

        $request->user()->update(['currency_code' => $currency->code]);
        $request->session()->put('currency_code', $currency->code);

        return back()->with('status', 'Currency preference updated.');
    }
}
