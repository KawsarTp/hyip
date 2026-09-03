<?php

use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\BankAccountController as AdminBankAccountController;
use App\Http\Controllers\Admin\CmsPageController as AdminCmsPageController;
use App\Http\Controllers\Admin\DepositController as AdminDepositController;
use App\Http\Controllers\Admin\EmailTemplateController as AdminEmailTemplateController;
use App\Http\Controllers\Admin\FeeRuleController as AdminFeeRuleController;
use App\Http\Controllers\Admin\InvestmentController as AdminInvestmentController;
use App\Http\Controllers\Admin\InvestmentPlanController as AdminInvestmentPlanController;
use App\Http\Controllers\Admin\KycController as AdminKycController;
use App\Http\Controllers\Admin\LedgerController as AdminLedgerController;
use App\Http\Controllers\Admin\LocalizationController as AdminLocalizationController;
use App\Http\Controllers\Admin\PaymentGatewayController as AdminPaymentGatewayController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Admin\ReferralController as AdminReferralController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Admin\WithdrawalMethodController as AdminWithdrawalMethodController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\CmsPageController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProviderWebhookController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Middleware\ApplyReferralAttribution;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/ref/{code}', [ReferralController::class, 'visit'])->name('referrals.visit');
Route::get('/pages/{cmsPage:slug}', [CmsPageController::class, 'show'])->name('cms.pages.show');
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

