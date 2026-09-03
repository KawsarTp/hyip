<?php

namespace App\Http\Controllers\Admin;

use App\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffRequest;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $role = $request->string('role')->toString();
        $roles = array_map(fn (UserRole $item): string => $item->value, UserRole::cases());
        $query = User::query()->with('kycProfile')->latest();

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search): void {
                $like = "%{$search}%";
                $searchQuery->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('referral_code', 'like', $like);
            });
        }

        if (in_array($role, $roles, true)) {
            $query->where('role', $role);
        } else {
            $role = '';
        }

        $users = $query->paginate(25)->withQueryString();

        return view('admin.users.index', compact('users', 'search', 'role', 'roles'));
    }

    public function create(): View
    {
        return view('admin.users.create', ['roles' => UserRole::cases(), 'statuses' => AccountStatus::cases()]);
    }

    public function store(StaffRequest $request, EmailNotificationService $emailNotificationService): RedirectResponse
    {
        $validated = $request->validated();
        $managedUser = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'status' => $validated['status'],
            'email_verified_at' => now(),
        ]);
        $emailNotificationService->queueForUser(
            $managedUser,
            'account_welcome',
            [],
            "user:{$managedUser->getKey()}:welcome",
        );

        return redirect()->route('admin.users.index')->with('status', 'User or staff account created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['managedUser' => $user, 'roles' => UserRole::cases(), 'statuses' => AccountStatus::cases()]);
    }

    public function update(StaffRequest $request, User $user, EmailNotificationService $emailNotificationService): RedirectResponse
    {
        $validated = $request->validated();
        $previousStatus = $user->status?->value;
        $passwordChanged = filled($validated['password'] ?? null);
        if ($user->is($request->user()) && ($validated['role'] !== UserRole::Admin->value || $validated['status'] !== AccountStatus::Active->value)) {
            throw ValidationException::withMessages(['role' => 'You cannot deactivate or remove your own administrator access.']);
        }

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'status' => $validated['status'],
        ];
        if ($passwordChanged) {
            $attributes['password'] = $validated['password'];
        }
        $user->update($attributes);

        if ($passwordChanged) {
            $emailNotificationService->queueForUser(
                $user,
                'security_event',
                ['event' => 'Password changed by an administrator'],
                "security:password-change:{$user->getKey()}:".Str::uuid(),
            );
        }

        if ($previousStatus !== $validated['status'] && in_array($validated['status'], [AccountStatus::Restricted->value, AccountStatus::Frozen->value], true)) {
            $emailNotificationService->queueForUser(
                $user,
                'account_hold',
                ['status' => $validated['status']],
                "user:{$user->getKey()}:hold:{$validated['status']}:".now()->timestamp,
            );
        }

        return redirect()->route('admin.users.index')->with('status', 'Account settings updated.');
    }
}
