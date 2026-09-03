<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WithdrawalMethodRequest;
use App\Models\WithdrawalMethod;
use App\Services\LocalizationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class WithdrawalMethodController extends Controller
{
    public function index(): View
    {
        $methods = WithdrawalMethod::query()->withCount('withdrawals')->orderBy('sort_order')->get();

        return view('admin.withdrawal-methods.index', compact('methods'));
    }

    public function create(LocalizationService $localization): View
    {
        return view('admin.withdrawal-methods.create', ['currencies' => $localization->enabledCurrencies()]);
    }

    public function store(WithdrawalMethodRequest $request): RedirectResponse
    {
        WithdrawalMethod::query()->create($this->attributes($request));

        return redirect()->route('admin.withdrawal-methods.index')->with('status', 'Withdrawal method added.');
    }

    public function edit(WithdrawalMethod $withdrawalMethod, LocalizationService $localization): View
    {
        return view('admin.withdrawal-methods.edit', ['method' => $withdrawalMethod, 'currencies' => $localization->enabledCurrencies()]);
    }

    public function update(WithdrawalMethodRequest $request, WithdrawalMethod $withdrawalMethod): RedirectResponse
    {
        $withdrawalMethod->update($this->attributes($request));

        return redirect()->route('admin.withdrawal-methods.index')->with('status', 'Withdrawal method settings updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(WithdrawalMethodRequest $request): array
    {
        $validated = $request->validated();
        $minimum = $this->amountToMinor($validated['minimum_amount']);
        $maximum = filled($validated['maximum_amount'] ?? null) ? $this->amountToMinor($validated['maximum_amount']) : null;

        if ($maximum !== null && $maximum < $minimum) {
            throw ValidationException::withMessages(['maximum_amount' => 'The maximum amount must be greater than the minimum amount.']);
        }

        return [
            'name' => $validated['name'],
            'method' => $validated['method'],
            'currency' => strtoupper($validated['currency']),
            'instructions' => $validated['instructions'],
            'destination_label' => $validated['destination_label'],
            'minimum_amount_minor' => $minimum,
            'maximum_amount_minor' => $maximum,
            'fee_fixed_minor' => $this->amountToMinor($validated['fee_fixed']),
            'fee_percent_bps' => $this->percentToBps((string) $validated['fee_percent']),
            'is_enabled' => $request->boolean('is_enabled'),
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
