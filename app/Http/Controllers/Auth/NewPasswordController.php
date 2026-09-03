<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class NewPasswordController extends Controller
{
    public function store(ResetPasswordRequest $request, EmailNotificationService $emailNotificationService): RedirectResponse
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password) use ($emailNotificationService): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                $emailNotificationService->queueForUser(
                    $user,
                    'security_event',
                    ['event' => 'Password reset'],
                    "security:password-reset:{$user->getKey()}:".Str::uuid(),
                );
            },
        );

        if ($status !== Password::PasswordReset) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        return redirect()->route('login')->with('status', __($status));
    }
}
