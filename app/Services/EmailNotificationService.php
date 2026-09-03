<?php

namespace App\Services;

use App\AccountStatus;
use App\Jobs\SendTemplateEmail;
use App\Models\EmailSetting;
use App\Models\EmailTemplate;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /**
     * @param  array<string, scalar|null>  $variables
     */
    public function queueForUser(User $user, string $templateKey, array $variables, string $eventKey): void
    {
        $this->queueForAddress($user->email, $user->name, $templateKey, ['user_name' => $user->name] + $variables, $eventKey, $user->locale);
    }

    /**
     * @param  array<string, scalar|null>  $variables
     */
    public function queueForAddress(string $recipient, string $recipientName, string $templateKey, array $variables, string $eventKey, ?string $locale = null): void
    {
        if (blank($recipient)) {
            return;
        }

        SendTemplateEmail::dispatch($templateKey, $recipient, $recipientName, $variables, $eventKey, $locale);
    }

    /**
     * @param  array<string, scalar|null>  $variables
     */
    public function queueToSupportTeam(string $templateKey, array $variables, string $eventKey): void
    {
        $supportEmail = SiteSetting::query()->where('key', 'support_email')->value('value');

        if (blank($supportEmail)) {
            return;
        }

        $this->queueForAddress($supportEmail, 'Support team', $templateKey, $variables, $eventKey);
    }

    /**
     * Queue one copy of a template for every active account.
     *
     * @param  array<string, scalar|null>  $variables
     */
    public function queueForActiveUsers(string $templateKey, array $variables, string $eventKey): void
    {
        User::query()
            ->where('status', AccountStatus::Active->value)
            ->select(['id', 'name', 'email', 'locale'])
            ->chunkById(100, function (EloquentCollection $users) use ($templateKey, $variables, $eventKey): void {
                foreach ($users as $user) {
                    $this->queueForUser($user, $templateKey, $variables, "{$eventKey}:user:{$user->getKey()}");
                }
            });
    }

    /**
     * @param  array<string, scalar|null>  $variables
     */
    public function send(string $templateKey, string $recipient, string $recipientName, array $variables, ?string $locale = null): void
    {
        $emailSetting = EmailSetting::query()->first();
        $templateQuery = EmailTemplate::query()->where('key', $templateKey)->where('is_enabled', true);
        $locale = $locale ?: (string) config('app.locale', 'en');
        $template = (clone $templateQuery)->where('locale', $locale)->first()
            ?? (clone $templateQuery)->where('locale', config('app.fallback_locale', 'en'))->first()
            ?? $templateQuery->first();

        if ($emailSetting === null || ! $emailSetting->is_enabled || $template === null) {
            return;
        }

        $renderer = app(TemplateRenderer::class);
        $subject = $renderer->render($template->subject, $variables);
        $bodyHtml = $this->sanitizeHtml($renderer->render($template->body_html, $variables));
        $bodyText = $renderer->render($template->body_text ?? strip_tags($template->body_html), $variables);

        if (strip_tags($bodyHtml) === $bodyHtml) {
            $bodyHtml = nl2br(e($bodyHtml), false);
        }

        $mailer = $this->configureMailer($emailSetting);

        Mail::mailer($mailer)->html($bodyHtml, function (Message $message) use ($emailSetting, $recipient, $recipientName, $subject, $bodyText): void {
            $message->to($recipient, $recipientName)->subject($subject);

            if (filled($emailSetting->reply_to)) {
                $message->replyTo($emailSetting->reply_to);
            }

            $message->getSymfonyMessage()->text($bodyText);
        });
    }

    public function formatAmount(int $amountMinor, string $currency): string
    {
        return app(LocalizationService::class)->formatMinor($amountMinor, $currency);
    }

    private function configureMailer(EmailSetting $emailSetting): string
    {
        $mailer = in_array($emailSetting->mailer, ['smtp', 'log', 'array'], true)
            ? $emailSetting->mailer
            : 'smtp';

        if ($mailer === 'smtp') {
            $smtpConfig = config('mail.mailers.smtp', []);
            Config::set('mail.mailers.smtp', array_merge($smtpConfig, [
                'transport' => 'smtp',
                'scheme' => $emailSetting->encryption === 'ssl' ? 'smtps' : 'smtp',
                'url' => null,
                'host' => $emailSetting->host ?: ($smtpConfig['host'] ?? '127.0.0.1'),
                'port' => $emailSetting->port ?: ($smtpConfig['port'] ?? 587),
                'username' => $emailSetting->username,
                'password' => $emailSetting->password,
            ]));
        }

        Config::set('mail.from', [
            'address' => $emailSetting->from_address,
            'name' => $emailSetting->from_name,
        ]);
        Mail::purge($mailer);

        Log::debug('Configured outbound email transport from admin settings.', ['mailer' => $mailer]);

        return $mailer;
    }

    private function sanitizeHtml(string $html): string
    {
        $html = strip_tags($html, '<p><br><strong><em><u><a><ul><ol><li><h2><h3><blockquote>');
        $html = preg_replace('/\s+on[a-z]+\s*=\s*(["\']).*?\1/i', '', $html) ?? $html;

        return preg_replace('/javascript\s*:/i', '', $html) ?? $html;
    }
}
