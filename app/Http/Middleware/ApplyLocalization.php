<?php

namespace App\Http\Middleware;

use App\Services\LocalizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyLocalization
{
    public function __construct(private readonly LocalizationService $localization) {}

    public function handle(Request $request, Closure $next): Response
    {
        $requestedLocale = session('locale') ?: $request->user()?->locale ?: $this->localization->defaultLanguageCode();
        $language = $this->localization->language((string) $requestedLocale);
        $locale = $language?->code ?: $this->localization->defaultLanguageCode();

        app()->setLocale($locale);
        $request->setLocale($locale);

        return $next($request);
    }
}
