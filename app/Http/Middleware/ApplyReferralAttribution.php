<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ReferralService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyReferralAttribution
{
    public function __construct(private readonly ReferralService $referralService) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null && $user->referred_by_id === null && session()->has('referral_code')) {
            $this->referralService->attribute($user, session('referral_code'));
            session()->forget('referral_code');
        }

        return $next($request);
    }
}
