<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClaimReferralRequest;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    public function index(Request $request, ReferralService $referralService): View
    {
        /** @var User $user */
        $user = $request->user();
        if ($user->referred_by_id === null && session()->has('referral_code')) {
            $referralService->attribute($user, session('referral_code'));
            session()->forget('referral_code');
            $user = $user->fresh();
        }
        $referralService->ensureCode($user);
        $user->loadCount('referrals');
        $referrals = $user->referrals()->latest()->paginate(10, ['*'], 'referrals_page');
        $commissions = $user->referralCommissions()->with('referredUser')->latest()->paginate(10, ['*'], 'commissions_page');

        return view('referrals.index', compact('user', 'referrals', 'commissions'));
    }

    public function claim(ClaimReferralRequest $request, ReferralService $referralService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $referralService->attribute($user, $request->validated('referral_code'));

        if ($user->fresh()->referred_by_id === null) {
            return back()->withErrors(['referral_code' => 'That referral code cannot be used for this account.']);
        }

        return back()->with('status', 'Referral attribution saved. Qualifying activity will be reviewed under the referral terms.');
    }

    public function visit(string $code, Request $request): RedirectResponse
    {
        if ($request->user() !== null) {
            return redirect()->route('referrals.index')->with('referral_code', Str::upper($code));
        }

        session(['referral_code' => Str::upper($code)]);

        return redirect()->route('register');
    }
}
