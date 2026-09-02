<?php

use App\Http\Controllers\Admin\BankAccountController as AdminBankAccountController;
use App\Http\Controllers\Admin\DepositController as AdminDepositController;
use App\Http\Controllers\Admin\KycController as AdminKycController;
use App\Http\Controllers\Admin\LedgerController as AdminLedgerController;
use App\Http\Controllers\Admin\PaymentGatewayController as AdminPaymentGatewayController;
use App\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Admin\WithdrawalMethodController as AdminWithdrawalMethodController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProviderWebhookController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
    Route::view('/register', 'auth.register')->name('register');
    Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
    Route::view('/reset-password/{token}', 'auth.reset-password')->name('password.reset');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::get('/deposits', [DepositController::class, 'index'])->name('deposits.index');
    Route::get('/deposits/create', [DepositController::class, 'create'])->name('deposits.create');
    Route::post('/deposits', [DepositController::class, 'store'])->name('deposits.store');
    Route::get('/deposits/{deposit}', [DepositController::class, 'show'])->name('deposits.show');
    Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::get('/withdrawals/create', [WithdrawalController::class, 'create'])->name('withdrawals.create');
    Route::post('/withdrawals', [WithdrawalController::class, 'store'])->name('withdrawals.store');
    Route::get('/withdrawals/{withdrawal}', [WithdrawalController::class, 'show'])->name('withdrawals.show');
    Route::patch('/withdrawals/{withdrawal}/cancel', [WithdrawalController::class, 'cancel'])->name('withdrawals.cancel');
    Route::get('/kyc', [KycController::class, 'edit'])->name('kyc.edit');
    Route::post('/kyc', [KycController::class, 'update'])->name('kyc.update');
    Route::get('/verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/verify-email/notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::middleware('admin')->prefix('admin')->group(function (): void {
        Route::view('/', 'admin.dashboard')->name('admin.dashboard');
        Route::get('/deposits', [AdminDepositController::class, 'index'])->name('admin.deposits.index');
        Route::get('/deposits/{deposit}', [AdminDepositController::class, 'show'])->name('admin.deposits.show');
        Route::patch('/deposits/{deposit}', [AdminDepositController::class, 'review'])->name('admin.deposits.review');
        Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])->name('admin.withdrawals.index');
        Route::get('/withdrawals/{withdrawal}', [AdminWithdrawalController::class, 'show'])->name('admin.withdrawals.show');
        Route::patch('/withdrawals/{withdrawal}', [AdminWithdrawalController::class, 'review'])->name('admin.withdrawals.review');
        Route::get('/withdrawal-methods', [AdminWithdrawalMethodController::class, 'index'])->name('admin.withdrawal-methods.index');
        Route::get('/withdrawal-methods/create', [AdminWithdrawalMethodController::class, 'create'])->name('admin.withdrawal-methods.create');
        Route::post('/withdrawal-methods', [AdminWithdrawalMethodController::class, 'store'])->name('admin.withdrawal-methods.store');
        Route::get('/withdrawal-methods/{withdrawalMethod}/edit', [AdminWithdrawalMethodController::class, 'edit'])->name('admin.withdrawal-methods.edit');
        Route::patch('/withdrawal-methods/{withdrawalMethod}', [AdminWithdrawalMethodController::class, 'update'])->name('admin.withdrawal-methods.update');
        Route::get('/payment-gateways', [AdminPaymentGatewayController::class, 'index'])->name('admin.payment-gateways.index');
        Route::get('/payment-gateways/create', [AdminPaymentGatewayController::class, 'create'])->name('admin.payment-gateways.create');
        Route::post('/payment-gateways', [AdminPaymentGatewayController::class, 'store'])->name('admin.payment-gateways.store');
        Route::get('/payment-gateways/{paymentGateway}/edit', [AdminPaymentGatewayController::class, 'edit'])->name('admin.payment-gateways.edit');
        Route::patch('/payment-gateways/{paymentGateway}', [AdminPaymentGatewayController::class, 'update'])->name('admin.payment-gateways.update');
        Route::get('/bank-accounts', [AdminBankAccountController::class, 'index'])->name('admin.bank-accounts.index');
        Route::get('/bank-accounts/create', [AdminBankAccountController::class, 'create'])->name('admin.bank-accounts.create');
        Route::post('/bank-accounts', [AdminBankAccountController::class, 'store'])->name('admin.bank-accounts.store');
        Route::get('/bank-accounts/{bankAccount}/edit', [AdminBankAccountController::class, 'edit'])->name('admin.bank-accounts.edit');
        Route::patch('/bank-accounts/{bankAccount}', [AdminBankAccountController::class, 'update'])->name('admin.bank-accounts.update');
        Route::delete('/bank-accounts/{bankAccount}', [AdminBankAccountController::class, 'destroy'])->name('admin.bank-accounts.destroy');
        Route::get('/ledger', [AdminLedgerController::class, 'index'])->name('admin.ledger.index');
        Route::get('/kyc', [AdminKycController::class, 'index'])->name('admin.kyc.index');
        Route::get('/kyc/{kycProfile}', [AdminKycController::class, 'show'])->name('admin.kyc.show');
        Route::patch('/kyc/{kycProfile}', [AdminKycController::class, 'update'])->name('admin.kyc.update');
        Route::get('/kyc/documents/{kycDocument}/download', [AdminKycController::class, 'downloadDocument'])->name('admin.kyc.documents.download');
    });
});

Route::post('/webhooks/payments/{provider}', PaymentWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.payments');

Route::post('/webhooks/payment-gateways/{paymentGateway}', ProviderWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.payment-gateways');
