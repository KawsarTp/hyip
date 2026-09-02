<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentGatewayRequest;
use App\Models\PaymentGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class PaymentGatewayController extends Controller
{
    public function index(): View
    {
        $gateways = PaymentGateway::query()
            ->withCount(['bankAccounts' => fn ($query) => $query->where('is_enabled', true)])
            ->orderBy('sort_order')
            ->get();

        return view('admin.payment-gateways.index', compact('gateways'));
    }

    public function create(): View
    {
        return view('admin.payment-gateways.create');
    }

    public function store(PaymentGatewayRequest $request): RedirectResponse
    {
        PaymentGateway::query()->create($this->attributes($request));

        return redirect()->route('admin.payment-gateways.index')->with('status', 'Payment gateway added.');
    }

    public function edit(PaymentGateway $paymentGateway): View
    {
        return view('admin.payment-gateways.edit', ['gateway' => $paymentGateway]);
    }

    public function update(PaymentGatewayRequest $request, PaymentGateway $paymentGateway): RedirectResponse
    {
        $paymentGateway->update($this->attributes($request, $paymentGateway));

        return redirect()->route('admin.payment-gateways.index')->with('status', 'Payment gateway settings updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(PaymentGatewayRequest $request, ?PaymentGateway $gateway = null): array
    {
        $validated = $request->validated();
        $expectedMethods = ['stripe' => 'card', 'paypal' => 'paypal', 'crypto' => 'crypto', 'bank_transfer' => 'bank_transfer'];

        if (($expectedMethods[$validated['driver']] ?? null) !== $validated['method']) {
            throw ValidationException::withMessages(['method' => 'The selected payment method must match the gateway driver.']);
        }

        $credentialKeys = ['publishable_key', 'secret_key', 'client_id', 'client_secret', 'webhook_secret'];
        $credentials = $gateway?->credentials ?? [];

        foreach ($credentialKeys as $key) {
            if (filled($validated[$key] ?? null)) {
                $credentials[$key] = $validated[$key];
            }
        }

        return [
            'name' => $validated['name'],
            'driver' => $validated['driver'],
            'method' => $validated['method'],
            'credentials' => $credentials,
            'settings' => [
                'network' => $validated['network'] ?? null,
                'wallet_address' => $validated['wallet_address'] ?? null,
                'instructions' => $validated['instructions'] ?? null,
            ],
            'is_enabled' => $request->boolean('is_enabled'),
            'is_test_mode' => $request->boolean('is_test_mode'),
            'sort_order' => $validated['sort_order'],
        ];
    }
}
