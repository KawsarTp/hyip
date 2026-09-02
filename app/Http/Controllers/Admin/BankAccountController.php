<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BankAccountRequest;
use App\Models\BankAccount;
use App\Models\PaymentGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BankAccountController extends Controller
{
    public function index(): View
    {
        $accounts = BankAccount::query()->with('paymentGateway')->orderBy('sort_order')->paginate(20);

        return view('admin.bank-accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        $gateways = PaymentGateway::query()->where('driver', 'bank_transfer')->orderBy('name')->get();

        return view('admin.bank-accounts.create', compact('gateways'));
    }

    public function store(BankAccountRequest $request): RedirectResponse
    {
        BankAccount::query()->create($request->validated() + ['is_enabled' => $request->boolean('is_enabled')]);

        return redirect()->route('admin.bank-accounts.index')->with('status', 'Bank account added.');
    }

    public function edit(BankAccount $bankAccount): View
    {
        $gateways = PaymentGateway::query()->where('driver', 'bank_transfer')->orderBy('name')->get();

        return view('admin.bank-accounts.edit', compact('bankAccount', 'gateways'));
    }

    public function update(BankAccountRequest $request, BankAccount $bankAccount): RedirectResponse
    {
        $attributes = $request->validated();

        foreach (['account_number', 'iban', 'swift_code'] as $sensitiveField) {
            if (blank($attributes[$sensitiveField] ?? null)) {
                unset($attributes[$sensitiveField]);
            }
        }

        $bankAccount->update($attributes + ['is_enabled' => $request->boolean('is_enabled')]);

        return redirect()->route('admin.bank-accounts.index')->with('status', 'Bank account settings updated.');
    }

    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->delete();

        return redirect()->route('admin.bank-accounts.index')->with('status', 'Bank account removed.');
    }
}
