<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentProviderException;
use App\Http\Requests\DepositRequest;
use App\Models\Deposit;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\DepositService;
use App\Services\LocalizationService;
use App\Services\PaymentGatewayManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DepositController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $deposits = $user->deposits()->with('paymentIntent')->latest()->paginate(12);

        return view('deposits.index', compact('deposits'));
    }

    public function create(LocalizationService $localization): View
    {
        $gateways = PaymentGateway::query()
            ->where('is_enabled', true)
            ->with(['bankAccounts' => fn ($query) => $query->where('is_enabled', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $currencies = $localization->enabledCurrencies();
        $currency = auth()->user()->currency_code ?: $localization->defaultCurrencyCode();

        return view('deposits.create', compact('gateways', 'currencies', 'currency'));
    }

    public function store(DepositRequest $request, DepositService $depositService, PaymentGatewayManager $gatewayManager): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $idempotencyKey = $validated['idempotency_key'] ?? (string) Str::uuid();
        $gateway = PaymentGateway::query()
            ->whereKey($validated['payment_gateway_id'])
            ->where('is_enabled', true)
            ->with(['bankAccounts' => fn ($query) => $query->where('is_enabled', true)->orderBy('sort_order')])
            ->firstOrFail();

        $deposit = $depositService->create(
            $user,
            $validated['amount'],
            $validated['currency'],
            $gateway,
            $idempotencyKey,
        );

        try {
            $gatewayManager->createPayment($deposit->load('paymentIntent')->paymentIntent, $gateway);
        } catch (PaymentProviderException $exception) {
            return redirect()->route('deposits.show', $deposit)->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->route('deposits.show', $deposit)->with('status', 'Deposit request created. Follow the payment instructions to complete it.');
    }

    public function show(Request $request, Deposit $deposit): View
    {
        abort_unless($deposit->user_id === $request->user()->id, 403);
        $deposit->load(['paymentIntent', 'events']);

        return view('deposits.show', compact('deposit'));
    }
}
