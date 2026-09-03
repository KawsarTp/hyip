<?php

namespace Database\Seeders;

use App\AccountStatus;
use App\Models\CmsPage;
use App\Models\Currency;
use App\Models\EmailSetting;
use App\Models\EmailTemplate;
use App\Models\Language;
use App\Models\PaymentGateway;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\SiteSetting;
use App\Models\TranslationLine;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'role' => UserRole::User,
                'status' => AccountStatus::Active,
                'email_verified_at' => now(),
            ],
        );

        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Arcadia Admin',
                'password' => 'password',
                'role' => UserRole::Admin,
                'status' => AccountStatus::Active,
                'email_verified_at' => now(),
            ],
        );

        $gateways = [
            ['name' => 'Stripe', 'driver' => 'stripe', 'method' => 'card'],
            ['name' => 'PayPal', 'driver' => 'paypal', 'method' => 'paypal'],
            ['name' => 'Crypto payments', 'driver' => 'crypto', 'method' => 'crypto'],
            ['name' => 'Bank transfer', 'driver' => 'bank_transfer', 'method' => 'bank_transfer'],
        ];

        foreach ($gateways as $order => $gateway) {
            PaymentGateway::firstOrCreate(
                ['driver' => $gateway['driver'], 'name' => $gateway['name']],
                [
                    'method' => $gateway['method'],
                    'credentials' => [],
                    'settings' => [],
                    'is_enabled' => false,
                    'is_test_mode' => true,
                    'sort_order' => $order,
                ],
            );
        }

        $permissions = [
            ['key' => 'users.view', 'name' => 'View users and staff', 'group_name' => 'Users', 'description' => 'View account profiles and access status.'],
            ['key' => 'users.manage', 'name' => 'Manage users and staff', 'group_name' => 'Users', 'description' => 'Create accounts and change roles or status.'],
            ['key' => 'compliance.review', 'name' => 'Review compliance', 'group_name' => 'Compliance', 'description' => 'Review KYC submissions and sensitive documents.'],
            ['key' => 'finance.manage', 'name' => 'Manage financial operations', 'group_name' => 'Finance', 'description' => 'Operate deposits, payouts, fees, and reconciliation.'],
            ['key' => 'investments.manage', 'name' => 'Manage investments', 'group_name' => 'Investments', 'description' => 'Operate plans, investments, and return workflows.'],
            ['key' => 'settings.manage', 'name' => 'Manage system settings', 'group_name' => 'Settings', 'description' => 'Change site and outbound email configuration.'],
            ['key' => 'content.manage', 'name' => 'Manage content', 'group_name' => 'Content', 'description' => 'Edit CMS pages and email templates.'],
            ['key' => 'support.manage', 'name' => 'Manage support tickets', 'group_name' => 'Support', 'description' => 'Read, assign, reply to, and resolve customer support tickets.'],
            ['key' => 'audit.view', 'name' => 'View audit logs', 'group_name' => 'Audit', 'description' => 'Read the immutable activity trail.'],
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['key' => $permission['key']], $permission);
        }

        $permissionIds = Permission::query()->pluck('id', 'key');
        $rolePermissions = [
            UserRole::Admin->value => array_column($permissions, 'key'),
            UserRole::Finance->value => ['users.view', 'finance.manage', 'audit.view'],
            UserRole::Compliance->value => ['users.view', 'compliance.review', 'audit.view'],
            UserRole::Support->value => ['users.view', 'content.manage', 'support.manage'],
        ];
        foreach ($rolePermissions as $role => $keys) {
            foreach ($keys as $key) {
                RolePermission::firstOrCreate(['role' => $role, 'permission_id' => $permissionIds[$key]]);
            }
        }

        $settings = [
            'site_name' => 'Arcadia',
            'support_email' => 'support@example.com',
            'default_currency' => 'USD',
            'timezone' => 'UTC',
            'risk_notice' => 'Investments involve risk. Returns are not guaranteed.',
            'maintenance_mode' => '0',
        ];
        foreach ($settings as $key => $value) {
            SiteSetting::firstOrCreate(['key' => $key], ['value' => $value, 'type' => $key === 'maintenance_mode' ? 'boolean' : 'string', 'group_name' => 'general']);
        }

        $languages = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_default' => true, 'sort_order' => 0],
            ['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 1],
        ];
        foreach ($languages as $language) {
            Language::firstOrCreate(['code' => $language['code']], $language + ['is_enabled' => true]);
        }

        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'rate_to_default' => 1, 'sort_order' => 0],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'rate_to_default' => 0.92, 'sort_order' => 1],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'rate_to_default' => 0.79, 'sort_order' => 2],
            ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳', 'rate_to_default' => 117, 'sort_order' => 3],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'rate_to_default' => 83, 'sort_order' => 4],
        ];
        foreach ($currencies as $currency) {
            Currency::firstOrCreate(
                ['code' => $currency['code']],
                $currency + [
                    'decimal_places' => 2,
                    'rate_source' => 'Initial admin seed; update before production use',
                    'rate_updated_at' => now(),
                    'symbol_position' => 'prefix',
                    'thousands_separator' => ',',
                    'decimal_separator' => '.',
                    'is_enabled' => true,
                    'is_default' => $currency['code'] === 'USD',
                ],
            );
        }

        $navigationTranslations = [
            'en' => [
                'workspace' => 'Workspace', 'management' => 'Management', 'finance' => 'Finance', 'risk-compliance' => 'Risk & compliance', 'system' => 'System',
                'overview' => 'Overview', 'users-staff' => 'Users & staff', 'investments' => 'Investments', 'investment-plans' => 'Investment plans', 'referrals' => 'Referrals', 'support-tickets' => 'Support tickets',
                'deposits' => 'Deposits', 'payouts' => 'Payouts', 'ledger' => 'Ledger', 'fees-taxes' => 'Fees & taxes', 'payment-methods' => 'Payment methods', 'payout-methods' => 'Payout methods',
                'compliance' => 'Compliance', 'audit-logs' => 'Audit logs', 'cms-pages' => 'CMS pages', 'email-templates' => 'Email templates', 'roles-permissions' => 'Roles & permissions', 'reports' => 'Reports', 'settings' => 'Settings', 'languages-currency' => 'Languages & currency',
                'portfolio' => 'Portfolio', 'money-movement' => 'Money movement', 'account' => 'Account', 'my-investments' => 'My investments', 'wallet' => 'Wallet', 'deposit-funds' => 'Deposit funds', 'withdraw-funds' => 'Withdraw funds', 'activity' => 'Activity', 'verification' => 'Verification', 'help-support' => 'Help & support', 'statements' => 'Statements',
            ],
            'bn' => [
                'workspace' => 'ওয়ার্কস্পেস', 'management' => 'ম্যানেজমেন্ট', 'finance' => 'ফাইন্যান্স', 'risk-compliance' => 'ঝুঁকি ও কমপ্লায়েন্স', 'system' => 'সিস্টেম',
                'overview' => 'ওভারভিউ', 'users-staff' => 'ইউজার ও স্টাফ', 'investments' => 'ইনভেস্টমেন্ট', 'investment-plans' => 'ইনভেস্টমেন্ট প্ল্যান', 'referrals' => 'রেফারেল', 'support-tickets' => 'সাপোর্ট টিকিট',
                'deposits' => 'ডিপোজিট', 'payouts' => 'পেআউট', 'ledger' => 'লেজার', 'fees-taxes' => 'ফি ও ট্যাক্স', 'payment-methods' => 'পেমেন্ট মেথড', 'payout-methods' => 'পেআউট মেথড',
                'compliance' => 'কমপ্লায়েন্স', 'audit-logs' => 'অডিট লগ', 'cms-pages' => 'CMS পেজ', 'email-templates' => 'ইমেইল টেমপ্লেট', 'roles-permissions' => 'রোল ও পারমিশন', 'reports' => 'রিপোর্ট', 'settings' => 'সেটিংস', 'languages-currency' => 'ভাষা ও কারেন্সি',
                'portfolio' => 'পোর্টফোলিও', 'money-movement' => 'অর্থ লেনদেন', 'account' => 'অ্যাকাউন্ট', 'my-investments' => 'আমার ইনভেস্টমেন্ট', 'wallet' => 'ওয়ালেট', 'deposit-funds' => 'ডিপোজিট করুন', 'withdraw-funds' => 'উইথড্র করুন', 'activity' => 'অ্যাক্টিভিটি', 'verification' => 'ভেরিফিকেশন', 'help-support' => 'হেল্প ও সাপোর্ট', 'statements' => 'স্টেটমেন্ট',
            ],
        ];
        foreach ($navigationTranslations as $locale => $translations) {
            $language = Language::query()->where('code', $locale)->first();
            foreach ($translations as $key => $value) {
                TranslationLine::updateOrCreate(
                    ['language_id' => $language->id, 'group_name' => 'messages', 'key' => 'navigation.'.$key],
                    ['value' => $value],
                );
            }
        }

        EmailSetting::firstOrCreate(['id' => 1], ['mailer' => 'smtp', 'port' => 587, 'from_address' => 'no-reply@example.com', 'from_name' => 'Arcadia']);
        $emailTemplates = [
            ['key' => 'account_welcome', 'name' => 'Account welcome', 'subject' => 'Welcome to {{site_name}}', 'body' => "Hello {{user_name}},\n\nYour {{site_name}} account is ready.", 'variables' => ['user_name', 'site_name']],
            ['key' => 'email_verification', 'name' => 'Email verification', 'subject' => 'Verify your {{site_name}} email address', 'body' => "Hello {{user_name}},\n\nPlease verify your email address by opening this link: {{verification_url}}", 'variables' => ['user_name', 'site_name', 'verification_url']],
            ['key' => 'password_reset', 'name' => 'Password reset', 'subject' => 'Reset your {{site_name}} password', 'body' => "Hello {{user_name}},\n\nReset your password by opening this link: {{reset_url}}", 'variables' => ['user_name', 'site_name', 'reset_url']],
            ['key' => 'ticket_created', 'name' => 'Support ticket created', 'subject' => 'Support ticket {{ticket_number}} received', 'body' => "Hello {{user_name}},\n\nWe received your support request: {{subject}}. Track it here: {{ticket_url}}", 'variables' => ['user_name', 'ticket_number', 'subject', 'ticket_url']],
            ['key' => 'ticket_reply', 'name' => 'Support ticket reply', 'subject' => 'New reply on support ticket {{ticket_number}}', 'body' => "Hello {{user_name}},\n\nThere is a new reply on your support ticket: {{subject}}. Open the conversation here: {{ticket_url}}", 'variables' => ['user_name', 'ticket_number', 'subject', 'ticket_url', 'reply_preview', 'replied_by']],
            ['key' => 'ticket_status_updated', 'name' => 'Support ticket status updated', 'subject' => 'Support ticket {{ticket_number}} updated', 'body' => "Hello {{user_name}},\n\nYour support ticket {{ticket_number}} is now {{status}}. Open it here: {{ticket_url}}", 'variables' => ['user_name', 'ticket_number', 'subject', 'status', 'ticket_url', 'assigned_to', 'updated_by']],
            ['key' => 'kyc_submitted', 'name' => 'KYC submitted', 'subject' => 'Verification submission received', 'body' => "Hello {{user_name}},\n\nWe received your verification submission. We will notify you when the review is complete.", 'variables' => ['user_name']],
            ['key' => 'kyc_verified', 'name' => 'KYC verified', 'subject' => 'Your verification is approved', 'body' => "Hello {{user_name}},\n\nYour identity verification is approved. You can now use the features available to your account.", 'variables' => ['user_name']],
            ['key' => 'kyc_rejected', 'name' => 'KYC needs changes', 'subject' => 'Action needed for verification', 'body' => "Hello {{user_name}},\n\nYour verification needs attention. Please review the note in your account and resubmit the required information.", 'variables' => ['user_name']],
            ['key' => 'deposit_created', 'name' => 'Deposit created', 'subject' => 'Deposit request {{reference}} created', 'body' => "Hello {{user_name}},\n\nYour {{site_name}} deposit request for {{amount}} is awaiting confirmation. Reference: {{reference}}.", 'variables' => ['user_name', 'site_name', 'amount', 'reference']],
            ['key' => 'deposit_settled', 'name' => 'Deposit settled', 'subject' => 'Deposit {{reference}} completed', 'body' => "Hello {{user_name}},\n\nYour deposit of {{amount}} has been settled and credited to your account.", 'variables' => ['user_name', 'amount', 'reference']],
            ['key' => 'deposit_failed', 'name' => 'Deposit failed', 'subject' => 'Deposit {{reference}} needs attention', 'body' => "Hello {{user_name}},\n\nWe could not complete your deposit of {{amount}}. Please review the deposit details or contact support.", 'variables' => ['user_name', 'amount', 'reference']],
            ['key' => 'investment_activated', 'name' => 'Investment activated', 'subject' => 'Investment {{reference}} is active', 'body' => "Hello {{user_name}},\n\nYour investment of {{amount}} is now active. Review its terms and schedule in your dashboard.", 'variables' => ['user_name', 'amount', 'reference']],
            ['key' => 'return_posted', 'name' => 'Return posted', 'subject' => 'A return was posted to your account', 'body' => "Hello {{user_name}},\n\nA return of {{amount}} was posted to your account. Reference: {{reference}}.", 'variables' => ['user_name', 'amount', 'reference']],
            ['key' => 'payout_requested', 'name' => 'Payout requested', 'subject' => 'Payout request {{reference}} received', 'body' => "Hello {{user_name}},\n\nYour payout request for {{amount}} is reserved and awaiting manual review.", 'variables' => ['user_name', 'amount', 'reference']],
            ['key' => 'payout_approved', 'name' => 'Payout approved', 'subject' => 'Payout {{reference}} approved', 'body' => "Hello {{user_name}},\n\nYour payout request for {{amount}} was approved and is being prepared for settlement.", 'variables' => ['user_name', 'amount', 'reference']],
            ['key' => 'payout_failed', 'name' => 'Payout failed', 'subject' => 'Payout {{reference}} could not be completed', 'body' => "Hello {{user_name}},\n\nYour payout could not be completed. Any reserved funds will be handled according to the payout status shown in your account.", 'variables' => ['user_name', 'reference']],
            ['key' => 'payout_completed', 'name' => 'Payout completed', 'subject' => 'Payout {{reference}} completed', 'body' => "Hello {{user_name}},\n\nYour payout of {{amount}} has been marked completed. Reference: {{reference}}.", 'variables' => ['user_name', 'amount', 'reference']],
            ['key' => 'security_event', 'name' => 'Security event', 'subject' => 'Security notice from {{site_name}}', 'body' => "Hello {{user_name}},\n\nA security-related change was recorded on your account. If this was not you, contact support immediately.", 'variables' => ['user_name', 'site_name']],
            ['key' => 'account_hold', 'name' => 'Account hold', 'subject' => 'Important account notice', 'body' => "Hello {{user_name}},\n\nSome account activity is temporarily on hold while our team completes a review. Contact support if you need help.", 'variables' => ['user_name']],
            ['key' => 'legal_update', 'name' => 'Legal terms update', 'subject' => 'Important terms update from {{site_name}}', 'body' => "Hello {{user_name}},\n\nOur legal terms have been updated. Please review the latest version before continuing to use the platform.", 'variables' => ['user_name', 'site_name']],
        ];
        foreach ($emailTemplates as $emailTemplate) {
            EmailTemplate::firstOrCreate(['key' => $emailTemplate['key']], [
                'name' => $emailTemplate['name'],
                'subject' => $emailTemplate['subject'],
                'body_html' => $emailTemplate['body'],
                'body_text' => $emailTemplate['body'],
                'variables' => $emailTemplate['variables'],
                'locale' => 'en',
                'is_enabled' => true,
                'version' => 1,
            ]);
        }
        CmsPage::firstOrCreate(['slug' => 'risk-disclosure'], [
            'title' => 'Risk disclosure',
            'excerpt' => 'Understand the risks before using the platform.',
            'body' => "Investments involve risk. Returns are not guaranteed.\n\nReview the full terms and seek independent advice before committing funds.",
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
