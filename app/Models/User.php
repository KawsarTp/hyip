<?php

namespace App\Models;

use App\AccountStatus;
use App\Services\EmailNotificationService;
use App\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'role', 'status', 'locale', 'currency_code', 'referral_code', 'referred_by_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MustVerifyEmailTrait, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => AccountStatus::class,
            'locale' => 'string',
            'currency_code' => 'string',
            'referred_by_id' => 'integer',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === AccountStatus::Active;
    }

    public function canAccessAdminArea(): bool
    {
        return in_array($this->role, [
            UserRole::Admin,
            UserRole::Finance,
            UserRole::Compliance,
            UserRole::Support,
        ], true);
    }

    public function sendEmailVerificationNotification(): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ],
        );

        app(EmailNotificationService::class)->queueForUser(
            $this,
            'email_verification',
            ['verification_url' => $verificationUrl],
            "security:email-verification:{$this->getKey()}:".Str::uuid(),
        );
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ]);

        app(EmailNotificationService::class)->queueForUser(
            $this,
            'password_reset',
            ['reset_url' => $resetUrl],
            "security:password-reset-request:{$this->getKey()}:".Str::uuid(),
        );
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === UserRole::Admin) {
            return true;
        }

        return RolePermission::query()
            ->where('role', $this->role->value)
            ->whereHas('permission', fn ($query) => $query->where('key', $permission))
            ->exists();
    }

    public function kycProfile(): HasOne
    {
        return $this->hasOne(KycProfile::class);
    }

    public function kycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function paymentIntents(): HasMany
    {
        return $this->hasMany(PaymentIntent::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_id');
    }

    public function referralCommissions(): HasMany
    {
        return $this->hasMany(ReferralCommission::class, 'referrer_id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function assignedSupportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to_id');
    }

    public function supportTicketMessages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }
}
