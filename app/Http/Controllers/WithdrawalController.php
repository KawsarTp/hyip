<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientWalletBalance;
use App\Http\Requests\WithdrawalRequest;
use App\Models\User;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use App\WalletType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class WithdrawalController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $withdrawals = $user->withdrawals()->with('withdrawalMethod')->latest()->paginate(12);

        return view('withdrawals.index', compact('withdrawals'));
    }

    public function create(Request $request, WalletService $walletService, WithdrawalService $withdrawalService): View
    {
        /** @var User $user */
        $user = $request->user();
        $methods = WithdrawalMethod::query()
            ->where('is_enabled', true)
            ->where('currency', 'USD')
            ->orderBy('sort_order')
            ->get();
        $availableWallet = $walletService->wallet($user, WalletType::Available);

        return view('withdrawals.create', compact('methods', 'availableWallet', 'withdrawalService'));
    }

    public function store(WithdrawalRequest $request, WithdrawalService $withdrawalService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $method = WithdrawalMethod::query()->whereKey($validated['withdrawal_method_id'])->where('is_enabled', true)->firstOrFail();

        try {
            $withdrawal = $withdrawalService->create(
                $user,
                $validated['amount'],
                $validated['currency'],
                $method,
                [
                    'destination_name' => $validated['destination_name'],
                    'destination_address' => $validated['destination_address'],
                    'destination_network' => $validated['destination_network'] ?? null,
                    'destination_note' => $validated['destination_note'] ?? null,
                ],
                $validated['idempotency_key'] ?? (string) Str::uuid(),
            );
        } catch (InsufficientWalletBalance|InvalidArgumentException|LogicException $exception) {
            return back()->withInput()->withErrors(['withdrawal' => $exception->getMessage()]);
        }

        return redirect()->route('withdrawals.show', $withdrawal)->with('status', 'Withdrawal request submitted for manual review.');
    }

    public function show(Request $request, Withdrawal $withdrawal): View
    {
        abort_unless($withdrawal->user_id === $request->user()->id, 403);
        $withdrawal->load('withdrawalMethod');

        return view('withdrawals.show', compact('withdrawal'));
    }

    public function cancel(Request $request, Withdrawal $withdrawal, WithdrawalService $withdrawalService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $withdrawalService->cancel($withdrawal, $user);
        } catch (LogicException $exception) {
            return back()->withErrors(['withdrawal' => $exception->getMessage()]);
        }

        return redirect()->route('withdrawals.show', $withdrawal)->with('status', 'Withdrawal cancelled and funds released.');
    }
}
