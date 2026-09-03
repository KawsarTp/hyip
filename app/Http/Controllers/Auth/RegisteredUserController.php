<?php

namespace App\Http\Controllers\Auth;

use App\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    public function store(RegisterRequest $request, EmailNotificationService $emailNotificationService): RedirectResponse
    {
        $user = User::create([
            'name' => $request->string('name')->trim()->toString(),
            'email' => $request->string('email')->lower()->toString(),
            'password' => $request->string('password')->toString(),
            'role' => UserRole::User,
            'status' => AccountStatus::Active,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();
        $emailNotificationService->queueForUser(
            $user,
            'account_welcome',
            [],
            "user:{$user->getKey()}:welcome",
        );

        return redirect()->route('dashboard');
    }
}
