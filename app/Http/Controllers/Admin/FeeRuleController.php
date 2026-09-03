<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FeeRuleRequest;
use App\Models\FeeRule;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FeeRuleController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}

    public function index(): View
    {
        $rules = FeeRule::query()->orderBy('event')->orderByDesc('priority')->orderByDesc('id')->get();

        return view('admin.fees.index', compact('rules'));
    }

    public function create(): View
    {
        return view('admin.fees.create');
    }

    public function store(FeeRuleRequest $request): RedirectResponse
    {
        $feeRule = FeeRule::query()->create($this->attributes($request));
        $this->auditService->record('fee_rule_created', $feeRule, 'financial', after: $feeRule->getAttributes());

        return redirect()->route('admin.fees.index')->with('status', 'Fee and tax rule added.');
    }

    public function edit(FeeRule $feeRule): View
    {
        return view('admin.fees.edit', ['rule' => $feeRule]);
    }

    public function update(FeeRuleRequest $request, FeeRule $feeRule): RedirectResponse
    {
        $before = $feeRule->getAttributes();
        $feeRule->update($this->attributes($request));
        $this->auditService->record('fee_rule_updated', $feeRule, 'financial', before: $before, after: $feeRule->fresh()->getAttributes());

        return redirect()->route('admin.fees.index')->with('status', 'Fee and tax rule updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(FeeRuleRequest $request): array
    {
        $validated = $request->validated();

        return [
            'name' => $validated['name'],
            'event' => $validated['event'],
            'currency' => filled($validated['currency'] ?? null) ? strtoupper($validated['currency']) : null,
            'country' => filled($validated['country'] ?? null) ? strtoupper($validated['country']) : null,
            'fee_fixed_minor' => $this->amountToMinor($validated['fee_fixed']),
            'fee_percent_bps' => $this->percentToBps((string) $validated['fee_percent']),
            'maximum_fee_minor' => filled($validated['maximum_fee'] ?? null) ? $this->amountToMinor($validated['maximum_fee']) : null,
            'tax_fixed_minor' => $this->amountToMinor($validated['tax_fixed']),
            'tax_percent_bps' => $this->percentToBps((string) $validated['tax_percent']),
            'tax_basis' => $validated['tax_basis'],
            'priority' => $validated['priority'],
            'is_enabled' => $request->boolean('is_enabled'),
            'effective_from' => $validated['effective_from'] ?? null,
            'effective_until' => $validated['effective_until'] ?? null,
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
