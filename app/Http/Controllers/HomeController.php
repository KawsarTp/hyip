<?php

namespace App\Http\Controllers;

use App\DepositStatus;
use App\Models\CmsPage;
use App\Models\Deposit;
use App\Models\InvestmentPlan;
use App\Models\PaymentGateway;
use App\Models\ReferralSetting;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Withdrawal;
use App\PlanStatus;
use App\Services\LocalizationService;
use App\WithdrawalStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class HomeController extends Controller
{
    public function __invoke(LocalizationService $localization): View
    {
        $defaultCurrency = $localization->defaultCurrencyCode();
        $site = collect();
        $plans = collect();
        $deposits = collect();
        $withdrawals = collect();
        $gateways = collect();
        $news = collect();
        $testimonials = collect();
        $legalPages = collect();
        $referralSetting = null;
        $stats = [
            'members' => 0,
            'online_days' => 0,
            'deposited_minor' => 0,
            'withdrawn_minor' => 0,
        ];

        try {
            $site = SiteSetting::query()->pluck('value', 'key');
            $plans = InvestmentPlan::query()->where('status', PlanStatus::Published->value)->orderBy('sort_order')->orderBy('id')->limit(6)->get();
            $deposits = Deposit::query()->with(['user', 'paymentIntent.paymentGateway'])->where('status', DepositStatus::Completed->value)->latest()->limit(6)->get();
            $withdrawals = Withdrawal::query()->with(['user', 'withdrawalMethod'])->where('status', WithdrawalStatus::Completed->value)->latest()->limit(6)->get();
            $gateways = PaymentGateway::query()->where('is_enabled', true)->orderBy('sort_order')->limit(8)->get();
            $news = CmsPage::query()
                ->where('status', 'published')
                ->where('slug', 'not like', 'testimonial-%')
                ->whereNotIn('slug', ['risk-disclosure', 'terms-and-conditions', 'privacy-policy'])
                ->where(function (Builder $query): void {
                    $query->whereNull('published_at')->orWhere('published_at', '<=', now());
                })
                ->latest('published_at')
                ->limit(3)
                ->get();
            $testimonials = CmsPage::query()->where('status', 'published')->where('slug', 'like', 'testimonial-%')->where(function (Builder $query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })->latest('published_at')->limit(3)->get();
            $legalPages = CmsPage::query()
                ->where('status', 'published')
                ->whereIn('slug', ['risk-disclosure', 'terms-and-conditions', 'privacy-policy'])
                ->where(function (Builder $query): void {
                    $query->whereNull('published_at')->orWhere('published_at', '<=', now());
                })
                ->get()
                ->keyBy('slug');
            $referralSetting = ReferralSetting::query()->first();
            $stats['members'] = User::query()->where('status', 'active')->count();
            $firstUser = User::query()->oldest('created_at')->first();
            $stats['online_days'] = $firstUser?->created_at
                ? $firstUser->created_at->diffInDays(now()) + 1
                : 0;
            $stats['deposited_minor'] = $this->totalInDefaultCurrency(Deposit::class, 'amount_minor', DepositStatus::Completed->value, $defaultCurrency, $localization);
            $stats['withdrawn_minor'] = $this->totalInDefaultCurrency(Withdrawal::class, 'gross_amount_minor', WithdrawalStatus::Completed->value, $defaultCurrency, $localization);
        } catch (Throwable) {
            // The public homepage remains available while an installation is being migrated.
        }

        return view('welcome', compact('site', 'plans', 'deposits', 'withdrawals', 'gateways', 'news', 'testimonials', 'legalPages', 'referralSetting', 'stats', 'defaultCurrency'));
    }

    private function totalInDefaultCurrency(string $model, string $amountColumn, string $status, string $defaultCurrency, LocalizationService $localization): int
    {
        $totals = $model::query()
            ->where('status', $status)
            ->selectRaw("currency, SUM({$amountColumn}) as amount")
            ->groupBy('currency')
            ->get();
        $default = $localization->currency($defaultCurrency);
        $defaultDecimals = $default?->decimal_places ?? 2;
        $totalMinor = 0;

        foreach ($totals as $total) {
            $currency = $localization->currency($total->currency);
            $decimals = $currency?->decimal_places ?? 2;
            $rate = (float) ($currency?->rate_to_default ?: 1);
            $major = ((int) $total->amount) / (10 ** $decimals);
            $totalMinor += (int) round(($major / $rate) * (10 ** $defaultDecimals));
        }

        return $totalMinor;
    }
}