Route::middleware(['auth', ApplyReferralAttribution::class, 'localization'])->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::patch('/preferences/locale', [UserPreferenceController::class, 'updateLocale'])->name('preferences.locale.update');
    Route::patch('/preferences/currency', [UserPreferenceController::class, 'updateCurrency'])->name('preferences.currency.update');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::get('/deposits', [DepositController::class, 'index'])->name('deposits.index');
    Route::get('/deposits/create', [DepositController::class, 'create'])->name('deposits.create');
    Route::post('/deposits', [DepositController::class, 'store'])->name('deposits.store');
    Route::get('/deposits/{deposit}', [DepositController::class, 'show'])->name('deposits.show');
    Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::get('/withdrawals/create', [WithdrawalController::class, 'create'])->name('withdrawals.create');
    Route::get('/withdrawals/quote', [WithdrawalController::class, 'quote'])->name('withdrawals.quote');
    Route::post('/withdrawals', [WithdrawalController::class, 'store'])->name('withdrawals.store');
    Route::get('/withdrawals/{withdrawal}', [WithdrawalController::class, 'show'])->name('withdrawals.show');
    Route::patch('/withdrawals/{withdrawal}/cancel', [WithdrawalController::class, 'cancel'])->name('withdrawals.cancel');
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
    Route::post('/referrals/claim', [ReferralController::class, 'claim'])->name('referrals.claim');
    Route::get('/support-tickets', [SupportTicketController::class, 'index'])->name('support.tickets.index');
    Route::get('/support-tickets/create', [SupportTicketController::class, 'create'])->name('support.tickets.create');
    Route::post('/support-tickets', [SupportTicketController::class, 'store'])->name('support.tickets.store');
    Route::get('/support-tickets/{supportTicket}', [SupportTicketController::class, 'show'])->name('support.tickets.show');
    Route::post('/support-tickets/{supportTicket}/reply', [SupportTicketController::class, 'reply'])->name('support.tickets.reply');
    Route::patch('/support-tickets/{supportTicket}/close', [SupportTicketController::class, 'close'])->name('support.tickets.close');
    Route::patch('/support-tickets/{supportTicket}/reopen', [SupportTicketController::class, 'reopen'])->name('support.tickets.reopen');
    Route::get('/support-tickets/{supportTicket}/attachments/{attachment}', [SupportTicketController::class, 'downloadAttachment'])->name('support.tickets.attachments.download');
    Route::get('/investments/plans', [InvestmentController::class, 'plans'])->name('investment-plans.index');
    Route::get('/investments', [InvestmentController::class, 'index'])->name('investments.index');
    Route::get('/investments/create/{investmentPlan}', [InvestmentController::class, 'create'])->name('investments.create');
    Route::post('/investments', [InvestmentController::class, 'store'])->name('investments.store');
    Route::get('/investments/{investment}', [InvestmentController::class, 'show'])->name('investments.show');
    Route::get('/kyc', [KycController::class, 'edit'])->name('kyc.edit');
    Route::post('/kyc', [KycController::class, 'update'])->name('kyc.update');
    Route::get('/verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/verify-email/notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::middleware(['admin', 'audit.admin'])->prefix('admin')->group(function (): void {
        Route::view('/', 'admin.dashboard')->name('admin.dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->middleware('permission:users.view')->name('admin.users.index');
        Route::get('/users/create', [AdminUserController::class, 'create'])->middleware('permission:users.manage')->name('admin.users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->middleware('permission:users.manage')->name('admin.users.store');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->middleware('permission:users.manage')->name('admin.users.edit');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->middleware('permission:users.manage')->name('admin.users.update');
        Route::get('/roles', [AdminPermissionController::class, 'index'])->middleware('permission:users.manage')->name('admin.roles.index');
        Route::patch('/roles/{role}/permissions', [AdminPermissionController::class, 'update'])->middleware('permission:users.manage')->name('admin.roles.update');
        Route::get('/support-tickets', [AdminSupportTicketController::class, 'index'])->middleware('permission:support.manage')->name('admin.support.index');
        Route::get('/support-tickets/{supportTicket}', [AdminSupportTicketController::class, 'show'])->middleware('permission:support.manage')->name('admin.support.show');
        Route::patch('/support-tickets/{supportTicket}', [AdminSupportTicketController::class, 'update'])->middleware('permission:support.manage')->name('admin.support.update');
        Route::post('/support-tickets/{supportTicket}/reply', [AdminSupportTicketController::class, 'reply'])->middleware('permission:support.manage')->name('admin.support.reply');
        Route::get('/support-tickets/{supportTicket}/attachments/{attachment}', [AdminSupportTicketController::class, 'downloadAttachment'])->middleware('permission:support.manage')->name('admin.support.attachments.download');
        Route::get('/settings', [AdminSettingController::class, 'index'])->middleware('permission:settings.manage')->name('admin.settings.index');
        Route::patch('/settings/site', [AdminSettingController::class, 'updateSite'])->middleware('permission:settings.manage')->name('admin.settings.site.update');
        Route::patch('/settings/email', [AdminSettingController::class, 'updateEmail'])->middleware('permission:settings.manage')->name('admin.settings.email.update');
        Route::patch('/settings/branding', [AdminSettingController::class, 'updateBranding'])->middleware('permission:settings.manage')->name('admin.settings.branding.update');
        Route::patch('/settings/social', [AdminSettingController::class, 'updateSocial'])->middleware('permission:settings.manage')->name('admin.settings.social.update');
        Route::patch('/settings/social-auth', [AdminSettingController::class, 'updateSocialAuth'])->middleware('permission:settings.manage')->name('admin.settings.social-auth.update');
        Route::patch('/settings/cookie', [AdminSettingController::class, 'updateCookie'])->middleware('permission:settings.manage')->name('admin.settings.cookie.update');
        Route::patch('/settings/extensions', [AdminSettingController::class, 'updateExtensions'])->middleware('permission:settings.manage')->name('admin.settings.extensions.update');
        Route::get('/localization', [AdminLocalizationController::class, 'index'])->middleware('permission:settings.manage')->name('admin.localization.index');
        Route::get('/languages', [AdminLocalizationController::class, 'languages'])->middleware('permission:settings.manage')->name('admin.languages.index');
        Route::post('/languages', [AdminLocalizationController::class, 'storeLanguage'])->middleware('permission:settings.manage')->name('admin.languages.store');
        Route::patch('/languages/{language}', [AdminLocalizationController::class, 'updateLanguage'])->middleware('permission:settings.manage')->name('admin.languages.update');
        Route::patch('/languages/{language}/default', [AdminLocalizationController::class, 'setDefaultLanguage'])->middleware('permission:settings.manage')->name('admin.languages.default');
        Route::get('/languages/{language}/translations', [AdminLocalizationController::class, 'translations'])->middleware('permission:settings.manage')->name('admin.languages.translations');
        Route::patch('/languages/{language}/translations', [AdminLocalizationController::class, 'updateTranslations'])->middleware('permission:settings.manage')->name('admin.languages.translations.update');
        Route::get('/currencies', [AdminLocalizationController::class, 'currencies'])->middleware('permission:settings.manage')->name('admin.currencies.index');
        Route::post('/currencies', [AdminLocalizationController::class, 'storeCurrency'])->middleware('permission:settings.manage')->name('admin.currencies.store');
        Route::patch('/currencies/{currency}', [AdminLocalizationController::class, 'updateCurrency'])->middleware('permission:settings.manage')->name('admin.currencies.update');
        Route::patch('/currencies/{currency}/default', [AdminLocalizationController::class, 'setDefaultCurrency'])->middleware('permission:settings.manage')->name('admin.currencies.default');
        Route::get('/email-templates', [AdminEmailTemplateController::class, 'index'])->middleware('permission:content.manage')->name('admin.email-templates.index');
        Route::get('/email-templates/{emailTemplate}/edit', [AdminEmailTemplateController::class, 'edit'])->middleware('permission:content.manage')->name('admin.email-templates.edit');
        Route::patch('/email-templates/{emailTemplate}', [AdminEmailTemplateController::class, 'update'])->middleware('permission:content.manage')->name('admin.email-templates.update');
        Route::get('/email-templates/{emailTemplate}/preview', [AdminEmailTemplateController::class, 'preview'])->middleware('permission:content.manage')->name('admin.email-templates.preview');
        Route::get('/cms', [AdminCmsPageController::class, 'index'])->middleware('permission:content.manage')->name('admin.cms.index');
        Route::get('/cms/create', [AdminCmsPageController::class, 'create'])->middleware('permission:content.manage')->name('admin.cms.create');
        Route::post('/cms', [AdminCmsPageController::class, 'store'])->middleware('permission:content.manage')->name('admin.cms.store');
        Route::get('/cms/{cmsPage}/edit', [AdminCmsPageController::class, 'edit'])->middleware('permission:content.manage')->name('admin.cms.edit');
        Route::patch('/cms/{cmsPage}', [AdminCmsPageController::class, 'update'])->middleware('permission:content.manage')->name('admin.cms.update');
        Route::get('/deposits', [AdminDepositController::class, 'index'])->name('admin.deposits.index');
        Route::get('/deposits/{deposit}', [AdminDepositController::class, 'show'])->name('admin.deposits.show');
        Route::patch('/deposits/{deposit}', [AdminDepositController::class, 'review'])->name('admin.deposits.review');
        Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])->name('admin.withdrawals.index');
        Route::get('/withdrawals/{withdrawal}', [AdminWithdrawalController::class, 'show'])->name('admin.withdrawals.show');
        Route::patch('/withdrawals/{withdrawal}', [AdminWithdrawalController::class, 'review'])->name('admin.withdrawals.review');
        Route::get('/investment-plans', [AdminInvestmentPlanController::class, 'index'])->name('admin.investment-plans.index');
        Route::get('/investment-plans/create', [AdminInvestmentPlanController::class, 'create'])->name('admin.investment-plans.create');
        Route::post('/investment-plans', [AdminInvestmentPlanController::class, 'store'])->name('admin.investment-plans.store');
        Route::get('/investment-plans/{investmentPlan}/edit', [AdminInvestmentPlanController::class, 'edit'])->name('admin.investment-plans.edit');
        Route::patch('/investment-plans/{investmentPlan}', [AdminInvestmentPlanController::class, 'update'])->name('admin.investment-plans.update');
        Route::get('/investments', [AdminInvestmentController::class, 'index'])->name('admin.investments.index');
        Route::get('/investments/{investment}', [AdminInvestmentController::class, 'show'])->name('admin.investments.show');
        Route::patch('/investments/{investment}/calculate-return', [AdminInvestmentController::class, 'calculate'])->name('admin.investments.calculate');
        Route::patch('/investments/{investment}/mature', [AdminInvestmentController::class, 'mature'])->name('admin.investments.mature');
        Route::patch('/investments/{investment}/complete', [AdminInvestmentController::class, 'complete'])->name('admin.investments.complete');
        Route::patch('/investment-returns/{investmentReturn}', [AdminInvestmentController::class, 'reviewReturn'])->name('admin.investment-returns.review');
        Route::get('/referrals', [AdminReferralController::class, 'index'])->name('admin.referrals.index');
        Route::get('/referrals/settings', [AdminReferralController::class, 'settings'])->name('admin.referrals.settings');
        Route::patch('/referrals/settings', [AdminReferralController::class, 'updateSettings'])->name('admin.referrals.settings.update');
        Route::patch('/referrals/{referralCommission}', [AdminReferralController::class, 'review'])->name('admin.referrals.review');
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
        Route::get('/audit', [AdminAuditLogController::class, 'index'])->name('admin.audit.index');
        Route::get('/fees', [AdminFeeRuleController::class, 'index'])->name('admin.fees.index');
        Route::get('/fees/create', [AdminFeeRuleController::class, 'create'])->name('admin.fees.create');
        Route::post('/fees', [AdminFeeRuleController::class, 'store'])->name('admin.fees.store');
        Route::get('/fees/{feeRule}/edit', [AdminFeeRuleController::class, 'edit'])->name('admin.fees.edit');
        Route::patch('/fees/{feeRule}', [AdminFeeRuleController::class, 'update'])->name('admin.fees.update');
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
