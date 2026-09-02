<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReferralSettingsRequest;
use App\Http\Requests\ReferralReviewRequest;
use App\Models\ReferralCommission;
use App\ReferralCommissionStatus;
use App\Services\ReferralService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use LogicException;

class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $query = ReferralCommission::query()->with(['referrer', 'referredUser', 'investment'])->latest();
        $validStatuses = array_map(fn (ReferralCommissionStatus $commissionStatus): string => $commissionStatus->value, ReferralCommissionStatus::cases());

        if (in_array($status, $validStatuses, true)) {
            $query->where('status', $status);
        }

        $commissions = $query->paginate(20)->withQueryString();

        return view('admin.referrals.index', compact('commissions', 'status'));
    }

    public function settings(ReferralService $referralService): View
    {
        $settings = $referralService->settings();

        return view('admin.referrals.settings', compact('settings'));
    }

    public function updateSettings(ReferralSettingsRequest $request, ReferralService $referralService): RedirectResponse
    {
        $validated = $request->validated();
        $settings = $referralService->settings();
        $settings->update([
            'investment_commission_bps' => $this->percentToBps((string) $validated['investment_commission']),
            'hold_days' => $validated['hold_days'],
            'minimum_qualifying_investment_minor' => $this->amountToMinor($validated['minimum_qualifying_investment']),
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return redirect()->route('admin.referrals.settings')->with('status', 'Referral settings updated.');
    }

    public function review(ReferralReviewRequest $request, ReferralCommission $referralCommission, ReferralService $referralService): RedirectResponse
    {
        $validated = $request->validated();

        try {
            match ($validated['decision']) {
                'approved' => $referralService->approve($referralCommission, $request->user()->id, $validated['review_notes'] ?? null),
                'released' => $referralService->release($referralCommission, $request->user()->id),
                'clawed_back' => $referralService->clawBack($referralCommission, $request->user()->id, $validated['review_notes']),
            };
        } catch (LogicException $exception) {
            return back()->withInput()->withErrors(['referral' => $exception->getMessage()]);
        }

        return back()->with('status', 'Referral commission review saved.');
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
