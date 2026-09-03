<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvestmentPlanRequest;
use App\Models\InvestmentPlan;
use App\Services\LocalizationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class InvestmentPlanController extends Controller
{
    public function index(): View
    {
        $plans = InvestmentPlan::query()->withCount('investments')->orderBy('sort_order')->get();

        return view('admin.investment-plans.index', compact('plans'));
    }

    public function create(LocalizationService $localization): View
    {
        return view('admin.investment-plans.create', ['currencies' => $localization->enabledCurrencies()]);
    }

    public function store(InvestmentPlanRequest $request): RedirectResponse
    {
        InvestmentPlan::query()->create($this->attributes($request));

        return redirect()->route('admin.investment-plans.index')->with('status', 'Investment plan created.');
    }

    public function edit(InvestmentPlan $investmentPlan, LocalizationService $localization): View
    {
        return view('admin.investment-plans.edit', ['plan' => $investmentPlan, 'currencies' => $localization->enabledCurrencies()]);
    }

    public function update(InvestmentPlanRequest $request, InvestmentPlan $investmentPlan): RedirectResponse
    {
        $investmentPlan->update($this->attributes($request, $investmentPlan));

        return redirect()->route('admin.investment-plans.index')->with('status', 'Investment plan version updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(InvestmentPlanRequest $request, ?InvestmentPlan $plan = null): array
    {
        $validated = $request->validated();
        $minimum = $this->amountToMinor($validated['minimum_amount']);
        $maximum = filled($validated['maximum_amount'] ?? null) ? $this->amountToMinor($validated['maximum_amount']) : null;
        $capacity = filled($validated['capacity'] ?? null) ? $this->amountToMinor($validated['capacity']) : null;

        if ($maximum !== null && $maximum < $minimum) {
            throw ValidationException::withMessages(['maximum_amount' => 'The maximum amount must be greater than the minimum amount.']);
        }

        if ($validated['lock_days'] > $validated['term_days']) {
            throw ValidationException::withMessages(['lock_days' => 'The lock period cannot exceed the plan term.']);
        }

        if ($capacity !== null && $plan !== null && $capacity < $plan->current_invested_minor) {
            throw ValidationException::withMessages(['capacity' => 'Capacity cannot be lower than the amount already invested.']);
        }

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'],
            'currency' => strtoupper($validated['currency']),
            'risk_rating' => $validated['risk_rating'],
            'minimum_amount_minor' => $minimum,
            'maximum_amount_minor' => $maximum,
            'capacity_minor' => $capacity,
            'term_days' => $validated['term_days'],
            'lock_days' => $validated['lock_days'],
            'projected_rate_bps' => $this->percentToBps((string) $validated['projected_rate']),
            'rate_type' => $validated['rate_type'],
            'calculation_frequency' => $validated['calculation_frequency'],
            'day_count_basis' => (int) $validated['day_count_basis'],
            'compounding' => false,
            'status' => $validated['status'],
            'version' => $plan === null ? 1 : $plan->version + 1,
            'effective_at' => $validated['status'] === 'published' ? ($plan?->effective_at ?? now()) : null,
            'sort_order' => $validated['sort_order'],
        ];
    }

    private function amountToMinor(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function percentToBps(string $percent): int
    {
        [$whole, $fraction] = array_pad(explode('.', $percent, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
